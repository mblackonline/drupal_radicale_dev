<?php

namespace Drupal\calendar_submissions\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker for processing approved calendar submissions to Radicale.
 *
 * @QueueWorker(
 *   id = "calendar_submission_publisher",
 *   title = @Translation("Calendar Submission Publisher"),
 *   cron = {"time" = 60}
 * )
 */
class CalendarSubmissionPublisher extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('calendar_submissions');
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Load the calendar submission entity.
    $entity_storage = $this->entityTypeManager->getStorage('calendar_submission');
    $entity = $entity_storage->load($data->entity_id);

    if (!$entity) {
      $this->logger->error('Calendar submission entity @id not found for publishing.', ['@id' => $data->entity_id]);
      return;
    }

    // Check if entity is in approved status.
    if ($entity->get('status')->value !== 'approved') {
      $this->logger->warning('Calendar submission @id is not in approved status. Current status: @status', [
        '@id' => $entity->id(),
        '@status' => $entity->get('status')->value,
      ]);
      return;
    }

    try {
      // Convert submission to CalDAV format.
      $ical_content = $this->convertToIcal($entity);

      // Get Radicale server configuration.
      $radicale_config = $this->configFactory->get('radicale_calendar.settings');
      $server_url = $radicale_config->get('server_url') ?: 'http://127.0.0.1:5232';
      $username = $radicale_config->get('username') ?: 'admin';
      $password = $radicale_config->get('password') ?: '';

      // Create unique event filename.
      $event_filename = 'event-' . $entity->id() . '-' . date('YmdHis') . '.ics';
      
      // Try to use the correct Radicale CalDAV URL format.
      // First attempt: use the admin user's calendar collection
      $calendar_url = $server_url . '/admin/' . $event_filename;

      // Send to Radicale server using CalDAV.
      $response = $this->httpClient->request('PUT', $calendar_url, [
        'auth' => [$username, $password],
        'headers' => [
          'Content-Type' => 'text/calendar; charset=utf-8',
          'User-Agent' => 'Drupal Calendar Submissions',
        ],
        'body' => $ical_content,
        'http_errors' => false, // Don't throw on HTTP errors, let us handle them
      ]);

      $status_code = $response->getStatusCode();
      if ($status_code === 201 || $status_code === 200) {
        // Successfully published - update entity status.
        $entity->set('status', 'published');
        $entity->save();

        $this->logger->info('Successfully published calendar submission @id to Radicale server (HTTP @code).', [
          '@id' => $entity->id(),
          '@code' => $status_code,
        ]);
      } else {
        $response_body = $response->getBody()->getContents();
        throw new \Exception('HTTP ' . $status_code . ': ' . $response_body);
      }

    } catch (\Exception $e) {
      $this->logger->error('Failed to publish calendar submission @id to Radicale: @error', [
        '@id' => $entity->id(),
        '@error' => $e->getMessage(),
      ]);
      
      // Re-throw the exception to put the item back in the queue for retry.
      throw $e;
    }
  }

  /**
   * Convert calendar submission entity to iCal format.
   *
   * @param \Drupal\calendar_submissions\Entity\CalendarSubmissionInterface $entity
   *   The calendar submission entity.
   *
   * @return string
   *   The iCal formatted string.
   */
  protected function convertToIcal($entity) {
    $title = $entity->getTitle();
    $description = $entity->getDescription();
    $location = $entity->getLocation();
    $start_date = $entity->getStartDate();
    $end_date = $entity->getEndDate();
    
    // Generate unique ID for the event.
    $uid = 'calendar-submission-' . $entity->id() . '@' . \Drupal::request()->getHost();
    
    // Format dates for iCal (convert to UTC and format as YYYYMMDDTHHMMSSZ).
    $start_formatted = $this->formatDateForIcal($start_date);
    $end_formatted = $this->formatDateForIcal($end_date);
    
    // Current timestamp for DTSTAMP.
    $dtstamp = gmdate('Ymd\THis\Z');

    // Build iCal content.
    $ical = [];
    $ical[] = 'BEGIN:VCALENDAR';
    $ical[] = 'VERSION:2.0';
    $ical[] = 'PRODID:-//Drupal Calendar Submissions//NONSGML v1.0//EN';
    $ical[] = 'CALSCALE:GREGORIAN';
    $ical[] = 'BEGIN:VEVENT';
    $ical[] = 'UID:' . $uid;
    $ical[] = 'DTSTAMP:' . $dtstamp;
    $ical[] = 'DTSTART:' . $start_formatted;
    if ($end_formatted) {
      $ical[] = 'DTEND:' . $end_formatted;
    }
    $ical[] = 'SUMMARY:' . $this->escapeIcalText($title);
    if ($description) {
      $ical[] = 'DESCRIPTION:' . $this->escapeIcalText($description);
    }
    if ($location) {
      $ical[] = 'LOCATION:' . $this->escapeIcalText($location);
    }
    $ical[] = 'STATUS:CONFIRMED';
    $ical[] = 'END:VEVENT';
    $ical[] = 'END:VCALENDAR';

    return implode("\r\n", $ical);
  }

  /**
   * Format a date for iCal format.
   *
   * @param string $date
   *   The date string.
   *
   * @return string
   *   The formatted date.
   */
  protected function formatDateForIcal($date) {
    if (empty($date)) {
      return '';
    }
    
    // Convert to DateTime and then to UTC.
    $datetime = new \DateTime($date);
    $datetime->setTimezone(new \DateTimeZone('UTC'));
    return $datetime->format('Ymd\THis\Z');
  }

  /**
   * Escape text for iCal format.
   *
   * @param string $text
   *   The text to escape.
   *
   * @return string
   *   The escaped text.
   */
  protected function escapeIcalText($text) {
    // Escape special characters for iCal format.
    $text = str_replace(['\\', ',', ';', "\n", "\r"], ['\\\\', '\\,', '\\;', '\\n', '\\n'], $text);
    return $text;
  }

}

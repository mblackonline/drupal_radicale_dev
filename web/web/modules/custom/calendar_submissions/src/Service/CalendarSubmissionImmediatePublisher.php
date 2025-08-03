<?php

namespace Drupal\calendar_submissions\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\calendar_submissions\Entity\CalendarSubmissionInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service for immediately publishing calendar submissions to Radicale.
 */
class CalendarSubmissionImmediatePublisher {

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
   * Constructs a CalendarSubmissionImmediatePublisher object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, ClientInterface $http_client) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('calendar_submissions');
    $this->httpClient = $http_client;
  }

  /**
   * Publish a calendar submission immediately to Radicale.
   *
   * @param \Drupal\calendar_submissions\Entity\CalendarSubmissionInterface $entity
   *   The calendar submission entity.
   *
   * @throws \Exception
   *   If the publishing fails.
   */
  public function publishToRadicale(CalendarSubmissionInterface $entity) {
    // Convert submission to CalDAV format.
    $ical_content = $this->convertToIcal($entity);

    // Get Radicale server configuration.
    $radicale_config = $this->configFactory->get('radicale_calendar.settings');
    $server_url = $radicale_config->get('server_url') ?: 'http://127.0.0.1:5232';
    $username = $radicale_config->get('username') ?: 'admin';
    $password = $radicale_config->get('password') ?: '';

    // Define the calendar collection name and URL.
    $collection_name = 'calendar';
    $collection_url = $server_url . '/' . $username . '/' . $collection_name . '/';
    
    // First, try to ensure the calendar collection exists by doing a PROPFIND.
    $collection_check = $this->httpClient->request('PROPFIND', $collection_url, [
      'auth' => [$username, $password],
      'headers' => [
        'Depth' => '0',
        'Content-Type' => 'application/xml; charset=utf-8',
      ],
      'http_errors' => false,
    ]);

    // If collection doesn't exist (404), try to create it using MKCOL.
    if ($collection_check->getStatusCode() === 404) {
      $this->logger->info('Calendar collection does not exist, attempting to create it.');
      
      $create_collection = $this->httpClient->request('MKCOL', $collection_url, [
        'auth' => [$username, $password],
        'headers' => [
          'Content-Type' => 'application/xml; charset=utf-8',
        ],
        'body' => '<?xml version="1.0" encoding="utf-8" ?>' .
                  '<D:mkcol xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">' .
                  '<D:set><D:prop>' .
                  '<D:resourcetype><D:collection/><C:calendar/></D:resourcetype>' .
                  '<D:displayname>Drupal Submissions Calendar</D:displayname>' .
                  '<C:supported-calendar-component-set>' .
                  '<C:comp name="VEVENT"/>' .
                  '</C:supported-calendar-component-set>' .
                  '</D:prop></D:set>' .
                  '</D:mkcol>',
        'http_errors' => false,
      ]);
      
      $create_status = $create_collection->getStatusCode();
      if ($create_status !== 201) {
        throw new \Exception('Failed to create calendar collection. HTTP status: ' . $create_status);
      } else {
        $this->logger->info('Successfully created calendar collection.');
      }
    }

    // Create unique event filename using proper Radicale format.
    $event_filename = 'event-' . $entity->id() . '-' . date('YmdHis') . '.ics';
    
    // Use the correct Radicale CalDAV URL format for individual events.
    $calendar_url = $collection_url . $event_filename;

    // Send event to Radicale server using CalDAV PUT.
    $response = $this->httpClient->request('PUT', $calendar_url, [
      'auth' => [$username, $password],
      'headers' => [
        'Content-Type' => 'text/calendar; charset=utf-8',
        'User-Agent' => 'Drupal Calendar Submissions',
      ],
      'body' => $ical_content,
      'http_errors' => false,
    ]);

    $status_code = $response->getStatusCode();
    if ($status_code === 201 || $status_code === 200) {
      $this->logger->info('Successfully published calendar submission @id to Radicale server (HTTP @code).', [
        '@id' => $entity->id(),
        '@code' => $status_code,
      ]);
    } else {
      $response_body = $response->getBody()->getContents();
      throw new \Exception('HTTP ' . $status_code . ': ' . $response_body);
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

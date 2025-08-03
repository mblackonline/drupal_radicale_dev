<?php

namespace Drupal\calendar_submissions\Service;

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\calendar_submissions\Entity\CalendarSubmissionInterface;

/**
 * Service for managing calendar submission queue operations.
 */
class CalendarSubmissionQueueManager {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a CalendarSubmissionQueueManager object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(QueueFactory $queue_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->queueFactory = $queue_factory;
    $this->logger = $logger_factory->get('calendar_submissions');
  }

  /**
   * Add a calendar submission to the publishing queue.
   *
   * @param \Drupal\calendar_submissions\Entity\CalendarSubmissionInterface $entity
   *   The calendar submission entity.
   */
  public function queueForPublishing(CalendarSubmissionInterface $entity) {
    $queue = $this->queueFactory->get('calendar_submission_publisher');
    
    // Create queue item data.
    $queue_data = (object) [
      'entity_id' => $entity->id(),
      'title' => $entity->getTitle(),
      'queued_time' => time(),
    ];

    // Add to queue.
    $queue->createItem($queue_data);

    $this->logger->info('Added calendar submission @id (@title) to publishing queue.', [
      '@id' => $entity->id(),
      '@title' => $entity->getTitle(),
    ]);
  }

  /**
   * Get the number of items in the publishing queue.
   *
   * @return int
   *   The number of items in the queue.
   */
  public function getQueueCount() {
    $queue = $this->queueFactory->get('calendar_submission_publisher');
    return $queue->numberOfItems();
  }

  /**
   * Process the publishing queue manually.
   *
   * @param int $limit
   *   Maximum number of items to process.
   *
   * @return int
   *   Number of items processed.
   */
  public function processQueue($limit = 10) {
    $queue = $this->queueFactory->get('calendar_submission_publisher');
    $queue_worker = \Drupal::service('plugin.manager.queue_worker')->createInstance('calendar_submission_publisher');
    
    $processed = 0;
    
    while ($processed < $limit && ($item = $queue->claimItem())) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
        $processed++;
      } catch (\Exception $e) {
        $queue->releaseItem($item);
        $this->logger->error('Failed to process queue item: @error', ['@error' => $e->getMessage()]);
        break;
      }
    }

    return $processed;
  }

}

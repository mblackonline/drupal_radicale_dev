<?php

namespace Drupal\calendar_submissions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for calendar submission pages.
 */
class CalendarSubmissionController extends ControllerBase {

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CalendarSubmissionController object.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityFormBuilderInterface $entity_form_builder, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * User-friendly submission form page.
   *
   * @return array
   *   A render array.
   */
  public function submitEventPage() {
    $build = [];

    // Attach CSS library for consistent styling.
    $build['#attached']['library'][] = 'calendar_submissions/calendar_submissions';

    // Page header with instructions.
    $build['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['calendar-submission-header']],
    ];

    $build['header']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $this->t('Submit a Calendar Event'),
    ];

    $build['header']['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Submit your event for review and inclusion in the public calendar. All submissions are reviewed by moderators before being published.'),
      '#attributes' => ['class' => ['lead']],
    ];

    // Create a new entity and get its form.
    $entity = $this->entityTypeManager
      ->getStorage('calendar_submission')
      ->create();

    $build['form'] = $this->entityFormBuilder->getForm($entity, 'add');

    return $build;
  }

  /**
   * User's own submissions page.
   *
   * @return array
   *   A render array.
   */
  public function mySubmissionsPage() {
    $build = [];

    // Attach CSS library for consistent styling.
    $build['#attached']['library'][] = 'calendar_submissions/calendar_submissions';

    // Add navigation header.
    $build['navigation'] = $this->buildNavigationButtons();
    $build['navigation']['#weight'] = -30;

    $build['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['my-submissions-header']],
    ];

    $build['header']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $this->t('My Calendar Submissions'),
    ];

    // Get current user's submissions.
    $current_user = $this->currentUser();
    $storage = $this->entityTypeManager->getStorage('calendar_submission');
    
    $query = $storage->getQuery()
      ->condition('user_id', $current_user->id())
      ->sort('created', 'DESC')
      ->accessCheck(TRUE);
    
    $entity_ids = $query->execute();

    if (empty($entity_ids)) {
      $build['empty'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('You have not submitted any calendar events yet. <a href="@url">Submit your first event</a>.', [
          '@url' => '/submit-calendar-event',
        ]) . '</p>',
      ];
      return $build;
    }

    $entities = $storage->loadMultiple($entity_ids);

    // Build a table of submissions.
    $header = [
      $this->t('Title'),
      $this->t('Start Date'),
      $this->t('Status'),
      $this->t('Submitted'),
      $this->t('Actions'),
    ];

    $rows = [];
    foreach ($entities as $entity) {
      $start_date = $entity->get('start_date')->value;
      $start_formatted = $start_date ? \Drupal::service('date.formatter')->format(strtotime($start_date), 'medium') : '';
      
      $status = $entity->get('status')->value;
      $status_class = 'status-' . str_replace('_', '-', $status);
      
      $actions = [];
      
      // Only allow editing if still in submitted status.
      if ($status === 'submitted') {
        $actions[] = [
          '#type' => 'link',
          '#title' => $this->t('Edit'),
          '#url' => $entity->toUrl('edit-form'),
          '#attributes' => ['class' => ['button', 'button--small']],
        ];
      }
      
      $actions[] = [
        '#type' => 'link',
        '#title' => $this->t('View'),
        '#url' => $entity->toUrl(),
        '#attributes' => ['class' => ['button', 'button--small']],
      ];

      $rows[] = [
        $entity->getTitle(),
        $start_formatted,
        [
          'data' => [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => ucfirst(str_replace('_', ' ', $status)),
            '#attributes' => ['class' => ['status-badge', $status_class]],
          ],
        ],
        \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'short'),
        [
          'data' => $actions,
        ],
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['class' => ['my-submissions-table']],
    ];

    $build['submit_new'] = [
      '#type' => 'link',
      '#title' => $this->t('Submit New Event'),
      '#url' => \Drupal\Core\Url::fromRoute('calendar_submissions.submit_event'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    return $build;
  }

  /**
   * Queue status page for administrators.
   *
   * @return array
   *   A render array.
   */
  public function queueStatusPage() {
    $build = [];

    // Attach CSS library for consistent styling.
    $build['#attached']['library'][] = 'calendar_submissions/calendar_submissions';

    $build['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['queue-status-header']],
    ];

    $build['header']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $this->t('Calendar Submission Queue Status'),
    ];

    // Get queue manager service.
    $queue_manager = \Drupal::service('calendar_submissions.queue_manager');
    $queue_count = $queue_manager->getQueueCount();

    // Queue status information.
    $build['status'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['queue-status-info']],
    ];

    $build['status']['count'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Items in publishing queue: <strong>@count</strong>', ['@count' => $queue_count]),
    ];

    if ($queue_count > 0) {
      $build['status']['process'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Queue items will be processed automatically during cron runs, or you can process them manually below.'),
      ];

      // Manual processing button.
      $build['actions'] = [
        '#type' => 'actions',
      ];

      $build['actions']['process'] = [
        '#type' => 'link',
        '#title' => $this->t('Process Queue Now'),
        '#url' => \Drupal\Core\Url::fromRoute('calendar_submissions.process_queue'),
        '#attributes' => [
          'class' => ['button', 'button--primary'],
        ],
      ];
    } else {
      $build['status']['empty'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('The publishing queue is currently empty.'),
      ];
    }

    // Recent activity.
    $build['recent'] = [
      '#type' => 'details',
      '#title' => $this->t('Recent Activity'),
      '#open' => TRUE,
    ];

    // Get recent submissions.
    $storage = $this->entityTypeManager->getStorage('calendar_submission');
    $query = $storage->getQuery()
      ->sort('changed', 'DESC')
      ->range(0, 10)
      ->accessCheck(TRUE);
    
    $entity_ids = $query->execute();
    
    if ($entity_ids) {
      $entities = $storage->loadMultiple($entity_ids);
      
      $header = [
        $this->t('Title'),
        $this->t('Status'),
        $this->t('Submitter'),
        $this->t('Last Updated'),
      ];
      
      $rows = [];
      foreach ($entities as $entity) {
        $status = $entity->get('status')->value;
        $status_class = 'status-' . str_replace('_', '-', $status);
        
        $rows[] = [
          $entity->toLink()->toString(),
          [
            'data' => [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#value' => ucfirst(str_replace('_', ' ', $status)),
              '#attributes' => ['class' => ['status-badge', $status_class]],
            ],
          ],
          $entity->getOwner() ? $entity->getOwner()->getDisplayName() : $this->t('Anonymous'),
          \Drupal::service('date.formatter')->format($entity->getChangedTime(), 'short'),
        ];
      }
      
      $build['recent']['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => ['class' => ['recent-activity-table']],
      ];
    } else {
      $build['recent']['empty'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('No recent activity.') . '</p>',
      ];
    }

    return $build;
  }

  /**
   * Process queue manually.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function processQueue() {
    $queue_manager = \Drupal::service('calendar_submissions.queue_manager');
    $processed = $queue_manager->processQueue(10);
    
    if ($processed > 0) {
      $this->messenger()->addMessage($this->t('Processed @count queue items.', ['@count' => $processed]));
    } else {
      $this->messenger()->addMessage($this->t('No queue items to process.'));
    }
    
    return $this->redirect('calendar_submissions.queue_status');
  }

  /**
   * Build navigation buttons for calendar submission pages.
   *
   * @return array
   *   Render array for navigation buttons.
   */
  protected function buildNavigationButtons() {
    $current_user = $this->currentUser();
    $navigation = [
      '#type' => 'container',
      '#attributes' => ['class' => ['calendar-submission-navigation']],
    ];

    $buttons = [];

    // Back to Welcome
    $buttons['back'] = [
      '#type' => 'link',
      '#title' => $this->t('← Back to Main'),
      '#url' => \Drupal\Core\Url::fromRoute('radicale_calendar.welcome'),
      '#attributes' => [
        'class' => ['button', 'button--secondary'],
      ],
    ];

    // Submit New Event 
    $buttons['submit_event'] = [
      '#type' => 'link',
      '#title' => $this->t('Submit New Event'),
      '#url' => \Drupal\Core\Url::fromRoute('calendar_submissions.submit_event'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    // View Calendar
    $buttons['view_calendar'] = [
      '#type' => 'link',
      '#title' => $this->t('View Calendar'),
      '#url' => \Drupal\Core\Url::fromRoute('radicale_calendar.calendar'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    $navigation['buttons'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['navigation-buttons']],
    ] + $buttons;

    return $navigation;
  }

}

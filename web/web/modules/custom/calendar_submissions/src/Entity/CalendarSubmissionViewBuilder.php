<?php

namespace Drupal\calendar_submissions\Entity;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * View builder handler for calendar submission entities.
 */
class CalendarSubmissionViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    
    // Add navigation buttons for the full view mode (canonical page).
    if ($view_mode === 'full') {
      $build['#attached']['library'][] = 'calendar_submissions/calendar_submissions';
      $build['navigation'] = $this->buildNavigationButtons();
      $build['navigation']['#weight'] = -100; // Show at the top
    }
    
    return $build;
  }

  /**
   * Build navigation buttons for calendar submission pages.
   *
   * @return array
   *   Render array for navigation buttons.
   */
  protected function buildNavigationButtons() {
    $current_user = \Drupal::currentUser();
    $navigation = [
      '#type' => 'container',
      '#attributes' => ['class' => ['calendar-submission-navigation']],
    ];

    $buttons = [];

    // Submit Calendar Event (always visible)
    $buttons['submit_event'] = [
      '#type' => 'link',
      '#title' => $this->t('Submit Calendar Event'),
      '#url' => \Drupal\Core\Url::fromRoute('calendar_submissions.submit_event'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    // My Submissions (for logged-in users)
    if ($current_user->isAuthenticated()) {
      $buttons['my_submissions'] = [
        '#type' => 'link',
        '#title' => $this->t('My Submissions'),
        '#url' => \Drupal\Core\Url::fromRoute('calendar_submissions.my_submissions'),
        '#attributes' => [
          'class' => ['button'],
        ],
      ];
    }

    // Manage Submissions (only for users with edit permissions)
    if ($current_user->hasPermission('edit calendar submissions')) {
      $buttons['manage_submissions'] = [
        '#type' => 'link',
        '#title' => $this->t('Manage Submissions'),
        '#url' => \Drupal\Core\Url::fromRoute('calendar_submissions.admin_list'),
        '#attributes' => [
          'class' => ['button'],
        ],
      ];
    }

    // View Calendar (always visible)
    $buttons['view_calendar'] = [
      '#type' => 'link',
      '#title' => $this->t('View Calendar'),
      '#url' => \Drupal\Core\Url::fromRoute('radicale_calendar.calendar'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    // Main Page (always visible)
    $buttons['main_page'] = [
      '#type' => 'link',
      '#title' => $this->t('Main Page'),
      '#url' => \Drupal\Core\Url::fromRoute('radicale_calendar.welcome'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    // Login/Logout
    if ($current_user->isAuthenticated()) {
      $buttons['logout'] = [
        '#type' => 'link',
        '#title' => $this->t('Logout'),
        '#url' => \Drupal\Core\Url::fromRoute('user.logout'),
        '#attributes' => [
          'class' => ['button', 'button--secondary'],
        ],
      ];
    } else {
      $buttons['login'] = [
        '#type' => 'link',
        '#title' => $this->t('Login'),
        '#url' => \Drupal\Core\Url::fromRoute('calendar_submissions.login'),
        '#attributes' => [
          'class' => ['button', 'button--secondary'],
        ],
      ];
    }

    $navigation['buttons'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['navigation-buttons']],
    ] + $buttons;

    return $navigation;
  }

}

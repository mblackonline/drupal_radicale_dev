<?php

namespace Drupal\calendar_submissions\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Calendar Submission edit forms.
 *
 * @ingroup calendar_submissions
 */
class CalendarSubmissionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\calendar_submissions\Entity\CalendarSubmissionInterface $entity */
    $entity = $this->entity;

    // Hide the status field for regular users - only moderators should see it.
    $current_user = \Drupal::currentUser();
    if (!$current_user->hasPermission('edit calendar submissions')) {
      $form['status']['#access'] = FALSE;
    }

    // Hide the user_id field for regular users - it should auto-populate.
    if (!$current_user->hasPermission('administer calendar submissions')) {
      $form['user_id']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Add our CSS library.
    $form['#attached']['library'][] = 'calendar_submissions/calendar_submissions';
    
    // Add CSS class to form.
    $form['#attributes']['class'][] = 'calendar-submission-form';

    // Add navigation header.
    $form['navigation'] = $this->buildNavigationHeader();
    $form['navigation']['#weight'] = -20;

    // Add some helpful text for users.
    $form['help'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--info">' . $this->t('Submit your calendar event for review. Once approved by a moderator, it will be added to the public calendar.') . '</div>',
      '#weight' => -10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Your calendar event submission "%label" has been created and is pending review.', [
          '%label' => $entity->getTitle(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Your calendar event submission "%label" has been updated.', [
          '%label' => $entity->getTitle(),
        ]));
    }

    $form_state->setRedirect('entity.calendar_submission.canonical', ['calendar_submission' => $entity->id()]);

    return $status;
  }

  /**
   * Build navigation header for the form.
   *
   * @return array
   *   Render array for navigation header.
   */
  protected function buildNavigationHeader() {
    $current_user = \Drupal::currentUser();
    $navigation = [
      '#type' => 'container',
      '#attributes' => ['class' => ['calendar-submission-navigation']],
    ];

    $buttons = [];

    // Cancel/Back button (primary navigation)
    $buttons['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('← Cancel'),
      '#url' => \Drupal\Core\Url::fromRoute('radicale_calendar.welcome'),
      '#attributes' => [
        'class' => ['button', 'button--secondary'],
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

    $navigation['buttons'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['navigation-buttons']],
    ] + $buttons;

    return $navigation;
  }

}

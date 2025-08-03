<?php

namespace Drupal\calendar_submissions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for calendar submissions.
 */
class CalendarSubmissionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'calendar_submissions.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'calendar_submission_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('calendar_submissions.settings');

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Configure settings for the calendar submission system.') . '</p>',
    ];

    $form['moderation'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Moderation Settings'),
    ];

    $form['moderation']['auto_approve'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-approve submissions from trusted users'),
      '#default_value' => $config->get('auto_approve') ?? FALSE,
      '#description' => $this->t('If checked, submissions from users with "edit calendar submissions" permission will be automatically approved.'),
    ];

    $form['moderation']['notification_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Notification email'),
      '#default_value' => $config->get('notification_email') ?? '',
      '#description' => $this->t('Email address to notify when new submissions are received. Leave empty to disable notifications.'),
    ];

    $form['queue'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Queue Settings'),
    ];

    $form['queue']['batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Queue batch size'),
      '#default_value' => $config->get('batch_size') ?? 5,
      '#min' => 1,
      '#max' => 50,
      '#description' => $this->t('Number of items to process per cron run.'),
    ];

    $form['queue']['retry_attempts'] = [
      '#type' => 'number',
      '#title' => $this->t('Retry attempts'),
      '#default_value' => $config->get('retry_attempts') ?? 3,
      '#min' => 1,
      '#max' => 10,
      '#description' => $this->t('Number of times to retry failed queue items.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('calendar_submissions.settings')
      ->set('auto_approve', $form_state->getValue('auto_approve'))
      ->set('notification_email', $form_state->getValue('notification_email'))
      ->set('batch_size', $form_state->getValue('batch_size'))
      ->set('retry_attempts', $form_state->getValue('retry_attempts'))
      ->save();

    $this->messenger()->addMessage($this->t('Calendar submission settings have been saved.'));
  }

}

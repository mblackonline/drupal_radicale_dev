<?php

namespace Drupal\radicale_calendar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Radicale Calendar settings for this site.
 */
class RadicaleCalendarSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'radicale_calendar_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['radicale_calendar.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('radicale_calendar.settings');

    $form['radicale_server_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Radicale Server URL'),
      '#description' => $this->t('The URL of the Radicale CalDAV server (e.g., http://127.0.0.1:5232)'),
      '#default_value' => $config->get('radicale_server_url'),
      '#required' => TRUE,
    ];

    $form['radicale_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('The username to connect to Radicale'),
      '#default_value' => $config->get('radicale_username'),
      '#required' => TRUE,
    ];

    $form['radicale_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('The password to connect to Radicale (leave empty if no password)'),
      '#default_value' => $config->get('radicale_password'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('radicale_calendar.settings')
      ->set('radicale_server_url', $form_state->getValue('radicale_server_url'))
      ->set('radicale_username', $form_state->getValue('radicale_username'))
      ->set('radicale_password', $form_state->getValue('radicale_password'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

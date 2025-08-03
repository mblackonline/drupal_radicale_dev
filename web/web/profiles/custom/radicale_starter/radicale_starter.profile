<?php

/**
 * @file
 * Radicale Calendar Starter installation profile.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 */
function radicale_starter_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  // Set default values for the installation form.
  $form['site_information']['site_name']['#default_value'] = 'Drupal + Radicale Calendar Server';
  $form['site_information']['site_mail']['#default_value'] = 'admin@example.com';
  $form['admin_account']['account']['name']['#default_value'] = 'admin';
  $form['admin_account']['account']['mail']['#default_value'] = 'admin@example.com';
}

/**
 * Implements hook_install_tasks().
 */
function radicale_starter_install_tasks(&$install_state) {
  return [
    'radicale_starter_enable_modules' => [
      'display_name' => t('Enable calendar modules'),
      'type' => 'normal',
    ],
    'radicale_starter_configure_site' => [
      'display_name' => t('Configure site settings'),
      'type' => 'normal',
    ],
  ];
}

/**
 * Enable the calendar modules.
 */
function radicale_starter_enable_modules(&$install_state) {
  // Enable the custom calendar modules
  \Drupal::service('module_installer')->install([
    'radicale_calendar',
    'calendar_submissions'
  ]);
}

/**
 * Configure site settings.
 */
function radicale_starter_configure_site(&$install_state) {
  // Set front page to the welcome route (provided by radicale_calendar module)
  \Drupal::configFactory()->getEditable('system.site')
    ->set('page.front', '/welcome')
    ->save();
  
  // Disable CSS/JS aggregation for development to prevent loading issues
  \Drupal::configFactory()->getEditable('system.performance')
    ->set('css.preprocess', FALSE)
    ->set('js.preprocess', FALSE)
    ->save();
  
  // Set favicon to the SVG file
  \Drupal::configFactory()->getEditable('system.theme.global')
    ->set('favicon.use_default', FALSE)
    ->set('favicon.path', 'favicon.svg')
    ->save();
    
  // Clear all caches to ensure changes take effect
  drupal_flush_all_caches();
}

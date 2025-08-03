<?php

namespace Drupal\calendar_submissions\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Custom user login form with calendar-specific styling.
 */
class CustomUserLoginForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'calendar_submissions_user_login';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add our CSS library.
    $form['#attached']['library'][] = 'calendar_submissions/calendar_submissions';
    
    // Add CSS class to form.
    $form['#attributes']['class'][] = 'calendar-login-form';

    $form['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['login-header']],
    ];

    $form['header']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $this->t('Welcome Back'),
    ];

    $form['header']['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Log in to submit and manage your calendar events.'),
      '#attributes' => ['class' => ['lead']],
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username or Email'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Enter your username or email'),
        'autofocus' => 'autofocus',
      ],
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Enter your password'),
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log In'),
      '#attributes' => ['class' => ['button', 'button--primary', 'button--large']],
    ];

    // Get destination from URL parameter
    $destination = $this->getRequest()->query->get('destination', '/');
    
    $form['actions']['register'] = [
      '#type' => 'link',
      '#title' => $this->t('Create Account'),
      '#url' => Url::fromRoute('calendar_submissions.register'),
      '#attributes' => ['class' => ['button', 'button--secondary']],
    ];

    $form['actions']['back'] = [
      '#type' => 'link',
      '#title' => $this->t('Back'),
      '#url' => Url::fromUserInput($destination),
      '#attributes' => ['class' => ['button', 'button--link']],
    ];

    // Store destination for redirect after login
    $form['destination'] = [
      '#type' => 'hidden',
      '#value' => $destination,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $username = $form_state->getValue('username');
    $password = $form_state->getValue('password');

    // Try to authenticate user
    $uid = \Drupal::service('user.auth')->authenticate($username, $password);
    
    if (!$uid) {
      $form_state->setErrorByName('username', $this->t('Invalid username/email or password.'));
    } else {
      $form_state->setValue('uid', $uid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('uid');
    $destination = $form_state->getValue('destination');

    // Load and login the user
    $user = \Drupal\user\Entity\User::load($uid);
    if ($user) {
      user_login_finalize($user);
      
      $this->messenger()->addMessage($this->t('Welcome back, %username!', [
        '%username' => $user->getDisplayName(),
      ]));

      // Redirect to destination
      $form_state->setRedirectUrl(Url::fromUserInput($destination));
    }
  }

}

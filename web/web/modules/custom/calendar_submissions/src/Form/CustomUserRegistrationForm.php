<?php

namespace Drupal\calendar_submissions\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Custom user registration form with calendar-specific styling.
 */
class CustomUserRegistrationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'calendar_submissions_user_registration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add our CSS library.
    $form['#attached']['library'][] = 'calendar_submissions/calendar_submissions';
    
    // Add CSS class to form.
    $form['#attributes']['class'][] = 'calendar-registration-form';

    $form['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['registration-header']],
    ];

    $form['header']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $this->t('Create Your Account'),
    ];

    $form['header']['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Join our community and start submitting calendar events!'),
      '#attributes' => ['class' => ['lead']],
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
      '#description' => $this->t('Choose a unique username for your account.'),
      '#attributes' => [
        'placeholder' => $this->t('Enter your username'),
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
      '#description' => $this->t('We\'ll use this to send you updates about your submissions.'),
      '#attributes' => [
        'placeholder' => $this->t('Enter your email address'),
      ],
    ];

    $form['password'] = [
      '#type' => 'password_confirm',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
      '#description' => $this->t('Choose a strong password for your account.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Account'),
      '#attributes' => ['class' => ['button', 'button--primary', 'button--large']],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Back to Calendar'),
      '#url' => \Drupal\Core\Url::fromRoute('radicale_calendar.calendar'),
      '#attributes' => ['class' => ['button', 'button--secondary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $username = $form_state->getValue('username');
    $email = $form_state->getValue('email');

    // Check if username already exists.
    $existing_user = user_load_by_name($username);
    if ($existing_user) {
      $form_state->setErrorByName('username', $this->t('Username %username is already taken.', ['%username' => $username]));
    }

    // Check if email already exists.
    $existing_email = user_load_by_mail($email);
    if ($existing_email) {
      $form_state->setErrorByName('email', $this->t('An account with email %email already exists.', ['%email' => $email]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Create new user account.
    $user = User::create([
      'name' => $values['username'],
      'mail' => $values['email'],
      'pass' => $values['password'],
      'status' => 1, // Active account
      'roles' => ['authenticated'], // Standard authenticated user role
    ]);

    try {
      $user->save();

      // Log the user in automatically.
      user_login_finalize($user);

      $this->messenger()->addMessage($this->t('Welcome %username! Your account has been created successfully. You can now submit calendar events.', [
        '%username' => $user->getDisplayName(),
      ]));

      // Redirect to calendar page.
      $form_state->setRedirect('radicale_calendar.calendar');

    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('There was an error creating your account. Please try again.'));
      \Drupal::logger('calendar_submissions')->error('User registration error: @error', ['@error' => $e->getMessage()]);
    }
  }

}

<?php

namespace Drupal\calendar_submissions\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Calendar Submission entity.
 *
 * @ContentEntityType(
 *   id = "calendar_submission",
 *   label = @Translation("Calendar Submission"),
 *   label_collection = @Translation("Calendar Submissions"),
 *   label_singular = @Translation("calendar submission"),
 *   label_plural = @Translation("calendar submissions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count calendar submission",
 *     plural = "@count calendar submissions",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\calendar_submissions\Entity\CalendarSubmissionViewBuilder",
 *     "list_builder" = "Drupal\calendar_submissions\Entity\Controller\CalendarSubmissionListBuilder",
 *     "access" = "Drupal\calendar_submissions\Entity\Access\CalendarSubmissionAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\calendar_submissions\Form\CalendarSubmissionForm",
 *       "add" = "Drupal\calendar_submissions\Form\CalendarSubmissionForm",
 *       "edit" = "Drupal\calendar_submissions\Form\CalendarSubmissionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "calendar_submission",
 *   data_table = "calendar_submission_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer calendar submissions",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/calendar-submission/{calendar_submission}",
 *     "add-form" = "/calendar-submission/add",
 *     "edit-form" = "/calendar-submission/{calendar_submission}/edit",
 *     "delete-form" = "/calendar-submission/{calendar_submission}/delete",
 *     "collection" = "/admin/content/calendar-submissions",
 *   },
 *   field_ui_base_route = "calendar_submissions.settings"
 * )
 */
class CalendarSubmission extends ContentEntityBase implements CalendarSubmissionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', $description);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate() {
    return $this->get('start_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartDate($start_date) {
    $this->set('start_date', $start_date);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate() {
    return $this->get('end_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndDate($end_date) {
    $this->set('end_date', $end_date);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation() {
    return $this->get('location')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocation($location) {
    $this->set('location', $location);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Title field.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the calendar event.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Description field.
    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the calendar event.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Start date field.
    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start Date'))
      ->setDescription(t('The start date and time of the event.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // End date field.
    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End Date'))
      ->setDescription(t('The end date and time of the event.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Location field.
    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location'))
      ->setDescription(t('The location of the event.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Submission status field.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of the calendar submission.'))
      ->setRequired(TRUE)
      ->setDefaultValue('submitted')
      ->setSetting('allowed_values', [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved & Published',
        'rejected' => 'Rejected',
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // User ID field (submitter).
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Submitted by'))
      ->setDescription(t('The user who submitted this calendar event.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\calendar_submissions\Entity\CalendarSubmission::getCurrentUserId')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Created field.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the submission was created.'));

    // Changed field.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the submission was last edited.'));

    return $fields;
  }

  /**
   * Default value callback for 'user_id' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}

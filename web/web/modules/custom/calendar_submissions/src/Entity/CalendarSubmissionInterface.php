<?php

namespace Drupal\calendar_submissions\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Calendar Submission entities.
 *
 * @ingroup calendar_submissions
 */
interface CalendarSubmissionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Calendar Submission title.
   *
   * @return string
   *   Title of the Calendar Submission.
   */
  public function getTitle();

  /**
   * Sets the Calendar Submission title.
   *
   * @param string $title
   *   The Calendar Submission title.
   *
   * @return \Drupal\calendar_submissions\Entity\CalendarSubmissionInterface
   *   The called Calendar Submission entity.
   */
  public function setTitle($title);

  /**
   * Gets the Calendar Submission description.
   *
   * @return string
   *   Description of the Calendar Submission.
   */
  public function getDescription();

  /**
   * Sets the Calendar Submission description.
   *
   * @param string $description
   *   The Calendar Submission description.
   *
   * @return \Drupal\calendar_submissions\Entity\CalendarSubmissionInterface
   *   The called Calendar Submission entity.
   */
  public function setDescription($description);

  /**
   * Gets the Calendar Submission start date.
   *
   * @return string
   *   Start date of the Calendar Submission.
   */
  public function getStartDate();

  /**
   * Sets the Calendar Submission start date.
   *
   * @param string $start_date
   *   The Calendar Submission start date.
   *
   * @return \Drupal\calendar_submissions\Entity\CalendarSubmissionInterface
   *   The called Calendar Submission entity.
   */
  public function setStartDate($start_date);

  /**
   * Gets the Calendar Submission end date.
   *
   * @return string
   *   End date of the Calendar Submission.
   */
  public function getEndDate();

  /**
   * Sets the Calendar Submission end date.
   *
   * @param string $end_date
   *   The Calendar Submission end date.
   *
   * @return \Drupal\calendar_submissions\Entity\CalendarSubmissionInterface
   *   The called Calendar Submission entity.
   */
  public function setEndDate($end_date);

  /**
   * Gets the Calendar Submission location.
   *
   * @return string
   *   Location of the Calendar Submission.
   */
  public function getLocation();

  /**
   * Sets the Calendar Submission location.
   *
   * @param string $location
   *   The Calendar Submission location.
   *
   * @return \Drupal\calendar_submissions\Entity\CalendarSubmissionInterface
   *   The called Calendar Submission entity.
   */
  public function setLocation($location);

  /**
   * Gets the Calendar Submission creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Calendar Submission.
   */
  public function getCreatedTime();

  /**
   * Sets the Calendar Submission creation timestamp.
   *
   * @param int $timestamp
   *   The Calendar Submission creation timestamp.
   *
   * @return \Drupal\calendar_submissions\Entity\CalendarSubmissionInterface
   *   The called Calendar Submission entity.
   */
  public function setCreatedTime($timestamp);

}

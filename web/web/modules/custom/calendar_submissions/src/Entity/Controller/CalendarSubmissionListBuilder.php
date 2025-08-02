<?php

namespace Drupal\calendar_submissions\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build listing of Calendar Submission entities.
 *
 * @ingroup calendar_submissions
 */
class CalendarSubmissionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Title');
    $header['start_date'] = $this->t('Start Date');
    $header['status'] = $this->t('Status');
    $header['submitter'] = $this->t('Submitted by');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\calendar_submissions\Entity\CalendarSubmissionInterface $entity */
    $row['id'] = $entity->id();
    $row['title'] = Link::createFromRoute(
      $entity->getTitle(),
      'entity.calendar_submission.canonical',
      ['calendar_submission' => $entity->id()]
    );
    
    $start_date = $entity->get('start_date')->value;
    $row['start_date'] = $start_date ? \Drupal::service('date.formatter')->format(strtotime($start_date), 'medium') : '';
    
    $row['status'] = $entity->get('status')->value;
    $row['submitter'] = $entity->getOwner() ? $entity->getOwner()->getDisplayName() : $this->t('Anonymous');
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'short');
    
    return $row + parent::buildRow($entity);
  }

}

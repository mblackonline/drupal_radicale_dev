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
  public function render() {
    $build = parent::render();
    
    // Add a header section for consistency.
    $header = [
      '#type' => 'container',
      '#attributes' => ['class' => ['calendar-submission-header']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h1',
        '#value' => $this->t('Manage Calendar Submissions'),
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Review, approve, or reject submitted calendar events. Approved events are immediately published to the calendar.'),
        '#attributes' => ['class' => ['lead']],
      ],
    ];
    
    // Add CSS classes for styling.
    $build['table']['#attributes']['class'][] = 'calendar-submissions-admin-table';
    
    // Wrap in a content container with header.
    $build = [
      '#attached' => ['library' => ['calendar_submissions/calendar_submissions']],
      '#prefix' => '<div class="calendar-submissions-page-wrapper"><div class="calendar-submissions-content">',
      '#suffix' => '</div></div>',
      'header' => $header,
      'list' => $build,
    ];
    
    return $build;
  }

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
    
    $status = $entity->get('status')->value;
    $status_class = 'status-' . str_replace('_', '-', $status);
    $row['status'] = [
      'data' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => ucfirst(str_replace('_', ' ', $status)),
        '#attributes' => ['class' => ['status-badge', $status_class]],
      ],
    ];
    $row['submitter'] = $entity->getOwner() ? $entity->getOwner()->getDisplayName() : $this->t('Anonymous');
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'short');
    
    return $row + parent::buildRow($entity);
  }

}

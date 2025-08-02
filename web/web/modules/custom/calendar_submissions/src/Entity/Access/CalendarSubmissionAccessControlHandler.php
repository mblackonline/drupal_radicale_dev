<?php

namespace Drupal\calendar_submissions\Entity\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Calendar Submission entity.
 *
 * @see \Drupal\calendar_submissions\Entity\CalendarSubmission.
 */
class CalendarSubmissionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\calendar_submissions\Entity\CalendarSubmissionInterface $entity */

    switch ($operation) {

      case 'view':
        // Allow users to view their own submissions.
        if ($entity->getOwnerId() == $account->id()) {
          return AccessResult::allowed()->cachePerUser();
        }
        // Allow users with appropriate permissions to view all submissions.
        return AccessResult::allowedIfHasPermission($account, 'view calendar submissions');

      case 'update':
        // Allow users to edit their own submissions if still in 'submitted' status.
        if ($entity->getOwnerId() == $account->id() && $entity->get('status')->value == 'submitted') {
          return AccessResult::allowed()->cachePerUser();
        }
        // Allow moderators to edit any submission.
        return AccessResult::allowedIfHasPermission($account, 'edit calendar submissions');

      case 'delete':
        // Allow users to delete their own submissions if still in 'submitted' status.
        if ($entity->getOwnerId() == $account->id() && $entity->get('status')->value == 'submitted') {
          return AccessResult::allowed()->cachePerUser();
        }
        // Allow administrators to delete any submission.
        return AccessResult::allowedIfHasPermission($account, 'delete calendar submissions');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add calendar submissions');
  }

}

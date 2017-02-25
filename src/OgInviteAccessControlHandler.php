<?php

namespace Drupal\og_invite;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Invite entity.
 *
 * @see \Drupal\og_invite\Entity\OgInvite.
 */
class OgInviteAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\og_invite\OgInviteInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isActive()) {
          return AccessResult::allowedIfHasPermission($account, 'view inactive invite entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view active invite entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit invite entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'revoke group invitation');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'invite group member');
  }

}

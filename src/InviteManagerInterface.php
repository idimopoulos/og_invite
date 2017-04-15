<?php

namespace Drupal\og_invite;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\user\UserInterface;

/**
 * Interface InviteManagerInterface.
 *
 * @package Drupal\og_invite
 */
interface InviteManagerInterface {

  /**
   * Creates an invite entity.
   *
   * Takes care of the in-between creation of the membership of the user.
   * The membership has initially a pending state assigned.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *    The group entity.
   * @param \Drupal\user\UserInterface $member
   *    The member user.
   * @param \Drupal\user\UserInterface|null $created_by
   *    The user that creates the entity. If NULL, the current user will be
   *    used.
   * @param \Drupal\og\Entity\OgRole[] $roles
   *    An array of OgRoles to pass to the new membership. It should not include
   *    default roles. Defaults to an empty array.
   * @param string $membership_state
   *    The membership state. Defaults to OgMembershipInterface::STATE_PENDING.
   * @param string $membership_type
   *   The membership type. Defaults to OG_MEMBERSHIP_TYPE_DEFAULT.
   *
   * @return EntityInterface
   *    The created entity.
   */
  public function createInvite(EntityInterface $group, UserInterface $member, UserInterface $created_by, array $roles = [], $membership_state = OgMembershipInterface::STATE_PENDING, $membership_type = OgMembershipInterface::TYPE_DEFAULT);

  /**
   * Revokes the invitation.
   *
   * The revoked invitation sets the membership as blocked and the invitation to
   * non active.
   *
   * @param int $invite_id
   *    The invite id.
   * @param string $membership_state
   *    The new membership state. Defaults to
   *    OgMembershipInterface::STATE_BLOCKED.
   */
  public function revokeInvite($invite_id, $membership_state = OgMembershipInterface::STATE_PENDING);

  /**
   * Loads an invite by group and user.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The group.
   * @param \Drupal\user\UserInterface $user
   *    The user object.
   *
   * @return \Drupal\og_invite\OgInviteInterface|null
   *    The og invite or null if no invite is found.
   */
  public function loadByGroupAndUser(EntityInterface $entity, UserInterface $user);

}

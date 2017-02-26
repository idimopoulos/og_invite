<?php

namespace Drupal\og_invite;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgMembershipInterface;

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
   * @param \Drupal\Core\Session\AccountInterface $member
   *    The member user.
   * @param \Drupal\Core\Session\AccountInterface|null $created_by
   *    The user that creates the entity. If NULL, the current user will be
   *    used.
   * @param \Drupal\og\Entity\OgRole[] $roles
   *    An array of OgRoles to pass to the new membership. It should not include
   *    default roles. Defaults to an empty array.
   * @param string $membership_state
   *    The membership state. Defaults to 'pending'.
   * @param string $membership_type
   *   The membership type. Defaults to OG_MEMBERSHIP_TYPE_DEFAULT.
   *
   * @return EntityInterface
   *    The created entity.
   */
  public function createInvite(EntityInterface $group, AccountInterface $member, AccountInterface $created_by, array $roles = [], $membership_state = OgMembershipInterface::STATE_PENDING, $membership_type = OgMembershipInterface::TYPE_DEFAULT);

}

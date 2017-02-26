<?php

namespace Drupal\og_invite;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\og\Entity\OgMembership;
use Drupal\og\GroupTypeManager;
use Drupal\og\MembershipManager;
use Drupal\og\OgMembershipInterface;
use Drupal\og_invite\Entity\OgInvite;

/**
 * Class InviteManager.
 *
 * @package Drupal\og_invite
 */
class InviteManager implements InviteManagerInterface {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\og\GroupTypeManager definition.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $ogGroupTypeManager;

  /**
   * Drupal\og\MembershipManager definition.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $ogMembershipManager;

  /**
   * Constructs an Invite manager service.
   *
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *    The current user.
   * @param \Drupal\og\GroupTypeManager $og_group_type_manager
   *    The og group type manager service.
   * @param \Drupal\og\MembershipManager $og_membership_manager
   *    The og membership manager service.
   */
  public function __construct(AccountProxy $current_user, GroupTypeManager $og_group_type_manager, MembershipManager $og_membership_manager) {
    $this->currentUser = $current_user;
    $this->ogGroupTypeManager = $og_group_type_manager;
    $this->ogMembershipManager = $og_membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createInvite(EntityInterface $group, AccountInterface $member, AccountInterface $created_by, array $roles = [], $membership_state = OgMembershipInterface::STATE_PENDING, $membership_type = OgMembershipInterface::TYPE_DEFAULT) {
    $membership = OgMembership::create([
      'type' => $membership_type,
    ]);
    $membership->setGroup($group)
      ->setUser($member)
      ->setState($membership_state);
    if (!empty($roles)) {
      $membership->setRoles($roles);
    }
    $membership->save();

    $invite = OgInvite::create();
    $invite->setOwner($created_by)
      ->setMembership($membership)
      ->save();
  }

}

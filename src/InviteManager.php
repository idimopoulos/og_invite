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
use Drupal\user\UserInterface;

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
  public function createInvite(EntityInterface $group, UserInterface $member, UserInterface $created_by, array $roles = [], $membership_state = OgMembershipInterface::STATE_PENDING, $membership_type = OgMembershipInterface::TYPE_DEFAULT) {
    if (!$this->canCreateInvite($group, $member)) {
      throw new \Exception('An invitation or a membership related to this group and user already exists.');
    }

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

    $invite = OgInvite::create([
      'uid' => $member->id(),
      'mid' => $membership->id(),
      'created_by' => $this->currentUser->id(),
      'entity_type' => $group->getEntityTypeId(),
      'entity_id' => $group->id(),
    ]);
    $invite->save();
  }

  /**
   * {@inheritdoc}
   */
  public function revokeInvite($invite_id, $membership_state = OgMembershipInterface::STATE_PENDING) {
    $og_invite = OgInvite::load($invite_id);
    $membership = $og_invite->getMembership();
    $membership->setState(OgMembershipInterface::STATE_BLOCKED);
    $og_invite->setStatus(OgInviteInterface::REVOKED);

    $membership->save();
    $og_invite->save();
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroupAndUser(EntityInterface $entity, UserInterface $user) {
    $og_invite = \Drupal::service('entity_type.manager')->getStorage('og_invite')->loadByProperties([
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'uid' => $user->id(),
    ]);
    return $og_invite;
  }

  /**
   * Checks if an invitation can be created.
   *
   * The requirements are that there is not any invite associated with the user
   * and the group and there is no membership as well.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The group.
   * @param \Drupal\user\UserInterface $user
   *    The user object.
   *
   * @return bool
   *    TRUE if there is no invitation or membership related to the user and the
   *    group.
   */
  protected function canCreateInvite(EntityInterface $entity, UserInterface $user) {
    $og_invite = $this->loadByGroupAndUser($entity, $user);
    if (!empty($og_invite)) {
      return FALSE;
    }

    $membership = $this->ogMembershipManager->getMembership($entity, $user);
    if (!empty($membership)) {
      return FALSE;
    }

    return TRUE;
  }

}

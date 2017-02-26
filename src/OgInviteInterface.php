<?php

namespace Drupal\og_invite;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\user\UserInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Invite entities.
 *
 * @ingroup og_invite
 */
interface OgInviteInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Represents that the invitation is active.
   *
   * @var int
   */
  public const ACTIVE = 1;

  /**
   * Represents that the invitation is not active.
   *
   * @var int
   */
  public const NOT_ACTIVE = 0;

  /**
   * Represents that the invitation is accepted.
   *
   * @var int
   */
  public const DECISION_ACCEPT = 1;

  /**
   * Represents that the invitation is rejected.
   *
   * @var int
   */
  public const DECISION_REJECT = 0;

  /**
   * Gets the Invite hash.
   *
   * @return string
   *   Hash of the Invite.
   */
  public function getName();

  /**
   * Sets the Invite hash.
   *
   * @param string $hash
   *   The Invite hash.
   *
   * @return $this
   */
  public function setName($hash);

  /**
   * Gets the Invite hash.
   *
   * @return string
   *   Hash of the Invite.
   */
  public function getInviteHash();

  /**
   * Sets the Invite hash.
   *
   * @param string $hash
   *   The Invite hash.
   *
   * @return $this
   */
  public function setInviteHash($hash);

  /**
   * Returns the user object that created the Invite.
   *
   * @return \Drupal\user\UserInterface
   *    The user object.
   */
  public function getCreatedBy();

  /**
   * Returns the user id of the user that created the Invite.
   *
   * @return int
   *    The user id.
   */
  public function getCreatedById();

  /**
   * Sets the user id that created the Invite.
   *
   * @param \Drupal\user\UserInterface $account
   *    The user account.
   *
   * @return $this
   */
  public function setCreatedBy(UserInterface $account);

  /**
   * Sets the user id that created the Invite.
   *
   * @param int $uid
   *    The user account id.
   *
   * @return $this
   */
  public function setCreatedById($uid);

  /**
   * Gets the Invite creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Invite.
   */
  public function getCreatedTime();

  /**
   * Sets the Invite creation timestamp.
   *
   * @param int $timestamp
   *   The Invite creation timestamp.
   *
   * @return \Drupal\og_invite\OgInviteInterface
   *   The called Invite entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Invite published status indicator.
   *
   * Unpublished Invite are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Invite is published.
   */
  public function isActive();

  /**
   * Sets the published status of a Invite.
   *
   * @param bool $published
   *   TRUE to set this Invite to published, FALSE to set it to unpublished.
   *
   * @return $this
   */
  public function setActive($published);

  /**
   * Returns the Invite decision date.
   *
   * @return int
   *    The timestamp of the decision.
   */
  public function getDecisionDate();

  /**
   * Sets the decision date.
   *
   * @param int $timestamp
   *    The timestamp of the decision.
   */
  public function setDecisionDate($timestamp);

  /**
   * Returns the decision on the invitation.
   *
   * @return int
   *    The decision on the invitation.
   */
  public function getDecision();

  /**
   * Sets the decision on the invitation.
   *
   * @param int $decision
   *    The decision on the invitation.
   *
   * @return $this
   */
  public function setDecision($decision);

  /**
   * Returns the membership associated with the Invite.
   *
   * @return \Drupal\og\OgMembershipInterface
   *    The membership entity.
   */
  public function getMembership();

  /**
   * Returns the membership id associated with the Invite.
   *
   * @return int
   *    The membership id.
   */
  public function getMembershipId();

  /**
   * Sets the membership id associated with the Invite.
   *
   * @param int $mid
   *    The og membership entity id.
   *
   * @return $this
   */
  public function setMembershipId($mid);

  /**
   * Sets the membership associated with the Invite.
   *
   * @param \Drupal\og\OgMembershipInterface $membership
   *    The og membership entity.
   *
   * @return $this
   */
  public function setMembership(OgMembershipInterface $membership);

}

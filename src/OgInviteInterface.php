<?php

namespace Drupal\og_invite;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Invite entities.
 *
 * @ingroup og_invite
 */
interface OgInviteInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * @var int
   *
   * Represents that the invitation is active.
   */
  public const ACTIVE = 1;

  /**
   * @var int
   *
   * Represents that the invitation is not active.
   */
  public const NOT_ACTIVE = 0;

  /**
   * @var int
   *
   * Represents that the invitation is accepted.
   */
  public const DECISION_ACCEPT = 1;

  /**
   * @var int
   *
   * Represents that the invitation is rejected.
   */
  public const DECISION_REJECT = 0;

  /**
   * Gets the Invite name.
   *
   * @return string
   *   Name of the Invite.
   */
  public function getName();

  /**
   * Sets the Invite name.
   *
   * @param string $name
   *   The Invite name.
   *
   * @return \Drupal\og_invite\OgInviteInterface
   *   The called Invite entity.
   */
  public function setName($name);

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
   * @return \Drupal\og_invite\OgInviteInterface
   *   The called Invite entity.
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
   */
  public function setDecision($decision);

}

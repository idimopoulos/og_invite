<?php

namespace Drupal\og_invite\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\og\ContextProvider\OgContext;
use Drupal\og\MembershipManager;
use Drupal\og\Og;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og_invite\InviteManagerInterface;
use Drupal\og_invite\OgInviteInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OgInvite.
 *
 * @package Drupal\og_invite\Controller
 */
class OgInvite extends ControllerBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The og invite storgae.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ogInviteStorage;

  /**
   * Drupal\og\MembershipManager definition.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $ogMembershipManager;

  /**
   * The invite manager service.
   *
   * @var \Drupal\og_invite\InviteManagerInterface
   */
  protected $inviteManager;

  /**
   * The og group retrieved from the context.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $ogGroup;

  /**
   * The og access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The number of users to list per page, or FALSE to list all entities.
   *
   * @var int|false
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManager $og_membership_manager, InviteManagerInterface $invite_manager, OgContext $og_context, OgAccessInterface $og_access, AccountProxy $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->ogMembershipManager = $og_membership_manager;
    $this->ogGroup = $og_context->getGroup();
    $this->ogAccess = $og_access;
    $this->inviteManager = $invite_manager;
    $this->currentUser = $current_user;
    $this->ogInviteStorage = $this->entityTypeManager->getStorage('og_invite');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager'),
      $container->get('og_invite.manager'),
      $container->get('og.context'),
      $container->get('og.access'),
      $container->get('current_user')
    );
  }

  /**
   * Generates a form to invite people.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   *
   * @return array
   *    An array to be rendered.
   */
  public function inviteForm(RouteMatchInterface $route_match) {
    $build['table'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->t('Available users'),
      '#rows' => array(),
      '#empty' => $this->t('No people available.'),
    );
    foreach ($this->loadNonMembers() as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = array(
        '#type' => 'pager',
      );
    }
    return $build;
  }

  /**
   * Returns an array of users that are not members of the group.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *    An array of users.
   */
  public function loadNonMembers() {
    $membership_storage = $this->entityTypeManager->getStorage('og_membership');
    $memberships = $membership_storage->loadByProperties([
      'entity_type' => $this->ogGroup->getEntityTypeId(),
      'entity_id' => $this->ogGroup->id(),
    ]);
    $excluded_ids = array_map(function (OgMembershipInterface $membership) {
      return $membership->getUser()->id();
    }, $memberships);

    $user_storage = $this->entityTypeManager()->getStorage('user');
    $entity_query = $user_storage->getQuery();
    $entity_query->condition('uid', 0, '<>');
    if (!empty($excluded_ids)) {
      $entity_query->condition('uid', $excluded_ids, 'NOT IN');
    }
    $entity_query->pager(50);
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $uids = $entity_query->execute();
    return $user_storage->loadMultiple($uids);
  }

  /**
   * Returns the header of the form.
   *
   * @return array
   *    An array of header entries.
   */
  public function buildHeader() {
    return [
      'username' => [
        'data' => $this->t('Username'),
        'field' => 'name',
        'specifier' => 'name',
      ],
      'operations' => $this->t('Operations'),
    ];
  }

  /**
   * Returns a row of data for each entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The user object.
   *
   * @return array
   *    An array of data for the user.
   */
  public function buildRow(EntityInterface $entity) {
    $operations['og_invite'] = [
      'title' => $this->t('Invite'),
      'weight' => 10,
      'url' => Url::fromRoute('og_invite.invite.create', [
        'entity_type_id' => $this->ogGroup->getEntityTypeId(),
        'entity_id' => $this->ogGroup->id(),
        'user' => $entity->id(),
      ]),
    ];
    $operations_row = [
      '#type' => 'operations',
      '#links' => $operations,
    ];

    $row['username']['data'] = array(
      '#theme' => 'username',
      '#account' => $entity,
    );
    $row['operations']['data'] = $operations_row;
    return $row;
  }

  /**
   * Creates a membership and an Invite entity.
   *
   * @param string $entity_type_id
   *    The id of the entity type.
   * @param \Drupal\Core\Entity\EntityInterface $entity_id
   *    The og group.
   * @param \Drupal\user\UserInterface $user
   *    The user object associated with the Invite entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *    A redirect to the invite route.
   */
  public function createInvite($entity_type_id, EntityInterface $entity_id, UserInterface $user) {
    $params = [
      'entity_type_id' => $entity_type_id,
      $entity_type_id => $entity_id->id(),
    ];

    $created_by = $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
    $this->inviteManager->createInvite($entity_id, $user, $created_by);
    drupal_set_message($this->t('User %user has been invited.', [
      '%user' => $user->getAccountName(),
    ]));
    return $this->redirect("entity.{$entity_type_id}.og_admin_routes.invite", $params);
  }

  /**
   * Custom check access for creating the invite.
   *
   * The reason for using a custom access is that the create route has a user
   * object passed in and this is used for the normal access check while
   * normally the user that creates the invite is responsible for that.
   *
   * @param string $entity_type_id
   *    The id of the entity type.
   * @param \Drupal\Core\Entity\EntityInterface $entity_id
   *    The og group.
   * @param \Drupal\user\UserInterface $user
   *    The user object associated with the Invite entity.
   *
   * @return AccessResult
   *    The result of the access check.
   */
  public function createInviteAccess($entity_type_id, EntityInterface $entity_id, UserInterface $user) {
    if (!$entity_id instanceof ContentEntityInterface) {
      // Not a valid entity.
      return AccessResult::forbidden();
    }

    if (!Og::isGroup($entity_type_id, $entity_id->bundle())) {
      // Not a valid group.
      return AccessResult::forbidden();
    }

    $user = $this->entityTypeManager->getStorage('user')->load($user);
    if ($user->isAnonymous()) {
      // Cannot invite anonymous user.
      return AccessResult::forbidden();
    }
    $states = [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_BLOCKED,
      OgMembershipInterface::STATE_PENDING,
    ];
    if ($this->ogMembershipManager->isMember($entity_id, $user, $states)) {
      return AccessResult::forbidden();
    }

    $created_by = $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
    if (!$this->ogAccess->userAccess($entity_id, 'invite group member', $created_by)->isAllowed()) {
      // User does not have permission to invite member.
      return AccessResult::forbidden();
    }

    return AccessResult::allowedIf($this->ogAccess->userAccess($entity_id, 'invite group member', $created_by)->isAllowed());
  }

  /**
   * Accepts an invitation.
   *
   * @param string $invite_hash
   *    The invite hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *    A redirect to the invite route.
   */
  public function acceptInvite($invite_hash) {
    $invite = $this->getInviteByHash($invite_hash);
    $membership = $invite->getMembership();
    $membership->setState(OgMembershipInterface::STATE_ACTIVE);
    $membership->save();

    $invite->setDecision(OgInviteInterface::DECISION_ACCEPT);
    $invite->getDecisionDate(\Drupal::time()->getRequestTime());
    // The invite is not active anymore.
    $invite->setActive(OgInviteInterface::NOT_ACTIVE);
    $invite->save();

    $group = $membership->getGroup();
    $route_parameters = $group->toUrl()->getRouteParameters();
    return $this->redirect("entity.{$group->getEntityTypeId()}.canonical", $route_parameters);
  }

  /**
   * Rejects an invitation.
   *
   * @param string $invite_hash
   *    The invite hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *    A redirect to the invite route.
   */
  public function rejectInvite($invite_hash) {
    $invite = $this->getInviteByHash($invite_hash);
    $membership = $invite->getMembership();
    // @todo: Global settings should be available for whether to delete or block
    // rejected memberships. Maybe an OgInviteType would be nice so that
    // settings are saved per entity.
    $membership->setState(OgMembershipInterface::STATE_BLOCKED);
    $membership->save();

    $invite->setDecision(OgInviteInterface::DECISION_REJECT);
    $invite->getDecisionDate(\Drupal::time()->getRequestTime());
    // The invite is not active anymore.
    $invite->setActive(OgInviteInterface::NOT_ACTIVE);
    $invite->save();

    $group = $membership->getGroup();
    $route_parameters = $group->toUrl()->getRouteParameters();
    return $this->redirect("entity.{$group->getEntityTypeId()}.canonical", $route_parameters);
  }

  /**
   * Custom check access for accepting/rejecting an Invite.
   *
   * Mainly, only the membership user is able to accept or reject the
   * invitation.
   *
   * @param string $invite_hash
   *    The invite hash.
   *
   * @return AccessResult
   *    The result of the access check.
   */
  public function decisionInviteAccess($invite_hash) {
    if ($this->currentUser->isAnonymous()) {
      return AccessResult::forbidden();
    }

    $invite = $this->getInviteByHash($invite_hash);
    if (empty($invite)) {
      return AccessResult::forbidden();
    }

    if (!($membership = $invite->getMembership())) {
      return AccessResult::forbidden();
    }

    if ($membership->getUser()->id() !== $this->currentUser()->id()) {
      return AccessResult::forbidden();
    }

    if ($membership->getState() !== OgMembershipInterface::STATE_PENDING || !empty($invite->getDecision())) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * Revokes an invitation.
   *
   * @param string $invite_hash
   *    The invite hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *    A redirect to the invite route.
   */
  public function revokeInvite($invite_hash) {
    $invite = $this->getInviteByHash($invite_hash);
    $membership = $invite->getMembership();
    // @todo: Global settings should be available for whether to delete or block
    // revoked invitations. Maybe an OgInviteType would be nice so that
    // settings are saved per entity.
    $membership->setState(OgMembershipInterface::STATE_BLOCKED);
    $membership->save();

    // The invite is not active anymore.
    $invite->setActive(OgInviteInterface::NOT_ACTIVE);
    $invite->save();

    $group = $membership->getGroup();
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
    $route_name = $user->toUrl()->getRouteName();
    $route_parameters = $group->toUrl()->getRouteParameters();
    return $this->redirect($route_name, $route_parameters);
  }

  /**
   * Custom check access for revoking an Invite.
   *
   * @param string $invite_hash
   *    The invite hash.
   *
   * @return AccessResult
   *    The result of the access check.
   */
  public function revokeInviteAccess($invite_hash) {
    if ($this->currentUser->isAnonymous()) {
      return AccessResult::forbidden();
    }

    $invite = $this->getInviteByHash($invite_hash);
    if (empty($invite)) {
      return AccessResult::forbidden();
    }

    if (!($membership = $invite->getMembership())) {
      return AccessResult::forbidden();
    }

    if ($membership->getState() !== OgMembershipInterface::STATE_PENDING || !empty($invite->getDecision())) {
      return AccessResult::forbidden();
    }

    $created_by = $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
    if (!($user_membership = $this->ogMembershipManager->getMembership($membership->getGroup(), $created_by))) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowedIf($this->ogAccess->userAccess($membership->getGroup(), 'revoke group invitation', $created_by)->isAllowed());
  }

  /**
   * Returns the invitation given the hash.
   *
   * @param string $invite_hash
   *    The invite hash.
   *
   * @return \Drupal\og_invite\OgInviteInterface|null
   *    The loaded invite or null if no invite is found.
   */
  protected function getInviteByHash($invite_hash) {
    $this->ogInviteStorage = $this->entityTypeManager->getStorage('og_invite');
    $invites = $this->ogInviteStorage->loadByProperties([
      'invite_hash' => $invite_hash,
    ]);

    return empty($invites) ? NULL : reset($invites);
  }

}

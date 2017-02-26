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
   * @return array
   *    A return array, whether to be rendered or to be processed.
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

}

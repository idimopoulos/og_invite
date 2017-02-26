<?php

namespace Drupal\og_invite\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManager;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgContextInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og_invite\InviteManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'InviteAction' action.
 *
 * @Action(
 *  id = "og_invite_action",
 *  label = @Translation("Invite member(s)"),
 *  type = "user"
 * )
 */
class InviteAction extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current group.
   *
   * @var \Drupal\og\OgContextInterface
   */
  protected $groupContenxt;

  /**
   * The og membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The og invite manager service.
   *
   * @var \Drupal\og_invite\InviteManagerInterface
   */
  protected $inviteManager;

  /**
   * Constructs a new InviteAction object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\og\OgContextInterface $og_context
   *   Current group.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The og membership manager service.
   * @param \Drupal\og_invite\InviteManagerInterface $invite_manager
   *   The og invite manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, OgContextInterface $og_context, MembershipManagerInterface $membership_manager, InviteManagerInterface $invite_manager) {
    $this->currentUser = $current_user;
    $this->groupContenxt = $og_context;
    $this->membershipManager = $membership_manager;
    $this->inviteManager = $invite_manager;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('og.context'),
      $container->get('og.membership_manager'),
      $container->get('og_invite.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {

  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $forbidden_access = $return_as_object ? AccessResult::forbidden() : FALSE;
    $group = $this->groupContenxt->getGroup();
    $account = empty($account) ? $this->currentUser : $account;

    if (empty($this->groupContenxt) || empty($this->groupContenxt->getGroup())) {
      return $forbidden_access;
    }

    // If the current user already has a membership, no invitation will be
    // created.
    $states = [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_BLOCKED,
      OgMembershipInterface::STATE_PENDING,
    ];
    if (!$this->membershipManager->isMember($group, $object, $states)) {
      return $forbidden_access;
    }

    $membership = $this->membershipManager->getMembership($group, $account);
    if (empty($membership)) {
      return $forbidden_access;
    }

    $access = $membership->hasPermission('invite group member');
    return $return_as_object ? $access : $access->isAllowed();
  }

}

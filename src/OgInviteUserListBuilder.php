<?php

namespace Drupal\og_invite;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\og\ContextProvider\OgContext;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of user to invite.
 */
class OgInviteUserListBuilder extends EntityListBuilder {

  /**
   * The og group retrieved from the context.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $ogGroup;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The currently logged in user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a new OgInviteUserListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\ContextProvider\OgContext $og_context
   *   The og context provider.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The currently logged in user.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entity_type_manager, OgContext $og_context, AccountProxyInterface $current_user, MembershipManagerInterface $membership_manager) {
    parent::__construct($entity_type, $storage);
    $this->entityTypeManager = $entity_type_manager;
    $this->ogGroup = $og_context->getGroup();
    $this->currentUser = $current_user;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager'),
      $container->get('og.context'),
      $container->get('current_user'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $membership_storage = $this->entityTypeManager->getStorage('og_membership');
    $membership_ids = $membership_storage->loadByProperties([
      'entity_type' => $this->ogGroup->getEntityTypeId(),
      'entity_id' => $this->ogGroup->id(),
    ]);
    $memberships = $membership_storage->loadMultiple($membership_ids);
    $excluded_ids = array_map(function (OgMembershipInterface $membership) {
      return $membership->getUser()->id();
    }, $memberships);

    $entity_query = $this->storage->getQuery();
    $entity_query->condition('uid', 0, '<>');
    $entity_query->condition('uid', $excluded_ids, 'NOT IN');
    $entity_query->pager(50);
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $uids = $entity_query->execute();
    return $this->storage->loadMultiple($uids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
      'username' => array(
        'data' => $this->t('Username'),
        'field' => 'name',
        'specifier' => 'name',
      ),
      'operations' => $this->t('Operations'),
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['username']['data'] = array(
      '#theme' => 'username',
      '#account' => $entity,
    );

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations['invite'] = [
      'title' => $this->t('Edit'),
      'weight' => 10,
      'url' => Url::fromRoute('/'),
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No people available.');
    return $build;
  }

  /**
   * Returns a form of users to be invited.
   *
   * @param string $entity_type_id
   *    The entity type id.
   * @param string $entity_id
   *    The entity id.
   *
   * @return array
   *    The form array.
   */
  public function getForm($entity_type_id, $entity_id) {
    return $this->render();
  }

}

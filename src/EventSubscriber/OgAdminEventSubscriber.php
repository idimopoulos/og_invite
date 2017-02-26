<?php

namespace Drupal\og_invite\EventSubscriber;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\OgAdminRoutesEventInterface;
use Drupal\og\PermissionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class OgAdminEventSubscriber.
 *
 * @package Drupal\og_invite
 */
class OgAdminEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The OG permission manager.
   *
   * @var \Drupal\og\PermissionManagerInterface
   */
  protected $permissionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The service providing information about bundles.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs an OgAdminEventSubscriber object.
   *
   * @param \Drupal\og\PermissionManagerInterface $permission_manager
   *   The OG permission manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The service providing information about bundles.
   */
  public function __construct(PermissionManagerInterface $permission_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->permissionManager = $permission_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[OgAdminRoutesEventInterface::EVENT_NAME] = ['provideOgAdminRoutes'];

    return $events;
  }

  /**
   * Provide OG admin routes.
   *
   * Og provides the admin base path where all sub paths should be placed.
   * The base route is 'entity.{$entity_type_id}.og_admin_routes.{$name}' where
   * $entity_type_id is the entity type id machine name and $name is the name of
   * the route. Og already supplies the 'members' name.
   * The base url is '/group/$entity_type_id/{{$entity_type_id}}/admin'. The
   * path below should not include the starting '/'.
   *
   * @param \Drupal\og\Event\OgAdminRoutesEventInterface $event
   *   The OG admin routes event object.
   *
   * @see \Drupal\og\Routing\RouteSubscriber
   * @see \Drupal\og\Event\OgAdminRoutesEvent
   * @see \Drupal\og\EventSubscriber\OgEventSubscriber
   */
  public function provideOgAdminRoutes(OgAdminRoutesEventInterface $event) {
    $routes_info = $event->getRoutesInfo();

    $routes_info['invite'] = [
      'controller' => '\Drupal\og_invite\Controller\OgInvite::inviteForm',
      'title' => 'Invite',
      'description' => 'Invite people to the group',
      'path' => 'members/invite',
      'requirements' => [
        '_og_user_access_group' => 'administer group|invite group member',
      ],
    ];

    $event->setRoutesInfo($routes_info);
  }

}

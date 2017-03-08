<?php

namespace Drupal\og_invite\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\og_invite\OgInviteTypeInterface;

/**
 * Defines the Og invite type entity.
 *
 * @ConfigEntityType(
 *   id = "og_invite_type",
 *   label = @Translation("Og invite type"),
 *   config_prefix = "og_invite_type",
 *   bundle_of = "og_invite",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "label",
 *   },
 * )
 */
class OgInviteType extends ConfigEntityBase implements OgInviteTypeInterface {

  /**
   * The Og invite type ID.
   *
   * @var string
   */
  protected $type;

  /**
   * Return the ID of the entity.
   *
   * @return string|null
   *   The type of the entity.
   */
  public function id() {
    return $this->type;
  }

}

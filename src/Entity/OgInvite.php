<?php

namespace Drupal\og_invite\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\og\OgMembershipInterface;
use Drupal\og_invite\OgInviteInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Invite entity.
 *
 * @ingroup og_invite
 *
 * @ContentEntityType(
 *   id = "og_invite",
 *   label = @Translation("Invite"),
 *   handlers = {
 *     "views_data" = "Drupal\og_invite\Entity\OgInviteViewsData",
 *     "access" = "Drupal\og_invite\OgInviteAccessControlHandler",
 *   },
 *   fieldable = TRUE,
 *   bundle_entity_type = "og_invite_type",
 *   base_table = "og_invite",
 *   admin_permission = "administer invite entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "invite_hash",
 *     "mid" = "mid",
 *     "nid" = "nid",
 *     "uuid" = "uuid",
 *     "uid" = "created_by",
 *   },
 *   bundle_keys = {
 *     "bundle" = "type",
 *   }
 * )
 */
class OgInvite extends ContentEntityBase implements OgInviteInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'uid' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Invite entity.'))
      ->setReadOnly(TRUE);

    $fields['mid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Membership'))
      ->setDescription(t('The membership entity related to the invitation.'))
      ->setSetting('target_type', 'og_membership')
      ->setSetting('handler', 'default');

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group entity type'))
      ->setDescription(t('The entity type of the group.'));

    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group entity id'))
      ->setDescription(t("The entity ID of the group."));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user ID related to the membership.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Invite entity.'))
      ->setReadOnly(TRUE);

    $fields['created_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of user that performs the invitation.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE);

    $fields['invite_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Invite hash'))
      ->setDescription(t('The invite_hash of the Invite entity.'))
      ->addConstraint('UniqueField')
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValueCallback('Drupal\og_invite\Entity\OgInvite::generateRandomSequence');

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Active status'))
      ->setDescription(t('A boolean indicating whether the Invite is active or not.'))
      ->setDefaultValue(OgInviteInterface::ACTIVE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['decision'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Decision'))
      ->setDescription(t('A boolean indicating whether the invitation has been accepted or not.'))
      ->setRequired(FALSE)
      ->setDefaultValue(OgInviteInterface::DECISION_PENDING);

    $fields['decision_date'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Decision date'))
      ->setDescription(t('The time of the decision.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getInviteHash() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getInviteAcceptUri() {
    return Url::fromRoute('og_invite.invite.accept', [
      'invite_hash' => $this->getInviteHash(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getInviteRejectUri() {
    return Url::fromRoute('og_invite.invite.reject', [
      'invite_hash' => $this->getInviteHash(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getInviteRevokeUri() {
    return Url::fromRoute('og_invite.invite.revoke', [
      'invite_hash' => $this->getInviteHash(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('invite_hash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setInviteHash($invite_hash) {
    $this->setName($invite_hash);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($invite_hash) {
    $this->set('invite_hash', $invite_hash);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedBy() {
    return $this->get('created_by')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedById() {
    return $this->get('created_by')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedById($uid) {
    $this->set('created_by', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedBy(UserInterface $account) {
    $this->set('created_by', $account->id());
    return $this;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getMembershipUser() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipUserId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return $this->get('status')->value == OgInviteInterface::ACTIVE;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    $this->get('status')->value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDecisionDate() {
    return $this->get('decision_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDecisionDate($timestamp) {
    $this->set('decision_date', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDecision() {
    return $this->get('decision')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDecision($decision) {
    $this->set('decision', $decision);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembership() {
    return $this->get('mid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembershipId() {
    return $this->get('mid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setMembershipId($mid) {
    $this->set('mid', $mid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMembership(OgMembershipInterface $membership) {
    $this->set('mid', $membership->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroup(EntityInterface $group) {
    $this->set('entity_type', $group->getEntityTypeId());
    $this->set('entity_id', $group->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupEntityType() {
    return $this->get('entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupId() {
    return $this->get('entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    $entity_type = $this->get('entity_type')->value;
    $entity_id = $this->get('entity_id')->value;
    return \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
  }

  /**
   * Generates a random string.
   *
   * @return string
   *    The generated sequence.
   */
  public static function generateRandomSequence() {
    $random = user_password();
    return [$random];
  }

}

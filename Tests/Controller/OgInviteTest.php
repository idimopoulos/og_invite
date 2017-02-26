<?php

namespace Drupal\og_invite\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the og_invite module.
 */
class OgInviteTest extends WebTestBase {

  /**
   * Drupal\og\MembershipManager definition.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $og_membership_manager;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $current_user;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "og_invite OgInvite's controller functionality",
      'description' => 'Test Unit for module og_invite and controller OgInvite.',
      'group' => 'Other',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests og_invite functionality.
   */
  public function testOgInvite() {
    // Check that the basic functions of module og_invite.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}

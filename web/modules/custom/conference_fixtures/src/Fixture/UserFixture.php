<?php

namespace Drupal\conference_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\user\Entity\User;

/**
 * Class UserFixture.
 *
 * @package Drupal\conference_fixtures\Fixture
 */
class UserFixture extends AbstractFixture {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\user\Entity\User $administrator */
    $administrator = User::create()
      ->setUsername('administrator@example.com')
      ->setPassword('administrator')
      ->activate();

    $administrator->addRole('administrator');

    $this->setReference('user:administrator', $administrator);

    $administrator->save();

    /** @var \Drupal\user\Entity\User $organizer */
    $organizer = User::create()
      ->setUsername('organizer@example.com')
      ->setPassword('organizer')
      ->activate();

    $organizer->addRole('organizer');

    $this->setReference('user:organizer', $organizer);

    $organizer->save();
  }

}

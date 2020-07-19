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
    /** @var \Drupal\user\Entity\User $user */
    $user = User::create()
      ->setUsername('administrator@example.com')
      ->setPassword('administrator')
      ->activate();
    $user->addRole('administrator');
    $user->save();
    $this->setReference('user:administrator', $user);

    $user = User::create()
      ->setUsername('organizer@example.com')
      ->setPassword('organizer')
      ->activate();
    $user->addRole('organizer');
    $user->save();
    $this->setReference('user:organizer', $user);

    $user = User::create()
      ->setUsername('conference-administrator@example.com')
      ->setPassword('conference-administrator')
      ->activate();
    $user->save();
    $this->setReference('user:conference-administrator', $user);

    $user = User::create()
      ->setUsername('conference-editor@example.com')
      ->setPassword('conference-editor')
      ->activate();
    $user->save();
    $this->setReference('user:conference-editor', $user);
  }

}

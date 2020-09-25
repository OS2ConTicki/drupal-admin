<?php

namespace Drupal\os2conticki_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\user\Entity\User;

/**
 * TODO: Description of what the class does.
 *
 * @package Drupal\os2conticki_fixtures\Fixture
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
      ->setUsername('conference-administrator@example.com')
      ->setPassword('conference-administrator')
      ->activate();
    $user->addRole('conference_administrator');
    $user->save();
    $this->setReference('user:conference-administrator', $user);

    $user = User::create()
      ->setUsername('conference-editor@example.com')
      ->setPassword('conference-editor')
      ->activate();
    $user->addRole('conference_editor');
    $user->save();
    $this->setReference('user:conference-editor', $user);
  }

}

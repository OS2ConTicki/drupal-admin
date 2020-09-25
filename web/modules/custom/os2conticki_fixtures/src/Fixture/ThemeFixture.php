<?php

namespace Drupal\os2conticki_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;
use Drupal\node\Entity\Node;

/**
 * TODO: Description of what the class does.
 *
 * @package Drupal\os2conticki_fixtures\Fixture
 */
class ThemeFixture extends AbstractFixture implements DependentFixtureInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\node\Entity\Node $theme */
    $theme = Node::create([
      'type' => 'theme',
      'title' => 'Drupal',
      'field_conference' => $this->getReference('conference:001'),
    ]);
    $theme->setOwner($this->getReference('user:conference-administrator'));

    $this->setReference('theme:drupal', $theme);

    $theme->save();

    $theme = Node::create([
      'type' => 'theme',
      'title' => 'API',
      'field_conference' => $this->getReference('conference:001'),
    ]);
    $theme->setOwner($this->getReference('user:conference-administrator'));

    $this->setReference('theme:api', $theme);

    $theme->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return [
      ConferenceFixture::class,
      UserFixture::class,
    ];
  }

}

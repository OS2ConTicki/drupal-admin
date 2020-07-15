<?php

namespace Drupal\conference_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;
use Drupal\node\Entity\Node;

/**
 * Class TagFixture.
 *
 * @package Drupal\conference_fixtures\Fixture
 */
class TagFixture extends AbstractFixture implements DependentFixtureInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\node\Entity\Node $tag */
    $tag = Node::create([
      'type' => 'tag',
      'title' => 'Hat',
      'field_conference' => $this->getReference('conference:001'),
    ]);
    $tag->setOwner($this->getReference('user:organizer'));

    $this->setReference('tag:hat', $tag);

    $tag->save();

    $tag = Node::create([
      'type' => 'tag',
      'title' => 'Glasses',
      'field_conference' => $this->getReference('conference:002'),
    ]);
    $tag->setOwner($this->getReference('user:organizer'));

    $this->setReference('tag:glasses', $tag);

    $tag->save();
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

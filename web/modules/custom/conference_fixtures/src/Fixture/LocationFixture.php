<?php

namespace Drupal\conference_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;
use Drupal\node\Entity\Node;

/**
 * Class LocationFixture.
 *
 * @package Drupal\conference_fixtures\Fixture
 */
class LocationFixture extends AbstractFixture implements DependentFixtureInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\node\Entity\Node $location */
    $location = Node::create([
      'type' => 'location',
      'title' => 'Room 1',
      'field_conference' => $this->getReference('conference:001'),
    ]);
    $location->setOwner($this->getReference('user:organizer'));

    $this->setReference('location:room1', $location);

    $location->save();

    $location = Node::create([
      'type' => 'location',
      'title' => 'Room 2',
      'field_conference' => $this->getReference('conference:001'),
    ]);
    $location->setOwner($this->getReference('user:organizer'));

    $this->setReference('location:room2', $location);

    $location->save();

    $location = Node::create([
      'type' => 'location',
      'title' => 'Room 3',
      'field_conference' => $this->getReference('conference:002'),
    ]);
    $location->setOwner($this->getReference('user:organizer'));

    $this->setReference('location:room3', $location);

    $location->save();
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

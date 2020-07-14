<?php

namespace Drupal\conference_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;
use Drupal\node\Entity\Node;

/**
 * Class EventFixture.
 *
 * @package Drupal\conference_fixtures\Fixture
 */
class EventFixture extends AbstractFixture implements DependentFixtureInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\node\Entity\Node $event */
    $event = Node::create([
      'type' => 'event',
      'title' => 'The first event',
      'field_conference' => $this->getReference('conference:001'),
      'field_image' => $this->getReference('image:001'),
      'field_date' => [
        'value' => '2001-01-01T12:00:00',
        'end_value' => '2001-01-01T13:00:00',
      ],
    ]);
    $event->setOwner($this->getReference('user:organizer'));

    $event->save();

    $event = Node::create([
      'type' => 'event',
      'title' => 'Another event',
      'field_conference' => $this->getReference('conference:001'),
      'field_image' => $this->getReference('image:002'),
      'field_date' => [
        'value' => '2001-01-01T15:00:00',
        'end_value' => '2001-01-01T15:30:00',
      ],
    ]);
    $event->setUnpublished();
    $event->setOwner($this->getReference('user:organizer'));

    $event->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return [
      ConferenceFixture::class,
      ImageFixture::class,
      UserFixture::class,
    ];
  }

}

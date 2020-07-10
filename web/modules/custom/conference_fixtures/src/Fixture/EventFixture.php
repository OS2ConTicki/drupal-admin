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
      'type' => 'conference_event',
      'title' => 'The first event',
      'field_conference_conference' => $this->getReference('conference:001'),
    ]);

    $event->save();

    $event = Node::create([
      'type' => 'conference_event',
      'title' => 'Another event',
      'field_conference_conference' => $this->getReference('conference:001'),
    ]);

    $event->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return [
      ConferenceFixture::class,
    ];
  }

}

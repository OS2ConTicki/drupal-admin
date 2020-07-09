<?php

namespace Drupal\conference_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\DependentFixtureInterface;

/**
 * Class EventFixture.
 *
 * @package Drupal\conference_fixtures\Fixture
 */
class EventFixture extends AbstractNodeFixture implements DependentFixtureInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $event = $this->nodeStorage->create([
      'type' => 'conference_event',
      'title' => 'The first event',
      'field_conference_conference' => $this->getReference('conference:001'),
    ]);

    $event->save();

    $event = $this->nodeStorage->create([
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

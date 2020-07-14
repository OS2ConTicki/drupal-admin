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
      'body' => [
        'value' => <<<'BODY'
This is the first event in <emph>the conference</emph>.
BODY,
        'format' => 'rich_text',
      ],
      'field_conference' => $this->getReference('conference:001'),
      'field_image' => $this->getReference('image:001'),
      'field_image_alt' => 'An image',
      'field_date' => [
        'value' => '2001-01-01T12:00:00',
        'end_value' => '2001-01-01T13:00:00',
      ],
      'field_location' => $this->getReference('location:room1'),
      'field_tags' => [
        $this->getReference('tag:hat'),
      ],
      'field_organizers' => [
        $this->getReference('organizer:someone'),
      ],
      'field_speakers' => [
        $this->getReference('speaker:donald'),
      ],
      'field_themes' => [
        $this->getReference('theme:api'),
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
      LocationFixture::class,
      OrganizerFixture::class,
      SpeakerFixture::class,
      ThemeFixture::class,
      UserFixture::class,
    ];
  }

}

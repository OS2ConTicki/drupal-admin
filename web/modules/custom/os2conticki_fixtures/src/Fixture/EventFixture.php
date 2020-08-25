<?php

namespace Drupal\os2conticki_fixtures\Fixture;

use DateInterval;
use DateTimeImmutable;
use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;
use Drupal\node\Entity\Node;

/**
 * Class EventFixture.
 *
 * @package Drupal\os2conticki_fixtures\Fixture
 */
class EventFixture extends AbstractFixture implements DependentFixtureInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\node\Entity\Node $event */
    $event = Node::create([
      'type' => 'event',
      'title' => 'Welcome',
      'body' => [
        'value' => <<<'BODY'
Welcome!
BODY,
        'format' => 'rich_text',
      ],
      'field_conference' => $this->getReference('conference:001'),
      'field_image' => [
        'target_id' => $this->getReference('image:007')->id(),
        'alt' => 'Welcome!',
      ],
      'field_times' => [
        'value' => '2001-01-01T09:00:00',
        'end_value' => '2001-01-01T09:30:00',
      ],
      'field_location' => $this->getReference('location:room1'),
    ]);
    $event->setOwner($this->getReference('user:conference-editor'));

    $event->save();

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
      'field_image' => [
        'target_id' => $this->getReference('image:001')->id(),
        'alt' => 'An image',
      ],
      'field_times' => [
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
    $event->setOwner($this->getReference('user:conference-editor'));

    $event->save();

    $event = Node::create([
      'type' => 'event',
      'title' => 'Another event',
      'field_conference' => $this->getReference('conference:002'),
      'field_image' => [
        'target_id' => $this->getReference('image:002')->id(),
        'alt' => 'Image for the event',
      ],
      'field_times' => [
        'value' => '2001-01-01T15:00:00',
        'end_value' => '2001-01-01T15:30:00',
      ],
    ]);
    $event->setUnpublished();
    $event->setOwner($this->getReference('user:conference-editor'));

    $event->save();

    $event = Node::create([
      'type' => 'event',
      'title' => 'The third event',
      'field_conference' => $this->getReference('conference:002'),
      'field_image' => [
        'target_id' => $this->getReference('image:004')->id(),
        'alt' => 'Image for the third event',
      ],
      'field_times' => [
        'value' => '2001-12-01T15:00:00',
        'end_value' => '2001-12-01T15:30:00',
      ],
      'field_location' => $this->getReference('location:room3'),
    ]);
    $event->setUnpublished();
    $event->setOwner($this->getReference('user:conference-editor'));

    $event->save();

    // Events on a very long conference.
    $startTime = new DateTimeImmutable('2001-01-01T10:00:00');
    for ($i = 0; $i < 100; $i++) {
      $title = sprintf('Event %d', $i);
      $event = Node::create([
        'type' => 'event',
        'title' => $title,
        'field_conference' => $this->getReference('conference:long'),
        'field_image' => [
          'target_id' => $this->getReference(sprintf('image:%03d', $i % 8 + 1))->id(),
          'alt' => 'Image for the event',
        ],
        'field_times' => [
          'value' => $startTime->format('Y-m-d\TH:i:s'),
          'end_value' => $startTime->add(new DateInterval('PT45M'))->format('Y-m-d\TH:i:s'),
        ],
        'field_location' => $this->getReference('location:long-room'),
        'field_ticket' => [
          'uri' => 'https://dummyimage.com/600x400/000/fff&text=' . urlencode($title),
          'title' => sprintf('Buy ticket for %s', $title),
        ],
      ]);
      $event->setOwner($this->getReference('user:conference-editor'));
      $event->save();

      $startTime = $startTime->add(new DateInterval('PT60M'));
    }
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

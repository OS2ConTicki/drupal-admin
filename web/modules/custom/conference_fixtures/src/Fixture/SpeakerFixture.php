<?php

namespace Drupal\conference_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;
use Drupal\node\Entity\Node;

/**
 * Class SpeakerFixture.
 *
 * @package Drupal\conference_fixtures\Fixture
 */
class SpeakerFixture extends AbstractFixture implements DependentFixtureInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\node\Entity\Node $speaker */
    $speaker = Node::create([
      'type' => 'speaker',
      'title' => 'Donald Duck',
      'field_conference' => $this->getReference('conference:001'),
    ]);
    $speaker->setOwner($this->getReference('user:conference-editor'));

    $this->setReference('speaker:donald', $speaker);

    $speaker->save();

    $speaker = Node::create([
      'type' => 'speaker',
      'title' => 'Mickey Mouse',
      'field_conference' => $this->getReference('conference:001'),
    ]);
    $speaker->setOwner($this->getReference('user:conference-editor'));

    $this->setReference('speaker:mickey', $speaker);

    $speaker->save();
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

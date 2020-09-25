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
class OrganizerFixture extends AbstractFixture implements DependentFixtureInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\node\Entity\Node $organizer */
    $organizer = Node::create([
      'type' => 'organizer',
      'title' => 'Someone',
      'field_conference' => $this->getReference('conference:001'),
    ]);
    $organizer->setOwner($this->getReference('user:conference-editor'));

    $this->setReference('organizer:someone', $organizer);

    $organizer->save();

    $organizer = Node::create([
      'type' => 'organizer',
      'title' => 'Another organizer',
      'field_conference' => $this->getReference('conference:002'),
    ]);
    $organizer->setOwner($this->getReference('user:conference-editor'));

    $this->setReference('organizer:another', $organizer);

    $organizer->save();
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

<?php

namespace Drupal\conference_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;

/**
 * Class OrganizerFixture.
 *
 * @package Drupal\conference_fixtures\Fixture
 */
class ConferenceOrganizerFixture extends AbstractFixture implements DependentFixtureInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\node\Entity\Node $organizer */
    $conference = $this->getReference('conference:001');
    $conference->field_organizers = [
      $this->getReference('organizer:someone'),
    ];
    $conference->save();

    $conference = $this->getReference('conference:002');
    $conference->field_organizers = [
      $this->getReference('organizer:another'),
    ];
    $conference->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return [
      ConferenceFixture::class,
      OrganizerFixture::class,
    ];
  }

}

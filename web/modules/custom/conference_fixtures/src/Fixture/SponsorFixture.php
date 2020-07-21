<?php

namespace Drupal\conference_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;
use Drupal\node\Entity\Node;

/**
 * Class SponsorFixture.
 *
 * @package Drupal\conference_fixtures\Fixture
 */
class SponsorFixture extends AbstractFixture implements DependentFixtureInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\node\Entity\Node $sponsor */
    $sponsor = Node::create([
      'type' => 'sponsor',
      'title' => 'Damage, Inc.',
      'field_conference' => $this->getReference('conference:001'),
    ]);
    $sponsor->setOwner($this->getReference('user:conference-editor'));

    $this->setReference('sponsor:damage', $sponsor);

    $sponsor->save();

    $sponsor = Node::create([
      'type' => 'sponsor',
      'title' => 'Acme Corporation',
      'field_conference' => $this->getReference('conference:001'),
    ]);
    $sponsor->setOwner($this->getReference('user:conference-editor'));

    $this->setReference('sponsor:acme', $sponsor);

    $sponsor->save();
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

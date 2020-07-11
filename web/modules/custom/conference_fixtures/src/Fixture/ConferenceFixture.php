<?php

namespace Drupal\conference_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;
use Drupal\node\Entity\Node;

/**
 * Class ConferenceFixture.
 *
 * @package Drupal\conference_fixtures\Fixture
 */
class ConferenceFixture extends AbstractFixture implements DependentFixtureInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\node\Entity\Node $conference */
    $conference = Node::create([
      'type' => 'conference',
      'title' => 'The first conference',
      'body' => [
        'value' => <<<'BODY'
This is the first conference.

It'll be <strong>fun</strong>!
BODY,
        'format' => 'rich_text',
      ],
    ]);
    $conference->setOwner($this->getReference('user:organizer'));

    $this->setReference('conference:001', $conference);

    $conference->save();

    /** @var \Drupal\node\Entity\Node $conference */
    $conference = Node::create([
      'type' => 'conference',
      'title' => 'Another conference',
      'body' => <<<'BODY'
This is the second conference.

It'll be <strong>fun</strong>!
BODY,
    ]);
    $conference->setOwner($this->getReference('user:organizer'));

    $this->setReference('conference:002', $conference);

    $conference->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return [
      UserFixture::class,
    ];
  }

}

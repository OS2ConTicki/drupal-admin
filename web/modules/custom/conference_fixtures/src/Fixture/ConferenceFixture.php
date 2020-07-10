<?php

namespace Drupal\conference_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\node\Entity\Node;

/**
 * Class ConferenceFixture.
 *
 * @package Drupal\conference_fixtures\Fixture
 */
class ConferenceFixture extends AbstractFixture {

  /**
   * {@inheritdoc}
   */
  public function load() {
    /** @var \Drupal\node\Entity\Node $conference */
    $conference = Node::create([
      'type' => 'conference_conference',
      'title' => 'The first conference',
      'body' => <<<'BODY'
This is the first conference.

It'll be <strong>fun</strong>!
BODY,
    ]);

    $this->setReference('conference:001', $conference);

    $conference->save();
  }

}

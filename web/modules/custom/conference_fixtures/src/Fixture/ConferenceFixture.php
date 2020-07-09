<?php

namespace Drupal\conference_fixtures\Fixture;

/**
 * Class ConferenceFixture.
 *
 * @package Drupal\conference_fixtures\Fixture
 */
class ConferenceFixture extends AbstractNodeFixture {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $conference = $this->nodeStorage->create([
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

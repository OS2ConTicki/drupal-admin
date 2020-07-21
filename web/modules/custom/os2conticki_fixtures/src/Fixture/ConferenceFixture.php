<?php

namespace Drupal\os2conticki_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\content_fixtures\Fixture\DependentFixtureInterface;
use Drupal\node\Entity\Node;

/**
 * Class ConferenceFixture.
 *
 * @package Drupal\os2conticki_fixtures\Fixture
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
      'field_dates' => [
        'value' => '2001-01-07T00:00:00',
        'end_value' => '2001-01-11T23:59:59',
      ],
      'field_image' => [
        'target_id' => $this->getReference('image:004')->id(),
        'alt' => 'Image for the conference',
      ],
    ]);
    $conference->setOwner($this->getReference('user:conference-administrator'));

    $this->setReference('conference:001', $conference);

    $conference->save();

    $conference = Node::create([
      'type' => 'conference',
      'title' => 'Another conference',
      'body' => <<<'BODY'
This is the second conference.

It'll be <strong>fun</strong>!
BODY,
      'field_dates' => [
        'value' => '2001-01-01T00:00:00',
        'end_value' => '2001-01-04T23:59:59',
      ],
    ]);
    $conference->setOwner($this->getReference('user:conference-administrator'));

    $this->setReference('conference:002', $conference);

    $conference->save();

    $conference = Node::create([
      'type' => 'conference',
      'title' => 'A very long conference',
      'body' => <<<'BODY'
This conference has many events.
BODY,
      'field_dates' => [
        'value' => '2001-01-01T00:00:00',
        'end_value' => '2001-12-31T23:59:59',
      ],
    ]);
    $conference->setOwner($this->getReference('user:conference-administrator'));
    $this->setReference('conference:long', $conference);

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

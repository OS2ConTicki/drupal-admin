<?php

namespace Drupal\conference_content\Helper;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Conference helper.
 */
class ConferenceHelper {
  use StringTranslationTrait;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  private $entityRepository;

  /**
   * Constructor.
   */
  public function __construct(EntityRepositoryInterface $entityRepository, TranslationInterface $translation) {
    $this->entityRepository = $entityRepository;
    $this->setStringTranslation($translation);
  }

  /**
   * Get info on entities that belong to a conference.
   *
   * @return array[]
   *   Info on entities.
   */
  public function getConferenceEntititesInfo() {
    return [
      'event' => [
        'title' => $this->t('Events'),
        'add' => $this->t('Add event'),
      ],
      'speaker' => [
        'title' => $this->t('Speakers'),
        'add' => $this->t('Add speaker'),
      ],
      'tag' => [
        'title' => $this->t('Tags'),
        'add' => $this->t('Add tag'),
      ],
      'location' => [
        'title' => $this->t('Locations'),
        'add' => $this->t('Add location'),
      ],
      'theme' => [
        'title' => $this->t('Themes'),
        'add' => $this->t('Add theme'),
      ],
      'sponsor' => [
        'title' => $this->t('Sponsors'),
        'add' => $this->t('Add sponsor'),
      ],
      'organizer' => [
        'title' => $this->t('Organizers'),
        'add' => $this->t('Add organizer'),
      ],
    ];
  }

  /**
   * Get entities related to a conference.
   */
  public function getEntitites(NodeInterface $conference, string $type) {
    $ids = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->condition('field_conference', $conference->id())
      ->execute();

    return Node::loadMultiple($ids);
  }

  /**
   * Get conference by uuid.
   */
  public function loadByUuid(string $uuid = NULL): ?NodeInterface {
    $conference = $this->entityRepository->loadEntityByUuid('node', $uuid ?? '');

    return (NULL !== $conference && 'conference' === $conference->bundle()) ? $conference : NULL;
  }

  /**
   * Get conference by uuid.
   */
  public function loadById($id): ?NodeInterface {
    $conference = Node::load($id);

    return (NULL !== $conference && 'conference' === $conference->bundle()) ? $conference : NULL;
  }

}

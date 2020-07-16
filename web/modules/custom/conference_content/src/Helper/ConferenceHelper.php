<?php

namespace Drupal\conference_content\Helper;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Conference helper.
 */
class ConferenceHelper {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  private $entityRepository;

  /**
   * Constructor.
   */
  public function __construct(EntityRepositoryInterface $entityRepository) {
    $this->entityRepository = $entityRepository;
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

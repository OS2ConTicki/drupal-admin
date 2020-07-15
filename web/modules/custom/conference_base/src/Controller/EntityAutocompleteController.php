<?php

namespace Drupal\conference_base\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EntityAutocompleteController.
 *
 * @package Drupal\conference_base\Controller
 */
class EntityAutocompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request, NodeInterface $conference, string $type) {
    $results = [];

    $input = $request->query->get('q');
    $input = Xss::filter($input);

    $ids = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->condition('status', 1)
      ->condition('field_conference', $conference->id())
    // ->condition('title', '%'.$input.'%', 'LIKE')
      ->execute();

    $nodes = Node::loadMultiple($ids ?? []);
    foreach ($nodes as $node) {
      $label = [
        $node->getTitle(),
      ];

      $results[] = [
        'value' => EntityAutocomplete::getEntityLabels([$node]),
        'label' => implode(' ', $label),
      ];
    }

    return new JsonResponse($results);
  }

}

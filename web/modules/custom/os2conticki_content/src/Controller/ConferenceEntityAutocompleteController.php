<?php

namespace Drupal\os2conticki_content\Controller;

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
 * @package Drupal\os2conticki_content\Controller
 */
class ConferenceEntityAutocompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request, NodeInterface $conference, string $target_type) {
    $results = [];

    $input = $request->query->get('q');
    $input = Xss::filter($input);

    $query = \Drupal::entityQuery($target_type)
      ->condition('status', 1)
      ->condition('field_conference', $conference->id());
    if ('*' !== $input) {
      $query->condition('title', '%' . \Drupal::database()->escapeLike($input) . '%',
        'LIKE');
    }
    if ($bundles = $request->get('bundles')) {
      $query->condition('type', (array) $bundles, 'IN');
    }

    $ids = $query->execute();

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

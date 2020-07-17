<?php

namespace Drupal\conference_api\JsonApi;

use Drupal\Core\Url;
use Drupal\file\FileInterface;

/**
 * Class Helper.
 *
 * @package Drupal\conference_api\JsonApi
 */
class Helper {

  /**
   * Converts JSON:API data to Conference API data.
   */
  public function convertContent(array $document): array {
    foreach (['data', 'included'] as $key) {
      if (isset($document[$key])) {
        $document[$key] = $this->isAssoc($document[$key])
          ? $this->convertItem($document[$key])
          : array_map([$this, 'convertItem'], $document[$key]);
      }
    }

    // Fix JSON:API urls to point to our custom api.
    array_walk_recursive($document, function (&$value) {
      if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
        $value = preg_replace('@/jsonapi/node/@', '/api/', $value);
      }
    });

    return $document;
  }

  /**
   * Convert item.
   */
  private function convertItem(array $item) {
    $item['type'] = $this->getType($item['type']);

    if (isset($item['attributes'])) {
      $attributes = &$item['attributes'];

      if (isset($item['relationships']['field_image']['data']['id'])) {
        $data = $item['relationships']['field_image']['data'];
        $image = $this->getFile($data['id']);
        if ($image) {
          $attributes['image'] = [
            'url' => $image->createFileUrl(FALSE),
            'meta' => $data['meta'] ?? NULL,
          ];
        }
      }

      foreach ($attributes as $name => $value) {
        if ('body' === $name && is_array($value)) {
          // Flatten rich text value.
          $attributes['summary'] = $value['summary'] ?? NULL;
          $attributes['description'] = $value['processed'] ?? NULL;
          unset($attributes[$name]);
        }

        // Flatten date ranges.
        if (in_array($name, ['field_dates', 'field_times'], TRUE) && isset($value['value'], $value['end_value'])) {
          $attributes['start_time'] = $value['value'];
          $attributes['end_time'] = $value['end_value'];
          unset($attributes[$name]);
        }
      }

      // Add links to related resources.
      switch ($item['type']) {
        case 'conference':
          foreach (array_keys($this->getContentTypes()) as $type) {
            if ($item['type'] === $type) {
              continue;
            }
            $item['links'][$type]['href'] = $this->generateApiUrl([
              'type' => $type,
              'filter' => ['field_' . $item['type'] . '.id' => $item['id']],
            ]);
          }
          $item['links']['all']['href'] = $this->generateApiUrl([
            'type' => 'event',
            'filter' => ['field_' . $item['type'] . '.id' => $item['id']],
            'include' => implode(',', [
              'conference',
              'conference.organizers',
              'location',
              'organizers',
              'speakers',
              'sponsors',
              'tags',
              'themes',
            ]),
          ]);
          break;
      }

      $relationships = [];
      foreach ([
        'field_conference' => 'conference',
        'field_location' => 'location',
        'field_organizers' => 'organizers',
        'field_speakers' => 'speakers',
        'field_sponsors' => 'sponsors',
        'field_tags' => 'tags',
        'field_themes' => 'themes',
      ] as $field => $type) {
        if (isset($item['relationships'][$field])) {
          $relationships[$type] = $this->convertRelationship($item['relationships'][$field]);
        }
      }

      if ($relationships) {
        $item['relationships'] = $relationships;
      }
      else {
        unset($item['relationships']);
      }

      // Keep only the stuff we need.
      $allowedNames = [
        'title',
        'image',
        'langcode',
        'title',
        'created',
        'changed',
        'promote',
        'start_time',
        'end_time',
        'description',
        'summary',
      ];
      $attributes = $this->includeKeys($allowedNames, $attributes);
    }

    return $item;
  }

  /**
   * Convert relationship.
   */
  private function convertRelationship(array $relationship): array {
    if (isset($relationship['data'])) {
      $links = [];
      if ($this->isAssoc($relationship['data'])) {
        $relationship['data']['type'] = $this->getType($relationship['data']['type']);

        if (isset($relationship['links']['related'])) {
          $links['related'] = [
            'href' => $this->generateApiUrl([
              'type' => $relationship['data']['type'],
              'id' => $relationship['data']['id'],
            ]),
          ];
        }
      }
      else {
        foreach ($relationship['data'] as &$item) {
          $item['type'] = $this->getType($item['type']);
        }

        // @TODO Add "related" to links.
      }
      if ($links) {
        $relationship['links'] = $links;
      }
      else {
        unset($relationship['links']);
      }
    }

    return $relationship;
  }

  /**
   * Get type.
   */
  private function getType(string $type): string {
    return preg_replace('/^node--/', '', $type);
  }

  /**
   * Include keys in array.
   */
  private function includeKeys(array $keys, array $value) {
    return array_filter($value, static function ($name) use ($keys) {
      return in_array($name, $keys, TRUE);
    }, ARRAY_FILTER_USE_KEY);
  }

  /**
   * Is assoc.
   *
   * @see https://stackoverflow.com/a/173479
   */
  private function isAssoc(array $arr) {
    if ([] === $arr) {
      return FALSE;
    }
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

  /**
   * Generate API url.
   */
  public function generateApiUrl(array $parameters = []): string {
    $url = Url::fromRoute('conference_api.api_controller_index', $parameters, ['absolute' => TRUE]);

    // @see https://www.lullabot.com/articles/early-rendering-a-lesson-in-debugging-drupal-8
    return $url->toString(TRUE)->getGeneratedUrl();
  }

  /**
   * Get JSON:API path from a Conference API path.
   */
  public function getJsonApiPath(string $type = NULL, string $id = NULL): ?string {
    $apiPath = '/jsonapi/node';

    if (NULL !== $type) {
      $apiPath .= '/' . $this->getNodeType($type);

      if (NULL !== $id) {
        // Entity id.
        $apiPath .= '/' . $id;
      }
    }

    return $apiPath;
  }

  /**
   * Get Conference API path from JSON:API path.
   */
  private function getApiPath(string $jsonApiPath = NULL): ?string {
    throw new \RuntimeException(__METHOD__ . ' not implemented!');
  }

  /**
   * Get node type.
   */
  private function getNodeType(string $type): string {
    $types = $this->getContentTypes();

    if (isset($types[$type])) {
      return $types[$type];
    }

    throw new InvalidArgumentException(sprintf('Invalid type: %s', $type));
  }

  /**
   * Get content types.
   */
  public function getContentTypes() {
    $config = \Drupal::config('conference_api.settings');

    return array_filter($config->get('content_types') ?? []);
  }

  /**
   * Get file by uuid.
   */
  private function getFile(string $uuid): ?FileInterface {
    return \Drupal::service('entity.repository')->loadEntityByUuid('file', $uuid);
  }

}

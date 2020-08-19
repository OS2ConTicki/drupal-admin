<?php

namespace Drupal\os2conticki_api\JsonApi;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Helper.
 *
 * @package Drupal\os2conticki_api\JsonApi
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
      if (is_string($value)
        && filter_var($value, FILTER_VALIDATE_URL)
        && FALSE !== strpos($value, '/jsonapi/node/')) {
        $parts = UrlHelper::parse($value);
        $parts['path'] = preg_replace('@/jsonapi/node/@', '/api/', $parts['path']);
        if (isset($parts['query']) && is_array($parts['query'])) {
          $parts['query'] = $this->buildApiQuery($parts['query']);
        }
        $value = Url::fromUri($parts['path'], $parts)->toString();
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

      // Add related (via relationships) images.
      foreach ($item['relationships'] as $name => $relationship) {
        if ('file--file' === ($item['relationships'][$name]['data']['type'] ?? NULL)
          && isset($item['relationships'][$name]['data']['id'])) {
          $data = $item['relationships'][$name]['data'];
          $image = $this->getFile($data['id']);
          if ($image) {
            $attributes[$name] = [
              'url' => $image->createFileUrl(FALSE),
              'meta' => $data['meta'] ?? NULL,
            ];
          }
        }
      }

      if (isset($attributes['field_image'])) {
        $attributes['image'] = $attributes['field_image'];
        unset($attributes['field_image']);
      }

      foreach ($attributes as $name => $value) {
        if ('body' === $name && is_array($value)) {
          // Flatten rich text value.
          $attributes['summary'] = $value['summary'] ?: NULL;
          $attributes['description'] = $value['processed'] ?? NULL;
          unset($attributes[$name]);
        }

        // Flatten date ranges.
        if (in_array($name, ['field_dates', 'field_times'], TRUE) && isset($value['value'], $value['end_value'])) {
          $attributes['start_time'] = $value['value'];
          $attributes['end_time'] = $value['end_value'];
          unset($attributes[$name]);
        }

        // Handle ticket url.
        if (in_array($name, ['field_ticket'], TRUE) && isset($value['uri'])) {
          $attributes['ticket'] = [
            'url' => $value['uri'],
            'text' => $value['title'] ?: NULL,
          ];
          unset($attributes[$name]);
        }
      }

      switch ($item['type']) {
        case 'conference':
          // App metadata.
          $attributes['app'] = [
            'primary_color' => $attributes['field_app_primary_color']['color'] ?? '#ffffff',
            'logo' => $attributes['field_app_logo'] ?? NULL,
          ];
          // Add links to related resources.
          foreach (array_keys($this->getContentTypes()) as $type) {
            if ($item['type'] === $type) {
              continue;
            }
            $query = [
              'type' => $type,
              'filter' => [$item['type'] . '.id' => $item['id']],
            ];
            if ('event' === $type) {
              $query['sort'] = 'field_times.value';
            }
            $item['links'][$type]['href'] = $this->generateApiUrl($query);
          }
          $item['links']['all']['href'] = $this->generateApiUrl([
            'type' => 'event',
            'filter' => [$item['type'] . '.id' => $item['id']],
            'sort' => 'field_times.value',
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

      // Keep only the attributes we need.
      $allowedAttributes = [
        'app',
        'changed',
        'created',
        'description',
        'end_time',
        'image',
        'langcode',
        'promote',
        'start_time',
        'summary',
        'ticket',
        'title',
      ];
      $attributes = $this->includeKeys($allowedAttributes, $attributes);
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
    $url = Url::fromRoute('os2conticki_api.api_controller_index', $parameters, ['absolute' => TRUE]);

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
   * Build JSON:API request.
   */
  public function buildJsonApiRequest(Request $request, string $path): Request {
    $query = $this->buildJsonApiQuery($request->query->all());

    // Keep server info (specifically domain and port).
    // @TODO (How) Can we use Request::duplicate for this?
    return Request::create(
      $path,
      'GET',
      $query,
      $request->cookies->all(),
      $request->files->all(),
      $request->server->all(),
    );
  }

  /**
   * Build API query from a JSON:API query.
   *
   * Basically just removes leading `field_` from all field references in the
   * query, and is the reverse of `buildJsonApiQuery` (which see).
   *
   * @see Helper:buildJsonApiQuery()
   */
  private function buildApiQuery(array $jsonApiQuery) {
    $query = $jsonApiQuery;

    foreach ($jsonApiQuery as $name => $value) {
      switch ($name) {
        case 'filter':
          if (is_array($value)) {
            $filter = [];
            foreach ($value as $filterField => $filterValue) {
              $filterField = preg_replace('/^field_([^.,]+)/', '$1', $filterField);
              $filter[$filterField] = $filterValue;
            }
            $query[$name] = $filter;
          }
          break;

        case 'include':
          // @see https://jsonapi.org/format/#fetching-includes
          $query[$name] = preg_replace('/field_([^.,]+)/', '$1', $value);
          break;
      }
    }

    return $query;

  }

  /**
   * Build JSON:API query from a custom query.
   *
   * Basically just adds leading `field_` to all field references in the
   * query, and is the reverse of `buildApiQuery` (which see).
   *
   * @see Helper:buildApiQuery()
   */
  private function buildJsonApiQuery(array $query) {
    $jsonApiQuery = $query;

    foreach ($query as $name => $value) {
      switch ($name) {
        case 'filter':
          if (is_array($value)) {
            $filter = [];
            foreach ($value as $filterField => $filterValue) {
              $filterField = preg_replace('/^[^.,]+/', 'field_$0', $filterField);
              $filter[$filterField] = $filterValue;
            }
            $jsonApiQuery[$name] = $filter;
          }
          break;

        case 'include':
          // @see https://jsonapi.org/format/#fetching-includes
          $jsonApiQuery[$name] = preg_replace('/[^.,]+/', 'field_$0', $value);
          break;
      }
    }

    return $jsonApiQuery;
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
    $config = \Drupal::config('os2conticki_api.settings');

    return array_filter($config->get('content_types') ?? []);
  }

  /**
   * Get file by uuid.
   */
  private function getFile(string $uuid): ?FileInterface {
    return \Drupal::service('entity.repository')->loadEntityByUuid('file', $uuid);
  }

}

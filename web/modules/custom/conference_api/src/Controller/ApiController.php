<?php

namespace Drupal\conference_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Api controller.
 *
 * @see https://www.drupal.org/project/jsonapi/issues/3032259#comment-12967876
 * @see https://www.drupal.org/project/jsonapi_extras/issues/3036904
 */
class ApiController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * The http kernel.
   *
   * @var Symfony\Component\HttpKernel\HttpKernelInterface
   */
  private $httpKernel;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  private $urlGenerator;

  /**
   * Constructor.
   */
  public function __construct(HttpKernelInterface $httpKernel, UrlGeneratorInterface $urlGenerator) {
    $this->httpKernel = $httpKernel;
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * Kreator.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_kernel.basic'),
      $container->get('url_generator')
    );
  }

  /**
   * Index method.
   *
   * Proxies to the underlying JSON:API and returns the modified response.
   */
  public function index(Request $request, string $type = NULL, string $id = NULL): Response {
    try {
      if (NULL === $type) {
        return $this->generateIndex();
      }

      $requestPath = $this->getJsonApiPath($type, $id);

      if (NULL === $requestPath) {
        throw new BadRequestHttpException(sprintf('Invalid path: %s', $type));
      }

    }
    catch (Exception $exception) {
      throw new BadRequestHttpException($exception->getMessage());
    }

    $request = $this->buildJsonApiRequest($request, $requestPath);
    $response = $this->httpKernel->handle($request,
      HttpKernelInterface::SUB_REQUEST);

    if (Response::HTTP_OK === $response->getStatusCode()) {
      $response->setContent($this->convertContent($response->getContent()));
    }

    // @see https://medium.com/thefirstcode/cors-cross-origin-resource-sharing-in-drupal-8-19778cf2838a
    $response->headers->add([
      'access-control-allow-origin' => '*',
      'access-control-allow-methods' => 'GET',
    ]);

    return $response;
  }

  /**
   * Build JSON:API request.
   */
  private function buildJsonApiRequest(Request $request, string $path): Request {
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
   * Build JSON:API query.
   */
  private function buildJsonApiQuery(array $query) {
    $jsonApiQuery = $query;

    foreach ($query as $name => $value) {
      switch ($name) {
        case 'include':
          // @see https://jsonapi.org/format/#fetching-includes
          $jsonApiQuery[$name] = implode(
            ',',
            array_map(
              // @TODO Get the field names from config.
              static function ($field) {
                return 'field_' . $field;
              },
              array_filter(explode(',', $value)
              )
            )
          );
          break;
      }
    }

    return $jsonApiQuery;
  }

  /**
   * Generate API url.
   */
  private function generateApiUrl(array $parameters = []): string {
    $url = Url::fromRoute('conference_api.api_controller_index', $parameters, ['absolute' => TRUE]);

    // @see https://www.lullabot.com/articles/early-rendering-a-lesson-in-debugging-drupal-8
    return $url->toString(TRUE)->getGeneratedUrl();
  }

  /**
   * Generate API index.
   */
  private function generateIndex(): Response {
    $index = [];
    $types = $this->getContentTypes();
    foreach ($types as $type => $_) {
      $index[$type] = $this->generateApiUrl(
        [
          'type' => $type,
        ]
      );
    }

    return new JsonResponse($index);
  }

  /**
   * Converts JSON:API data to Conference API data.
   */
  private function convertContent(string $content): string {
    $document = json_decode($content, TRUE);

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

    return json_encode($document);
  }

  /**
   * Convert item.
   */
  private function convertItem(array $item) {
    $item['type'] = preg_replace('/^node--/', '', $item['type']);

    if (isset($item['attributes'])) {
      $attributes = &$item['attributes'];

      if (isset($item['relationships']['field_image']['data']['id'])) {
        $image = $this->getFile($item['relationships']['field_image']['data']['id']);
        if ($image) {
          $attributes['image'] = $image->createFileUrl(FALSE);
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
        if ('field_date' === $name) {
          if (is_array($value)) {
            $attributes['start_time'] = $value['value'];
            $attributes['end_time'] = $value['end_value'];
          }
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
          break;
      }

      $relationships = [];
      if (isset($item['relationships']['field_conference'])) {
        $relationships['conference'] = $this->convertRelationship($item['relationships']['field_conference']);
      }

      // Keep only the stuff we need.
      $allowedRelationships = ['field_conference'];
      if ($relationships) {
        $item['relationships'] = $relationships;
      }
      else {
        unset($item['relationships']);
      }
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
    if (isset($relationship['data']['type'])) {
      $relationship['data']['type'] = preg_replace('/^node--/', '', $relationship['data']['type']);
    }
    $links = [];
    if (isset($relationship['links']['related'])) {
      $links['related'] = [
        'href' => $this->generateApiUrl(['type' => $relationship['data']['type'], 'id' => $relationship['data']['id']]),
      ];
    }
    if ($links) {
      $relationship['links'] = $links;
    }
    else {
      unset($relationship['links']);
    }

    return $relationship;
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
   * Get JSON:API path from a Conference API path.
   */
  private function getJsonApiPath(string $type = NULL, string $id = NULL): ?string {
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
  private function getContentTypes() {
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

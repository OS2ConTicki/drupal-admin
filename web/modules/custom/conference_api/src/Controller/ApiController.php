<?php

namespace Drupal\conference_api\Controller;

use Drupal\conference_api\JsonApi\Helper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
   * The entity repository.
   *
   * @var \Drupal\conference_api\JsonApi\Helper
   */
  private $helper;

  /**
   * Constructor.
   */
  public function __construct(HttpKernelInterface $httpKernel, Helper $helper) {
    $this->httpKernel = $httpKernel;
    $this->helper = $helper;
  }

  /**
   * Kreator.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_kernel.basic'),
      $container->get('conference_api.json_api_helper')
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

      $requestPath = $this->helper->getJsonApiPath($type, $id);

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
      $response->setContent($this->helper->convertContent($response->getContent()));
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
   * Generate API index.
   */
  private function generateIndex(): Response {
    $index = [];
    $types = $this->helper->getContentTypes();
    foreach ($types as $type => $_) {
      $index[$type] = $this->helper->generateApiUrl(
        [
          'type' => $type,
        ]
      );
    }

    return new JsonResponse($index);
  }

}

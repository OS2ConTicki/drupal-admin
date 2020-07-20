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

    $request = $this->helper->buildJsonApiRequest($request, $requestPath);
    // @TODO Houston, we may have a caching problem here …
    $response = $this->httpKernel->handle($request,
      HttpKernelInterface::SUB_REQUEST);

    if (Response::HTTP_OK === $response->getStatusCode()) {
      $content = json_decode($response->getContent(), TRUE, 512, JSON_THROW_ON_ERROR);
      $content = $this->helper->convertContent($content);
      $response->setContent(json_encode($content));
    }

    // @see https://medium.com/thefirstcode/cors-cross-origin-resource-sharing-in-drupal-8-19778cf2838a
    $response->headers->add([
      'access-control-allow-origin' => '*',
      'access-control-allow-methods' => 'GET',
    ]);

    return $response;
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

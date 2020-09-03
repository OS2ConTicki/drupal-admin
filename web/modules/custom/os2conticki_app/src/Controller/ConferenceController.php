<?php

namespace Drupal\os2conticki_app\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Conference controller.
 */
class ConferenceController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * Constructor.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Render conference app.
   */
  public function app(NodeInterface $node, string $type = NULL, string $uuid = NULL) {
    // Redirect to conference path if node is not a conference.
    if (('conference' !== $node->bundle()) && $node->hasField('field_conference')) {
      $list = $node->get('field_conference')->referencedEntities();
      $conference = reset($list);
      return $this->redirect('os2conticki_app.conference_app', [
        'node' => $conference->id(),
        'type' => $node->bundle(),
        'entity' => $node->uuid(),
      ]);
    }

    if ('conference' !== $node->bundle()) {
      throw new NotFoundHttpException();
    }

    $basename = $this->getBasename($node);
    $apiUrl = $this->getApiUrl($node);
    $appData = $this->getAppData($apiUrl);
    $icons = $this->getIcons($appData);
    $applicationName = $node->getTitle();

    $config = $this->config('os2conticki_app.settings');
    $styleUrls = array_filter(array_map('trim', explode(PHP_EOL, $config->get('app_style_urls') ?? '')));
    $scriptUrls = array_filter(array_map('trim', explode(PHP_EOL, $config->get('app_script_urls') ?? '')));

    $manifestUrl = $this->getManifestUrl($node);
    $serviceWorkerUrl = $this->getServiceWorkerUrl($node);

    $renderable = [
      '#theme' => 'os2conticki_app_app',
      '#conference' => $node,
      '#basename' => $basename,
      '#manifest_url' => $manifestUrl,
      '#icons' => $icons,
      '#application_name' => $applicationName,
      '#app_data' => $appData,
      '#api_url' => $apiUrl,
      '#style_urls' => $styleUrls,
      '#script_urls' => $scriptUrls,
      '#service_worker_url' => $serviceWorkerUrl,
    ];

    $content = $this->renderer->renderPlain($renderable);

    return $this->cacheResponse(new CacheableResponse($content), $node);
  }

  /**
   * Render app manifest.
   */
  public function manifest(NodeInterface $node) {
    if ('conference' !== $node->bundle()) {
      throw new NotFoundHttpException();
    }

    $manifest = [];

    $apiUrl = $this->getApiUrl($node);
    $data = $this->getConferenceData($apiUrl);
    if ($data) {
      $manifest = [
        'short_name' => $data['title'],
        'name' => $data['title'],
        'start_url' => $this->generateUrl('os2conticki_app.conference_app', [
          'node' => $node->id(),
        ]),
        'theme_color' => $data['app']['primary_color'] ?? '#1E3284',
        'background_color' => '#1E3284',
        'display' => 'standalone',
      ];
      if (isset($data['app']['icons'])) {
        $icons = [];
        foreach ($data['app']['icons'] as $size => $url) {
          $parts = parse_url($url);
          $extension = pathinfo($parts['path'], PATHINFO_EXTENSION);
          $type = 'image/' . $extension;
          $icons[] = [
            'src' => $url,
            'sizes' => $size,
            'type' => $type,
          ];
        }
        $manifest['icons'] = $icons;
      }
    }

    return $this->cacheResponse(new CacheableJsonResponse($manifest), $node);
  }

  /**
   * Render app service worker.
   */
  public function serviceWorker(NodeInterface $node) {
    if ('conference' !== $node->bundle()) {
      throw new NotFoundHttpException();
    }

    $renderable = [
      '#theme' => 'os2conticki_app_service_worker',
      '#precache_urls' => [
        $this->getBasename($node),
      ],
    ];

    $content = $this->renderer->renderPlain($renderable);
    // Remove HTML comments (e.g. template suggestions).
    $content = preg_replace('/<!--(.|\s)*?-->/', '', $content);

    $response = new CacheableResponse($content);
    $response->headers->set('content-type', 'text/javascript');

    return $this->cacheResponse($response, $node);
  }

  /**
   * Cache a response.
   */
  private function cacheResponse(CacheableResponseInterface $response, NodeInterface $node) {
    $cacheMetadata = new CacheableMetadata();
    // Invalidate cache when the conference node changes.
    // @see https://www.drupal.org/docs/drupal-apis/cache-api/cache-tags
    $cacheMetadata->addCacheTags(['node:' . $node->id()]);
    $response->addCacheableDependency($cacheMetadata);

    return $response;
  }

  /**
   * Get app url.
   */
  private function getAppUrl(NodeInterface $node, string $path = NULL): string {
    $appUrl = $this->generateUrl('os2conticki_app.conference_app', [
      'node' => $node->id(),
    ], [
      'absolute' => TRUE,
    ]);

    if (isset($node->field_custom_app_url->uri)) {
      $appUrl = $node->field_custom_app_url->uri;
    }

    if (NULL !== $path) {
      $appUrl = rtrim($appUrl, '/') . '/' . $path;
    }

    return $appUrl;
  }

  /**
   * Get manifest url.
   */
  private function getManifestUrl(NodeInterface $node): string {
    return $this->getAppUrl($node, 'manifest');
  }

  /**
   * Get service worker url.
   */
  private function getServiceWorkerUrl(NodeInterface $node): string {
    return $this->getAppUrl($node, 'service-worker');
  }

  /**
   * Get app basename.
   */
  private function getBasename(NodeInterface $node): string {
    $appUrl = $this->getAppUrl($node);
    $parts = parse_url($appUrl);

    return $parts['path'] ?? '/';
  }

  /**
   * Get conference api url.
   */
  private function getApiUrl(NodeInterface $node): string {
    return $this->generateUrl('os2conticki_api.api_controller_index', [
      'type' => $node->bundle(),
      'id' => $node->uuid(),
      'include' => implode(',', ['organizers']),
    ], [
      'absolute' => TRUE,
    ]);
  }

  /**
   * Generate a url.
   */
  private function generateUrl(string $route, array $parameters = [], array $options = []): string {
    $options += [
      // We want to get content in the default language.
      'language' => \Drupal::service('language_manager')->getDefaultLanguage(),
    ];

    return Url::fromRoute($route, $parameters, $options)
      // @see https://www.lullabot.com/articles/early-rendering-a-lesson-in-debugging-drupal-8
      ->toString(TRUE)
      ->getGeneratedUrl();
  }

  /**
   * Get data from api.
   */
  private function getConferenceData(string $apiUrl) {
    try {
      $client = \Drupal::httpClient();
      $response = $client->get($apiUrl);

      $data = json_decode($response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);

      return $data['data']['attributes'] ?? NULL;
    }
    catch (Exception $exception) {
      return NULL;
    }
  }

  /**
   * Get app data from api.
   */
  private function getAppData(string $apiUrl) {
    $data = $this->getConferenceData($apiUrl);

    return $data['app'] ?? NULL;
  }

  /**
   * Get app icons.
   */
  private function getIcons(array $appData = NULL): array {
    $icons = [];
    $appIcons = $appData['icons'] ?? NULL;
    if ($appIcons) {
      $addIcons = function ($rel, $sizes, $tagName = 'link') use ($appIcons, &$icons) {
        foreach ($sizes as $size) {
          if (isset($appIcons[$size])) {
            $icons[] = [
              'tagName' => $tagName,
              'attributes' => [
                'rel' => $rel,
                'sizes' => $size,
                'href' => $appIcons[$size],
              ],
            ];
          }
        }
      };

      // iOS icons.
      $rel = 'apple-touch-icon-precomposed';
      $sizes = [
        "152x152",
        "144x144",
        "120x120",
        "114x114",
        "76x76",
        "72x72",
        "60x60",
        "57x57",
      ];
      $addIcons($rel, $sizes);

      // iOS launch screens.
      $rel = 'apple-touch-startup-image';
      $sizes = [
        '2048x2732',
        '1668x2224',
        '1536x2048',
        '1125x2436',
        '1242x2208',
        '750x1334',
        '640x1136',
      ];
      $addIcons($rel, $sizes);

      // Android icons - for legacy devices, newer versions gets the icons
      // from the webmanifest file.
      $rel = 'icon';
      $sizes = [
        '196x196',
        '96x96',
        '32x32',
        '16x16',
        '128x128',
      ];
      $addIcons($rel, $sizes);

      // MS icons.
      $metas = [
        'msapplication-square150x150logo' => '150x150',
        'msapplication-wide310x150logo' => '310x150',
        'msapplication-square310x310logo' => '310x310',
        'msapplication-square70x70logo' => '70x70',
      ];
      foreach ($metas as $name => $size) {
        if (isset($appIcons[$size])) {
          $icons[] = [
            'tagName' => 'meta',
            'attributes' => [
              'name' => $name,
              'content' => $appIcons[$size],
            ],
          ];
        }
      }
    }

    return $icons;
  }

}

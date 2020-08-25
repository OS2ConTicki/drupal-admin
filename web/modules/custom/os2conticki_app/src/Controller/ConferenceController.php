<?php

namespace Drupal\os2conticki_app\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Exception;
use Laminas\Diactoros\Response\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Conference controller.
 */
class ConferenceController extends ControllerBase {

  /**
   * Render conference app.
   */
  public function app(NodeInterface $node, string $type = NULL, string $uuid = NULL) {
    $conference = NULL;

    if ('conference' !== $node->bundle()) {
      if ($node->hasField('field_conference')) {
        $list = $node->get('field_conference')->referencedEntities();
        $conference = reset($list);
        return $this->redirect('os2conticki_app.conference_app', [
          'node' => $conference->id(),
          'type' => $node->bundle(),
          'entity' => $node->uuid(),
        ]);
      }
    }
    else {
      $conference = $node;
    }

    if (NULL === $conference) {
      throw new NotFoundHttpException();
    }

    $basename = Url::fromRoute('os2conticki_app.conference_app', [
      'node' => $conference->id(),
    ], [
      'language' => \Drupal::service('language_manager')->getDefaultLanguage(),
    ])->toString();

    // Build conference api url.
    $apiUrl = Url::fromRoute('os2conticki_api.api_controller_index', [
      'type' => $conference->bundle(),
      'id' => $conference->uuid(),
      'include' => implode(',', ['organizers']),
    ], [
      'absolute' => TRUE,
      // We want to get content in the default language.
      'language' => \Drupal::service('language_manager')->getDefaultLanguage(),
    ])->toString();

    $appData = $this->getAppData($apiUrl);
    $manifestUrl = Url::fromRoute('os2conticki_app.conference_app_manifest', [
      'node' => $conference->id(),
    ], [
      'language' => \Drupal::service('language_manager')->getDefaultLanguage(),
    ])->toString();

    $icons = [];
    $appIcons = $appData['icons'] ?? NULL;
    if ($appIcons) {
      if ($appIcons['152x152']) {
        $icons[] = [
          'tagName' => 'link',
          'attributes' => [
            'rel' => 'apple-touch-icon-precomposed',
            'sizes' => '152x152',
            'href' => $appIcons['152x152'],
          ],
        ];
      }
    }

    $applicationName = $conference->getTitle();

    $config = $this->config('os2conticki_app.settings');
    $styleUrls = array_filter(array_map('trim', explode(PHP_EOL, $config->get('app_style_urls') ?? '')));
    $scriptUrls = array_filter(array_map('trim', explode(PHP_EOL, $config->get('app_script_urls') ?? '')));

    $renderable = [
      '#theme' => 'os2conticki_app_app',
      '#conference' => $conference,
      '#basename' => $basename,
      '#manifest_url' => $manifestUrl,
      '#icons' => $icons,
      '#application_name' => $applicationName,
      '#app_data' => $appData,
      '#api_url' => $apiUrl,
      '#style_urls' => $styleUrls,
      '#script_urls' => $scriptUrls,
    ];

    $content = \Drupal::service('renderer')->renderPlain($renderable);

    return new Response($content);
  }

  /**
   * Render app manifest.
   */
  public function manifest() {
    $manifest = [];

    return new JsonResponse($manifest);
  }

  /**
   * Get app data from api.
   */
  private function getAppData(string $apiUrl) {
    try {
      $client = \Drupal::httpClient();
      $response = $client->get($apiUrl);

      $data = json_decode($response->getBody(), TRUE);

      return $data['data']['attributes']['app'] ?? NULL;
    }
    catch (Exception $exception) {
      return NULL;
    }
  }

}

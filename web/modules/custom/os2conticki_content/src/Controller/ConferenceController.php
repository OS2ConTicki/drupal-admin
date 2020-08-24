<?php

namespace Drupal\os2conticki_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
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
        return $this->redirect('os2conticki_content.conference_app', [
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

    $basename = Url::fromRoute('os2conticki_content.conference_app', [
      'node' => $conference->id(),
    ])->toString();

    // Build conference api url.
    $apiUrl = Url::fromRoute('os2conticki_api.api_controller_index', [
      'type' => $conference->bundle(),
      'id' => $conference->uuid(),
      'include' => implode(',', ['organizers']),
    ], [
    // 'absolute' => TRUE,
      // We want to get content in the default language.
      'language' => \Drupal::service('language_manager')->getDefaultLanguage(),
    ])->toString();

    $renderable = [
      '#theme' => 'os2conticki_content_app',
      '#conference' => $conference,
      '#basename' => $basename,
      '#api_url' => $apiUrl,
      '#style_urls' => ['/modules/custom/os2conticki_content/app/dist/entry.css'],
      '#script_urls' => ['/modules/custom/os2conticki_content/app/dist/entry.js'],
    ];

    $content = \Drupal::service('renderer')->renderPlain($renderable);

    return new Response($content);
  }

}

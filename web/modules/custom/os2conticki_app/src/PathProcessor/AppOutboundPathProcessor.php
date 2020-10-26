<?php

namespace Drupal\os2conticki_app\PathProcessor;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * App path processor.
 */
class AppOutboundPathProcessor implements OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processOutbound(
    $path,
    &$options = [],
    Request $request = NULL,
    BubbleableMetadata $bubbleable_metadata = NULL
  ) {
    // We need a trailing slash on the main app url to get the correct scope
    // for the service worker.
    $addTrailingSlash = 1 === preg_match('@^/app/[^/]+$@', $path);
    if ($addTrailingSlash) {
      $path = preg_replace('/((?:^|\\/)[^\\/\\.]+?)$/isD', '$1/', $path);
    }

    return $path;
  }

}

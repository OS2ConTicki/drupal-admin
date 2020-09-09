<?php

namespace Drupal\os2conticki_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'os2conticki_conference_app_data_formatter'
 * formatter.
 *
 * @FieldFormatter(
 *   id = "os2conticki_conference_app_data_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "os2conticki_conference_app_data"
 *   }
 * )
 */
class ConferenceAppDataDefaultFormatter extends FormatterBase {

  /**
   *
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\os2conticki_content\Plugin\Field\FieldType\ConferenceAppDataItem $item */
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'fieldset',

        'custom_app_url' => [
          '#title' => 'Custom app url',
          '#markup' => $item->custom_app_url ?? NULL,
        ],
        'icons' => [
          '#title' => 'icons',
          '#markup' => 'icons',
        ],
      ];
    }

    return $elements;
  }

}

<?php

namespace Drupal\conference_content\Element;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an entity autocomplete form element.
 *
 * The #default_value accepted by this element is either an entity object or an
 * array of entity objects.
 *
 * @FormElement("conference_entity_autocomplete")
 */
class ConferenceEntityAutocomplete extends EntityAutocomplete {

  /**
   * {@inheritdoc}
   */
  public static function processEntityAutocomplete(
    array &$element,
    FormStateInterface $form_state,
    array &$complete_form
  ) {
    $element = parent::processEntityAutocomplete($element, $form_state,
      $complete_form);

    $conference = $form_state->get(['conference_content', 'conference']);

    if (NULL !== $conference) {
      $element['#autocomplete_route_name'] = 'conference_content.conference_entity_autocomplete';
      $element['#autocomplete_route_parameters'] = [
        'target_type' => $element['#target_type'],
        'bundles' => array_keys($element['#selection_settings']['target_bundles']),
        'conference' => $conference->id(),
      ];
    }

    return $element;
  }

}

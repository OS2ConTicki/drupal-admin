<?php

namespace Drupal\os2conticki_content\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implementation of the 'conference_entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "conference_entity_reference_autocomplete",
 *   label = @Translation("Autocomplete within conference"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ConferenceEntityReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['target_id']['#type'] = 'conference_entity_autocomplete';

    return $element;
  }

}

<?php

namespace Drupal\conference_content\Form;

use Drupal\conference_content\Helper\ConferenceHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\NodeInterface;

/**
 * Form helper.
 */
class Helper {
  use StringTranslationTrait;

  /**
   * The conference helper.
   *
   * @var \Drupal\conference_content\Helper\ConferenceHelper
   */
  private $conferenceHelper;

  /**
   * Constructor.
   */
  public function __construct(ConferenceHelper $conferenceHelper, TranslationInterface $translation) {
    $this->conferenceHelper = $conferenceHelper;
    $this->setStringTranslation($translation);
  }

  /**
   * Implements hook_form_alter().
   */
  public function alter(array &$form, FormStateInterface $formState, string $formId) {
    switch ($formId) {
      case 'node_conference_form':
      case 'node_conference_edit_form':
        $this->alterConferenceForm($form, $formState, $formId);
        break;
    }

    switch ($formId) {
      case 'node_conference_edit_form':
      case 'node_conference_form':
      case 'node_event_edit_form':
      case 'node_event_form':
      case 'node_location_edit_form':
      case 'node_location_form':
      case 'node_organizer_edit_form':
      case 'node_organizer_form':
      case 'node_speaker_edit_form':
      case 'node_speaker_form':
      case 'node_sponsor_edit_form':
      case 'node_sponsor_form':
      case 'node_tag_edit_form':
      case 'node_tag_form':
      case 'node_theme_edit_form':
      case 'node_theme_form':
        $this->setConference($form, $formState, $formId);
        break;
    }
  }

  /**
   * Alter conference form.
   *
   * * Add link to API data.
   * * Adds lists of sub entities.
   */
  private function alterConferenceForm(
    array &$form,
    FormStateInterface $formState,
    string $formId
  ) {
    /** @var \Drupal\node\NodeInterface $conference */
    $conference = $formState->getFormObject()->getEntity();

    if (NULL === $conference->id()) {
      return;

    }
    $form['conference_content'] = [
      '#theme' => 'conference_content_conference_info',
      '#conference' => $conference,
      '#weight' => -1000,
    ];

    $weight = 10000;
    foreach ([
      'event' => [
        'title' => $this->t('Events'),
        'add' => $this->t('Add event'),
      ],
      'speaker' => [
        'title' => $this->t('Speakers'),
        'add' => $this->t('Add speaker'),
      ],
      'tag' => [
        'title' => $this->t('Tags'),
        'add' => $this->t('Add tag'),
      ],
      'location' => [
        'title' => $this->t('Locations'),
        'add' => $this->t('Add location'),
      ],
      'theme' => [
        'title' => $this->t('Themes'),
        'add' => $this->t('Add theme'),
      ],
      'sponsor' => [
        'title' => $this->t('Sponsors'),
        'add' => $this->t('Add sponsor'),
      ],
      'organizer' => [
        'title' => $this->t('Organizers'),
        'add' => $this->t('Add organizer'),
      ],
    ] as $type => $info) {
      $entities = $this->conferenceHelper->getEntitites($conference, $type);

      $form['conference_' . $type] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $info['title'] ?? $type,
        '#weight' => $weight++,
        'list' => [
          '#theme' => 'conference_content_conference_entity_list',
          '#conference' => $conference,
          '#type' => $type,
          '#entities' => $entities,
        ],
      ];
    }

    $form['#attached']['library'][] = 'conference_content/form-conference';
  }

  /**
   * Set conference on a new entity.
   */
  private function setConference(
    array &$form,
    FormStateInterface $formState,
    string $formId
    ) {
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $formState->getFormObject()->getEntity();
    $conference = NULL;

    if (isset($form['field_conference']['widget'][0])) {
      // Add conference on new entities.
      if (NULL === $entity->id()) {
        $conference = $this->getConference();
        $form['field_conference']['widget'][0]['target_id']['#default_value'] = $conference;
      }
      else {
        $conference = $entity->field_conference->entity;
      }
      if (NULL !== $conference) {
        // Make field readonly.
        $form['field_conference']['widget'][0]['target_id']['#attributes']['readonly'] = TRUE;
      }
    }

    // @TODO Set up custom autocomplete.
    // @see https://antistatique.net/en/we/blog/2019/07/10/how-to-create-a-custom-autocomplete-using-the-drupal-8-form-api
    foreach ([
               // 'field_events' => 'event',
               // 'field_locations' => 'location',
               // 'field_organizers' => 'organizer',
               // 'field_speakers' => 'speaker',
               // 'field_sponsors' => 'sponsor',
               // 'field_tags' => 'tag',
               // 'field_themes' => 'theme',
      ] as $field => $type) {
      if (isset($form[$field]['widget'])) {
        foreach (Element::children($form[$field]['widget']) as $child) {
          if ('entity_autocomplete' === ($form[$field]['widget'][$child]['target_id']['#type'] ?? NULL)) {
            $element = &$form[$field]['widget'][$child]['target_id'];
            $element['#type'] = 'textfield';
            $element['#autocomplete_route_name'] = 'conference_content.entity_autocomplete';
            $element['#autocomplete_route_parameters'] = [
              'conference' => $conference->id(),
              'type' => $type,
            ];
          }
        }
      }
    }
  }

  /**
   * Get conference by uuid or from request.
   */
  private function getConference(string $uuid = NULL): ?NodeInterface {
    if (NULL === $uuid) {
      $uuid = \Drupal::request()->get('conference');
    }

    return $this->conferenceHelper->loadByUuid($uuid);
  }

}

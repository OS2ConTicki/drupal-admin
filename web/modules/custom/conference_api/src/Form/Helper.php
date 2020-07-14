<?php

namespace Drupal\conference_api\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Form helper.
 */
class Helper {
  use StringTranslationTrait;

  /**
   * Constructor.
   */
  public function __construct(TranslationInterface $stringTranslation) {
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * Implements hook_form_alter().
   */
  public function alter(
    array &$form,
    FormStateInterface $formState,
    string $formId
  ) {
    switch ($formId) {
      case 'node_conference_form':
      case 'node_conference_edit_form':
        $this->alterConferenceForm($form, $formState, $formId);
        break;
    }

    switch ($formId) {
      case 'node_conference_form':
      case 'node_event_form':
      case 'node_location_form':
      case 'node_organizer_form':
      case 'node_speaker_form':
      case 'node_sponsor_form':
      case 'node_tag_form':
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
    $apiUrl = Url::fromRoute(
      'conference_api.api_controller_index',
      [
        'type' => 'conference',
        'id' => $conference->uuid(),
      ],
      [
        'absolute' => TRUE,
      ]
    );
    $form['conference_api'] = [
      '#markup' => Link::fromTextAndUrl($apiUrl->toString(), $apiUrl)
        ->toString(),
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
      $form['conference_' . $type] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $info['title'] ?? $type,
        '#weight' => $weight++,
        'add' => [
          '#markup' => Link::fromTextAndUrl(
            $info['add'] ?? $this->t('Add @type', ['@type' => $type]),
            Url::fromRoute('node.add',
              ['node_type' => $type, 'conference' => $conference->uuid()]),
          )->toString(),
        ],
        'list' => [
          '#markup' => 'list',
        ],
      ];
    }
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

    if (isset($form['field_conference']['widget'][0]) && NULL === $entity->id()) {
      $conference = $this->getConference();
      $form['field_conference']['widget'][0]['target_id']['#default_value'] = $conference;
    }
  }

  /**
   * Get conference by uuid or from request.
   */
  private function getConference(string $uuid = NULL): ?NodeInterface {
    if (NULL === $uuid) {
      $uuid = \Drupal::request()->get('conference');
    }

    $conference = \Drupal::service('entity.repository')->loadEntityByUuid('node', $uuid);

    if (NULL === $conference || 'conference' !== $conference->bundle()) {
      throw new BadRequestHttpException('Missing conference');
    }

    return $conference;
  }

}

<?php

namespace Drupal\os2conticki_content\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\NodeInterface;
use Drupal\os2conticki_content\Helper\ConferenceHelper;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Form helper.
 */
class Helper {
  use StringTranslationTrait;

  /**
   * The conference helper.
   *
   * @var \Drupal\os2conticki_content\Helper\ConferenceHelper
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

    $form['os2conticki_content'] = [
      '#theme' => 'os2conticki_content_conference_info',
      '#conference' => $conference,
      '#weight' => -1000,
    ];

    // Store conference to be used by conference autocomplete.
    $formState->set(['os2conticki_content', 'conference'], $conference);
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

    if (NULL === $conference) {
      $message = $this->t('Content of type @bundle must be created inside a conference.', ['@bundle' => $entity->bundle()]);
      \Drupal::messenger()->addError($message);
      throw new BadRequestHttpException($message);
    }

    // Store conference to be used by conference autocomplete.
    $formState->set(['os2conticki_content', 'conference'], $conference);
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

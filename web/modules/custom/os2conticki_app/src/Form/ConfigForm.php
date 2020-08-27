<?php

namespace Drupal\os2conticki_app\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'os2conticki_app.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2conticki_app_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('os2conticki_app.settings');

    $form['app_style_urls'] = [
      '#title' => $this->t('App style urls'),
      '#type' => 'textarea',
      '#default_value' => $config->get('app_style_urls') ?? implode(PHP_EOL, [
        'https://cdn.jsdelivr.net/npm/@os2conticki/display-react@latest/public/dist/entry.css',
      ]),
      '#description' => $this->t(
        'App style urls (one per line). Default: @default', [
          '@default' => 'https://cdn.jsdelivr.net/npm/@os2conticki/display-react@latest/public/dist/entry.css',
        ]
      ),
    ];

    $form['app_script_urls'] = [
      '#title' => $this->t('App script urls'),
      '#type' => 'textarea',
      '#default_value' => $config->get('app_script_urls') ?? implode(PHP_EOL, [
        'https://cdn.jsdelivr.net/npm/@os2conticki/display-react@latest/public/dist/entry.js',
      ]),
      '#description' => $this->t(
        'App script urls (one per line). Default: @default',
        [
          '@default' => 'https://cdn.jsdelivr.net/npm/@os2conticki/display-react@latest/public/dist/entry.js',
        ]
      ),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('os2conticki_app.settings')
      ->set('app_style_urls', $form_state->getValue('app_style_urls'))
      ->set('app_script_urls', $form_state->getValue('app_script_urls'))
      ->save();
  }

}

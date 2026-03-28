<?php

declare(strict_types=1);

namespace Drupal\wink\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Administration form for Wink Travel settings.
 *
 * Route: admin/config/wink
 * Permission: administer wink
 */
final class WinkSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wink_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['wink.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('wink.settings');

    $form['client_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Wink Client ID'),
      '#description'   => $this->t(
        'The Client ID provided by <a href="https://wink.travel" target="_blank">Wink Travel</a>. '
        . 'This value is embedded in the <code>&lt;wink-app-loader&gt;</code> element on every page.'
      ),
      '#default_value' => $config->get('client_id') ?? '',
      '#required'      => FALSE,
      '#maxlength'     => 255,
    ];

    $form['environment'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Environment'),
      '#description'   => $this->t(
        'Select which Wink environment to load web components from. '
        . 'Use <em>Production</em> for live sites.'
      ),
      '#options'       => [
        'production'  => $this->t('Production (elements.wink.travel)'),
        'staging'     => $this->t('Staging (staging-elements.wink.travel)'),
        'development' => $this->t('Development (dev.traveliko.com:8011)'),
      ],
      '#default_value' => $config->get('environment') ?? 'production',
      '#required'      => TRUE,
    ];

    $form['oauth2'] = [
      '#type'        => 'details',
      '#title'       => $this->t('OAuth2 API Credentials (server-side)'),
      '#description' => $this->t(
        'These credentials are used server-side to fetch available layouts for the '
        . '<em>Wink Content</em> block configuration form. They are <strong>never</strong> '
        . 'sent to the browser.'
      ),
      '#open'        => FALSE,
    ];

    $form['oauth2']['oauth2_client_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('OAuth2 Client ID'),
      '#default_value' => $config->get('oauth2_client_id') ?? '',
      '#maxlength'     => 255,
    ];

    $form['oauth2']['oauth2_client_secret'] = [
      '#type'          => 'password',
      '#title'         => $this->t('OAuth2 Client Secret'),
      '#description'   => $this->t('Leave blank to keep the existing secret.'),
      '#default_value' => '',
      '#maxlength'     => 512,
      '#attributes'    => ['autocomplete' => 'new-password'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $client_id = trim((string) $form_state->getValue('client_id'));

    if ($client_id !== '' && strlen($client_id) > 255) {
      $form_state->setErrorByName(
        'client_id',
        $this->t('Client ID must not exceed 255 characters.')
      );
    }

    $env = $form_state->getValue('environment');
    $allowed_envs = ['production', 'staging', 'development'];
    if (!in_array($env, $allowed_envs, TRUE)) {
      $form_state->setErrorByName(
        'environment',
        $this->t('Invalid environment selected.')
      );
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('wink.settings');

    $config->set('client_id', trim((string) $form_state->getValue('client_id')));
    $config->set('environment', $form_state->getValue('environment'));
    $config->set('oauth2_client_id', trim((string) $form_state->getValue('oauth2_client_id')));

    // Only overwrite the stored secret when the user actually typed a new one.
    $new_secret = trim((string) $form_state->getValue('oauth2_client_secret'));
    if ($new_secret !== '') {
      $config->set('oauth2_client_secret', $new_secret);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}

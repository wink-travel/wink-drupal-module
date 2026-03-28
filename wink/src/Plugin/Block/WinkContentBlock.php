<?php

declare(strict_types=1);

namespace Drupal\wink\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\wink\Service\WinkApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the "Wink Content" block.
 *
 * Renders <wink-content-loader layout="..." id="..."> which displays a
 * curated travel content module (hotel card, search grid, etc.) configured
 * via the Wink back-office.
 *
 * @Block(
 *   id = "wink_content_block",
 *   admin_label = @Translation("Wink Content"),
 *   category = @Translation("Wink Travel"),
 * )
 */
final class WinkContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    private readonly WinkApiService $winkApi,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('wink.api_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'layout' => '',
      'id'     => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(array $form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $layouts = $this->winkApi->getLayouts();

    if (!empty($layouts)) {
      $form['layout'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Layout'),
        '#description'   => $this->t('Select the Wink layout to display.'),
        '#options'       => ['' => $this->t('— Select a layout —')] + $layouts,
        '#default_value' => $config['layout'] ?? '',
        '#required'      => TRUE,
      ];
    }
    else {
      $form['layout'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Layout ID'),
        '#description'   => $this->t(
          'Enter the Wink layout ID manually. To populate this as a dropdown, '
          . 'configure OAuth2 credentials at <a href="/admin/config/wink">Wink settings</a>.'
        ),
        '#default_value' => $config['layout'] ?? '',
        '#required'      => TRUE,
        '#maxlength'     => 255,
      ];
    }

    $form['id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Content ID'),
      '#description'   => $this->t(
        'The Wink content identifier passed as the <code>id</code> attribute on '
        . '<code>&lt;wink-content-loader&gt;</code>.'
      ),
      '#default_value' => $config['id'] ?? '',
      '#required'      => TRUE,
      '#maxlength'     => 255,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate(array $form, FormStateInterface $form_state): void {
    $layout = trim((string) $form_state->getValue('layout'));
    $id     = trim((string) $form_state->getValue('id'));

    if ($layout === '') {
      $form_state->setErrorByName('layout', $this->t('Layout is required.'));
    }
    if ($id === '') {
      $form_state->setErrorByName('id', $this->t('Content ID is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(array $form, FormStateInterface $form_state): void {
    $this->setConfigurationValue('layout', trim((string) $form_state->getValue('layout')));
    $this->setConfigurationValue('id', trim((string) $form_state->getValue('id')));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();
    $layout = $config['layout'] ?? '';
    $id     = $config['id'] ?? '';

    if ($layout === '' || $id === '') {
      return [];
    }

    return [
      '#theme'  => 'wink_content_block',
      '#layout' => $layout,
      '#id'     => $id,
      '#cache'  => [
        'contexts' => ['user.roles'],
        'tags'     => ['config:wink.settings'],
      ],
    ];
  }

}

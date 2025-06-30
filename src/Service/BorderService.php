<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;
use Drupal\kingly_layouts\KinglyLayoutsUtilityTrait;

/**
 * Service to manage border options for Kingly Layouts.
 */
class BorderService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;
  use KinglyLayoutsUtilityTrait;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * The options service.
   */
  protected OptionsService $optionsService;

  /**
   * The color service.
   */
  protected ColorService $colorService;

  /**
   * Constructs a new BorderService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\kingly_layouts\Service\OptionsService $options_service
   *   The options service.
   * @param \Drupal\kingly_layouts\Service\ColorService $color_service
   *   The color service.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation, OptionsService $options_service, ColorService $color_service) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
    $this->optionsService = $options_service;
    $this->colorService = $color_service;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $color_options = $this->optionsService->getColorOptions();

    $form['border'] = [
      '#type' => 'details',
      '#title' => $this->t('Border'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts border'),
    ];

    if (count($color_options) > 1) {
      $form['border']['border_color'] = [
        '#type' => 'select',
        '#title' => $this->t('Border Color'),
        '#options' => $color_options,
        '#default_value' => $configuration['border_color'],
        '#description' => $this->t('Selecting a color will enable the border options below.'),
      ];
    }
    $form['border']['border_width_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Width'),
      '#options' => $this->optionsService->getOptions('border_width'),
      '#default_value' => $configuration['border_width_option'],
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[border][border_color]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];
    $form['border']['border_style_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Style'),
      '#options' => $this->optionsService->getOptions('border_style'),
      '#default_value' => $configuration['border_style_option'],
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[border][border_color]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];
    $form['border']['border_radius_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Radius'),
      '#options' => $this->optionsService->getOptions('border_radius'),
      '#default_value' => $configuration['border_radius_option'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $values = $form_state->getValue('border', []);
    foreach ([
      'border_color',
      'border_width_option',
      'border_style_option',
      'border_radius_option',
    ] as $key) {
      $configuration[$key] = $values[$key] ?? self::NONE_OPTION_KEY;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    $this->applyClassFromConfig($build, 'kingly-layout-border-radius-', 'border_radius_option', $configuration);

    if ($border_color_hex = $this->colorService->getTermColorHex($configuration['border_color'])) {
      $build['#attributes']['style'][] = 'border-color: ' . $border_color_hex . ';';
      $border_width = $configuration['border_width_option'] !== self::NONE_OPTION_KEY ? $configuration['border_width_option'] : 'sm';
      $border_style = $configuration['border_style_option'] !== self::NONE_OPTION_KEY ? $configuration['border_style_option'] : 'solid';
      $this->applyClassFromConfig($build, 'kingly-layout-border-width-', $border_width, $configuration);
      $this->applyClassFromConfig($build, 'kingly-layout-border-style-', $border_style, $configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'border_radius_option' => self::NONE_OPTION_KEY,
      'border_color' => self::NONE_OPTION_KEY,
      'border_width_option' => self::NONE_OPTION_KEY,
      'border_style_option' => self::NONE_OPTION_KEY,
    ];
  }

}

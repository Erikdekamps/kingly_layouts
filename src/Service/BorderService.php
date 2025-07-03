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
   * @param \Drupal\kingly_layouts\Service\ColorService $color_service
   *   The color service.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation, ColorService $color_service) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
    $this->colorService = $color_service;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $color_options = $this->colorService->getColorOptions();

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
      '#options' => $this->getBorderOptions('width'),
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
      '#options' => $this->getBorderOptions('style'),
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
      '#options' => $this->getBorderOptions('radius'),
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
    $has_border = FALSE;

    // Apply border radius classes.
    $has_border = $this->applyBorderRadius($build, $configuration) || $has_border;

    // Apply border color, width, and style.
    $has_border = $this->applyBorderProperties($build, $configuration) || $has_border;

    // Attach the library if any border options are active.
    if ($has_border) {
      $build['#attached']['library'][] = 'kingly_layouts/borders';
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

  /**
   * Returns border-related options.
   *
   * @param string $type
   *   The type of border options to return (width, style, or radius).
   *
   * @return array
   *   An array of border options.
   */
  private function getBorderOptions(string $type): array {
    $none = [self::NONE_OPTION_KEY => $this->t('None')];
    $options = [
      'width' => $none + [
        'sm' => $this->t('Small (1px)'),
        'md' => $this->t('Medium (2px)'),
        'lg' => $this->t('Large (4px)'),
      ],
      'style' => $none + [
        'solid' => $this->t('Solid'),
        'dashed' => $this->t('Dashed'),
        'dotted' => $this->t('Dotted'),
      ],
      'radius' => $none + [
        'xs' => $this->t('Extra Small (0.25rem)'),
        'sm' => $this->t('Small (0.5rem)'),
        'md' => $this->t('Medium (1rem)'),
        'lg' => $this->t('Large (2rem)'),
        'xl' => $this->t('Extra Large (4rem)'),
        'full' => $this->t('Full (Pill/Circle)'),
      ],
    ];

    return $options[$type] ?? [];
  }

  /**
   * Applies border-radius CSS class to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return bool
   *   TRUE if a border-radius class was applied, FALSE otherwise.
   */
  private function applyBorderRadius(array &$build, array $configuration): bool {
    if ($configuration['border_radius_option'] !== self::NONE_OPTION_KEY) {
      $this->applyClassFromConfig($build, 'kl-border-radius-', 'border_radius_option', $configuration);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Applies border color, width, and style to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return bool
   *   TRUE if border properties were applied, FALSE otherwise.
   */
  private function applyBorderProperties(array &$build, array $configuration): bool {
    if ($border_color_hex = $this->colorService->getTermColorHex($configuration['border_color'])) {
      $build['#attributes']['style'][] = 'border-color: ' . $border_color_hex . ';';

      // Set default width and style if 'None' is selected for them, but color
      // is.
      $border_width = $configuration['border_width_option'] !== self::NONE_OPTION_KEY ? $configuration['border_width_option'] : 'sm';
      $border_style = $configuration['border_style_option'] !== self::NONE_OPTION_KEY ? $configuration['border_style_option'] : 'solid';

      $this->applyClassFromConfig($build, 'kl-border-width-', $border_width, $configuration);
      $this->applyClassFromConfig($build, 'kl-border-style-', $border_style, $configuration);
      return TRUE;
    }
    return FALSE;
  }

}

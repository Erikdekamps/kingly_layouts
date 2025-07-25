<?php

namespace Drupal\kingly_layouts\Service\DisplayOption;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Service to manage border options for Kingly Layouts.
 *
 * This service now uses a direct color input field instead of a taxonomy term
 * for border color.
 */
class BorderService extends DisplayOptionBase {

  /**
   * The color service.
   *
   * @var \Drupal\kingly_layouts\Service\DisplayOption\ColorService
   */
  protected ColorService $colorService;

  /**
   * Constructs a new BorderService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\kingly_layouts\Service\DisplayOption\ColorService $color_service
   *   The color service.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation, ColorService $color_service) {
    // Call the parent constructor to initialize currentUser and translator.
    parent::__construct($current_user, $string_translation);
    $this->colorService = $color_service;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey(): string {
    return 'border';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form_key = $this->getFormKey();
    $form[$form_key] = [
      '#type' => 'details',
      '#title' => $this->t('Border'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts border'),
    ];

    $form[$form_key]['border_color_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set a border color'),
      '#description' => $this->t('Enable this to set a border. A border requires a color to be visible.'),
      '#default_value' => !empty($configuration['border_color']),
    ];

    $form[$form_key]['border_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Border Color'),
      '#default_value' => $configuration['border_color'] ?: '#000000',
      '#element_validate' => [[$this, 'validateColorHex']],
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[' . $form_key . '][border_color_enable]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form[$form_key]['border_width_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Width'),
      '#options' => $this->getBorderOptions('width'),
      '#default_value' => $configuration['border_width_option'],
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[' . $form_key . '][border_color_enable]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form[$form_key]['border_style_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Style'),
      '#options' => $this->getBorderOptions('style'),
      '#default_value' => $configuration['border_style_option'],
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[' . $form_key . '][border_color_enable]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form[$form_key]['border_radius_option'] = [
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
    $form_key = $this->getFormKey();
    $values = $form_state->getValue($form_key, []);

    if (!empty($values['border_color_enable'])) {
      // Store the color as a string.
      $configuration['border_color'] = $values['border_color'] ?? '';
      $configuration['border_width_option'] = $values['border_width_option'] ?? self::NONE_OPTION_KEY;
      $configuration['border_style_option'] = $values['border_style_option'] ?? self::NONE_OPTION_KEY;
    }
    else {
      // If border is not enabled, clear the color and related properties.
      $configuration['border_color'] = '';
      $configuration['border_width_option'] = self::NONE_OPTION_KEY;
      $configuration['border_style_option'] = self::NONE_OPTION_KEY;
    }

    // Border radius can be set independently.
    $configuration['border_radius_option'] = $values['border_radius_option'] ?? self::NONE_OPTION_KEY;
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
      'border_color' => '',
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
    $border_color_hex = $configuration['border_color'];
    // Validate that a color value is explicitly set.
    if (!empty($border_color_hex) && preg_match('/^#([a-fA-F0-9]{6})$/', $border_color_hex)) {
      $build['#attributes']['style'][] = 'border-color: ' . $border_color_hex . ';';

      // Set default width and style if 'None' is selected, but color is set.
      $border_width = $configuration['border_width_option'] !== self::NONE_OPTION_KEY ? $configuration['border_width_option'] : 'sm';
      $border_style = $configuration['border_style_option'] !== self::NONE_OPTION_KEY ? $configuration['border_style_option'] : 'solid';

      $this->applyClassFromConfig($build, 'kl-border-width-', $border_width, $configuration);
      $this->applyClassFromConfig($build, 'kl-border-style-', $border_style, $configuration);
      return TRUE;
    }
    return FALSE;
  }

}

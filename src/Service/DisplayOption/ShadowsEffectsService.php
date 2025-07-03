<?php

namespace Drupal\kingly_layouts\Service\DisplayOption;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;
use Drupal\kingly_layouts\KinglyLayoutsUtilityTrait;

/**
 * Service to manage shadow and effects options for Kingly Layouts.
 */
class ShadowsEffectsService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;
  use KinglyLayoutsUtilityTrait;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * Constructs a new ShadowsEffectsService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey(): string {
    return 'shadows_effects';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form_key = $this->getFormKey();
    $form[$form_key] = [
      '#type' => 'details',
      '#title' => $this->t('Shadows & Effects'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts shadows effects'),
    ];
    $form[$form_key]['static_effects'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Static Effects'),
      '#description' => $this->t('These effects are applied to the layout section by default.'),
    ];
    $form[$form_key]['static_effects']['box_shadow_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Box Shadow'),
      '#options' => $this->getEffectsOptions('box_shadow'),
      '#default_value' => $configuration['box_shadow_option'],
    ];
    $form[$form_key]['static_effects']['filter_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter'),
      '#options' => $this->getEffectsOptions('filter'),
      '#default_value' => $configuration['filter_option'],
    ];
    $form[$form_key]['static_effects']['opacity_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Opacity'),
      '#options' => $this->getEffectsOptions('opacity'),
      '#default_value' => $configuration['opacity_option'],
      '#description' => $this->t('Adjust the overall transparency of the layout section.'),
    ];
    $form[$form_key]['static_effects']['transform_scale_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Scale'),
      '#options' => $this->getEffectsOptions('transform_scale'),
      '#default_value' => $configuration['transform_scale_option'],
      '#description' => $this->t('Scale the size of the layout section.'),
    ];
    $form[$form_key]['static_effects']['transform_rotate_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Rotate'),
      '#options' => $this->getEffectsOptions('transform_rotate'),
      '#default_value' => $configuration['transform_rotate_option'],
      '#description' => $this->t('Rotate the layout section.'),
    ];

    $form[$form_key]['hover_effects'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hover Effects'),
      '#description' => $this->t('These effects are applied to the layout section when a user hovers over it.'),
    ];
    $form[$form_key]['hover_effects']['hover_transform_scale_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Hover Scale'),
      '#options' => $this->getEffectsOptions('hover_transform_scale'),
      '#default_value' => $configuration['hover_transform_scale_option'],
      '#description' => $this->t('Adjust the scale of the layout section on hover.'),
    ];
    $form[$form_key]['hover_effects']['hover_box_shadow_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Hover Box Shadow'),
      '#options' => $this->getEffectsOptions('hover_box_shadow'),
      '#default_value' => $configuration['hover_box_shadow_option'],
      '#description' => $this->t('Apply a box shadow to the layout section on hover.'),
    ];
    $form[$form_key]['hover_effects']['hover_filter_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Hover Filter'),
      '#options' => $this->getEffectsOptions('hover_filter'),
      '#default_value' => $configuration['hover_filter_option'],
      '#description' => $this->t('Apply a visual filter to the layout section on hover.'),
    ];
    $form[$form_key]['hover_effects']['hover_font_size_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Hover Font Size'),
      '#options' => $this->getEffectsOptions('hover_font_size'),
      '#default_value' => $configuration['hover_font_size_option'],
      '#description' => $this->t('Adjust the font size of text within the layout section on hover.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $form_key = $this->getFormKey();
    $values = $form_state->getValue($form_key, []);

    // Static effects.
    foreach ([
      'box_shadow_option',
      'filter_option',
      'opacity_option',
      'transform_scale_option',
      'transform_rotate_option',
    ] as $key) {
      $configuration[$key] = $values['static_effects'][$key] ?? self::NONE_OPTION_KEY;
    }

    // Hover effects.
    foreach ([
      'hover_transform_scale_option',
      'hover_box_shadow_option',
      'hover_filter_option',
      'hover_font_size_option',
    ] as $key) {
      $configuration[$key] = $values['hover_effects'][$key] ?? self::NONE_OPTION_KEY;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    $has_effects = FALSE;

    // Apply static box shadow and filter classes.
    $has_effects = $this->applyStaticClassBasedEffects($build, $configuration) || $has_effects;

    // Apply static opacity and transform inline styles.
    $has_effects = $this->applyStaticInlineStyles($build, $configuration) || $has_effects;

    // Apply hover transform scale, box shadow, filter, and font size classes.
    $has_effects = $this->applyHoverEffects($build, $configuration) || $has_effects;

    // If any effect (static or hover) was applied, attach the necessary
    // libraries and the base animation class for transitions.
    if ($has_effects) {
      $build['#attached']['library'][] = 'kingly_layouts/effects';
      // The 'animations' library contains the base 'kl-animate' class
      // and its transition properties, which are essential for smooth
      // hover effects.
      $build['#attached']['library'][] = 'kingly_layouts/animations';
      $build['#attributes']['class'][] = 'kl-animate';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'box_shadow_option' => self::NONE_OPTION_KEY,
      'filter_option' => self::NONE_OPTION_KEY,
      'opacity_option' => self::NONE_OPTION_KEY,
      'transform_scale_option' => self::NONE_OPTION_KEY,
      'transform_rotate_option' => self::NONE_OPTION_KEY,
      'hover_transform_scale_option' => self::NONE_OPTION_KEY,
      'hover_box_shadow_option' => self::NONE_OPTION_KEY,
      'hover_filter_option' => self::NONE_OPTION_KEY,
      'hover_font_size_option' => self::NONE_OPTION_KEY,
    ];
  }

  /**
   * Gets effects-related options.
   *
   * @param string $key
   *   The key for the specific options to retrieve.
   *
   * @return array
   *   An array of options.
   */
  private function getEffectsOptions(string $key): array {
    $none = [self::NONE_OPTION_KEY => $this->t('None')];
    $options = [
      'box_shadow' => $none + [
        'sm' => $this->t('Small'),
        'md' => $this->t('Medium'),
        'lg' => $this->t('Large'),
        'xl' => $this->t('Extra Large'),
        'inner' => $this->t('Inner'),
      ],
      'filter' => $none + [
        'grayscale' => $this->t('Grayscale'),
        'blur' => $this->t('Blur'),
        'sepia' => $this->t('Sepia'),
        'brightness' => $this->t('Brightness'),
      ],
      'opacity' => [
        self::NONE_OPTION_KEY => $this->t('100% (Default)'),
        '0.9' => $this->t('90%'),
        '0.75' => $this->t('75%'),
        '0.5' => $this->t('50%'),
        '0.25' => $this->t('25%'),
        '0' => $this->t('0% (Transparent)'),
      ],
      'transform_scale' => [
        self::NONE_OPTION_KEY => $this->t('None (100%)'),
        '0.9' => $this->t('90%'),
        '0.95' => $this->t('95%'),
        '1.05' => $this->t('105%'),
        '1.1' => $this->t('110%'),
        '1.25' => $this->t('125%'),
      ],
      'transform_rotate' => $none + [
        '1' => $this->t('1 degree'),
        '2' => $this->t('2 degrees'),
        '3' => $this->t('3 degrees'),
        '5' => $this->t('5 degrees'),
        '-1' => $this->t('-1 degree'),
        '-2' => $this->t('-2 degrees'),
        '-3' => $this->t('-3 degrees'),
        '-5' => $this->t('-5 degrees'),
      ],
      'hover_transform_scale' => $none + [
        'scale-90' => $this->t('Scale Down (90%)'),
        'scale-95' => $this->t('Slightly Scale Down (95%)'),
        'scale-105' => $this->t('Slightly Scale Up (105%)'),
        'scale-110' => $this->t('Scale Up (110%)'),
        'scale-125' => $this->t('Enlarge (125%)'),
      ],
      'hover_box_shadow' => $none + [
        'sm' => $this->t('Small Shadow'),
        'md' => $this->t('Medium Shadow'),
        'lg' => $this->t('Large Shadow'),
        'xl' => $this->t('Extra Large Shadow'),
        'inner' => $this->t('Inner Shadow'),
      ],
      'hover_filter' => $none + [
        'grayscale-to-color' => $this->t('Grayscale to Color'),
        'brightness-down' => $this->t('Brightness Down'),
        'brightness-up' => $this->t('Brightness Up'),
      ],
      'hover_font_size' => $none + [
        'size-90' => $this->t('Smaller (90%)'),
        'size-95' => $this->t('Slightly Smaller (95%)'),
        'size-105' => $this->t('Slightly Larger (105%)'),
        'size-110' => $this->t('Larger (110%)'),
        'size-125' => $this->t('Much Larger (125%)'),
      ],
    ];

    return $options[$key] ?? [];
  }

  /**
   * Applies static CSS class-based effects (box shadow, filter).
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return bool
   *   TRUE if any static class-based effect was applied, FALSE otherwise.
   */
  private function applyStaticClassBasedEffects(array &$build, array $configuration): bool {
    $applied = FALSE;
    $class_map = [
      'box_shadow_option' => 'kl-shadow-',
      'filter_option' => 'kl-filter-',
    ];
    foreach ($class_map as $config_key => $prefix) {
      if ($configuration[$config_key] !== self::NONE_OPTION_KEY) {
        $this->applyClassFromConfig($build, $prefix, $config_key, $configuration);
        $applied = TRUE;
      }
    }
    return $applied;
  }

  /**
   * Applies static inline CSS styles (opacity, transform).
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return bool
   *   TRUE if any static inline style effect was applied, FALSE otherwise.
   */
  private function applyStaticInlineStyles(array &$build, array $configuration): bool {
    $applied = FALSE;
    $style_map = [
      'opacity_option' => 'opacity',
    ];
    foreach ($style_map as $config_key => $property) {
      if ($configuration[$config_key] !== self::NONE_OPTION_KEY) {
        $this->applyInlineStyleFromOption($build, $property, $config_key, $configuration);
        $applied = TRUE;
      }
    }

    // Handle combined transforms for scale and rotate.
    $transforms = [];
    if (($scale_value = $configuration['transform_scale_option']) !== self::NONE_OPTION_KEY) {
      $transforms[] = 'scale(' . $scale_value . ')';
      $applied = TRUE;
    }
    if (($rotate_value = $configuration['transform_rotate_option']) !== self::NONE_OPTION_KEY) {
      $transforms[] = 'rotate(' . $rotate_value . 'deg)';
      $applied = TRUE;
    }
    if (!empty($transforms)) {
      $build['#attributes']['style'][] = 'transform: ' . implode(' ', $transforms) . ';';
    }
    return $applied;
  }

  /**
   * Applies hover effects to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return bool
   *   TRUE if any hover effect was applied, FALSE otherwise.
   */
  private function applyHoverEffects(array &$build, array $configuration): bool {
    $applied = FALSE;
    $hover_class_map = [
      'hover_transform_scale_option' => 'kl--hover-scale-',
      'hover_box_shadow_option' => 'kl--hover-shadow-',
      'hover_filter_option' => 'kl--hover-filter-',
      'hover_font_size_option' => 'kl--hover-font-size-',
    ];

    foreach ($hover_class_map as $config_key => $prefix) {
      if ($configuration[$config_key] !== self::NONE_OPTION_KEY) {
        // The applyClassFromConfig helper will automatically fetch the correct
        // class suffix (e.g., 'scale-110' or 'size-110') from the configuration
        // based on the $config_key provided.
        $this->applyClassFromConfig($build, $prefix, $config_key, $configuration);
        $applied = TRUE;
      }
    }
    return $applied;
  }

}

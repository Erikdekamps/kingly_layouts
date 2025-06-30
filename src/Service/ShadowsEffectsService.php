<?php

namespace Drupal\kingly_layouts\Service;

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
   * The options service.
   */
  protected OptionsService $optionsService;

  /**
   * Constructs a new ShadowsEffectsService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\kingly_layouts\Service\OptionsService $options_service
   *   The options service.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation, OptionsService $options_service) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
    $this->optionsService = $options_service;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form['shadows_effects'] = [
      '#type' => 'details',
      '#title' => $this->t('Shadows & Effects'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts shadows effects'),
    ];
    $form['shadows_effects']['static_effects'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Static Effects'),
      '#description' => $this->t('These effects are applied to the layout section by default.'),
    ];
    $form['shadows_effects']['static_effects']['box_shadow_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Box Shadow'),
      '#options' => $this->optionsService->getOptions('box_shadow'),
      '#default_value' => $configuration['box_shadow_option'],
    ];
    $form['shadows_effects']['static_effects']['filter_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter'),
      '#options' => $this->optionsService->getOptions('filter'),
      '#default_value' => $configuration['filter_option'],
    ];
    $form['shadows_effects']['static_effects']['opacity_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Opacity'),
      '#options' => $this->optionsService->getOptions('opacity'),
      '#default_value' => $configuration['opacity_option'],
      '#description' => $this->t('Adjust the overall transparency of the layout section.'),
    ];
    $form['shadows_effects']['static_effects']['transform_scale_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Scale'),
      '#options' => $this->optionsService->getOptions('transform_scale'),
      '#default_value' => $configuration['transform_scale_option'],
      '#description' => $this->t('Scale the size of the layout section.'),
    ];
    $form['shadows_effects']['static_effects']['transform_rotate_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Rotate'),
      '#options' => $this->optionsService->getOptions('transform_rotate'),
      '#default_value' => $configuration['transform_rotate_option'],
      '#description' => $this->t('Rotate the layout section.'),
    ];

    $form['shadows_effects']['hover_effects'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hover Effects'),
      '#description' => $this->t('These effects are applied to the layout section when a user hovers over it.'),
    ];
    $form['shadows_effects']['hover_effects']['hover_transform_scale_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Hover Scale'),
      '#options' => $this->optionsService->getOptions('hover_transform_scale'),
      '#default_value' => $configuration['hover_transform_scale_option'],
      '#description' => $this->t('Adjust the scale of the layout section on hover.'),
    ];
    $form['shadows_effects']['hover_effects']['hover_box_shadow_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Hover Box Shadow'),
      '#options' => $this->optionsService->getOptions('hover_box_shadow'),
      '#default_value' => $configuration['hover_box_shadow_option'],
      '#description' => $this->t('Apply a box shadow to the layout section on hover.'),
    ];
    $form['shadows_effects']['hover_effects']['hover_filter_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Hover Filter'),
      '#options' => $this->optionsService->getOptions('hover_filter'),
      '#default_value' => $configuration['hover_filter_option'],
      '#description' => $this->t('Apply a visual filter to the layout section on hover.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $values = $form_state->getValue('shadows_effects', []);

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

    // Apply hover transform scale, box shadow, and filter classes.
    $has_effects = $this->applyHoverEffects($build, $configuration) || $has_effects;

    // If any effect (static or hover) was applied, attach the effects library
    // and the base animation class for transitions.
    if ($has_effects) {
      $build['#attached']['library'][] = 'kingly_layouts/effects';
      $build['#attributes']['class'][] = 'kingly-animate';
    }
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
      'box_shadow_option' => 'kingly-layout-shadow-',
      'filter_option' => 'kingly-layout-filter-',
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
   * Applies hover effects (transform, box shadow, filter) to the build array.
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
      'hover_transform_scale_option' => 'kingly-layout--hover-scale-',
      'hover_box_shadow_option' => 'kingly-layout--hover-shadow-',
      'hover_filter_option' => 'kingly-layout--hover-filter-',
    ];

    foreach ($hover_class_map as $config_key => $prefix) {
      if ($configuration[$config_key] !== self::NONE_OPTION_KEY) {
        // Special handling for scale values that are decimals.
        $value_for_class = $configuration[$config_key];
        if (str_contains($value_for_class, '.')) {
          $value_for_class = str_replace('.', '\.', $value_for_class);
        }
        $this->applyClassFromConfig($build, $prefix, $value_for_class, ['_value' => $configuration[$config_key]]);
        $applied = TRUE;
      }
    }
    return $applied;
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
    ];
  }

}

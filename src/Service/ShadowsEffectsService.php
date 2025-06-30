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
    $form['shadows_effects']['box_shadow_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Box Shadow'),
      '#options' => $this->optionsService->getOptions('box_shadow'),
      '#default_value' => $configuration['box_shadow_option'],
    ];
    $form['shadows_effects']['filter_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter'),
      '#options' => $this->optionsService->getOptions('filter'),
      '#default_value' => $configuration['filter_option'],
    ];
    $form['shadows_effects']['opacity_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Opacity'),
      '#options' => $this->optionsService->getOptions('opacity'),
      '#default_value' => $configuration['opacity_option'],
      '#description' => $this->t('Adjust the overall transparency of the layout section.'),
    ];
    $form['shadows_effects']['transform_scale_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Scale'),
      '#options' => $this->optionsService->getOptions('transform_scale'),
      '#default_value' => $configuration['transform_scale_option'],
      '#description' => $this->t('Scale the size of the layout section.'),
    ];
    $form['shadows_effects']['transform_rotate_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Rotate'),
      '#options' => $this->optionsService->getOptions('transform_rotate'),
      '#default_value' => $configuration['transform_rotate_option'],
      '#description' => $this->t('Rotate the layout section.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $values = $form_state->getValue('shadows_effects', []);
    foreach ([
      'box_shadow_option',
      'filter_option',
      'opacity_option',
      'transform_scale_option',
      'transform_rotate_option',
    ] as $key) {
      $configuration[$key] = $values[$key] ?? self::NONE_OPTION_KEY;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    $has_effects = FALSE;
    $class_map = [
      'box_shadow_option' => 'kingly-layout-shadow-',
      'filter_option' => 'kingly-layout-filter-',
    ];
    foreach ($class_map as $config_key => $prefix) {
      if ($configuration[$config_key] !== self::NONE_OPTION_KEY) {
        $has_effects = TRUE;
        $this->applyClassFromConfig($build, $prefix, $config_key, $configuration);
      }
    }

    if ($has_effects) {
      $build['#attached']['library'][] = 'kingly_layouts/effects';
    }

    $style_map = [
      'opacity_option' => 'opacity',
    ];
    foreach ($style_map as $config_key => $property) {
      $this->applyInlineStyleFromOption($build, $property, $config_key, $configuration);
    }

    // Handle combined transforms.
    $transforms = [];
    if (($scale_value = $configuration['transform_scale_option']) !== self::NONE_OPTION_KEY) {
      $transforms[] = 'scale(' . $scale_value . ')';
    }
    if (($rotate_value = $configuration['transform_rotate_option']) !== self::NONE_OPTION_KEY) {
      $transforms[] = 'rotate(' . $rotate_value . 'deg)';
    }
    if (!empty($transforms)) {
      $build['#attributes']['style'][] = 'transform: ' . implode(' ', $transforms) . ';';
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
    ];
  }

  /**
   * Helper to apply a generic inline style from a configuration option.
   *
   * @param array &$build
   *   The render array.
   * @param string $style_property
   *   The CSS property to set.
   * @param string $config_key
   *   The configuration key whose value will be used.
   * @param array $configuration
   *   The layout's current configuration.
   */
  private function applyInlineStyleFromOption(array &$build, string $style_property, string $config_key, array $configuration): void {
    $value = $configuration[$config_key];
    if ($value !== self::NONE_OPTION_KEY) {
      $build['#attributes']['style'][] = $style_property . ': ' . $value . ';';
    }
  }

}

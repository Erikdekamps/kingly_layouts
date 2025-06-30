<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;
use Drupal\kingly_layouts\KinglyLayoutsUtilityTrait;

/**
 * Service to manage animation options for Kingly Layouts.
 */
class AnimationService implements KinglyLayoutsDisplayOptionInterface {

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
   * Constructs a new AnimationService object.
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
    $form['animation'] = [
      '#type' => 'details',
      '#title' => $this->t('Animation'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts animation'),
    ];

    $form['animation']['animation_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Animation Type'),
      '#options' => $this->optionsService->getOptions('animation_type'),
      '#default_value' => $configuration['animation_type'],
      '#description' => $this->t('Select an animation to apply when the layout scrolls into view. This defines the start and end states.'),
    ];

    $form['animation']['slide_direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Direction'),
      '#options' => $this->optionsService->getOptions('slide_direction'),
      '#default_value' => $configuration['slide_direction'],
      '#description' => $this->t('Select the direction for slide-in animations.'),
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[animation][animation_type]"]' => ['value' => 'slide-in'],
        ],
      ],
    ];

    $form['animation']['transition_property'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition Property'),
      '#options' => $this->optionsService->getOptions('transition_property'),
      '#default_value' => $configuration['transition_property'],
      '#description' => $this->t('The CSS property that the transition will animate.'),
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[animation][animation_type]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];

    $form['animation']['transition_duration'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition Duration'),
      '#options' => $this->optionsService->getOptions('transition_duration'),
      '#default_value' => $configuration['transition_duration'],
      '#description' => $this->t('How long the animation takes to complete.'),
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[animation][animation_type]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];

    $form['animation']['transition_timing_function'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition Speed Curve'),
      '#options' => $this->optionsService->getOptions('transition_timing_function'),
      '#default_value' => $configuration['transition_timing_function'],
      '#description' => $this->t('The speed curve of the animation.'),
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[animation][animation_type]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];

    $form['animation']['transition_delay'] = [
      '#type' => 'select',
      '#title' => $this->t('Transition Delay'),
      '#options' => $this->optionsService->getOptions('transition_delay'),
      '#default_value' => $configuration['transition_delay'],
      '#description' => $this->t('The delay before the animation starts.'),
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[animation][animation_type]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $values = $form_state->getValue('animation', []);
    foreach ([
      'animation_type',
      'slide_direction',
      'transition_property',
      'transition_duration',
      'transition_timing_function',
      'transition_delay',
    ] as $key) {
      $configuration[$key] = $values[$key] ?? self::NONE_OPTION_KEY;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    if ($configuration['animation_type'] !== self::NONE_OPTION_KEY) {
      $build['#attached']['library'][] = 'kingly_layouts/animations';
      $build['#attributes']['class'][] = 'kingly-animate';
      $this->applyClassFromConfig($build, 'kingly-animate--', 'animation_type', $configuration);

      if ($configuration['animation_type'] === 'slide-in' && $configuration['slide_direction'] !== self::NONE_OPTION_KEY) {
        $this->applyClassFromConfig($build, 'kingly-animate--direction-', 'slide_direction', $configuration);
      }

      $animation_style_map = [
        'transition_property' => 'transition-property',
        'transition_duration' => 'transition-duration',
        'transition_timing_function' => 'transition-timing-function',
        'transition_delay' => 'transition-delay',
      ];
      foreach ($animation_style_map as $config_key => $property) {
        $this->applyInlineStyleFromOption($build, $property, $config_key, $configuration);
      }
    }
  }

  /**
   * Helper to apply a generic inline style from a configuration option.
   *
   * @param array &$build
   *   The render array.
   * @param string $style_property
   *   The CSS property to set (e.g., 'transition-duration').
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

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'animation_type' => self::NONE_OPTION_KEY,
      'slide_direction' => self::NONE_OPTION_KEY,
      'transition_property' => self::NONE_OPTION_KEY,
      'transition_duration' => self::NONE_OPTION_KEY,
      'transition_timing_function' => self::NONE_OPTION_KEY,
      'transition_delay' => self::NONE_OPTION_KEY,
    ];
  }

}

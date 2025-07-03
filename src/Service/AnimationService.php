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
   * Constructs a new AnimationService object.
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
      '#options' => $this->getAnimationOptions('type'),
      '#default_value' => $configuration['animation_type'],
      '#description' => $this->t('Select an animation to apply when the layout scrolls into view. This defines the start and end states.'),
    ];

    $form['animation']['slide_direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Direction'),
      '#options' => $this->getAnimationOptions('slide_direction'),
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
      '#options' => $this->getAnimationOptions('transition_property'),
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
      '#options' => $this->getAnimationOptions('transition_duration'),
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
      '#options' => $this->getAnimationOptions('transition_timing_function'),
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
      '#options' => $this->getAnimationOptions('transition_delay'),
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
      // Attach the necessary library.
      $this->attachAnimationLibrary($build);
      // Apply base animation classes.
      $this->applyBaseAnimationClasses($build);
      // Apply specific animation type and direction classes.
      $this->applySpecificAnimationClasses($build, $configuration);
      // Apply inline transition styles.
      $this->applyAnimationTransitionStyles($build, $configuration);
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

  /**
   * Gets animation-related options.
   *
   * @param string $key
   *   The key for the specific options to retrieve.
   *
   * @return array
   *   An array of animation options.
   */
  private function getAnimationOptions(string $key): array {
    $none = [self::NONE_OPTION_KEY => $this->t('None')];
    $options = [
      'type' => $none + [
        'fade-in' => $this->t('Fade In'),
        'slide-in' => $this->t('Slide In'),
      ],
      'slide_direction' => $none + [
        'up' => $this->t('Bottom up'),
        'down' => $this->t('Top down'),
        'left' => $this->t('Right to Left'),
        'right' => $this->t('Left to Right'),
      ],
      'transition_property' => [
        self::NONE_OPTION_KEY => $this->t('Default (opacity, transform)'),
        'opacity' => $this->t('Opacity only'),
        'transform' => $this->t('Transform only'),
        'all' => $this->t('All properties'),
        'opacity, transform' => $this->t('Opacity and Transform'),
      ],
      'transition_duration' => [
        self::NONE_OPTION_KEY => $this->t('Default (600ms)'),
        '150ms' => $this->t('150ms'),
        '300ms' => $this->t('300ms'),
        '500ms' => $this->t('500ms'),
        '750ms' => $this->t('750ms'),
        '1s' => $this->t('1s'),
      ],
      'transition_timing_function' => [
        self::NONE_OPTION_KEY => $this->t('Default (ease-out)'),
        'ease' => $this->t('ease'),
        'ease-in' => $this->t('ease-in'),
        'ease-in-out' => $this->t('ease-in-out'),
        'linear' => $this->t('linear'),
      ],
      'transition_delay' => $none + [
        '150ms' => $this->t('150ms'),
        '300ms' => $this->t('300ms'),
        '500ms' => $this->t('500ms'),
        '750ms' => $this->t('750ms'),
        '1s' => $this->t('1s'),
      ],
    ];

    return $options[$key] ?? [];
  }

  /**
   * Attaches the animation library.
   *
   * @param array &$build
   *   The render array, passed by reference.
   */
  private function attachAnimationLibrary(array &$build): void {
    $build['#attached']['library'][] = 'kingly_layouts/animations';
  }

  /**
   * Applies base animation classes.
   *
   * @param array &$build
   *   The render array, passed by reference.
   */
  private function applyBaseAnimationClasses(array &$build): void {
    $build['#attributes']['class'][] = 'kl-animate';
  }

  /**
   * Applies specific animation type and direction classes.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   */
  private function applySpecificAnimationClasses(array &$build, array $configuration): void {
    $this->applyClassFromConfig($build, 'kl-animate--', 'animation_type', $configuration);

    if ($configuration['animation_type'] === 'slide-in' && $configuration['slide_direction'] !== self::NONE_OPTION_KEY) {
      $this->applyClassFromConfig($build, 'kl-animate--direction-', 'slide_direction', $configuration);
    }
  }

  /**
   * Applies inline transition styles.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   */
  private function applyAnimationTransitionStyles(array &$build, array $configuration): void {
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

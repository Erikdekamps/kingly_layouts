<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Kingly layouts with sizing and background options.
 */
abstract class KinglyLayoutBase extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * The key used for the "None" option in select lists.
   */
  protected const NONE_OPTION_KEY = '_none';

  /**
   * The ID of the taxonomy vocabulary used for CSS colors.
   */
  protected const KINGLY_CSS_COLOR_VOCABULARY = 'kingly_css_color';

  /**
   * The field name on the taxonomy term that stores the hex color value.
   */
  protected const KINGLY_CSS_COLOR_FIELD = 'field_kingly_css_color';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityTypeManager;

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new KinglyLayoutBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache_backend) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $this->cache = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = parent::defaultConfiguration();

    $sizing_options = $this->getSizingOptions();
    $configuration['sizing_option'] = key($sizing_options);

    // Add defaults for padding options.
    $padding_options = $this->getScaleOptions();
    $default_padding = key($padding_options);
    $configuration['horizontal_padding_option'] = $default_padding;
    $configuration['vertical_padding_option'] = $default_padding;

    // Add default for gap option.
    $gap_options = $this->getGapOptions();
    $configuration['gap_option'] = key($gap_options);

    // Add defaults for margin options.
    $configuration['horizontal_margin_option'] = self::NONE_OPTION_KEY;
    $configuration['vertical_margin_option'] = self::NONE_OPTION_KEY;

    // Default to no background or foreground color.
    $configuration['background_color'] = self::NONE_OPTION_KEY;
    $configuration['foreground_color'] = self::NONE_OPTION_KEY;

    // Add default for container type.
    $configuration['container_type'] = 'boxed';

    // Add defaults for border options.
    $configuration['border_radius_option'] = self::NONE_OPTION_KEY;
    $configuration['border_color'] = self::NONE_OPTION_KEY;
    $configuration['border_width_option'] = self::NONE_OPTION_KEY;
    $configuration['border_style_option'] = self::NONE_OPTION_KEY;

    // Add defaults for animation options.
    $configuration['animation_type'] = self::NONE_OPTION_KEY;
    // New default.
    $configuration['slide_direction'] = self::NONE_OPTION_KEY;
    $configuration['transition_property'] = self::NONE_OPTION_KEY;
    $configuration['transition_duration'] = self::NONE_OPTION_KEY;
    $configuration['transition_timing_function'] = self::NONE_OPTION_KEY;
    $configuration['transition_delay'] = self::NONE_OPTION_KEY;

    return $configuration;
  }

  /**
   * Returns the available sizing options for this layout.
   *
   * @return array
   *   An associative array of sizing options.
   */
  abstract protected function getSizingOptions(): array;

  /**
   * Returns the available padding scale options.
   *
   * @return array
   *   An associative array of padding scale options.
   */
  protected function getScaleOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('None'),
      'xs' => $this->t('Extra Small (0.25rem)'),
      'sm' => $this->t('Small (0.5rem)'),
      'md' => $this->t('Medium (1rem)'),
      'lg' => $this->t('Large (2rem)'),
      'xl' => $this->t('Extra Large (4rem)'),
    ];
  }

  /**
   * Returns the available gap options for this layout.
   *
   * @return array
   *   An associative array of gap options.
   */
  protected function getGapOptions(): array {
    return $this->getScaleOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Column Sizing (now at the top level).
    $form['sizing_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Column sizing'),
      '#options' => $this->getSizingOptions(),
      '#default_value' => $this->configuration['sizing_option'],
      '#description' => $this->t('Select the desired column width distribution.'),
      '#weight' => -10,
    ];

    // Container Type (now at the top level).
    $form['container_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Container Type'),
      '#options' => $this->getContainerTypeOptions(),
      '#default_value' => $this->configuration['container_type'],
      '#description' => $this->t('Select how the layout container should behave.'),
      '#weight' => -9,
    ];

    // Spacing.
    $form['spacing'] = [
      '#type' => 'details',
      '#title' => $this->t('Spacing'),
      '#open' => FALSE,
    ];
    $form['spacing']['horizontal_padding_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Padding'),
      '#options' => $this->getHorizontalPaddingOptions(),
      '#default_value' => $this->configuration['horizontal_padding_option'],
      '#description' => $this->t('Select the horizontal padding for the layout. For "Edge to Edge" layouts, this padding is applied from the viewport edge.'),
    ];
    $form['spacing']['vertical_padding_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Padding'),
      '#options' => $this->getVerticalPaddingOptions(),
      '#default_value' => $this->configuration['vertical_padding_option'],
      '#description' => $this->t('Select the desired vertical padding (top and bottom) for the layout container.'),
    ];
    $form['spacing']['gap_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Gap'),
      '#options' => $this->getGapOptions(),
      '#default_value' => $this->configuration['gap_option'],
      '#description' => $this->t('Select the desired gap between layout columns/regions.'),
    ];
    $form['spacing']['horizontal_margin_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Margin'),
      '#options' => $this->getHorizontalMarginOptions(),
      '#default_value' => $this->configuration['horizontal_margin_option'],
      '#description' => $this->t('Select the horizontal margin for the layout. This margin will not be applied if "Full Width" or "Edge to Edge" is selected.'),
    ];
    $form['spacing']['vertical_margin_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Margin'),
      '#options' => $this->getVerticalMarginOptions(),
      '#default_value' => $this->configuration['vertical_margin_option'],
      '#description' => $this->t('Select the desired vertical margin (top and bottom) for the layout container.'),
    ];

    // Colors.
    $form['colors'] = [
      '#type' => 'details',
      '#title' => $this->t('Colors'),
      '#open' => FALSE,
    ];
    $color_options = $this->getColorOptions();
    if (count($color_options) > 1) {
      $form['colors']['background_color'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Color'),
        '#options' => $color_options,
        '#default_value' => $this->configuration['background_color'],
      ];
      $form['colors']['foreground_color'] = [
        '#type' => 'select',
        '#title' => $this->t('Foreground Color'),
        '#options' => $color_options,
        '#default_value' => $this->configuration['foreground_color'],
      ];
      $form['colors']['color_info'] = [
        '#type' => 'item',
        '#markup' => $this->t('Colors are managed in the <a href="/admin/structure/taxonomy/manage/@vocab_id/overview" target="_blank">Kingly CSS Color</a> vocabulary.', ['@vocab_id' => self::KINGLY_CSS_COLOR_VOCABULARY]),
      ];
    }
    else {
      $form['colors']['color_info'] = [
        '#type' => 'item',
        '#title' => $this->t('Color Options'),
        '#markup' => $this->t('No colors defined. Please <a href="/admin/structure/taxonomy/manage/@vocab_id/add" target="_blank">add terms</a> to the "Kingly CSS Color" vocabulary.', ['@vocab_id' => self::KINGLY_CSS_COLOR_VOCABULARY]),
      ];
    }

    // Borders.
    $form['borders'] = [
      '#type' => 'details',
      '#title' => $this->t('Borders'),
      '#open' => FALSE,
    ];
    if (count($color_options) > 1) {
      $form['borders']['border_color'] = [
        '#type' => 'select',
        '#title' => $this->t('Border Color'),
        '#options' => $color_options,
        '#default_value' => $this->configuration['border_color'],
        '#description' => $this->t('Selecting a color will enable the border options below.'),
      ];
    }
    $form['borders']['border_width_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Width'),
      '#options' => $this->getBorderWidthOptions(),
      '#default_value' => $this->configuration['border_width_option'],
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[borders][border_color]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];
    $form['borders']['border_style_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Style'),
      '#options' => $this->getBorderStyleOptions(),
      '#default_value' => $this->configuration['border_style_option'],
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[borders][border_color]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];
    $form['borders']['border_radius_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Radius'),
      '#options' => $this->getBorderRadiusOptions(),
      '#default_value' => $this->configuration['border_radius_option'],
    ];

    // Animation Options.
    $form['animation'] = [
      '#type' => 'details',
      '#title' => $this->t('Animation'),
      '#open' => FALSE,
    ];
    $form['animation']['animation_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Animation Type'),
      '#options' => $this->getAnimationTypeOptions(),
      '#default_value' => $this->configuration['animation_type'],
      '#description' => $this->t('Select an animation to apply when the layout scrolls into view. This defines the start and end states.'),
    ];
    // New field for slide direction.
    $form['animation']['slide_direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Direction'),
      '#options' => $this->getSlideDirectionOptions(),
      '#default_value' => $this->configuration['slide_direction'],
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
      '#options' => $this->getTransitionPropertyOptions(),
      '#default_value' => $this->configuration['transition_property'],
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
      '#options' => $this->getTransitionDurationOptions(),
      '#default_value' => $this->configuration['transition_duration'],
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
      '#options' => $this->getTransitionTimingFunctionOptions(),
      '#default_value' => $this->configuration['transition_timing_function'],
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
      '#options' => $this->getTransitionDelayOptions(),
      '#default_value' => $this->configuration['transition_delay'],
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
   * Returns the available container type options.
   *
   * @return array
   *   An associative array of container type options.
   */
  protected function getContainerTypeOptions(): array {
    return [
      'boxed' => $this->t('Boxed'),
      'full' => $this->t('Full Width (Background Only)'),
      'edge-to-edge' => $this->t('Edge to Edge (Full Bleed)'),
    ];
  }

  /**
   * Returns the available horizontal padding options for this layout.
   *
   * @return array
   *   An associative array of padding options.
   */
  protected function getHorizontalPaddingOptions(): array {
    return $this->getScaleOptions();
  }

  /**
   * Returns the available vertical padding options for this layout.
   *
   * @return array
   *   An associative array of padding options.
   */
  protected function getVerticalPaddingOptions(): array {
    return $this->getScaleOptions();
  }

  /**
   * Returns the available horizontal margin options for this layout.
   *
   * @return array
   *   An associative array of margin options.
   */
  protected function getHorizontalMarginOptions(): array {
    return $this->getScaleOptions();
  }

  /**
   * Returns the available vertical margin options for this layout.
   *
   * @return array
   *   An associative array of margin options.
   */
  protected function getVerticalMarginOptions(): array {
    return $this->getScaleOptions();
  }

  /**
   * Returns color options from the 'kingly_css_color' vocabulary.
   *
   * @return array
   *   An associative array of color options.
   */
  protected function getColorOptions(): array {
    $cid = 'kingly_layouts:color_options';
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $options = [self::NONE_OPTION_KEY => $this->t('None')];
    if (!$this->entityTypeManager->getStorage('taxonomy_vocabulary')
      ->load(self::KINGLY_CSS_COLOR_VOCABULARY)) {
      $this->cache->set($cid, $options, CacheBackendInterface::CACHE_PERMANENT, ['taxonomy_term_list']);
      return $options;
    }
    $terms = $this->termStorage->loadTree(self::KINGLY_CSS_COLOR_VOCABULARY, 0, NULL, TRUE);
    foreach ($terms as $term) {
      $options[$term->id()] = $term->getName();
    }

    $this->cache->set($cid, $options, CacheBackendInterface::CACHE_PERMANENT, ['taxonomy_term_list']);

    return $options;
  }

  /**
   * Returns the available border width options.
   *
   * @return array
   *   An associative array of border width options.
   */
  protected function getBorderWidthOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('None'),
      'sm' => $this->t('Small (1px)'),
      'md' => $this->t('Medium (2px)'),
      'lg' => $this->t('Large (4px)'),
    ];
  }

  /**
   * Returns the available border style options.
   *
   * @return array
   *   An associative array of border style options.
   */
  protected function getBorderStyleOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('None'),
      'solid' => $this->t('Solid'),
      'dashed' => $this->t('Dashed'),
      'dotted' => $this->t('Dotted'),
    ];
  }

  /**
   * Returns the available border radius options.
   *
   * @return array
   *   An associative array of border radius options.
   */
  protected function getBorderRadiusOptions(): array {
    $options = $this->getScaleOptions();
    $options['full'] = $this->t('Full (Pill/Circle)');
    return $options;
  }

  /**
   * Returns the available animation type options.
   *
   * @return array
   *   An associative array of animation type options.
   */
  protected function getAnimationTypeOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('None'),
      'fade-in' => $this->t('Fade In'),
      // Generic slide-in.
      'slide-in' => $this->t('Slide In'),
    ];
  }

  /**
   * Returns the available slide direction options.
   *
   * Note: 'left' means it comes from the right and 'right' means it comes from
   * the left.
   *
   * @return array
   *   An associative array of slide direction options.
   */
  protected function getSlideDirectionOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('None'),
      'up' => $this->t('Bottom up'),
      'down' => $this->t('Top down'),
      'left' => $this->t('Right to Left'),
      'right' => $this->t('Left to Right'),
    ];
  }

  /**
   * Returns the available transition property options.
   *
   * @return array
   *   An associative array of transition property options.
   */
  protected function getTransitionPropertyOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('Default (opacity, transform)'),
      'opacity' => $this->t('Opacity only'),
      'transform' => $this->t('Transform only'),
      'all' => $this->t('All properties'),
      'opacity, transform' => $this->t('Opacity and Transform'),
    ];
  }

  /**
   * Returns the available transition duration options.
   *
   * @return array
   *   An associative array of transition duration options.
   */
  protected function getTransitionDurationOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('Default (600ms)'),
      '150ms' => $this->t('150ms'),
      '300ms' => $this->t('300ms'),
      '500ms' => $this->t('500ms'),
      '750ms' => $this->t('750ms'),
      '1s' => $this->t('1s'),
    ];
  }

  /**
   * Returns the available transition timing function options.
   *
   * @return array
   *   An associative array of transition timing function options.
   */
  protected function getTransitionTimingFunctionOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('Default (ease-out)'),
      'ease' => $this->t('ease'),
      'ease-in' => $this->t('ease-in'),
      'ease-in-out' => $this->t('ease-in-out'),
      'linear' => $this->t('linear'),
    ];
  }

  /**
   * Returns the available transition delay options.
   *
   * @return array
   *   An associative array of transition delay options.
   */
  protected function getTransitionDelayOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('None'),
      '150ms' => $this->t('150ms'),
      '300ms' => $this->t('300ms'),
      '500ms' => $this->t('500ms'),
      '750ms' => $this->t('750ms'),
      '1s' => $this->t('1s'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValues();
    $this->configuration['sizing_option'] = $values['sizing_option'];
    $this->configuration['container_type'] = $values['container_type'];

    $this->configuration['horizontal_padding_option'] = $values['spacing']['horizontal_padding_option'];
    $this->configuration['vertical_padding_option'] = $values['spacing']['vertical_padding_option'];
    $this->configuration['gap_option'] = $values['spacing']['gap_option'];
    $this->configuration['horizontal_margin_option'] = $values['spacing']['horizontal_margin_option'];
    $this->configuration['vertical_margin_option'] = $values['spacing']['vertical_margin_option'];

    $this->configuration['background_color'] = $values['colors']['background_color'] ?? self::NONE_OPTION_KEY;
    $this->configuration['foreground_color'] = $values['colors']['foreground_color'] ?? self::NONE_OPTION_KEY;
    $this->configuration['border_color'] = $values['borders']['border_color'] ?? self::NONE_OPTION_KEY;
    $this->configuration['border_width_option'] = $values['borders']['border_width_option'];
    $this->configuration['border_style_option'] = $values['borders']['border_style_option'];
    $this->configuration['border_radius_option'] = $values['borders']['border_radius_option'];

    $this->configuration['animation_type'] = $values['animation']['animation_type'];
    // New save.
    $this->configuration['slide_direction'] = $values['animation']['slide_direction'];
    $this->configuration['transition_property'] = $values['animation']['transition_property'];
    $this->configuration['transition_duration'] = $values['animation']['transition_duration'];
    $this->configuration['transition_timing_function'] = $values['animation']['transition_timing_function'];
    $this->configuration['transition_delay'] = $values['animation']['transition_delay'];
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $build = parent::build($regions);

    $build['#attached']['library'][] = 'kingly_layouts/kingly_utilities';

    $plugin_definition = $this->getPluginDefinition();
    $layout_id = $plugin_definition->id();

    // Add layout-specific sizing class.
    if (!empty($this->configuration['sizing_option'])) {
      $build['#attributes']['class'][] = 'layout--' . $layout_id . '--' . $this->configuration['sizing_option'];
    }

    // Apply container type classes and adjust padding/margin behavior.
    $container_type = $this->configuration['container_type'];
    $h_padding_effective = $this->configuration['horizontal_padding_option'];
    $apply_horizontal_margin = TRUE;

    switch ($container_type) {
      case 'full':
        $build['#attributes']['class'][] = 'kingly-layout--full-width';
        // Padding is handled by the full-width class itself to align content.
        $h_padding_effective = self::NONE_OPTION_KEY;
        $apply_horizontal_margin = FALSE;
        break;

      case 'edge-to-edge':
        $build['#attributes']['class'][] = 'kingly-layout--edge-to-edge';
        // Padding is applied from the viewport edge via utility class.
        $apply_horizontal_margin = FALSE;
        break;
    }

    // Apply spacing utility classes.
    $this->applyClassFromConfig($build, 'kingly-layout-padding-x-', $h_padding_effective);
    $this->applyClassFromConfig($build, 'kingly-layout-padding-y-', 'vertical_padding_option');
    $this->applyClassFromConfig($build, 'kingly-layout-gap-', 'gap_option');
    $this->applyClassFromConfig($build, 'kingly-layout-margin-y-', 'vertical_margin_option');

    // Apply horizontal margin class only if container is boxed.
    if ($apply_horizontal_margin) {
      $this->applyClassFromConfig($build, 'kingly-layout-margin-x-', 'horizontal_margin_option');
    }

    // Apply border radius class.
    $this->applyClassFromConfig($build, 'kingly-layout-border-radius-', 'border_radius_option');

    // Apply background and foreground colors.
    $this->applyStyleFromConfig($build, 'background-color', 'background_color');
    $this->applyStyleFromConfig($build, 'color', 'foreground_color');

    // Apply border styles.
    $border_color_hex = $this->getTermColorHex($this->configuration['border_color']);
    if ($border_color_hex) {
      $build['#attributes']['style'][] = 'border-color: ' . $border_color_hex . ';';

      // Default to 'sm' width and 'solid' style if a color is set but they are
      // not.
      $border_width = $this->configuration['border_width_option'] !== self::NONE_OPTION_KEY ? $this->configuration['border_width_option'] : 'sm';
      $border_style = $this->configuration['border_style_option'] !== self::NONE_OPTION_KEY ? $this->configuration['border_style_option'] : 'solid';

      $this->applyClassFromConfig($build, 'kingly-layout-border-width-', $border_width);
      $this->applyClassFromConfig($build, 'kingly-layout-border-style-', $border_style);
    }

    // Apply animation.
    $animation_type = $this->configuration['animation_type'];
    $slide_direction = $this->configuration['slide_direction'];

    if ($animation_type !== self::NONE_OPTION_KEY) {
      $build['#attached']['library'][] = 'kingly_layouts/kingly_animations';
      $build['#attributes']['class'][] = 'kingly-animate';

      // Apply animation type class.
      $this->applyClassFromConfig($build, 'kingly-animate--', 'animation_type');

      // If it's a slide animation, apply the direction class.
      if ($animation_type === 'slide-in' && $slide_direction !== self::NONE_OPTION_KEY) {
        $this->applyClassFromConfig($build, 'kingly-animate--direction-', 'slide_direction');
      }

      // Apply transition properties as inline styles to override CSS defaults.
      $this->applyInlineStyleFromOption($build, 'transition-property', 'transition_property');
      $this->applyInlineStyleFromOption($build, 'transition-duration', 'transition_duration');
      $this->applyInlineStyleFromOption($build, 'transition-timing-function', 'transition_timing_function');
      $this->applyInlineStyleFromOption($build, 'transition-delay', 'transition_delay');
    }

    return $build;
  }

  /**
   * Helper to apply a CSS class from a configuration value.
   *
   * @param array &$build
   *   The render array.
   * @param string $class_prefix
   *   The prefix for the CSS class.
   * @param string $config_key_or_value
   *   The configuration key or a direct value to use for the class suffix.
   */
  private function applyClassFromConfig(array &$build, string $class_prefix, string $config_key_or_value): void {
    // Check if the provided string is a config key or a direct value.
    $value = $this->configuration[$config_key_or_value] ?? $config_key_or_value;
    if (!empty($value) && $value !== self::NONE_OPTION_KEY) {
      $build['#attributes']['class'][] = $class_prefix . $value;
    }
  }

  /**
   * Helper to apply an inline style from a configuration value.
   *
   * @param array &$build
   *   The render array.
   * @param string $style_property
   *   The CSS property to set.
   * @param string $config_key
   *   The configuration key for the color term ID.
   */
  private function applyStyleFromConfig(array &$build, string $style_property, string $config_key): void {
    $color_hex = $this->getTermColorHex($this->configuration[$config_key]);
    if ($color_hex) {
      $build['#attributes']['style'][] = $style_property . ': ' . $color_hex . ';';
    }
  }

  /**
   * Retrieves the hex color value from a Kingly CSS Color taxonomy term.
   *
   * @param string $term_id
   *   The ID of the taxonomy term.
   *
   * @return string|null
   *   The hex color string if found and valid, NULL otherwise.
   */
  protected function getTermColorHex(string $term_id): ?string {
    if (empty($term_id) || $term_id === self::NONE_OPTION_KEY) {
      return NULL;
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->termStorage->load($term_id);

    if ($term instanceof TermInterface &&
      $term->bundle() === self::KINGLY_CSS_COLOR_VOCABULARY &&
      $term->hasField(self::KINGLY_CSS_COLOR_FIELD) &&
      !$term->get(self::KINGLY_CSS_COLOR_FIELD)->isEmpty()) {
      return $term->get(self::KINGLY_CSS_COLOR_FIELD)->value;
    }

    return NULL;
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
   */
  private function applyInlineStyleFromOption(array &$build, string $style_property, string $config_key): void {
    $value = $this->configuration[$config_key];
    if (!empty($value) && $value !== self::NONE_OPTION_KEY) {
      $build['#attributes']['style'][] = $style_property . ': ' . $value . ';';
    }
  }

}

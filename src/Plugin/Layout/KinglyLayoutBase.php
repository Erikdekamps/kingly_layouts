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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
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

    // Default to no background or foreground color, and no background opacity.
    $configuration['background_color'] = self::NONE_OPTION_KEY;
    $configuration['background_opacity'] = self::NONE_OPTION_KEY;
    $configuration['foreground_color'] = self::NONE_OPTION_KEY;

    // Add default for container type.
    $configuration['container_type'] = 'boxed';

    // Add defaults for border options.
    $configuration['border_radius_option'] = self::NONE_OPTION_KEY;
    $configuration['border_color'] = self::NONE_OPTION_KEY;
    $configuration['border_width_option'] = self::NONE_OPTION_KEY;
    $configuration['border_style_option'] = self::NONE_OPTION_KEY;

    // Add default for vertical alignment.
    $configuration['vertical_alignment'] = 'stretch';

    // Add defaults for animation options.
    $configuration['animation_type'] = self::NONE_OPTION_KEY;
    $configuration['slide_direction'] = self::NONE_OPTION_KEY;
    $configuration['transition_property'] = self::NONE_OPTION_KEY;
    $configuration['transition_duration'] = self::NONE_OPTION_KEY;
    $configuration['transition_timing_function'] = self::NONE_OPTION_KEY;
    $configuration['transition_delay'] = self::NONE_OPTION_KEY;

    // Add defaults for background media options.
    $configuration['background_type'] = 'color';
    $configuration['background_media_url'] = '';
    $configuration['background_media_min_height'] = '';
    $configuration['background_image_position'] = 'center center';
    $configuration['background_image_repeat'] = 'no-repeat';
    $configuration['background_image_size'] = 'cover';
    $configuration['background_image_attachment'] = 'scroll';
    $configuration['background_video_loop'] = FALSE;
    $configuration['background_video_autoplay'] = TRUE;
    $configuration['background_video_muted'] = TRUE;
    $configuration['background_video_preload'] = 'auto';
    $configuration['background_overlay_color'] = self::NONE_OPTION_KEY;
    $configuration['background_overlay_opacity'] = self::NONE_OPTION_KEY;

    // Add defaults for shadows & effects.
    $configuration['box_shadow_option'] = self::NONE_OPTION_KEY;
    $configuration['filter_option'] = self::NONE_OPTION_KEY;

    // Add defaults for responsiveness.
    $configuration['hide_on_breakpoint'] = [];

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
        '#description' => $this->t('This color is used as a fallback if a background image or video is not set.'),
      ];
      // Added new background opacity field.
      $form['colors']['background_opacity'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Opacity'),
        '#options' => $this->getBackgroundOpacityOptions(),
        '#default_value' => $this->configuration['background_opacity'],
        '#description' => $this->t('Set the opacity for the background color. This requires a background color to be selected.'),
        '#states' => [
          'visible' => [
            ':input[name="layout_settings[colors][background_color]"]' => ['!value' => self::NONE_OPTION_KEY],
          ],
        ],
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

    // Alignment.
    $form['alignment'] = [
      '#type' => 'details',
      '#title' => $this->t('Alignment'),
      '#open' => FALSE,
    ];
    $form['alignment']['vertical_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Alignment'),
      '#options' => $this->getVerticalAlignmentOptions(),
      '#default_value' => $this->configuration['vertical_alignment'],
      '#description' => $this->t('Align content vertically within the layout. This assumes the layout uses Flexbox or Grid. "Stretch" makes columns in the same row equal height.'),
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

    // Background Media.
    $form['background_media'] = [
      '#type' => 'details',
      '#title' => $this->t('Background Media'),
      '#open' => FALSE,
    ];
    $form['background_media']['background_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Background Type'),
      '#options' => $this->getBackgroundTypeOptions(),
      '#default_value' => $this->configuration['background_type'],
      '#description' => $this->t('Choose the type of background for this layout section.'),
    ];

    // URL field for background media.
    $form['background_media']['background_media_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Background Media URL'),
      '#default_value' => $this->configuration['background_media_url'],
      '#description' => $this->t('Enter the full, absolute URL for the background image or video (e.g., https://example.com/image.jpg or https://example.com/video.mp4). YouTube or Vimeo URLs are not supported; please use direct links to video files.'),
      '#states' => [
        'visible' => [
          [':input[name="layout_settings[background_media][background_type]"]' => ['value' => 'image']],
          'or',
          [':input[name="layout_settings[background_media][background_type]"]' => ['value' => 'video']],
        ],
      ],
    ];

    // Media min height field.
    $form['background_media']['background_media_min_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Media Minimum Height'),
      '#default_value' => $this->configuration['background_media_min_height'],
      '#description' => $this->t('Set a minimum height for the background media container. Include the unit (e.g., 400px, 50vh, 20rem). Leave blank for default height.'),
      '#states' => [
        'visible' => [
          [':input[name="layout_settings[background_media][background_type]"]' => ['value' => 'image']],
          'or',
          [':input[name="layout_settings[background_media][background_type]"]' => ['value' => 'video']],
        ],
        // Add this new state to hide/disable when container_type is 'hero'.
        'disabled' => [
          ':input[name="layout_settings[container_type]"]' => ['value' => 'hero'],
        ],
      ],
    ];

    // Image settings.
    $form['background_media']['image_settings'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[background_media][background_type]"]' => ['value' => 'image'],
        ],
      ],
    ];
    $form['background_media']['image_settings']['background_image_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Position'),
      '#options' => $this->getBackgroundImagePositionOptions(),
      '#default_value' => $this->configuration['background_image_position'],
      '#description' => $this->t("Select the starting position of the background image. This is most noticeable when the image is not set to 'cover' or 'contain'."),
    ];
    $form['background_media']['image_settings']['background_image_repeat'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Repeat'),
      '#options' => $this->getBackgroundImageRepeatOptions(),
      '#default_value' => $this->configuration['background_image_repeat'],
      '#description' => $this->t('Define if and how the background image should repeat.'),
    ];
    $form['background_media']['image_settings']['background_image_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Size'),
      '#options' => $this->getBackgroundImageSizeOptions(),
      '#default_value' => $this->configuration['background_image_size'],
      '#description' => $this->t("'Cover' will fill the entire area, potentially cropping the image. 'Contain' will show the entire image, potentially leaving empty space."),
    ];
    $form['background_media']['image_settings']['background_image_attachment'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Attachment'),
      '#options' => $this->getBackgroundImageAttachmentOptions(),
      '#default_value' => $this->configuration['background_image_attachment'],
      '#description' => $this->t("Define how the background image behaves when scrolling. 'Fixed' creates a parallax-like effect."),
    ];
    // Video settings.
    $form['background_media']['video_settings'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[background_media][background_type]"]' => ['value' => 'video'],
        ],
      ],
    ];
    $form['background_media']['video_settings']['background_video_loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop video'),
      '#default_value' => $this->configuration['background_video_loop'],
      '#description' => $this->t('If checked, the video will automatically restart from the beginning after it ends.'),
    ];
    $form['background_media']['video_settings']['background_video_autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay video'),
      '#default_value' => $this->configuration['background_video_autoplay'],
      '#description' => $this->t('If checked, the video will attempt to play automatically. For this to work reliably across browsers, the video must also be muted.'),
    ];
    $form['background_media']['video_settings']['background_video_muted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mute video'),
      '#default_value' => $this->configuration['background_video_muted'],
      '#description' => $this->t("If checked, the video's audio will be muted. This is required for autoplay to work in most modern browsers."),
    ];
    $form['background_media']['video_settings']['background_video_preload'] = [
      '#type' => 'select',
      '#title' => $this->t('Preload video'),
      '#options' => [
        'auto' => $this->t('Auto'),
        'metadata' => $this->t('Metadata only'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $this->configuration['background_video_preload'],
      '#description' => $this->t('Specifies if and how the video should be loaded when the page loads. The "preload" attribute is often ignored if "Autoplay" is enabled, but setting it to "Auto" is still best practice.'),
    ];
    // Overlay settings.
    $form['background_media']['overlay_settings'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          [':input[name="layout_settings[background_media][background_type]"]' => ['value' => 'image']],
          'or',
          [':input[name="layout_settings[background_media][background_type]"]' => ['value' => 'video']],
        ],
      ],
    ];
    $form['background_media']['overlay_settings']['background_overlay_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Overlay Color'),
      '#options' => $this->getColorOptions(),
      '#default_value' => $this->configuration['background_overlay_color'],
      '#description' => $this->t('Select a color for the overlay. The overlay sits on top of the background image or video, but behind the content.'),
    ];
    $form['background_media']['overlay_settings']['background_overlay_opacity'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Overlay Opacity'),
      '#options' => $this->getBackgroundOverlayOpacityOptions(),
      '#default_value' => $this->configuration['background_overlay_opacity'],
      '#description' => $this->t('Set the opacity for the overlay color. This requires an overlay color to be selected.'),
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[background_media][overlay_settings][background_overlay_color]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];

    // Shadows & Effects.
    $form['shadows_effects'] = [
      '#type' => 'details',
      '#title' => $this->t('Shadows & Effects'),
      '#open' => FALSE,
    ];
    $form['shadows_effects']['box_shadow_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Box Shadow'),
      '#options' => $this->getBoxShadowOptions(),
      '#default_value' => $this->configuration['box_shadow_option'],
    ];
    $form['shadows_effects']['filter_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter'),
      '#options' => $this->getFilterOptions(),
      '#default_value' => $this->configuration['filter_option'],
    ];

    // Responsiveness.
    $form['responsiveness'] = [
      '#type' => 'details',
      '#title' => $this->t('Responsiveness'),
      '#open' => FALSE,
    ];
    $form['responsiveness']['hide_on_breakpoint'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Hide on Breakpoint'),
      '#options' => $this->getHideOnBreakpointOptions(),
      '#default_value' => $this->configuration['hide_on_breakpoint'],
      '#description' => $this->t('Hide this entire layout section on specific screen sizes.'),
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
      'hero' => $this->t('Full Screen Hero'),
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

    $options = [
      self::NONE_OPTION_KEY => $this->t('None'),
    ];

    // The vocabulary config entity itself is a cache dependency.
    $cache_tags = ['config:taxonomy.vocabulary.' . self::KINGLY_CSS_COLOR_VOCABULARY];

    if ($this->entityTypeManager->getStorage('taxonomy_vocabulary')->load(self::KINGLY_CSS_COLOR_VOCABULARY)) {
      // The list of terms in the vocabulary is also a cache dependency.
      $cache_tags[] = 'taxonomy_term_list:' . self::KINGLY_CSS_COLOR_VOCABULARY;
      $terms = $this->termStorage->loadTree(self::KINGLY_CSS_COLOR_VOCABULARY, 0, NULL, TRUE);
      foreach ($terms as $term) {
        $options[$term->id()] = $term->getName();
      }
    }

    $this->cache->set($cid, $options, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);

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
   * Returns the available vertical alignment options.
   *
   * @return array
   *   An associative array of vertical alignment options.
   */
  protected function getVerticalAlignmentOptions(): array {
    return [
      'stretch' => $this->t('Stretch (Default)'),
      'flex-start' => $this->t('Top'),
      'center' => $this->t('Middle'),
      'flex-end' => $this->t('Bottom'),
      'baseline' => $this->t('Baseline'),
    ];
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
   * Returns the available background type options.
   *
   * @return array
   *   An associative array of background type options.
   */
  protected function getBackgroundTypeOptions(): array {
    return [
      'color' => $this->t('Color'),
      'image' => $this->t('Image'),
      'video' => $this->t('Video'),
    ];
  }

  /**
   * Returns the available background image position options.
   *
   * @return array
   *   An associative array of background image position options.
   */
  protected function getBackgroundImagePositionOptions(): array {
    return [
      'center center' => $this->t('Center Center'),
      'center top' => $this->t('Center Top'),
      'center bottom' => $this->t('Center Bottom'),
      'left top' => $this->t('Left Top'),
      'left center' => $this->t('Left Center'),
      'left bottom' => $this->t('Left Bottom'),
      'right top' => $this->t('Right Top'),
      'right center' => $this->t('Right Center'),
      'right bottom' => $this->t('Right Bottom'),
    ];
  }

  /**
   * Returns the available background image repeat options.
   *
   * @return array
   *   An associative array of background image repeat options.
   */
  protected function getBackgroundImageRepeatOptions(): array {
    return [
      'no-repeat' => $this->t('No Repeat'),
      'repeat' => $this->t('Repeat'),
      'repeat-x' => $this->t('Repeat Horizontally'),
      'repeat-y' => $this->t('Repeat Vertically'),
    ];
  }

  /**
   * Returns the available background image size options.
   *
   * @return array
   *   An associative array of background image size options.
   */
  protected function getBackgroundImageSizeOptions(): array {
    return [
      'cover' => $this->t('Cover'),
      'contain' => $this->t('Contain'),
      'auto' => $this->t('Auto'),
    ];
  }

  /**
   * Returns the available background image attachment options.
   *
   * @return array
   *   An associative array of background image attachment options.
   */
  protected function getBackgroundImageAttachmentOptions(): array {
    return [
      'scroll' => $this->t('Scroll'),
      'fixed' => $this->t('Fixed (Parallax)'),
      'local' => $this->t('Local'),
    ];
  }

  /**
   * Returns the available background overlay opacity options.
   *
   * @return array
   *   An associative array of opacity options.
   */
  protected function getBackgroundOverlayOpacityOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('None'),
      '25' => $this->t('25%'),
      '50' => $this->t('50%'),
      '75' => $this->t('75%'),
      '90' => $this->t('90%'),
    ];
  }

  /**
   * Returns the available background opacity options.
   *
   * @return array
   *   An associative array of opacity options.
   */
  protected function getBackgroundOpacityOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('100% (Default)'),
      '90' => $this->t('90%'),
      '75' => $this->t('75%'),
      '50' => $this->t('50%'),
      '25' => $this->t('25%'),
      '0' => $this->t('0% (Transparent)'),
    ];
  }

  /**
   * Returns the available box shadow options.
   *
   * @return array
   *   An associative array of box shadow options.
   */
  protected function getBoxShadowOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('None'),
      'sm' => $this->t('Small'),
      'md' => $this->t('Medium'),
      'lg' => $this->t('Large'),
      'xl' => $this->t('Extra Large'),
      'inner' => $this->t('Inner'),
    ];
  }

  /**
   * Returns the available filter options.
   *
   * @return array
   *   An associative array of filter options.
   */
  protected function getFilterOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('None'),
      'grayscale' => $this->t('Grayscale'),
      'blur' => $this->t('Blur'),
      'sepia' => $this->t('Sepia'),
      'brightness' => $this->t('Brightness'),
    ];
  }

  /**
   * Returns the available breakpoint visibility options.
   *
   * @return array
   *   An associative array of breakpoint options.
   */
  protected function getHideOnBreakpointOptions(): array {
    return [
      'mobile' => $this->t('Mobile'),
      'tablet' => $this->t('Tablet'),
      'desktop' => $this->t('Desktop'),
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
    $this->configuration['background_opacity'] = $values['colors']['background_opacity'] ?? self::NONE_OPTION_KEY;
    $this->configuration['foreground_color'] = $values['colors']['foreground_color'] ?? self::NONE_OPTION_KEY;
    $this->configuration['border_color'] = $values['borders']['border_color'] ?? self::NONE_OPTION_KEY;
    $this->configuration['border_width_option'] = $values['borders']['border_width_option'];
    $this->configuration['border_style_option'] = $values['borders']['border_style_option'];
    $this->configuration['border_radius_option'] = $values['borders']['border_radius_option'];

    $this->configuration['vertical_alignment'] = $values['alignment']['vertical_alignment'];

    $this->configuration['animation_type'] = $values['animation']['animation_type'];
    $this->configuration['slide_direction'] = $values['animation']['slide_direction'];
    $this->configuration['transition_property'] = $values['animation']['transition_property'];
    $this->configuration['transition_duration'] = $values['animation']['transition_duration'];
    $this->configuration['transition_timing_function'] = $values['animation']['transition_timing_function'];
    $this->configuration['transition_delay'] = $values['animation']['transition_delay'];

    // Background Media.
    $this->configuration['background_type'] = $values['background_media']['background_type'];
    $this->configuration['background_media_url'] = $values['background_media']['background_media_url'] ?? '';

    // Clear background_media_min_height if container type is 'hero'.
    if ($values['container_type'] === 'hero') {
      $this->configuration['background_media_min_height'] = '';
    }
    else {
      $this->configuration['background_media_min_height'] = $values['background_media']['background_media_min_height'] ?? '';
    }

    $this->configuration['background_image_position'] = $values['background_media']['image_settings']['background_image_position'];
    $this->configuration['background_image_repeat'] = $values['background_media']['image_settings']['background_image_repeat'];
    $this->configuration['background_image_size'] = $values['background_media']['image_settings']['background_image_size'];
    $this->configuration['background_image_attachment'] = $values['background_media']['image_settings']['background_image_attachment'];
    $this->configuration['background_video_loop'] = $values['background_media']['video_settings']['background_video_loop'];
    $this->configuration['background_video_autoplay'] = $values['background_media']['video_settings']['background_video_autoplay'];
    $this->configuration['background_video_muted'] = $values['background_media']['video_settings']['background_video_muted'];
    $this->configuration['background_video_preload'] = $values['background_media']['video_settings']['background_video_preload'];
    $this->configuration['background_overlay_color'] = $values['background_media']['overlay_settings']['background_overlay_color'];
    $this->configuration['background_overlay_opacity'] = $values['background_media']['overlay_settings']['background_overlay_opacity'];

    // Shadows & Effects.
    $this->configuration['box_shadow_option'] = $values['shadows_effects']['box_shadow_option'];
    $this->configuration['filter_option'] = $values['shadows_effects']['filter_option'];

    // Responsiveness.
    $this->configuration['hide_on_breakpoint'] = array_filter($values['responsiveness']['hide_on_breakpoint']);
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
      case 'boxed':
        // No specific class for boxed, it's the default.
        break;

      case 'full':
        $build['#attributes']['class'][] = 'kingly-layout--full-width';
        // For "Full Width (Background Only)", we keep the horizontal padding
        // to constrain the content within the full-width background.
        $apply_horizontal_margin = FALSE;
        break;

      case 'edge-to-edge':
        $build['#attributes']['class'][] = 'kingly-layout--edge-to-edge';
        // For "Edge to Edge", we remove the horizontal padding so that the
        // content can truly span the full bleed area.
        $h_padding_effective = self::NONE_OPTION_KEY;
        $apply_horizontal_margin = FALSE;
        break;

      case 'hero':
        $build['#attributes']['class'][] = 'kingly-layout--hero';
        // Typically, heroes are edge-to-edge.
        $h_padding_effective = self::NONE_OPTION_KEY;
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

    // Apply alignment class.
    $this->applyClassFromConfig($build, 'kingly-layout-align-content-', 'vertical_alignment');

    // Apply border radius class.
    $this->applyClassFromConfig($build, 'kingly-layout-border-radius-', 'border_radius_option');

    // Apply background media. This must come before background color.
    $this->applyBackgroundMedia($build);

    // Apply shadows & effects classes.
    $this->applyClassFromConfig($build, 'kingly-layout-shadow-', 'box_shadow_option');
    $this->applyClassFromConfig($build, 'kingly-layout-filter-', 'filter_option');

    // Apply responsiveness classes.
    if (!empty($this->configuration['hide_on_breakpoint'])) {
      foreach ($this->configuration['hide_on_breakpoint'] as $breakpoint) {
        if ($breakpoint) {
          $build['#attributes']['class'][] = 'kingly-layout-hide-on-' . $breakpoint;
        }
      }
    }

    // Apply background color with opacity.
    $background_color_term_id = $this->configuration['background_color'];
    $background_color_hex = $this->getTermColorHex($background_color_term_id);

    if ($background_color_hex) {
      $background_opacity_value = $this->configuration['background_opacity'];

      // If an opacity is selected (and it's not the 'None' default)
      if ($background_opacity_value !== self::NONE_OPTION_KEY) {
        $rgb = $this->hexToRgb($background_color_hex);
        if ($rgb) {
          $alpha = (float) $background_opacity_value / 100;
          $build['#attributes']['style'][] = 'background-color: rgba(' . $rgb[0] . ', ' . $rgb[1] . ', ' . $rgb[2] . ', ' . $alpha . ');';
        }
        else {
          // Fallback to hex if RGB conversion fails.
          $build['#attributes']['style'][] = 'background-color: ' . $background_color_hex . ';';
        }
      }
      else {
        // No specific opacity selected, use the hex color directly.
        $build['#attributes']['style'][] = 'background-color: ' . $background_color_hex . ';';
      }
    }

    // Apply foreground color.
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
   * Applies background media styles and elements to the build array.
   *
   * @param array &$build
   *   The render array.
   */
  private function applyBackgroundMedia(array &$build): void {
    $background_type = $this->configuration['background_type'];
    $media_url = $this->configuration['background_media_url'];
    $min_height = $this->configuration['background_media_min_height'];

    // Apply min-height if set for a media background.
    if (!empty($min_height)) {
      if ($background_type === 'image' || $background_type === 'video') {
        $build['#attributes']['style'][] = 'min-height: ' . $min_height . ';';
      }
    }

    if (!empty($media_url)) {
      // Handle background image.
      if ($background_type === 'image') {
        // Set all background image properties as inline styles for consistency.
        $build['#attributes']['style'][] = 'background-image: url("' . $media_url . '");';
        $this->applyInlineStyleFromOption($build, 'background-position', 'background_image_position');
        $this->applyInlineStyleFromOption($build, 'background-repeat', 'background_image_repeat');
        $this->applyInlineStyleFromOption($build, 'background-size', 'background_image_size');
        $this->applyInlineStyleFromOption($build, 'background-attachment', 'background_image_attachment');
      }
      // Handle background video.
      elseif ($background_type === 'video') {
        $build['#attributes']['class'][] = 'kingly-layout--has-bg-video';

        // Prepend the video element to the build array.
        $build['video_background'] = [
          '#theme' => 'kingly_background_video',
          '#video_url' => $media_url,
          '#loop' => $this->configuration['background_video_loop'],
          '#autoplay' => $this->configuration['background_video_autoplay'],
          '#muted' => $this->configuration['background_video_muted'],
          '#preload' => $this->configuration['background_video_preload'],
          '#weight' => -100,
        ];
      }
    }

    // Handle overlay for both image and video, if a URL is provided.
    if (!empty($media_url) && ($background_type === 'image' || $background_type === 'video')) {
      $overlay_color_hex = $this->getTermColorHex($this->configuration['background_overlay_color']);
      $overlay_opacity_value = $this->configuration['background_overlay_opacity'];

      if ($overlay_color_hex && $overlay_opacity_value !== self::NONE_OPTION_KEY) {
        $build['#attributes']['class'][] = 'kingly-layout--has-bg-overlay';
        // Prepend the overlay element.
        $build['overlay'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['kingly-layout__bg-overlay'],
            'style' => [
              'background-color: ' . $overlay_color_hex . ';',
              'opacity: ' . ($overlay_opacity_value / 100) . ';',
            ],
          ],
          '#weight' => -99,
        ];
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
   */
  private function applyInlineStyleFromOption(array &$build, string $style_property, string $config_key): void {
    $value = $this->configuration[$config_key];
    if (!empty($value) && $value !== self::NONE_OPTION_KEY) {
      $build['#attributes']['style'][] = $style_property . ': ' . $value . ';';
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
   * Converts a hex color string to an RGB array.
   *
   * @param string $hex
   *   The hex color string (e.g., "#RRGGBB" or "RRGGBB").
   *
   * @return array|null
   *   An array [R, G, B] if successful, NULL otherwise.
   */
  protected function hexToRgb(string $hex): ?array {
    $hex = ltrim($hex, '#');

    if (strlen($hex) === 3) {
      $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
      $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
      $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
    }
    elseif (strlen($hex) === 6) {
      $r = hexdec(substr($hex, 0, 2));
      $g = hexdec(substr($hex, 2, 2));
      $b = hexdec(substr($hex, 4, 2));
    }
    else {
      // Invalid hex format.
      return NULL;
    }

    return [$r, $g, $b];
  }

  /**
   * Helper to apply an inline style from a configuration value.
   *
   * This method is now primarily used for foreground and border colors,
   * as background color with opacity is handled directly in build().
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

}

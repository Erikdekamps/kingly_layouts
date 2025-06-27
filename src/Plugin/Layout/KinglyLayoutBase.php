<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache_backend, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $this->cache = $cache_backend;
    $this->currentUser = $current_user;
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
      $container->get('cache.default'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Get color options once, as it's used in multiple places.
    $color_options = $this->getColorOptions();

    // Column Sizing (now at the top level).
    $form['sizing_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Column sizing'),
      '#options' => $this->getSizingOptions(),
      '#default_value' => $this->configuration['sizing_option'],
      '#description' => $this->t('Select the desired column width distribution.'),
      '#weight' => -10,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts sizing'),
    ];

    // Container Type (now at the top level).
    $form['container_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Container Type'),
      '#options' => $this->getOptions('container_type'),
      '#default_value' => $this->configuration['container_type'],
      '#description' => $this->t("Select how the layout container should behave: <br> <strong>Boxed:</strong> Standard container with a maximum width. <br> <strong>Full Width (Background Only):</strong> The background spans the full viewport width, but the content remains aligned with the site's main content area. Horizontal padding will be applied *within* this content area. <br> <strong>Edge to Edge (Full Bleed):</strong> Both the background and content span the full viewport width. <br> <strong>Full Screen Hero:</strong> The section fills the entire viewport height and width."),
      '#weight' => -9,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts container type'),
    ];

    // Spacing.
    $form['spacing'] = [
      '#type' => 'details',
      '#title' => $this->t('Spacing'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts spacing'),
    ];
    $form['spacing']['horizontal_padding_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Padding'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $this->configuration['horizontal_padding_option'],
      '#description' => $this->t('Select the horizontal padding for the layout. For "Full Width (Background Only)" layouts, this padding is added to the default content alignment. For "Edge to Edge" layouts, this padding is applied from the viewport edge.'),
    ];
    $form['spacing']['vertical_padding_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Padding'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $this->configuration['vertical_padding_option'],
      '#description' => $this->t('Select the desired vertical padding (top and bottom) for the layout container.'),
    ];
    $form['spacing']['gap_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Gap'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $this->configuration['gap_option'],
      '#description' => $this->t('Select the desired gap between layout columns/regions.'),
    ];
    $form['spacing']['horizontal_margin_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Margin'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $this->configuration['horizontal_margin_option'],
      '#description' => $this->t('Select the horizontal margin for the layout. This margin will not be applied if "Full Width" or "Edge to Edge" is selected.'),
    ];
    $form['spacing']['vertical_margin_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Margin'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $this->configuration['vertical_margin_option'],
      '#description' => $this->t('Select the desired vertical margin (top and bottom) for the layout container.'),
    ];

    // Colors.
    $form['colors'] = [
      '#type' => 'details',
      '#title' => $this->t('Colors'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts colors'),
    ];
    if (count($color_options) > 1) {
      // Background color and opacity moved to 'background' group.
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
        '#default_value' => $this->configuration['border_color'],
        '#description' => $this->t('Selecting a color will enable the border options below.'),
      ];
    }
    $form['border']['border_width_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Width'),
      '#options' => $this->getOptions('border_width'),
      '#default_value' => $this->configuration['border_width_option'],
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[border][border_color]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];
    $form['border']['border_style_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Style'),
      '#options' => $this->getOptions('border_style'),
      '#default_value' => $this->configuration['border_style_option'],
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[border][border_color]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];
    $form['border']['border_radius_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Border Radius'),
      '#options' => $this->getOptions('border_radius'),
      '#default_value' => $this->configuration['border_radius_option'],
    ];

    // Alignment.
    $form['alignment'] = [
      '#type' => 'details',
      '#title' => $this->t('Alignment'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts alignment'),
    ];
    $form['alignment']['vertical_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Alignment'),
      '#options' => $this->getOptions('vertical_alignment'),
      '#default_value' => $this->configuration['vertical_alignment'],
      '#description' => $this->t('Align content vertically within the layout. This assumes the layout uses Flexbox or Grid. "Stretch" makes columns in the same row equal height.'),
    ];

    // Animation Options.
    $form['animation'] = [
      '#type' => 'details',
      '#title' => $this->t('Animation'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts animation'),
    ];
    $form['animation']['animation_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Animation Type'),
      '#options' => $this->getOptions('animation_type'),
      '#default_value' => $this->configuration['animation_type'],
      '#description' => $this->t('Select an animation to apply when the layout scrolls into view. This defines the start and end states.'),
    ];
    $form['animation']['slide_direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide Direction'),
      '#options' => $this->getOptions('slide_direction'),
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
      '#options' => $this->getOptions('transition_property'),
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
      '#options' => $this->getOptions('transition_duration'),
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
      '#options' => $this->getOptions('transition_timing_function'),
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
      '#options' => $this->getOptions('transition_delay'),
      '#default_value' => $this->configuration['transition_delay'],
      '#description' => $this->t('The delay before the animation starts.'),
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[animation][animation_type]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];

    // Background.
    $form['background'] = [
      '#type' => 'details',
      '#title' => $this->t('Background'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts background'),
    ];

    // --- Main Type Selector ---
    $form['background']['background_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Background Type'),
      '#options' => $this->getOptions('background_type'),
      '#default_value' => $this->configuration['background_type'],
      '#description' => $this->t('Choose the type of background for this layout section.'),
    ];

    // --- Color Settings ---
    $form['background']['color_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Color Options'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[background][background_type]"]' => ['value' => 'color'],
        ],
      ],
    ];
    if (count($color_options) > 1) {
      $form['background']['color_settings']['background_color'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Color'),
        '#options' => $color_options,
        '#default_value' => $this->configuration['background_color'],
      ];
      $form['background']['color_settings']['background_opacity'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Opacity'),
        '#options' => $this->getOptions('background_opacity'),
        '#default_value' => $this->configuration['background_opacity'],
        '#description' => $this->t('Set the opacity for the background color. This requires a background color to be selected.'),
        '#states' => [
          'visible' => [
            ':input[name="layout_settings[background][color_settings][background_color]"]' => ['!value' => self::NONE_OPTION_KEY],
          ],
        ],
      ];
    }

    // --- Overlay Settings (Common for Image, Video, Gradient) ---
    $form['background']['overlay_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Background Overlay'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          [':input[name="layout_settings[background][background_type]"]' => ['value' => 'image']],
          'or',
          [':input[name="layout_settings[background][background_type]"]' => ['value' => 'video']],
          'or',
          [':input[name="layout_settings[background][background_type]"]' => ['value' => 'gradient']],
        ],
      ],
    ];
    $form['background']['overlay_settings']['background_overlay_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Color'),
      '#options' => $color_options,
      '#default_value' => $this->configuration['background_overlay_color'],
      '#description' => $this->t('Select a color for the overlay. The overlay sits on top of the background media, but behind the content.'),
    ];
    $form['background']['overlay_settings']['background_overlay_opacity'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Opacity'),
      '#options' => $this->getOptions('background_overlay_opacity'),
      '#default_value' => $this->configuration['background_overlay_opacity'],
      '#description' => $this->t('Set the opacity for the overlay color. This requires an overlay color to be selected.'),
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[background][overlay_settings][background_overlay_color]"]' => ['!value' => self::NONE_OPTION_KEY],
        ],
      ],
    ];

    // --- Image Settings ---
    $form['background']['image_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Image Options'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[background][background_type]"]' => ['value' => 'image'],
        ],
      ],
    ];
    $form['background']['image_settings']['background_media_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Image URL'),
      '#default_value' => $this->configuration['background_media_url'],
      '#description' => $this->t('Enter the full, absolute URL for the background image.'),
    ];
    $form['background']['image_settings']['background_media_min_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum Height'),
      '#default_value' => $this->configuration['background_media_min_height'],
      '#description' => $this->t('Set a minimum height for the section. Include the unit (e.g., 400px, 50vh). Leave blank for default height.'),
      '#states' => [
        'disabled' => [
          ':input[name="layout_settings[container_type]"]' => ['value' => 'hero'],
        ],
      ],
    ];
    $form['background']['image_settings']['background_image_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Position'),
      '#options' => $this->getOptions('background_image_position'),
      '#default_value' => $this->configuration['background_image_position'],
      '#description' => $this->t("Select the starting position of the background image. This is most noticeable when the image is not set to 'cover' or 'contain'."),
    ];
    $form['background']['image_settings']['background_image_repeat'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Repeat'),
      '#options' => $this->getOptions('background_image_repeat'),
      '#default_value' => $this->configuration['background_image_repeat'],
      '#description' => $this->t('Define if and how the background image should repeat.'),
    ];
    $form['background']['image_settings']['background_image_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Size'),
      '#options' => $this->getOptions('background_image_size'),
      '#default_value' => $this->configuration['background_image_size'],
      '#description' => $this->t("'Cover' will fill the entire area, potentially cropping the image. 'Contain' will show the entire image, potentially leaving empty space."),
    ];
    $form['background']['image_settings']['background_image_attachment'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Attachment'),
      '#options' => $this->getOptions('background_image_attachment'),
      '#default_value' => $this->configuration['background_image_attachment'],
      '#description' => $this->t("Define how the background image behaves when scrolling. 'Fixed' creates a parallax-like effect."),
    ];

    // --- Video Settings ---
    $form['background']['video_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Video Options'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[background][background_type]"]' => ['value' => 'video'],
        ],
      ],
    ];
    $form['background']['video_settings']['background_media_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Video URL'),
      '#default_value' => $this->configuration['background_media_url'],
      '#description' => $this->t('Enter the full, absolute URL for the video file (e.g., https://example.com/video.mp4). YouTube or Vimeo URLs are not supported.'),
    ];
    $form['background']['video_settings']['background_media_min_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum Height'),
      '#default_value' => $this->configuration['background_media_min_height'],
      '#description' => $this->t('Set a minimum height for the section. Include the unit (e.g., 400px, 50vh). Leave blank for default height.'),
      '#states' => [
        'disabled' => [
          ':input[name="layout_settings[container_type]"]' => ['value' => 'hero'],
        ],
      ],
    ];
    $form['background']['video_settings']['background_video_loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop video'),
      '#default_value' => $this->configuration['background_video_loop'],
    ];
    $form['background']['video_settings']['background_video_autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay video'),
      '#default_value' => $this->configuration['background_video_autoplay'],
    ];
    $form['background']['video_settings']['background_video_muted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mute video'),
      '#default_value' => $this->configuration['background_video_muted'],
    ];
    $form['background']['video_settings']['background_video_preload'] = [
      '#type' => 'select',
      '#title' => $this->t('Preload video'),
      '#options' => [
        'auto' => $this->t('Auto'),
        'metadata' => $this->t('Metadata only'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $this->configuration['background_video_preload'],
    ];

    // --- Gradient Settings ---
    $form['background']['gradient_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Gradient Options'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[background][background_type]"]' => ['value' => 'gradient'],
        ],
      ],
    ];
    $form['background']['gradient_settings']['background_media_min_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum Height'),
      '#default_value' => $this->configuration['background_media_min_height'],
      '#description' => $this->t('Set a minimum height for the section. Include the unit (e.g., 400px, 50vh). Leave blank for default height.'),
      '#states' => [
        'disabled' => [
          ':input[name="layout_settings[container_type]"]' => ['value' => 'hero'],
        ],
      ],
    ];
    $form['background']['gradient_settings']['background_gradient_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Gradient Type'),
      '#options' => $this->getOptions('background_gradient_type'),
      '#default_value' => $this->configuration['background_gradient_type'],
    ];
    $form['background']['gradient_settings']['background_gradient_start_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Start Color'),
      '#options' => $color_options,
      '#default_value' => $this->configuration['background_gradient_start_color'],
    ];
    $form['background']['gradient_settings']['background_gradient_end_color'] = [
      '#type' => 'select',
      '#title' => $this->t('End Color'),
      '#options' => $color_options,
      '#default_value' => $this->configuration['background_gradient_end_color'],
    ];
    $form['background']['gradient_settings']['linear_gradient_settings'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[background][gradient_settings][background_gradient_type]"]' => ['value' => 'linear'],
        ],
      ],
    ];
    $form['background']['gradient_settings']['linear_gradient_settings']['background_gradient_linear_direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Direction'),
      '#options' => $this->getOptions('background_gradient_linear_direction'),
      '#default_value' => $this->configuration['background_gradient_linear_direction'],
    ];
    $form['background']['gradient_settings']['radial_gradient_settings'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[background][gradient_settings][background_gradient_type]"]' => ['value' => 'radial'],
        ],
      ],
    ];
    $form['background']['gradient_settings']['radial_gradient_settings']['background_gradient_radial_shape'] = [
      '#type' => 'select',
      '#title' => $this->t('Shape'),
      '#options' => $this->getOptions('background_gradient_radial_shape'),
      '#default_value' => $this->configuration['background_gradient_radial_shape'],
    ];
    $form['background']['gradient_settings']['radial_gradient_settings']['background_gradient_radial_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => $this->getOptions('background_gradient_radial_position'),
      '#default_value' => $this->configuration['background_gradient_radial_position'],
    ];

    // Shadows & Effects.
    $form['shadows_effects'] = [
      '#type' => 'details',
      '#title' => $this->t('Shadows & Effects'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts shadows effects'),
    ];
    $form['shadows_effects']['box_shadow_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Box Shadow'),
      '#options' => $this->getOptions('box_shadow'),
      '#default_value' => $this->configuration['box_shadow_option'],
    ];
    $form['shadows_effects']['filter_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter'),
      '#options' => $this->getOptions('filter'),
      '#default_value' => $this->configuration['filter_option'],
    ];
    // New fields for Shadows & Effects.
    $form['shadows_effects']['opacity_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Opacity'),
      '#options' => $this->getOptions('opacity'),
      '#default_value' => $this->configuration['opacity_option'],
      '#description' => $this->t('Adjust the overall transparency of the layout section.'),
    ];
    $form['shadows_effects']['transform_scale_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Scale'),
      '#options' => $this->getOptions('transform_scale'),
      '#default_value' => $this->configuration['transform_scale_option'],
      '#description' => $this->t('Scale the size of the layout section.'),
    ];
    $form['shadows_effects']['transform_rotate_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Rotate'),
      '#options' => $this->getOptions('transform_rotate'),
      '#default_value' => $this->configuration['transform_rotate_option'],
      '#description' => $this->t('Rotate the layout section.'),
    ];

    // Responsiveness.
    $form['responsiveness'] = [
      '#type' => 'details',
      '#title' => $this->t('Responsiveness'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts responsiveness'),
    ];
    $form['responsiveness']['hide_on_breakpoint'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Hide on Breakpoint'),
      '#options' => $this->getOptions('hide_on_breakpoint'),
      '#default_value' => $this->configuration['hide_on_breakpoint'],
      '#description' => $this->t('Hide this entire layout section on specific screen sizes.'),
    ];

    // Custom Attributes.
    $form['custom_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom Attributes'),
      '#open' => FALSE,
      '#weight' => 100,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts custom attributes'),
    ];
    $form['custom_attributes']['custom_css_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom ID'),
      '#default_value' => $this->configuration['custom_css_id'],
      '#description' => $this->t('Enter a unique ID for this layout section (e.g., `my-unique-section`). Must be unique on the page and contain only letters, numbers, hyphens, and underscores.'),
    ];
    $form['custom_attributes']['custom_css_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom CSS Classes'),
      '#default_value' => $this->configuration['custom_css_class'],
      '#description' => $this->t('Add one or more custom CSS classes to this layout section, separated by spaces (e.g., `my-custom-class another-class`).'),
    ];

    return $form;
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

    if ($this->entityTypeManager->getStorage('taxonomy_vocabulary')
      ->load(self::KINGLY_CSS_COLOR_VOCABULARY)) {
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
   * Returns the available sizing options for this layout.
   *
   * @return array
   *   An associative array of sizing options.
   */
  abstract protected function getSizingOptions(): array;

  /**
   * Returns an array of options for a given key.
   *
   * This method consolidates many small get...Options() methods into one.
   *
   * @param string $key
   *   The key for the options array to retrieve.
   *
   * @return array
   *   An associative array of options.
   */
  private function getOptions(string $key): array {
    $none = [self::NONE_OPTION_KEY => $this->t('None')];

    // Special case for border_radius which combines scale options with another.
    if ($key === 'border_radius') {
      return $this->getScaleOptions() + ['full' => $this->t('Full (Pill/Circle)')];
    }

    $options = [
      'container_type' => [
        'boxed' => $this->t('Boxed'),
        'full' => $this->t('Full Width (Background Only)'),
        'edge-to-edge' => $this->t('Edge to Edge (Full Bleed)'),
        'hero' => $this->t('Full Screen Hero'),
      ],
      'border_width' => $none + [
        'sm' => $this->t('Small (1px)'),
        'md' => $this->t('Medium (2px)'),
        'lg' => $this->t('Large (4px)'),
      ],
      'border_style' => $none + [
        'solid' => $this->t('Solid'),
        'dashed' => $this->t('Dashed'),
        'dotted' => $this->t('Dotted'),
      ],
      'vertical_alignment' => [
        'stretch' => $this->t('Stretch'),
        'flex-start' => $this->t('Top'),
        'center' => $this->t('Middle (Default)'),
        'flex-end' => $this->t('Bottom'),
        'baseline' => $this->t('Baseline'),
      ],
      'animation_type' => $none + [
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
      'background_type' => [
        'color' => $this->t('Color'),
        'image' => $this->t('Image'),
        'video' => $this->t('Video'),
        'gradient' => $this->t('Gradient'),
      ],
      'background_opacity' => [
        self::NONE_OPTION_KEY => $this->t('100% (Default)'),
        '90' => $this->t('90%'),
        '75' => $this->t('75%'),
        '50' => $this->t('50%'),
        '25' => $this->t('25%'),
        '0' => $this->t('0% (Transparent)'),
      ],
      'background_overlay_opacity' => $none + [
        '25' => $this->t('25%'),
        '50' => $this->t('50%'),
        '75' => $this->t('75%'),
        '90' => $this->t('90%'),
      ],
      'background_image_position' => [
        'center center' => $this->t('Center Center'),
        'center top' => $this->t('Center Top'),
        'center bottom' => $this->t('Center Bottom'),
        'left top' => $this->t('Left Top'),
        'left center' => $this->t('Left Center'),
        'left bottom' => $this->t('Left Bottom'),
        'right top' => $this->t('Right Top'),
        'right center' => $this->t('Right Center'),
        'right bottom' => $this->t('Right Bottom'),
      ],
      'background_image_repeat' => [
        'no-repeat' => $this->t('No Repeat'),
        'repeat' => $this->t('Repeat'),
        'repeat-x' => $this->t('Repeat Horizontally'),
        'repeat-y' => $this->t('Repeat Vertically'),
      ],
      'background_image_size' => [
        'cover' => $this->t('Cover'),
        'contain' => $this->t('Contain'),
        'auto' => $this->t('Auto'),
      ],
      'background_image_attachment' => [
        'scroll' => $this->t('Scroll'),
        'fixed' => $this->t('Fixed (Parallax)'),
        'local' => $this->t('Local'),
      ],
      'background_gradient_type' => [
        'linear' => $this->t('Linear'),
        'radial' => $this->t('Radial'),
      ],
      'background_gradient_linear_direction' => [
        'to bottom' => $this->t('To Bottom (Default)'),
        'to top' => $this->t('To Top'),
        'to right' => $this->t('To Right'),
        'to left' => $this->t('To Left'),
        'to bottom right' => $this->t('To Bottom Right'),
        'to top left' => $this->t('To Top Left'),
        '45deg' => $this->t('45 Degrees'),
        '90deg' => $this->t('90 Degrees (To Right)'),
        '135deg' => $this->t('135 Degrees'),
        '180deg' => $this->t('180 Degrees (To Top)'),
        '225deg' => $this->t('225 Degrees'),
        '270deg' => $this->t('270 Degrees (To Left)'),
        '315deg' => $this->t('315 Degrees'),
      ],
      'background_gradient_radial_shape' => [
        'ellipse' => $this->t('Ellipse (Default)'),
        'circle' => $this->t('Circle'),
      ],
      'background_gradient_radial_position' => [
        'center' => $this->t('Center (Default)'),
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
        'top left' => $this->t('Top Left'),
        'top right' => $this->t('Top Right'),
        'bottom left' => $this->t('Bottom Left'),
        'bottom right' => $this->t('Bottom Right'),
      ],
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
      'hide_on_breakpoint' => [
        'mobile' => $this->t('Mobile'),
        'tablet' => $this->t('Tablet'),
        'desktop' => $this->t('Desktop'),
      ],
    ];

    return $options[$key] ?? [];
  }

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
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValues();

    foreach (['sizing_option', 'container_type'] as $key) {
      $this->configuration[$key] = $values[$key];
    }

    foreach ([
      'horizontal_padding_option',
      'vertical_padding_option',
      'gap_option',
      'horizontal_margin_option',
      'vertical_margin_option',
    ] as $key) {
      $this->configuration[$key] = $values['spacing'][$key];
    }

    $this->configuration['foreground_color'] = $values['colors']['foreground_color'] ?? self::NONE_OPTION_KEY;

    foreach ([
      'border_color',
      'border_width_option',
      'border_style_option',
      'border_radius_option',
    ] as $key) {
      $this->configuration[$key] = $values['border'][$key] ?? self::NONE_OPTION_KEY;
    }

    $this->configuration['vertical_alignment'] = $values['alignment']['vertical_alignment'];

    foreach ([
      'animation_type',
      'slide_direction',
      'transition_property',
      'transition_duration',
      'transition_timing_function',
      'transition_delay',
    ] as $key) {
      $this->configuration[$key] = $values['animation'][$key];
    }

    // --- Background Settings ---
    $background_values = $values['background'];
    $this->configuration['background_type'] = $background_values['background_type'];

    // Consolidate shared fields based on background type.
    $media_url = '';
    $min_height = '';

    switch ($this->configuration['background_type']) {
      case 'image':
        $media_url = $background_values['image_settings']['background_media_url'] ?? '';
        $min_height = $background_values['image_settings']['background_media_min_height'] ?? '';
        break;

      case 'video':
        $media_url = $background_values['video_settings']['background_media_url'] ?? '';
        $min_height = $background_values['video_settings']['background_media_min_height'] ?? '';
        break;

      case 'gradient':
        $min_height = $background_values['gradient_settings']['background_media_min_height'] ?? '';
        break;
    }

    $this->configuration['background_media_url'] = $media_url;
    // Clear background_media_min_height if container type is 'hero'.
    $this->configuration['background_media_min_height'] = ($values['container_type'] === 'hero') ? '' : $min_height;

    // Color settings.
    $this->configuration['background_color'] = $background_values['color_settings']['background_color'] ?? self::NONE_OPTION_KEY;
    $this->configuration['background_opacity'] = $background_values['color_settings']['background_opacity'] ?? self::NONE_OPTION_KEY;

    // Image settings.
    foreach ([
      'background_image_position',
      'background_image_repeat',
      'background_image_size',
      'background_image_attachment',
    ] as $key) {
      $this->configuration[$key] = $background_values['image_settings'][$key] ?? $this->defaultConfiguration()[$key];
    }

    // Video settings.
    foreach ([
      'background_video_loop',
      'background_video_autoplay',
      'background_video_muted',
      'background_video_preload',
    ] as $key) {
      $this->configuration[$key] = $background_values['video_settings'][$key] ?? $this->defaultConfiguration()[$key];
    }

    // Gradient settings.
    $this->configuration['background_gradient_type'] = $background_values['gradient_settings']['background_gradient_type'] ?? 'linear';
    $this->configuration['background_gradient_start_color'] = $background_values['gradient_settings']['background_gradient_start_color'] ?? self::NONE_OPTION_KEY;
    $this->configuration['background_gradient_end_color'] = $background_values['gradient_settings']['background_gradient_end_color'] ?? self::NONE_OPTION_KEY;
    $this->configuration['background_gradient_linear_direction'] = $background_values['gradient_settings']['linear_gradient_settings']['background_gradient_linear_direction'] ?? 'to bottom';
    $this->configuration['background_gradient_radial_shape'] = $background_values['gradient_settings']['radial_gradient_settings']['background_gradient_radial_shape'] ?? 'ellipse';
    $this->configuration['background_gradient_radial_position'] = $background_values['gradient_settings']['radial_gradient_settings']['background_gradient_radial_position'] ?? 'center';

    // Overlay settings.
    $this->configuration['background_overlay_color'] = $background_values['overlay_settings']['background_overlay_color'] ?? self::NONE_OPTION_KEY;
    $this->configuration['background_overlay_opacity'] = $background_values['overlay_settings']['background_overlay_opacity'] ?? self::NONE_OPTION_KEY;

    // Shadows & Effects.
    foreach ([
      'box_shadow_option',
      'filter_option',
      'opacity_option',
      'transform_scale_option',
      'transform_rotate_option',
    ] as $key) {
      $this->configuration[$key] = $values['shadows_effects'][$key];
    }

    // Responsiveness.
    $this->configuration['hide_on_breakpoint'] = array_filter($values['responsiveness']['hide_on_breakpoint']);

    // Custom Attributes.
    $this->configuration['custom_css_id'] = trim($values['custom_attributes']['custom_css_id']);
    $this->configuration['custom_css_class'] = trim($values['custom_attributes']['custom_css_class']);
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
    $gap_options = $this->getScaleOptions();
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
    $configuration['vertical_alignment'] = 'middle';

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

    // Add defaults for background gradient options.
    $configuration['background_gradient_type'] = 'linear';
    $configuration['background_gradient_start_color'] = self::NONE_OPTION_KEY;
    $configuration['background_gradient_end_color'] = self::NONE_OPTION_KEY;
    $configuration['background_gradient_linear_direction'] = 'to bottom';
    $configuration['background_gradient_radial_shape'] = 'ellipse';
    $configuration['background_gradient_radial_position'] = 'center';

    // Add defaults for shadows & effects.
    $configuration['box_shadow_option'] = self::NONE_OPTION_KEY;
    $configuration['filter_option'] = self::NONE_OPTION_KEY;
    // New: Opacity, Scale, Rotate.
    $configuration['opacity_option'] = self::NONE_OPTION_KEY;
    $configuration['transform_scale_option'] = self::NONE_OPTION_KEY;
    $configuration['transform_rotate_option'] = self::NONE_OPTION_KEY;

    // Add defaults for responsiveness.
    $configuration['hide_on_breakpoint'] = [];

    // Add defaults for custom CSS attributes.
    $configuration['custom_css_id'] = '';
    $configuration['custom_css_class'] = '';

    return $configuration;
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
        $apply_horizontal_margin = FALSE;
        break;

      case 'edge-to-edge':
        $build['#attributes']['class'][] = 'kingly-layout--edge-to-edge';
        $h_padding_effective = self::NONE_OPTION_KEY;
        $apply_horizontal_margin = FALSE;
        break;

      case 'hero':
        $build['#attributes']['class'][] = 'kingly-layout--hero';
        $h_padding_effective = self::NONE_OPTION_KEY;
        $apply_horizontal_margin = FALSE;
        break;
    }

    // Apply spacing utility classes.
    $this->applyClassFromConfig($build, 'kingly-layout-padding-x-', $h_padding_effective);
    $this->applyClassFromConfig($build, 'kingly-layout-padding-y-', 'vertical_padding_option');
    $this->applyClassFromConfig($build, 'kingly-layout-gap-', 'gap_option');
    $this->applyClassFromConfig($build, 'kingly-layout-margin-y-', 'vertical_margin_option');

    if ($apply_horizontal_margin) {
      $this->applyClassFromConfig($build, 'kingly-layout-margin-x-', 'horizontal_margin_option');
    }

    // Apply classes from a map.
    $class_map = [
      'vertical_alignment' => 'kingly-layout-align-content-',
      'border_radius_option' => 'kingly-layout-border-radius-',
      'box_shadow_option' => 'kingly-layout-shadow-',
      'filter_option' => 'kingly-layout-filter-',
    ];
    foreach ($class_map as $config_key => $prefix) {
      $this->applyClassFromConfig($build, $prefix, $config_key);
    }

    // Apply background media (image, video, or gradient).
    $this->applyBackgroundMedia($build);

    // Apply inline styles from a map.
    $style_map = [
      'opacity_option' => 'opacity',
    ];
    foreach ($style_map as $config_key => $property) {
      $this->applyInlineStyleFromOption($build, $property, $config_key);
    }

    // Handle combined transforms.
    $transforms = [];
    if (($scale_value = $this->configuration['transform_scale_option']) !== self::NONE_OPTION_KEY) {
      $transforms[] = 'scale(' . $scale_value . ')';
    }
    if (($rotate_value = $this->configuration['transform_rotate_option']) !== self::NONE_OPTION_KEY) {
      $transforms[] = 'rotate(' . $rotate_value . 'deg)';
    }
    if (!empty($transforms)) {
      $build['#attributes']['style'][] = 'transform: ' . implode(' ', $transforms) . ';';
    }

    // Apply responsiveness classes.
    if (!empty($this->configuration['hide_on_breakpoint'])) {
      foreach ($this->configuration['hide_on_breakpoint'] as $breakpoint) {
        if ($breakpoint) {
          $build['#attributes']['class'][] = 'kingly-layout-hide-on-' . $breakpoint;
        }
      }
    }

    // Apply background color with opacity.
    if ($this->configuration['background_type'] === 'color' && ($background_color_hex = $this->getTermColorHex($this->configuration['background_color']))) {
      $background_opacity_value = $this->configuration['background_opacity'];
      if ($background_opacity_value !== self::NONE_OPTION_KEY && ($rgb = $this->hexToRgb($background_color_hex))) {
        $alpha = (float) $background_opacity_value / 100;
        $build['#attributes']['style'][] = "background-color: rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, {$alpha});";
      }
      else {
        $build['#attributes']['style'][] = 'background-color: ' . $background_color_hex . ';';
      }
    }

    // Apply foreground color.
    $this->applyStyleFromConfig($build, 'color', 'foreground_color');

    // Apply border styles.
    if ($border_color_hex = $this->getTermColorHex($this->configuration['border_color'])) {
      $build['#attributes']['style'][] = 'border-color: ' . $border_color_hex . ';';
      $border_width = $this->configuration['border_width_option'] !== self::NONE_OPTION_KEY ? $this->configuration['border_width_option'] : 'sm';
      $border_style = $this->configuration['border_style_option'] !== self::NONE_OPTION_KEY ? $this->configuration['border_style_option'] : 'solid';
      $this->applyClassFromConfig($build, 'kingly-layout-border-width-', $border_width);
      $this->applyClassFromConfig($build, 'kingly-layout-border-style-', $border_style);
    }

    // Apply animation.
    if ($this->configuration['animation_type'] !== self::NONE_OPTION_KEY) {
      $build['#attached']['library'][] = 'kingly_layouts/kingly_animations';
      $build['#attributes']['class'][] = 'kingly-animate';
      $this->applyClassFromConfig($build, 'kingly-animate--', 'animation_type');

      if ($this->configuration['animation_type'] === 'slide-in' && $this->configuration['slide_direction'] !== self::NONE_OPTION_KEY) {
        $this->applyClassFromConfig($build, 'kingly-animate--direction-', 'slide_direction');
      }

      $animation_style_map = [
        'transition_property' => 'transition-property',
        'transition_duration' => 'transition-duration',
        'transition_timing_function' => 'transition-timing-function',
        'transition_delay' => 'transition-delay',
      ];
      foreach ($animation_style_map as $config_key => $property) {
        $this->applyInlineStyleFromOption($build, $property, $config_key);
      }
    }

    // Apply custom CSS ID and classes.
    if (!empty($this->configuration['custom_css_id'])) {
      $build['#attributes']['id'] = $this->configuration['custom_css_id'];
    }
    if (!empty($this->configuration['custom_css_class'])) {
      $build['#attributes']['class'] = array_merge($build['#attributes']['class'], explode(' ', $this->configuration['custom_css_class']));
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

    // Apply min-height if set for a media background (image, video, or
    // gradient).
    if (!empty($min_height) && in_array($background_type, [
      'image',
      'video',
      'gradient',
    ])) {
      $build['#attributes']['style'][] = 'min-height: ' . $min_height . ';';
    }

    // Handle background image or video.
    if (!empty($media_url)) {
      if ($background_type === 'image') {
        $build['#attributes']['style'][] = 'background-image: url("' . $media_url . '");';
        $this->applyInlineStyleFromOption($build, 'background-position', 'background_image_position');
        $this->applyInlineStyleFromOption($build, 'background-repeat', 'background_image_repeat');
        $this->applyInlineStyleFromOption($build, 'background-size', 'background_image_size');
        $this->applyInlineStyleFromOption($build, 'background-attachment', 'background_image_attachment');
      }
      elseif ($background_type === 'video') {
        $build['#attributes']['class'][] = 'kingly-layout--has-bg-video';
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
    // Handle background gradient.
    elseif ($background_type === 'gradient') {
      $start_color_hex = $this->getTermColorHex($this->configuration['background_gradient_start_color']);
      $end_color_hex = $this->getTermColorHex($this->configuration['background_gradient_end_color']);

      if ($start_color_hex && $end_color_hex) {
        $gradient_type = $this->configuration['background_gradient_type'];
        if ($gradient_type === 'linear') {
          $direction = $this->configuration['background_gradient_linear_direction'];
          $gradient_css = "linear-gradient({$direction}, {$start_color_hex}, {$end_color_hex})";
        }
        else {
          $shape = $this->configuration['background_gradient_radial_shape'];
          $position = $this->configuration['background_gradient_radial_position'];
          $gradient_css = "radial-gradient({$shape} at {$position}, {$start_color_hex}, {$end_color_hex})";
        }
        $build['#attributes']['style'][] = 'background-image: ' . $gradient_css . ';';
      }
    }

    // Handle overlay for image, video, or gradient backgrounds.
    if (in_array($background_type, ['image', 'video', 'gradient'])) {
      $overlay_color_hex = $this->getTermColorHex($this->configuration['background_overlay_color']);
      $overlay_opacity_value = $this->configuration['background_overlay_opacity'];

      if ($overlay_color_hex && $overlay_opacity_value !== self::NONE_OPTION_KEY) {
        $build['#attributes']['class'][] = 'kingly-layout--has-bg-overlay';
        $build['overlay'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['kingly-layout__bg-overlay'],
            'style' => [
              'background-color: ' . $overlay_color_hex . ';',
              'opacity: ' . ((float) $overlay_opacity_value / 100) . ';',
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
    if ($value !== self::NONE_OPTION_KEY) {
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
      return NULL;
    }

    return [$r, $g, $b];
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
    if ($color_hex = $this->getTermColorHex($this->configuration[$config_key])) {
      $build['#attributes']['style'][] = $style_property . ': ' . $color_hex . ';';
    }
  }

}

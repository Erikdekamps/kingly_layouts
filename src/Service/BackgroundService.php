<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;
use Drupal\kingly_layouts\KinglyLayoutsUtilityTrait;
use Drupal\kingly_layouts\KinglyLayoutsValidationTrait;

/**
 * Service to manage background options for Kingly Layouts.
 *
 * This service now uses direct color input fields instead of taxonomy terms
 * for background, overlay, and gradient colors.
 */
class BackgroundService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;
  use KinglyLayoutsUtilityTrait;
  use KinglyLayoutsValidationTrait;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * The color service.
   *
   * While not directly used for picking colors anymore, it provides hex to RGB
   * conversion if needed and is conceptually related.
   */
  protected ColorService $colorService;

  /**
   * Constructs a new BackgroundService object.
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
      '#options' => $this->getBackgroundOptions('type'),
      '#default_value' => $configuration['background_type'],
      '#description' => $this->t('Choose the type of background for this layout section.'),
    ];

    // Consolidated background_media_min_height field.
    $form['background']['background_media_min_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum Height'),
      '#default_value' => $configuration['background_media_min_height'],
      '#description' => $this->t('Set a minimum height for the section. Include the unit (e.g., 400px, 50vh). Leave blank for default height.'),
      '#states' => [
        'visible' => [
          [':input[name="layout_settings[background][background_type]"]' => ['value' => 'image']],
          'or',
          [':input[name="layout_settings[background][background_type]"]' => ['value' => 'video']],
          'or',
          [':input[name="layout_settings[background][background_type]"]' => ['value' => 'gradient']],
        ],
        'disabled' => [
          ':input[name="layout_settings[container_type]"]' => ['value' => 'hero'],
        ],
      ],
      // Place after background_type.
      '#weight' => 1,
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
    $form['background']['color_settings']['background_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Background Color'),
      '#default_value' => $configuration['background_color'],
      '#description' => $this->t('Enter a hex code for the background color (e.g., #F0F0F0).'),
      '#attributes' => [
        'type' => 'color',
      ],
      '#pattern' => '#[0-9a-fA-F]{6}',
      // Add server-side validation for the hex color format.
      '#element_validate' => [[$this, 'validateColorHex']],
    ];
    $form['background']['color_settings']['background_opacity'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Opacity'),
      '#options' => $this->getBackgroundOptions('opacity'),
      '#default_value' => $configuration['background_opacity'],
      '#description' => $this->t('Set the opacity for the background color. This requires a background color to be selected.'),
      '#states' => [
        'visible' => [
          [':input[name="layout_settings[background][color_settings][background_color]"]' => ['!value' => '']],
        ],
      ],
    ];

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
      '#type' => 'textfield',
      '#title' => $this->t('Overlay Color'),
      '#default_value' => $configuration['background_overlay_color'],
      '#description' => $this->t('Enter a hex code for the overlay color (e.g., #000000). The overlay sits on top of the background media, but behind the content.'),
      '#attributes' => [
        'type' => 'color',
      ],
      '#pattern' => '#[0-9a-fA-F]{6}',
      // Add server-side validation for the hex color format.
      '#element_validate' => [[$this, 'validateColorHex']],
    ];
    $form['background']['overlay_settings']['background_overlay_opacity'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Opacity'),
      '#options' => $this->getBackgroundOptions('overlay_opacity'),
      '#default_value' => $configuration['background_overlay_opacity'],
      '#description' => $this->t('Set the opacity for the overlay color. This requires an overlay color to be selected.'),
      '#states' => [
        'visible' => [
          [':input[name="layout_settings[background][overlay_settings][background_overlay_color]"]' => ['!value' => '']],
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
      '#default_value' => $configuration['background_media_url'],
      '#description' => $this->t('Enter the full, absolute URL for the background image.'),
    ];
    $form['background']['image_settings']['background_image_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Position'),
      '#options' => $this->getBackgroundOptions('image_position'),
      '#default_value' => $configuration['background_image_position'],
      '#description' => $this->t("Select the starting position of the background image. This is most noticeable when the image is not set to 'cover' or 'contain'."),
    ];
    $form['background']['image_settings']['background_image_repeat'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Repeat'),
      '#options' => $this->getBackgroundOptions('image_repeat'),
      '#default_value' => $configuration['background_image_repeat'],
      '#description' => $this->t('Define if and how the background image should repeat.'),
    ];
    $form['background']['image_settings']['background_image_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Size'),
      '#options' => $this->getBackgroundOptions('image_size'),
      '#default_value' => $configuration['background_image_size'],
      '#description' => $this->t("'Cover' will fill the entire area, potentially cropping the image. 'Contain' will show the entire image, potentially leaving empty space."),
    ];
    $form['background']['image_settings']['background_image_attachment'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Attachment'),
      '#options' => $this->getBackgroundOptions('image_attachment'),
      '#default_value' => $configuration['background_image_attachment'],
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
      '#default_value' => $configuration['background_media_url'],
      '#description' => $this->t('Enter the full, absolute URL for the video file (e.g., https://example.com/video.mp4). YouTube or Vimeo URLs are not supported.'),
    ];
    $form['background']['video_settings']['background_video_loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop video'),
      '#default_value' => $configuration['background_video_loop'],
    ];
    $form['background']['video_settings']['background_video_autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay video'),
      '#default_value' => $configuration['background_video_autoplay'],
    ];
    $form['background']['video_settings']['background_video_muted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mute video'),
      '#default_value' => $configuration['background_video_muted'],
    ];
    $form['background']['video_settings']['background_video_preload'] = [
      '#type' => 'select',
      '#title' => $this->t('Preload video'),
      '#options' => [
        'auto' => $this->t('Auto'),
        'metadata' => $this->t('Metadata only'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $configuration['background_video_preload'],
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
    $form['background']['gradient_settings']['background_gradient_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Gradient Type'),
      '#options' => $this->getBackgroundOptions('gradient_type'),
      '#default_value' => $configuration['background_gradient_type'],
    ];
    $form['background']['gradient_settings']['background_gradient_start_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Start Color'),
      '#default_value' => $configuration['background_gradient_start_color'],
      '#description' => $this->t('Enter a hex code for the start color (e.g., #FFFFFF).'),
      '#attributes' => [
        'type' => 'color',
      ],
      '#pattern' => '#[0-9a-fA-F]{6}',
      // Add server-side validation for the hex color format.
      '#element_validate' => [[$this, 'validateColorHex']],
    ];
    $form['background']['gradient_settings']['background_gradient_end_color'] = [
      '#type' => 'color',
      '#title' => $this->t('End Color'),
      '#default_value' => $configuration['background_gradient_end_color'],
      '#description' => $this->t('Enter a hex code for the end color (e.g., #000000).'),
      '#attributes' => [
        'type' => 'color',
      ],
      '#pattern' => '#[0-9a-fA-F]{6}',
      // Add server-side validation for the hex color format.
      '#element_validate' => [[$this, 'validateColorHex']],
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
      '#options' => $this->getBackgroundOptions('gradient_linear_direction'),
      '#default_value' => $configuration['background_gradient_linear_direction'],
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
      '#options' => $this->getBackgroundOptions('gradient_radial_shape'),
      '#default_value' => $configuration['background_gradient_radial_shape'],
    ];
    $form['background']['gradient_settings']['radial_gradient_settings']['background_gradient_radial_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => $this->getBackgroundOptions('gradient_radial_position'),
      '#default_value' => $configuration['background_gradient_radial_position'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $values = $form_state->getValues();
    $background_values = $values['background'];
    $configuration['background_type'] = $background_values['background_type'];

    // Consolidate shared fields based on background type.
    $media_url = '';
    $min_height = $background_values['background_media_min_height'] ?? '';

    switch ($configuration['background_type']) {
      case 'image':
        $media_url = $background_values['image_settings']['background_media_url'] ?? '';
        break;

      case 'video':
        $media_url = $background_values['video_settings']['background_media_url'] ?? '';
        break;
    }

    $configuration['background_media_url'] = $media_url;
    // Min height is disabled for 'hero' container type, so its value should be
    // empty.
    $configuration['background_media_min_height'] = ($values['container_type'] === 'hero') ? '' : $min_height;

    // Color settings.
    $configuration['background_color'] = $background_values['color_settings']['background_color'] ?? '';
    $configuration['background_opacity'] = $background_values['color_settings']['background_opacity'] ?? self::NONE_OPTION_KEY;

    // Image settings.
    $defaults = self::defaultConfiguration();
    foreach ([
      'background_image_position',
      'background_image_repeat',
      'background_image_size',
      'background_image_attachment',
    ] as $key) {
      $configuration[$key] = $background_values['image_settings'][$key] ?? $defaults[$key];
    }

    // Video settings.
    foreach ([
      'background_video_loop',
      'background_video_autoplay',
      'background_video_muted',
      'background_video_preload',
    ] as $key) {
      $configuration[$key] = $background_values['video_settings'][$key] ?? $defaults[$key];
    }

    // Gradient settings.
    $configuration['background_gradient_type'] = $background_values['gradient_settings']['background_gradient_type'] ?? 'linear';
    $configuration['background_gradient_start_color'] = $background_values['gradient_settings']['background_gradient_start_color'] ?? '';
    $configuration['background_gradient_end_color'] = $background_values['gradient_settings']['background_gradient_end_color'] ?? '';
    $configuration['background_gradient_linear_direction'] = $background_values['gradient_settings']['linear_gradient_settings']['background_gradient_linear_direction'] ?? 'to bottom';
    $configuration['background_gradient_radial_shape'] = $background_values['gradient_settings']['radial_gradient_settings']['background_gradient_radial_shape'] ?? 'ellipse';
    $configuration['background_gradient_radial_position'] = $background_values['gradient_settings']['radial_gradient_settings']['background_gradient_radial_position'] ?? 'center';

    // Overlay settings.
    $configuration['background_overlay_color'] = $background_values['overlay_settings']['background_overlay_color'] ?? '';
    $configuration['background_overlay_opacity'] = $background_values['overlay_settings']['background_overlay_opacity'] ?? self::NONE_OPTION_KEY;
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    $background_type = $configuration['background_type'];
    $has_background = FALSE;

    // Apply minimum height if configured.
    $has_background = $this->applyMinHeight($build, $configuration) || $has_background;

    // Apply background based on type.
    if ($background_type === 'color') {
      $has_background = $this->applyBackgroundColor($build, $configuration) || $has_background;
    }
    elseif ($background_type === 'image') {
      $has_background = $this->applyBackgroundImage($build, $configuration) || $has_background;
    }
    elseif ($background_type === 'video') {
      $has_background = $this->applyBackgroundVideo($build, $configuration) || $has_background;
    }
    elseif ($background_type === 'gradient') {
      $has_background = $this->applyBackgroundGradient($build, $configuration) || $has_background;
    }

    // Apply overlay for media/gradient backgrounds.
    $has_background = $this->applyBackgroundOverlay($build, $configuration, $background_type) || $has_background;

    // Attach the library only if a background feature is actually used.
    if ($has_background) {
      $build['#attached']['library'][] = 'kingly_layouts/backgrounds';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'background_type' => 'color',
      'background_color' => '',
      'background_opacity' => self::NONE_OPTION_KEY,
      'background_media_url' => '',
      'background_media_min_height' => '',
      'background_image_position' => 'center center',
      'background_image_repeat' => 'no-repeat',
      'background_image_size' => 'cover',
      'background_image_attachment' => 'scroll',
      'background_video_loop' => FALSE,
      'background_video_autoplay' => TRUE,
      'background_video_muted' => TRUE,
      'background_video_preload' => 'auto',
      'background_overlay_color' => '',
      'background_overlay_opacity' => self::NONE_OPTION_KEY,
      'background_gradient_type' => 'linear',
      'background_gradient_start_color' => '',
      'background_gradient_end_color' => '',
      'background_gradient_linear_direction' => 'to bottom',
      'background_gradient_radial_shape' => 'ellipse',
      'background_gradient_radial_position' => 'center',
    ];
  }

  /**
   * Gets background-related options.
   *
   * @param string $key
   *   The key for the specific options to retrieve.
   *
   * @return array
   *   An array of background options.
   */
  private function getBackgroundOptions(string $key): array {
    $none = [self::NONE_OPTION_KEY => $this->t('None')];
    $options = [
      'type' => [
        'color' => $this->t('Color'),
        'image' => $this->t('Image'),
        'video' => $this->t('Video'),
        'gradient' => $this->t('Gradient'),
      ],
      'opacity' => [
        self::NONE_OPTION_KEY => $this->t('100% (Default)'),
        '90' => $this->t('90%'),
        '75' => $this->t('75%'),
        '50' => $this->t('50%'),
        '25' => $this->t('25%'),
        '0' => $this->t('0% (Transparent)'),
      ],
      'overlay_opacity' => $none + [
        '25' => $this->t('25%'),
        '50' => $this->t('50%'),
        '75' => $this->t('75%'),
        '90' => $this->t('90%'),
      ],
      'image_position' => [
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
      'image_repeat' => [
        'no-repeat' => $this->t('No Repeat'),
        'repeat' => $this->t('Repeat'),
        'repeat-x' => $this->t('Repeat Horizontally'),
        'repeat-y' => $this->t('Repeat Vertically'),
      ],
      'image_size' => [
        'cover' => $this->t('Cover'),
        'contain' => $this->t('Contain'),
        'auto' => $this->t('Auto'),
      ],
      'image_attachment' => [
        'scroll' => $this->t('Scroll'),
        'fixed' => $this->t('Fixed (Parallax)'),
        'local' => $this->t('Local'),
      ],
      'gradient_type' => [
        'linear' => $this->t('Linear'),
        'radial' => $this->t('Radial'),
      ],
      'gradient_linear_direction' => [
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
      'gradient_radial_shape' => [
        'ellipse' => $this->t('Ellipse (Default)'),
        'circle' => $this->t('Circle'),
      ],
      'gradient_radial_position' => [
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
    ];

    return $options[$key] ?? [];
  }

  /**
   * Applies the minimum height style to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return bool
   *   TRUE if min-height was applied, FALSE otherwise.
   */
  private function applyMinHeight(array &$build, array $configuration): bool {
    $min_height = $configuration['background_media_min_height'];
    $background_type = $configuration['background_type'];
    if (!empty($min_height) && in_array($background_type, ['image', 'video', 'gradient'])) {
      $build['#attributes']['style'][] = 'min-height: ' . $min_height . ';';
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Applies background color styles to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return bool
   *   TRUE if a background color was applied, FALSE otherwise.
   */
  private function applyBackgroundColor(array &$build, array $configuration): bool {
    $background_color_hex = $configuration['background_color'];
    // Validate if the stored color is a valid hex code before applying.
    if (!empty($background_color_hex) && preg_match('/^#([a-fA-F0-9]{6})$/', $background_color_hex)) {
      $opacity_value = $configuration['background_opacity'];
      if ($opacity_value !== self::NONE_OPTION_KEY && ($rgb = $this->hexToRgb($background_color_hex))) {
        $alpha = (float) $opacity_value / 100;
        $build['#attributes']['style'][] = "background-color: rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, {$alpha});";
      }
      else {
        $build['#attributes']['style'][] = 'background-color: ' . $background_color_hex . ';';
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Applies background image styles to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return bool
   *   TRUE if a background image was applied, FALSE otherwise.
   */
  private function applyBackgroundImage(array &$build, array $configuration): bool {
    $media_url = $configuration['background_media_url'];
    if (!empty($media_url)) {
      $build['#attributes']['style'][] = 'background-image: url("' . $media_url . '");';
      $this->applyInlineStyleFromOption($build, 'background-position', 'background_image_position', $configuration);
      $this->applyInlineStyleFromOption($build, 'background-repeat', 'background_image_repeat', $configuration);
      $this->applyInlineStyleFromOption($build, 'background-size', 'background_image_size', $configuration);
      $this->applyInlineStyleFromOption($build, 'background-attachment', 'background_image_attachment', $configuration);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Adds background video render array to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return bool
   *   TRUE if a background video was applied, FALSE otherwise.
   */
  private function applyBackgroundVideo(array &$build, array $configuration): bool {
    $media_url = $configuration['background_media_url'];
    if (!empty($media_url) && UrlHelper::isValid($media_url, TRUE)) {
      $build['#attributes']['class'][] = 'kl--has-bg-video';
      $build['video_background'] = [
        '#theme' => 'kingly_background_video',
        '#video_url' => $media_url,
        '#loop' => $configuration['background_video_loop'],
        '#autoplay' => $configuration['background_video_autoplay'],
        '#muted' => $configuration['background_video_muted'],
        '#preload' => $configuration['background_video_preload'],
        '#weight' => -100,
      ];
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Applies background gradient styles to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return bool
   *   TRUE if a background gradient was applied, FALSE otherwise.
   */
  private function applyBackgroundGradient(array &$build, array $configuration): bool {
    $start_hex = $configuration['background_gradient_start_color'];
    $end_hex = $configuration['background_gradient_end_color'];

    // Validate both colors are valid hex codes.
    if (!empty($start_hex) && preg_match('/^#([a-fA-F0-9]{6})$/', $start_hex) &&
      !empty($end_hex) && preg_match('/^#([a-fA-F0-9]{6})$/', $end_hex)) {
      if ($configuration['background_gradient_type'] === 'linear') {
        $direction = $configuration['background_gradient_linear_direction'];
        $gradient = "linear-gradient({$direction}, {$start_hex}, {$end_hex})";
      }
      else {
        $shape = $configuration['background_gradient_radial_shape'];
        $position = $configuration['background_gradient_radial_position'];
        $gradient = "radial-gradient({$shape} at {$position}, {$start_hex}, {$end_hex})";
      }
      $build['#attributes']['style'][] = 'background-image: ' . $gradient . ';';
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Applies background overlay to the build array.
   *
   * This applies to image, video, and gradient background types.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   * @param string $background_type
   *   The current background type (e.g., 'image', 'video').
   *
   * @return bool
   *   TRUE if an overlay was applied, FALSE otherwise.
   */
  private function applyBackgroundOverlay(array &$build, array $configuration, string $background_type): bool {
    $overlay_hex = $configuration['background_overlay_color'];
    // Validate if the stored color is a valid hex code before applying.
    if (in_array($background_type, ['image', 'video', 'gradient']) &&
      !empty($overlay_hex) && preg_match('/^#([a-fA-F0-9]{6})$/', $overlay_hex)) {
      $overlay_opacity = $configuration['background_overlay_opacity'];
      if ($overlay_opacity !== self::NONE_OPTION_KEY && ($rgb = $this->hexToRgb($overlay_hex))) {
        $build['#attributes']['class'][] = 'kl--has-bg-overlay';
        $build['overlay'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['kl__bg-overlay'],
            'style' => [
              "background-color: rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, " . ((float) $overlay_opacity / 100) . ');',
            ],
          ],
          '#weight' => -99,
        ];
        return TRUE;
      }
    }
    return FALSE;
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
  private function hexToRgb(string $hex): ?array {
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

}

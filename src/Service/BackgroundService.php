<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;
use Drupal\kingly_layouts\KinglyLayoutsUtilityTrait;

/**
 * Service to manage background options for Kingly Layouts.
 */
class BackgroundService implements KinglyLayoutsDisplayOptionInterface {

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
   * The color service.
   */
  protected ColorService $colorService;

  /**
   * Constructs a new BackgroundService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\kingly_layouts\Service\OptionsService $options_service
   *   The options service.
   * @param \Drupal\kingly_layouts\Service\ColorService $color_service
   *   The color service.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation, OptionsService $options_service, ColorService $color_service) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
    $this->optionsService = $options_service;
    $this->colorService = $color_service;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $color_options = $this->optionsService->getColorOptions();

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
      '#options' => $this->optionsService->getOptions('background_type'),
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
    if (count($color_options) > 1) {
      $form['background']['color_settings']['background_color'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Color'),
        '#options' => $color_options,
        '#default_value' => $configuration['background_color'],
      ];
      $form['background']['color_settings']['background_opacity'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Opacity'),
        '#options' => $this->optionsService->getOptions('background_opacity'),
        '#default_value' => $configuration['background_opacity'],
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
      '#default_value' => $configuration['background_overlay_color'],
      '#description' => $this->t('Select a color for the overlay. The overlay sits on top of the background media, but behind the content.'),
    ];
    $form['background']['overlay_settings']['background_overlay_opacity'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Opacity'),
      '#options' => $this->optionsService->getOptions('background_overlay_opacity'),
      '#default_value' => $configuration['background_overlay_opacity'],
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
      '#default_value' => $configuration['background_media_url'],
      '#description' => $this->t('Enter the full, absolute URL for the background image.'),
    ];
    $form['background']['image_settings']['background_image_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Position'),
      '#options' => $this->optionsService->getOptions('background_image_position'),
      '#default_value' => $configuration['background_image_position'],
      '#description' => $this->t("Select the starting position of the background image. This is most noticeable when the image is not set to 'cover' or 'contain'."),
    ];
    $form['background']['image_settings']['background_image_repeat'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Repeat'),
      '#options' => $this->optionsService->getOptions('background_image_repeat'),
      '#default_value' => $configuration['background_image_repeat'],
      '#description' => $this->t('Define if and how the background image should repeat.'),
    ];
    $form['background']['image_settings']['background_image_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Size'),
      '#options' => $this->optionsService->getOptions('background_image_size'),
      '#default_value' => $configuration['background_image_size'],
      '#description' => $this->t("'Cover' will fill the entire area, potentially cropping the image. 'Contain' will show the entire image, potentially leaving empty space."),
    ];
    $form['background']['image_settings']['background_image_attachment'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Attachment'),
      '#options' => $this->optionsService->getOptions('background_image_attachment'),
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
      '#options' => $this->optionsService->getOptions('background_gradient_type'),
      '#default_value' => $configuration['background_gradient_type'],
    ];
    $form['background']['gradient_settings']['background_gradient_start_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Start Color'),
      '#options' => $color_options,
      '#default_value' => $configuration['background_gradient_start_color'],
    ];
    $form['background']['gradient_settings']['background_gradient_end_color'] = [
      '#type' => 'select',
      '#title' => $this->t('End Color'),
      '#options' => $color_options,
      '#default_value' => $configuration['background_gradient_end_color'],
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
      '#options' => $this->optionsService->getOptions('background_gradient_linear_direction'),
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
      '#options' => $this->optionsService->getOptions('background_gradient_radial_shape'),
      '#default_value' => $configuration['background_gradient_radial_shape'],
    ];
    $form['background']['gradient_settings']['radial_gradient_settings']['background_gradient_radial_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => $this->optionsService->getOptions('background_gradient_radial_position'),
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
    $configuration['background_media_min_height'] = ($values['container_type'] === 'hero') ? '' : $min_height;

    // Color settings.
    $configuration['background_color'] = $background_values['color_settings']['background_color'] ?? self::NONE_OPTION_KEY;
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
    $configuration['background_gradient_start_color'] = $background_values['gradient_settings']['background_gradient_start_color'] ?? self::NONE_OPTION_KEY;
    $configuration['background_gradient_end_color'] = $background_values['gradient_settings']['background_gradient_end_color'] ?? self::NONE_OPTION_KEY;
    $configuration['background_gradient_linear_direction'] = $background_values['gradient_settings']['linear_gradient_settings']['background_gradient_linear_direction'] ?? 'to bottom';
    $configuration['background_gradient_radial_shape'] = $background_values['gradient_settings']['radial_gradient_settings']['background_gradient_radial_shape'] ?? 'ellipse';
    $configuration['background_gradient_radial_position'] = $background_values['gradient_settings']['radial_gradient_settings']['background_gradient_radial_position'] ?? 'center';

    // Overlay settings.
    $configuration['background_overlay_color'] = $background_values['overlay_settings']['background_overlay_color'] ?? self::NONE_OPTION_KEY;
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
    if (($background_color_hex = $this->colorService->getTermColorHex($configuration['background_color']))) {
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
    if (!empty($media_url)) {
      $build['#attributes']['class'][] = 'kingly-layout--has-bg-video';
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
    $start_hex = $this->colorService->getTermColorHex($configuration['background_gradient_start_color']);
    $end_hex = $this->colorService->getTermColorHex($configuration['background_gradient_end_color']);

    if ($start_hex && $end_hex) {
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
    $overlay_hex = $this->colorService->getTermColorHex($configuration['background_overlay_color']);
    if (in_array($background_type, ['image', 'video', 'gradient']) && $overlay_hex) {
      $overlay_opacity = $configuration['background_overlay_opacity'];
      if ($overlay_opacity !== self::NONE_OPTION_KEY) {
        $build['#attributes']['class'][] = 'kingly-layout--has-bg-overlay';
        $build['overlay'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['kingly-layout__bg-overlay'],
            'style' => [
              'background-color: ' . $overlay_hex . ';',
              'opacity: ' . ((float) $overlay_opacity / 100) . ';',
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
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'background_type' => 'color',
      'background_color' => self::NONE_OPTION_KEY,
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
      'background_overlay_color' => self::NONE_OPTION_KEY,
      'background_overlay_opacity' => self::NONE_OPTION_KEY,
      'background_gradient_type' => 'linear',
      'background_gradient_start_color' => self::NONE_OPTION_KEY,
      'background_gradient_end_color' => self::NONE_OPTION_KEY,
      'background_gradient_linear_direction' => 'to bottom',
      'background_gradient_radial_shape' => 'ellipse',
      'background_gradient_radial_position' => 'center',
    ];
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

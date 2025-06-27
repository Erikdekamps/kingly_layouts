<?php

namespace Drupal\kingly_layouts\Service;

/**
 * Service for building background media elements and styles.
 */
class BackgroundMediaBuilder {

  protected const NONE_OPTION_KEY = '_none';

  /**
   * The color resolver service.
   *
   * @var \Drupal\kingly_layouts\Service\ColorResolver
   */
  protected $colorResolver;

  /**
   * Constructs a new BackgroundMediaBuilder object.
   *
   * @param \Drupal\kingly_layouts\Service\ColorResolver $color_resolver
   *   The color resolver service.
   */
  public function __construct(ColorResolver $color_resolver) {
    $this->colorResolver = $color_resolver;
  }

  /**
   * Builds and applies background media to the render array.
   *
   * @param array $build
   *   The layout render array, passed by reference.
   * @param array $config
   *   The layout configuration array.
   */
  public function buildBackgroundMedia(array &$build, array $config): void {
    $background_type = $config['background_type'];
    $media_url = $config['background_media_url'];
    $min_height = $config['background_media_min_height'];

    // Apply min-height if set for a media background.
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
        $this->applyInlineStyle($build, 'background-position', $config['background_image_position']);
        $this->applyInlineStyle($build, 'background-repeat', $config['background_image_repeat']);
        $this->applyInlineStyle($build, 'background-size', $config['background_image_size']);
        $this->applyInlineStyle($build, 'background-attachment', $config['background_image_attachment']);
      }
      elseif ($background_type === 'video') {
        $build['#attributes']['class'][] = 'kingly-layout--has-bg-video';
        $build['video_background'] = [
          '#theme' => 'kingly_background_video',
          '#video_url' => $media_url,
          '#loop' => $config['background_video_loop'],
          '#autoplay' => $config['background_video_autoplay'],
          '#muted' => $config['background_video_muted'],
          '#preload' => $config['background_video_preload'],
          '#weight' => -100,
        ];
      }
    }
    // Handle background gradient.
    elseif ($background_type === 'gradient') {
      $start_color_hex = $this->colorResolver->getTermColorHex($config['background_gradient_start_color']);
      $end_color_hex = $this->colorResolver->getTermColorHex($config['background_gradient_end_color']);

      if ($start_color_hex && $end_color_hex) {
        $gradient_type = $config['background_gradient_type'];
        if ($gradient_type === 'linear') {
          $direction = $config['background_gradient_linear_direction'];
          $gradient_css = "linear-gradient({$direction}, {$start_color_hex}, {$end_color_hex})";
        }
        else {
          $shape = $config['background_gradient_radial_shape'];
          $position = $config['background_gradient_radial_position'];
          $gradient_css = "radial-gradient({$shape} at {$position}, {$start_color_hex}, {$end_color_hex})";
        }
        $build['#attributes']['style'][] = 'background-image: ' . $gradient_css . ';';
      }
    }

    // Handle overlay for image, video, or gradient backgrounds.
    if (in_array($background_type, ['image', 'video', 'gradient'])) {
      $overlay_color_hex = $this->colorResolver->getTermColorHex($config['background_overlay_color']);
      $overlay_opacity_value = $config['background_overlay_opacity'];

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
   * @param string $value
   *   The configuration value to use.
   */
  protected function applyInlineStyle(array &$build, string $style_property, string $value): void {
    if ($value !== self::NONE_OPTION_KEY) {
      $build['#attributes']['style'][] = $style_property . ': ' . $value . ';';
    }
  }

}

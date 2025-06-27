<?php

namespace Drupal\kingly_layouts\Service;

/**
 * Service for applying inline CSS styles to layout render arrays.
 */
class LayoutStyleApplier {

  protected const NONE_OPTION_KEY = '_none';

  /**
   * The color resolver service.
   *
   * @var \Drupal\kingly_layouts\Service\ColorResolver
   */
  protected $colorResolver;

  /**
   * Constructs a new LayoutStyleApplier object.
   *
   * @param \Drupal\kingly_layouts\Service\ColorResolver $color_resolver
   *   The color resolver service.
   */
  public function __construct(ColorResolver $color_resolver) {
    $this->colorResolver = $color_resolver;
  }

  /**
   * Applies all relevant inline CSS styles to the layout render array.
   *
   * @param array $build
   *   The layout render array, passed by reference.
   * @param array $config
   *   The layout configuration array.
   */
  public function applyStyles(array &$build, array $config): void {
    // Apply general inline styles.
    $this->applyInlineStyle($build, 'opacity', $config['opacity_option']);

    // Handle combined transforms.
    $transforms = [];
    if (($scale_value = $config['transform_scale_option']) !== self::NONE_OPTION_KEY) {
      $transforms[] = 'scale(' . $scale_value . ')';
    }
    if (($rotate_value = $config['transform_rotate_option']) !== self::NONE_OPTION_KEY) {
      $transforms[] = 'rotate(' . $rotate_value . 'deg)';
    }
    if (!empty($transforms)) {
      $build['#attributes']['style'][] = 'transform: ' . implode(' ', $transforms) . ';';
    }

    // Apply background color with opacity.
    if ($config['background_type'] === 'color' && ($background_color_hex = $this->colorResolver->getTermColorHex($config['background_color']))) {
      $background_opacity_value = $config['background_opacity'];
      if ($background_opacity_value !== self::NONE_OPTION_KEY && ($rgb = $this->colorResolver->hexToRgb($background_color_hex))) {
        $alpha = (float) $background_opacity_value / 100;
        $build['#attributes']['style'][] = "background-color: rgba({$rgb[0]}, {$rgb[1]}, {$rgb[2]}, {$alpha});";
      }
      else {
        $build['#attributes']['style'][] = 'background-color: ' . $background_color_hex . ';';
      }
    }

    // Apply foreground color.
    if ($color_hex = $this->colorResolver->getTermColorHex($config['foreground_color'])) {
      $build['#attributes']['style'][] = 'color: ' . $color_hex . ';';
    }

    // Apply border color. Border width/style classes are handled by
    // LayoutClassApplier.
    if ($border_color_hex = $this->colorResolver->getTermColorHex($config['border_color'])) {
      $build['#attributes']['style'][] = 'border-color: ' . $border_color_hex . ';';
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

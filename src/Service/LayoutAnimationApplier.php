<?php

namespace Drupal\kingly_layouts\Service;

/**
 * Service for applying animation styles and classes to layout render arrays.
 */
class LayoutAnimationApplier {

  protected const NONE_OPTION_KEY = '_none';

  /**
   * Applies animation-related classes and inline styles to the layout render
   * array.
   *
   * @param array $build
   *   The layout render array, passed by reference.
   * @param array $config
   *   The layout configuration array.
   */
  public function applyAnimation(array &$build, array $config): void {
    if ($config['animation_type'] !== self::NONE_OPTION_KEY) {
      $build['#attached']['library'][] = 'kingly_layouts/kingly_animations';
      $build['#attributes']['class'][] = 'kingly-animate';
      $this->applyClass($build, 'kingly-animate--', $config['animation_type']);

      if ($config['animation_type'] === 'slide-in' && $config['slide_direction'] !== self::NONE_OPTION_KEY) {
        $this->applyClass($build, 'kingly-animate--direction-', $config['slide_direction']);
      }

      // Apply inline animation styles.
      $this->applyInlineStyle($build, 'transition-property', $config['transition_property']);
      $this->applyInlineStyle($build, 'transition-duration', $config['transition_duration']);
      $this->applyInlineStyle($build, 'transition-timing-function', $config['transition_timing_function']);
      $this->applyInlineStyle($build, 'transition-delay', $config['transition_delay']);
    }
  }

  /**
   * Helper to apply a CSS class from a configuration value.
   *
   * @param array &$build
   *   The render array.
   * @param string $class_prefix
   *   The prefix for the CSS class.
   * @param string $value
   *   The configuration value to use for the class suffix.
   */
  protected function applyClass(array &$build, string $class_prefix, string $value): void {
    if (!empty($value) && $value !== self::NONE_OPTION_KEY) {
      $build['#attributes']['class'][] = $class_prefix . $value;
    }
  }

  /**
   * Helper to apply a generic inline style from a configuration option.
   *
   * @param array &$build
   *   The render array.
   * @param string $style_property
   *   The CSS property to set.
   * @param string $value
   *   The configuration value to use.
   */
  protected function applyInlineStyle(array &$build, string $style_property, string $value): void {
    if ($value !== self::NONE_OPTION_KEY) {
      $build['#attributes']['style'][] = $style_property . ': ' . $value . ';';
    }
  }

}

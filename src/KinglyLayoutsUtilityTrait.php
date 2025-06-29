<?php

namespace Drupal\kingly_layouts;

/**
 * Provides utility methods for Kingly Layouts services and plugins.
 */
trait KinglyLayoutsUtilityTrait {

  /**
   * Helper to apply a CSS class from a configuration value.
   *
   * The suffix for the class is determined by the value of the configuration
   * key provided. This method can also accept a direct string value instead of
   * a configuration key.
   *
   * @param array &$build
   *   The render array.
   * @param string $class_prefix
   *   The prefix for the CSS class (e.g., 'kingly-layout-padding-x-').
   * @param string $config_key_or_value
   *   The configuration key (e.g., 'horizontal_padding_option') or a direct
   *   string value (e.g., 'sm') to use for the class suffix.
   * @param array $configuration
   *   The layout's current configuration.
   */
  private function applyClassFromConfig(array &$build, string $class_prefix, string $config_key_or_value, array $configuration): void {
    // Check if the provided string is a config key or a direct value.
    $value = $configuration[$config_key_or_value] ?? $config_key_or_value;
    if (!empty($value) && $value !== '_none') {
      $build['#attributes']['class'][] = $class_prefix . $value;
    }
  }

}

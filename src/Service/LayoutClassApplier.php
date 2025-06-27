<?php

namespace Drupal\kingly_layouts\Service;

/**
 * Service for applying CSS classes to layout render arrays.
 */
class LayoutClassApplier {

  protected const NONE_OPTION_KEY = '_none';

  /**
   * Applies all relevant CSS classes to the layout render array.
   *
   * @param array $build
   *   The layout render array, passed by reference.
   * @param string $layout_id
   *   The ID of the current layout plugin.
   * @param array $config
   *   The layout configuration array.
   */
  public function applyClasses(array &$build, string $layout_id, array $config): void {
    // Apply layout-specific sizing class.
    if (!empty($config['sizing_option'])) {
      $build['#attributes']['class'][] = 'layout--' . $layout_id . '--' . $config['sizing_option'];
    }

    // Apply container type classes and determine padding/margin behavior.
    $container_type = $config['container_type'];
    $h_padding_effective = $config['horizontal_padding_option'];
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
    $this->applyClass($build, 'kingly-layout-padding-x-', $h_padding_effective);
    $this->applyClass($build, 'kingly-layout-padding-y-', $config['vertical_padding_option']);
    $this->applyClass($build, 'kingly-layout-gap-', $config['gap_option']);
    $this->applyClass($build, 'kingly-layout-margin-y-', $config['vertical_margin_option']);

    if ($apply_horizontal_margin) {
      $this->applyClass($build, 'kingly-layout-margin-x-', $config['horizontal_margin_option']);
    }

    // Apply classes from a map for various options.
    $class_map = [
      'vertical_alignment' => 'kingly-layout-align-content-',
      'horizontal_alignment' => 'kingly-layout-justify-content-',
      'border_radius_option' => 'kingly-layout-border-radius-',
      'box_shadow_option' => 'kingly-layout-shadow-',
      'filter_option' => 'kingly-layout-filter-',
    ];
    foreach ($class_map as $config_key => $prefix) {
      $this->applyClass($build, $prefix, $config[$config_key]);
    }

    // Apply border width and style classes (if border color is set).
    // Note: The check for border_color being set is done in StyleApplier,
    // so this assumes the class should be applied if the option is not 'none'.
    if ($config['border_color'] !== self::NONE_OPTION_KEY) {
      $border_width = $config['border_width_option'] !== self::NONE_OPTION_KEY ? $config['border_width_option'] : 'sm';
      $border_style = $config['border_style_option'] !== self::NONE_OPTION_KEY ? $config['border_style_option'] : 'solid';
      $this->applyClass($build, 'kingly-layout-border-width-', $border_width);
      $this->applyClass($build, 'kingly-layout-border-style-', $border_style);
    }

    // Apply responsiveness classes.
    if (!empty($config['hide_on_breakpoint'])) {
      foreach ($config['hide_on_breakpoint'] as $breakpoint) {
        if ($breakpoint) {
          $build['#attributes']['class'][] = 'kingly-layout-hide-on-' . $breakpoint;
        }
      }
    }

    // Apply custom CSS ID and classes.
    if (!empty($config['custom_css_id'])) {
      $build['#attributes']['id'] = $config['custom_css_id'];
    }
    if (!empty($config['custom_css_class'])) {
      $build['#attributes']['class'] = array_merge($build['#attributes']['class'], explode(' ', $config['custom_css_class']));
    }
  }

  /**
   * Helper to apply a CSS class from a configuration value.
   *
   * @param array &$build
   *   The render array.
   * @param string $class_prefix
   *   The prefix for the CSS class (e.g., 'kingly-layout-padding-x-').
   * @param string $value
   *   The configuration value to use for the class suffix.
   */
  protected function applyClass(array &$build, string $class_prefix, string $value): void {
    if (!empty($value) && $value !== self::NONE_OPTION_KEY) {
      $build['#attributes']['class'][] = $class_prefix . $value;
    }
  }

}

<?php

namespace Drupal\kingly_layouts\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Implements various CSS alter hooks for the Kingly Layouts module.
 */
class CssAlterHooks {

  /**
   * Implements hook_css_alter().
   *
   * Adds custom font @import rules to the aggregated CSS.
   *
   * @param array $css
   *   An associative array of all CSS assets being processed. The keys are
   *   the file paths and the values are arrays of properties for the asset.
   * @param array $media
   *   The media query for which the CSS is being altered.
   * @param bool $preprocess
   *   TRUE if the CSS is being preprocessed, FALSE otherwise.
   */
  #[Hook('css_alter')]
  public function cssAlter(array &$css, array $media, bool $preprocess): void {
    foreach ($css as $key => $properties) {
      // Check if this is our custom_font library.
      if (isset($properties['provider']) && $properties['provider'] === 'kingly_layouts' && $properties['id'] === 'custom_font') {
        // Ensure we have the custom URL data.
        if (isset($properties['data']['url'])) {
          $custom_font_url = $properties['data']['url'];

          // Add the @import rule as an inline CSS asset.
          // This ensures it appears at the top of the CSS file.
          $css['@import:' . hash('sha256', $custom_font_url)] = [
            'type' => 'file',
            'media' => 'all',
            'preprocess' => $preprocess,
            'weight' => -1000,
            'group' => CSS_SYSTEM,
            'browsers' => ['IE' => TRUE, '!IE' => TRUE],
            // The data key holds the actual CSS content.
            'data' => '@import url("' . $custom_font_url . '");',
            'literals' => TRUE,
          ];

          // Remove the original custom_font library entry, as its only purpose
          // was to carry the URL for this hook.
          unset($css[$key]);
        }
      }
    }
  }

}

<?php

namespace Drupal\kingly_layouts\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for Kingly Layouts.
 */
class KinglyLayoutsHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    // Get theme implementations from the layout plugin manager.
    return \Drupal::service('plugin.manager.core.layout')
      ->getThemeImplementations();
  }

}

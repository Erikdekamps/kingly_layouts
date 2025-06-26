<?php

namespace Drupal\kingly_layouts\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Implements hook_theme() for the Kingly Layouts module.
 */
class KinglyLayoutsHooks {

  /**
   * {@inheritdoc}
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'kingly_background_video' => [
        'variables' => [
          'video_url' => NULL,
          'loop' => FALSE,
          'autoplay' => TRUE,
          'muted' => TRUE,
          'preload' => 'auto',
        ],
        'template' => 'kingly-background-video',
      ],
    ];
  }

}

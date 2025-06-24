<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

/**
 * A layout with one column that supports Kingly configuration.
 */
class KinglyOneColumnLayout extends KinglyLayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getSizingOptions(): array {
    // A one-column layout has no variable sizing options.
    // We return a single value to satisfy the abstract method requirement.
    return ['100' => $this->t('100%')];
  }

}

<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A layout with one column that supports Kingly configuration.
 */
#[Layout(
  id: 'kl_one_column',
  label: new TranslatableMarkup('One column'),
  category: new TranslatableMarkup('Kingly'),
  template: 'layout--kl-one-column',
  library: 'kingly_layouts/kl_layout_one_column',
  regions: [
    'content' => [
      'label' => new TranslatableMarkup('Content'),
    ],
  ],
  default_region: 'content'
)]
class KlOneColumnLayout extends KinglyLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function getSizingOptions(): array {
    // A one-column layout has no variable sizing options.
    // We return a single value to satisfy the abstract method requirement.
    return ['100' => $this->t('100%')];
  }

}

<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Three column layout with sizing options.
 */
#[Layout(
  id: 'kl_three_column',
  label: new TranslatableMarkup('Three column'),
  category: new TranslatableMarkup('Kingly'),
  template: 'layout--kl-three-column',
  library: 'kingly_layouts/kl_layout_three_column',
  regions: [
    'first' => [
      'label' => new TranslatableMarkup('First'),
    ],
    'second' => [
      'label' => new TranslatableMarkup('Second'),
    ],
    'third' => [
      'label' => new TranslatableMarkup('Third'),
    ],
  ],
  default_region: 'second'
)]
class KlThreeColumnLayout extends KinglyLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function getSizingOptions(): array {
    return [
      '33-34-33' => $this->t('33/34/33'),
      '25-50-25' => $this->t('25/50/25'),
      '25-25-50' => $this->t('25/25/50'),
      '50-25-25' => $this->t('50/25/25'),
    ];
  }

}

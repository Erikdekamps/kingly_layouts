<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Four column layout with sizing options.
 */
#[Layout(
  id: 'kl_four_column',
  label: new TranslatableMarkup('Four column'),
  category: new TranslatableMarkup('Kingly'),
  template: 'layout--kl-four-column',
  library: 'kingly_layouts/kl_layout_four_column',
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
    'fourth' => [
      'label' => new TranslatableMarkup('Fourth'),
    ],
  ],
  default_region: 'first'
)]
class KlFourColumnLayout extends KinglyLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function getSizingOptions(): array {
    return [
      '25-25-25-25' => $this->t('25/25/25/25'),
      '40-20-20-20' => $this->t('40/20/20/20'),
      '20-40-20-20' => $this->t('20/40/20/20'),
      '20-20-40-20' => $this->t('20/20/40/20'),
      '20-20-20-40' => $this->t('20/20/20/40'),
    ];
  }

}

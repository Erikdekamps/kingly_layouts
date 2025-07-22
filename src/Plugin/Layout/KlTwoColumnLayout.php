<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Two column layout with sizing options.
 */
#[Layout(
  id: 'kl_two_column',
  label: new TranslatableMarkup('Two column'),
  category: new TranslatableMarkup('Kingly'),
  template: 'layout--kl-two-column',
  library: 'kingly_layouts/kl_layout_two_column',
  regions: [
    'first' => [
      'label' => new TranslatableMarkup('First'),
    ],
    'second' => [
      'label' => new TranslatableMarkup('Second'),
    ],
  ],
  default_region: 'first'
)]
class KlTwoColumnLayout extends KinglyLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function getSizingOptions(): array {
    return [
      '50-50' => $this->t('50/50'),
      '25-75' => $this->t('25/75'),
      '75-25' => $this->t('75/25'),
      '33-67' => $this->t('33/67'),
      '67-33' => $this->t('67/33'),
    ];
  }

}

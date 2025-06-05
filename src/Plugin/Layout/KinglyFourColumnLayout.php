<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Four column layout with sizing options.
 */
#[Layout(
  id: "kingly_fourcol",
  label: new TranslatableMarkup("Kingly: Four column"),
  category: new TranslatableMarkup("Kingly"),
  template: "layout--kingly-fourcol",
  library: "kingly_layouts/kingly_fourcol",
  regions: [
    "first" => [
      "label" => new TranslatableMarkup("First"),
    ],
    "second" => [
      "label" => new TranslatableMarkup("Second"),
    ],
    "third" => [
      "label" => new TranslatableMarkup("Third"),
    ],
    "fourth" => [
      "label" => new TranslatableMarkup("Fourth"),
    ],
  ],
  default_region: "first"
)]
class KinglyFourColumnLayout extends KinglyLayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getSizingOptions(): array {
    return [
      '25-25-25-25' => $this->t('25/25/25/25'),
      '40-20-20-20' => $this->t('40/20/20/20'),
      '20-40-20-20' => $this->t('20/40/20/20'),
      '20-20-40-20' => $this->t('20/20/40/20'),
      '20-20-20-40' => $this->t('20/20/20/40'),
    ];
  }

}

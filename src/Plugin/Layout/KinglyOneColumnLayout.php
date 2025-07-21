<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A layout with one column that supports Kingly configuration.
 */
#[Layout(
  id: "kingly_onecol",
  label: new TranslatableMarkup("One column"),
  category: new TranslatableMarkup("Kingly"),
  template: "kl-one-column",
  library: "kingly_layouts/kingly_onecol",
  regions: [
    "content" => [
      "label" => new TranslatableMarkup("Content"),
    ],
  ],
  default_region: "content"
)]
class KinglyOneColumnLayout extends KinglyLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function getSizingOptions(): array {
    // A one-column layout has no variable sizing options.
    // We return a single value to satisfy the abstract method requirement.
    return ['100' => $this->t('100%')];
  }

}

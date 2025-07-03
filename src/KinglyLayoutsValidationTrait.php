<?php

namespace Drupal\kingly_layouts;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides validation methods for Kingly Layouts services.
 *
 * This trait is intended to be used by services that need to validate common
 * input types, such as color hex codes, to reduce code duplication.
 */
trait KinglyLayoutsValidationTrait {

  use StringTranslationTrait;

  /**
   * Validates a color hex code form element.
   *
   * @param array $element
   *   The form element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateColorHex(array &$element, FormStateInterface $form_state): void {
    $value = $element['#value'];
    // Check if a value is provided and if it matches the hex color pattern.
    // The pattern ensures it starts with '#' and is followed by exactly 6 hex
    // characters.
    if (!empty($value) && !preg_match('/^#([a-fA-F0-9]{6})$/', $value)) {
      $form_state->setError(
        $element,
        $this->t('The color must be a valid 6-digit hex code starting with # (e.g., #RRGGBB).')
      );
    }
  }

}

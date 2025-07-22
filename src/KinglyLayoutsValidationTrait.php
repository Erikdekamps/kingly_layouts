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
    if (!empty($value) && !preg_match('/^#([a-fA-F0-9]{6})$/', $value)) {
      $form_state->setError(
        $element,
        $this->t('The color must be a valid 6-digit hex code starting with # (e.g., #RRGGBB).')
      );
    }
  }

  /**
   * Validates a CSS ID form element.
   *
   * @param array $element
   *   The form element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateCssId(array &$element, FormStateInterface $form_state): void {
    $value = $element['#value'];
    if (empty($value)) {
      return;
    }

    // A valid HTML ID must start with a letter and can only contain letters,
    // numbers, hyphens, and underscores. It cannot contain spaces.
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9\-_]*$/', $value)) {
      $form_state->setError(
        $element,
        $this->t('The Custom ID must start with a letter, contain no spaces, and can only consist of letters, numbers, hyphens (-), and underscores (_).')
      );
    }
  }

  /**
   * Validates a space-separated list of CSS classes.
   *
   * @param array $element
   *   The form element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateCssClasses(array &$element, FormStateInterface $form_state): void {
    $value = trim($element['#value']);
    if (empty($value)) {
      return;
    }

    // Split the string into individual classes by one or more whitespace chars.
    $classes = preg_split('/\s+/', $value);
    foreach ($classes as $class) {
      // Each class must follow the same rules as an ID for safety.
      if (!preg_match('/^[a-zA-Z][a-zA-Z0-9\-_]*$/', $class)) {
        $form_state->setError(
          $element,
          $this->t("The class '@class_name' is not valid. Each class must start with a letter and can only contain letters, numbers, hyphens (-), and underscores (_).", ['@class_name' => $class])
        );
        // Stop after the first error to avoid overwhelming the user.
        break;
      }
    }
  }

}

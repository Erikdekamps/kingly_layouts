<?php

namespace Drupal\kingly_layouts\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Implements various form alter hooks for the Kingly Layouts module.
 */
class FormAlterHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Adds validation to the 'field_kingly_css_color' field on its term form
   * to ensure a valid hex code format is used.
   */
  #[Hook('form_alter')]
  public function formTaxonomyTermKinglyCssColorFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Only act on the specific form for the 'kingly_css_color' vocabulary.
    if ($form_id !== 'taxonomy_term_kingly_css_color_form') {
      return;
    }

    // Check if the color field widget is present on the form.
    if (isset($form['field_kingly_css_color']['widget'][0]['value'])) {
      $color_field = &$form['field_kingly_css_color']['widget'][0]['value'];

      // Add a client-side pattern for immediate browser validation.
      $color_field['#pattern'] = '^#([A-Fa-f0-9]{6})$';

      // Enhance the description to guide the user.
      $color_field['#description'] .= ' ' . $this->t('Must be a 6-digit hex code starting with #, e.g., #1a2b3c.');
    }

    // Add a server-side validation handler for robust data integrity.
    // This runs even if client-side validation is bypassed.
    $form['#validate'][] = [$this, 'validateCssColorHex'];
  }

  /**
   * Custom validation handler for the Kingly CSS Color term form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateCssColorHex(array &$form, FormStateInterface $form_state): void {
    $color_value = $form_state->getValue(['field_kingly_css_color', 0, 'value']);

    // If a value is entered, it must match the required pattern.
    if (!empty($color_value) && !preg_match('/^#([a-fA-F0-9]{6})$/', $color_value)) {
      $form_state->setErrorByName(
        'field_kingly_css_color',
        $this->t('The CSS Color must be a valid 6-digit hex code starting with #.')
      );
    }
  }

}

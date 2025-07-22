<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;

/**
 * Service to generate and process responsive form fields for display options.
 *
 * This service allows other display option services (like SpacingService) to
 * easily create form fields that are configurable per breakpoint. It handles
 * the form generation, submission, and CSS class processing logic.
 */
class ResponsiveFieldService {

  use StringTranslationTrait;

  /**
   * The breakpoint service.
   *
   * @var \Drupal\kingly_layouts\Service\BreakpointService
   */
  protected BreakpointService $breakpointService;

  /**
   * Constructs a new ResponsiveFieldService object.
   *
   * @param \Drupal\kingly_layouts\Service\BreakpointService $breakpoint_service
   *   The breakpoint service.
   */
  public function __construct(BreakpointService $breakpoint_service) {
    $this->breakpointService = $breakpoint_service;
  }

  /**
   * Builds a set of responsive fields for a given configuration key.
   *
   * @param string $config_key
   *   The base configuration key (e.g., 'horizontal_padding_option').
   * @param array $base_field
   *   The base Form API element definition (e.g., a select list).
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return array
   *   A Form API array containing the set of fields for each breakpoint.
   */
  public function buildResponsiveFields(string $config_key, array $base_field, array $configuration): array {
    $fields_container = [
      '#type' => 'container',
      '#attributes' => ['class' => ['kl-responsive-fields']],
    ];

    $breakpoints = $this->breakpointService->getBreakpoints();
    foreach ($breakpoints as $id => $breakpoint) {
      // Each field gets a unique key based on the breakpoint ID.
      $field_key = $config_key . '__' . $id;

      $fields_container[$field_key] = $base_field;
      // Modify the title for each breakpoint field for clarity.
      $fields_container[$field_key]['#title'] = $breakpoint['label'];

      // Fetch the default value from the nested configuration structure.
      $default_value = $configuration[$config_key][$id] ?? KinglyLayoutsDisplayOptionInterface::NONE_OPTION_KEY;
      $fields_container[$field_key]['#default_value'] = $default_value;

      // Remove description from subsequent fields to avoid repetition.
      if ($id !== 'mobile') {
        unset($fields_container[$field_key]['#description']);
      }
    }

    // Wrap the container in a fieldset to group them visually.
    return [
      '#type' => 'fieldset',
      '#title' => $base_field['#title'],
      '#description' => $base_field['#description'] ?? NULL,
      'fields' => $fields_container,
    ];
  }

  /**
   * Handles form submission for a set of responsive fields.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array &$configuration
   *   The layout's configuration array, passed by reference.
   * @param string $config_key
   *   The base configuration key (e.g., 'horizontal_padding_option').
   * @param array $form_values
   *   The submitted values for the parent form group (e.g., 'spacing').
   */
  public function submitResponsiveFields(FormStateInterface $form_state, array &$configuration, string $config_key, array $form_values): void {
    $breakpoints = $this->breakpointService->getBreakpoints();
    $responsive_values = [];

    foreach ($breakpoints as $id => $breakpoint) {
      $field_key = $config_key . '__' . $id;
      if (isset($form_values['fields'][$field_key])) {
        $responsive_values[$id] = $form_values['fields'][$field_key];
      }
    }

    // Store the values in a nested array under the main config key.
    $configuration[$config_key] = $responsive_values;
  }

  /**
   * Processes responsive configuration to generate CSS classes.
   *
   * @param array &$build
   *   The render array for the layout, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   * @param string $config_key
   *   The base configuration key (e.g., 'horizontal_padding_option').
   * @param string $class_prefix
   *   The CSS class prefix (e.g., 'kl-padding-x-').
   *
   * @return bool
   *   TRUE if any classes were added, FALSE otherwise.
   */
  public function processResponsiveClasses(array &$build, array $configuration, string $config_key, string $class_prefix): bool {
    $has_classes = FALSE;
    if (empty($configuration[$config_key]) || !is_array($configuration[$config_key])) {
      return FALSE;
    }

    $breakpoints = $this->breakpointService->getBreakpoints();
    $values = $configuration[$config_key];

    foreach ($breakpoints as $id => $breakpoint) {
      $value = $values[$id] ?? NULL;

      // Ensure we have a value and it's not the 'None' option.
      $none_key = KinglyLayoutsDisplayOptionInterface::NONE_OPTION_KEY;
      if (!empty($value) && $value !== $none_key) {
        $class = $breakpoint['prefix'] . $class_prefix . $value;
        $build['#attributes']['class'][] = $class;
        $has_classes = TRUE;
      }
    }
    return $has_classes;
  }

}

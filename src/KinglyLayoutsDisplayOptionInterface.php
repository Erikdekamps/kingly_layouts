<?php

namespace Drupal\kingly_layouts;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Kingly Layouts display option services.
 *
 * Each service handles a specific category of layout settings, like Spacing,
 * Borders, etc. It is responsible for building the configuration form elements,
 * handling the form submission, and processing the build array to add classes
 * and styles.
 */
interface KinglyLayoutsDisplayOptionInterface {

  /**
   * The key used for the "None" option in select lists.
   */
  public const NONE_OPTION_KEY = '_none';

  /**
   * Builds the form elements for this display option category.
   *
   * @param array $form
   *   The form array to which the elements will be added.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return array
   *   The updated form array.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array;

  /**
   * Handles the submission of the configuration form for this category.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array &$configuration
   *   The layout's configuration array, passed by reference.
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void;

  /**
   * Processes the layout's build array to add classes, styles, or other data.
   *
   * @param array &$build
   *   The render array for the layout, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   */
  public function processBuild(array &$build, array $configuration): void;

  /**
   * Provides the default configuration values for this display option.
   *
   * This method must be static as it can be called before the plugin or
   * service is fully instantiated.
   *
   * @return array
   *   An associative array of default configuration values.
   */
  public static function defaultConfiguration(): array;

}

<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;

/**
 * Service to manage color options for Kingly Layouts.
 *
 * This service now manages a direct color picker for foreground color.
 */
class ColorService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * Constructs a new ColorService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form['colors'] = [
      '#type' => 'details',
      '#title' => $this->t('Colors'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts colors'),
    ];

    $form['colors']['foreground_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Foreground Color'),
      '#default_value' => $configuration['foreground_color'],
      '#description' => $this->t('Select the text color for this section. Enter a hex code (e.g., #000000).'),
      '#attributes' => [
        'type' => 'color',
      ],
      '#pattern' => '#[0-9a-fA-F]{6}',
      // Add server-side validation for the hex color format.
      '#element_validate' => [[$this, 'validateColorHex']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $values = $form_state->getValue('colors', []);
    // Ensure the hex code is stored, or an empty string if not provided.
    $configuration['foreground_color'] = $values['foreground_color'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    $foreground_color = $configuration['foreground_color'];
    // Validate if the stored color is a valid hex code before applying.
    if (!empty($foreground_color) && preg_match('/^#([a-fA-F0-9]{6})$/', $foreground_color)) {
      $build['#attributes']['style'][] = 'color: ' . $foreground_color . ';';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'foreground_color' => '',
    ];
  }

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
      $form_state->setError($element, $this->t('The color must be a valid 6-digit hex code starting with # (e.g., #RRGGBB).'));
    }
  }

}

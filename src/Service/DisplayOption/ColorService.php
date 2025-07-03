<?php

namespace Drupal\kingly_layouts\Service\DisplayOption;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;
use Drupal\kingly_layouts\KinglyLayoutsValidationTrait;

/**
 * Service to manage color options for Kingly Layouts.
 *
 * This service now manages a direct color picker for foreground color.
 */
class ColorService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;
  use KinglyLayoutsValidationTrait;

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
  public function getFormKey(): string {
    return 'color';
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
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form_key = $this->getFormKey();
    $form[$form_key] = [
      '#type' => 'details',
      '#title' => $this->t('Color'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts colors'),
    ];

    $form[$form_key]['foreground_color'] = [
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
    $form_key = $this->getFormKey();
    $values = $form_state->getValue($form_key, []);
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

}

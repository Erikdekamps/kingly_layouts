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

    // Use an "enable" checkbox to control whether a color is set.
    $form[$form_key]['foreground_color_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set a foreground color'),
      // The checkbox is ticked if a color value is already saved.
      '#default_value' => !empty($configuration['foreground_color']),
    ];

    $form[$form_key]['foreground_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Foreground Color'),
      // Set a default to prevent saving 'black' if enabled but untouched.
      '#default_value' => $configuration['foreground_color'] ?: '#ffffff',
      '#description' => $this->t('Select the text color for this section. Enter a hex code (e.g., #FFFFFF).'),
      '#element_validate' => [[$this, 'validateColorHex']],
      // The color picker is only visible if the 'enable' checkbox is ticked.
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[' . $form_key . '][foreground_color_enable]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $form_key = $this->getFormKey();
    $values = $form_state->getValue($form_key, []);

    // Check the 'enable' checkbox to decide what to save.
    if (!empty($values['foreground_color_enable'])) {
      // If enabled, save the color value.
      $configuration['foreground_color'] = $values['foreground_color'] ?? '';
    }
    else {
      // If not enabled, save an empty string to signify "no color".
      $configuration['foreground_color'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    $foreground_color = $configuration['foreground_color'];
    // This check now correctly handles an empty string for "no color".
    if (!empty($foreground_color) && preg_match('/^#([a-fA-F0-9]{6})$/', $foreground_color)) {
      $build['#attributes']['style'][] = 'color: ' . $foreground_color . ';';
    }
  }

}

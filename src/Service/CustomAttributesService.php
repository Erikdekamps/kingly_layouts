<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;

/**
 * Service to manage custom attribute options for Kingly Layouts.
 */
class CustomAttributesService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * Constructs a new CustomAttributesService object.
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
    $form['custom_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom Attributes'),
      '#open' => FALSE,
      '#weight' => 100,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts custom attributes'),
    ];
    $form['custom_attributes']['custom_css_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom ID'),
      '#default_value' => $configuration['custom_css_id'],
      '#description' => $this->t('Enter a unique ID for this layout section (e.g., `my-unique-section`). Must be unique on the page and contain only letters, numbers, hyphens, and underscores.'),
    ];
    $form['custom_attributes']['custom_css_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom CSS Classes'),
      '#default_value' => $configuration['custom_css_class'],
      '#description' => $this->t('Add one or more custom CSS classes to this layout section, separated by spaces (e.g., `my-custom-class another-class`).'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $values = $form_state->getValue('custom_attributes', []);
    $configuration['custom_css_id'] = trim($values['custom_css_id'] ?? '');
    $configuration['custom_css_class'] = trim($values['custom_css_class'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    if (!empty($configuration['custom_css_id'])) {
      $build['#attributes']['id'] = $configuration['custom_css_id'];
    }
    if (!empty($configuration['custom_css_class'])) {
      $build['#attributes']['class'] = array_merge($build['#attributes']['class'] ?? [], explode(' ', $configuration['custom_css_class']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'custom_css_id' => '',
      'custom_css_class' => '',
    ];
  }

}

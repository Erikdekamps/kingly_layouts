<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;

/**
 * Service to manage sizing options for Kingly Layouts.
 */
class SizingService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * Constructs a new SizingService object.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    // Get layout instance from form state.
    $layout = $form_state->get('layout_instance');

    // Try to get layout from form object if available.
    if (!$layout) {
      $form_object = $form_state->getFormObject();
      if ($form_object && method_exists($form_object, 'getEntity')) {
        $layout = $form_object->getEntity();
      }
    }

    if (!$layout || !method_exists($layout, 'getSizingOptions')) {
      return $form;
    }

    $sizing_options = $layout->getSizingOptions();

    // Only show sizing form if there are actual options to choose from.
    if (count($sizing_options) > 1) {
      $form['sizing_option'] = [
        '#type' => 'select',
        '#title' => $this->t('Column sizing'),
        '#options' => $sizing_options,
        '#default_value' => $configuration['sizing_option'] ?? key($sizing_options),
        '#description' => $this->t('Select the desired column width distribution.'),
        '#weight' => -10,
        '#access' => $this->currentUser->hasPermission('administer kingly layouts sizing'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $sizing_value = $form_state->getValue('sizing_option');
    if ($sizing_value !== NULL) {
      $configuration['sizing_option'] = $sizing_value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    if (!empty($configuration['sizing_option']) && $configuration['sizing_option'] !== 'default') {
      $layout_id = $build['#layout']->getPluginDefinition()->id() ?? 'unknown';
      $build['#attributes']['class'][] = 'layout--' . $layout_id . '--' . $configuration['sizing_option'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'sizing_option' => 'default',
    ];
  }

}

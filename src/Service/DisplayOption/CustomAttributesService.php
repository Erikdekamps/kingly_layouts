<?php

namespace Drupal\kingly_layouts\Service\DisplayOption;

use Drupal\Core\Form\FormStateInterface;

/**
 * Service to manage custom attribute options for Kingly Layouts.
 */
class CustomAttributesService extends DisplayOptionBase {

  /**
   *
   */
  public function getFormKey(): string {
    return 'custom_attributes';
  }

  /**
   *
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form_key = $this->getFormKey();
    $form[$form_key] = [
      '#type' => 'details',
      '#title' => $this->t('Custom Attributes'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts custom attributes'),
    ];
    $form[$form_key]['custom_css_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom ID'),
      '#default_value' => $configuration['custom_css_id'],
      '#description' => $this->t('Enter a unique ID for this layout section (e.g., `my-unique-section`).'),
      '#element_validate' => [[$this, 'validateCssId']],
    ];
    $form[$form_key]['custom_css_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom CSS Classes'),
      '#default_value' => $configuration['custom_css_class'],
      '#description' => $this->t('Add one or more custom CSS classes, separated by spaces.'),
      '#element_validate' => [[$this, 'validateCssClasses']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $form_key = $this->getFormKey();
    $values = $form_state->getValue($form_key, []);
    // Using preg_split ensures multiple spaces are collapsed to a single space.
    $configuration['custom_css_id'] = trim($values['custom_css_id'] ?? '');
    $configuration['custom_css_class'] = implode(' ', preg_split('/\s+/', trim($values['custom_css_class'] ?? '')));
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

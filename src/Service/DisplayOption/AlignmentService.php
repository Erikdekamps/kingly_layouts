<?php

namespace Drupal\kingly_layouts\Service\DisplayOption;

use Drupal\Core\Form\FormStateInterface;

/**
 * Service to manage alignment options for Kingly Layouts.
 */
class AlignmentService extends DisplayOptionBase {

  /**
   * {@inheritdoc}
   */
  public function getFormKey(): string {
    return 'alignment';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form_key = $this->getFormKey();
    $form[$form_key] = [
      '#type' => 'details',
      '#title' => $this->t('Alignment'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts alignment'),
    ];

    $form[$form_key]['vertical_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Alignment'),
      '#options' => $this->getAlignmentOptions('vertical'),
      '#default_value' => $configuration['vertical_alignment'],
      '#description' => $this->t('Align content vertically within the layout. This assumes the layout uses Flexbox or Grid. "Stretch" makes columns in the same row equal height.'),
    ];

    $form[$form_key]['horizontal_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Alignment'),
      '#options' => $this->getAlignmentOptions('horizontal'),
      '#default_value' => $configuration['horizontal_alignment'],
      '#description' => $this->t('Justify content horizontally within the layout. This assumes the layout uses Flexbox or Grid.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $form_key = $this->getFormKey();
    $alignment_values = $form_state->getValue($form_key, []);
    $defaults = self::defaultConfiguration();
    $configuration['vertical_alignment'] = $alignment_values['vertical_alignment'] ?? $defaults['vertical_alignment'];
    $configuration['horizontal_alignment'] = $alignment_values['horizontal_alignment'] ?? $defaults['horizontal_alignment'];
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    // Always apply alignment classes based on the saved configuration. The
    // check against the default value was flawed, as it prevented the class
    // from being applied if the user saved the form with the default selected.
    // This ensures a class is always present, providing consistent behavior.
    $this->applyClassFromConfig($build, 'kl-align-content-', 'vertical_alignment', $configuration);
    $this->applyClassFromConfig($build, 'kl-justify-content-', 'horizontal_alignment', $configuration);

    // Since an alignment is always active (either default or user-selected),
    // we always attach the library.
    $build['#attached']['library'][] = 'kingly_layouts/alignment';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'vertical_alignment' => 'center',
      'horizontal_alignment' => 'start',
    ];
  }

  /**
   * Returns alignment options.
   *
   * @param string $type
   *   The type of alignment options ('vertical' or 'horizontal').
   *
   * @return array
   *   An array of alignment options.
   */
  private function getAlignmentOptions(string $type): array {
    if ($type === 'vertical') {
      return [
        'stretch' => $this->t('Stretch'),
        'flex-start' => $this->t('Top'),
        'center' => $this->t('Center (Default)'),
        'flex-end' => $this->t('Bottom'),
        'baseline' => $this->t('Baseline'),
      ];
    }
    if ($type === 'horizontal') {
      return [
        'start' => $this->t('Start (Left)'),
        'center' => $this->t('Center'),
        'end' => $this->t('End (Right)'),
        'space-between' => $this->t('Space Between'),
        'space-around' => $this->t('Space Around'),
        'space-evenly' => $this->t('Space Evenly'),
      ];
    }
    return [];
  }

}

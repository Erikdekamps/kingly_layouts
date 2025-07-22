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
    $v_align = $configuration['vertical_alignment'];
    $h_align = $configuration['horizontal_alignment'];
    $defaults = self::defaultConfiguration();

    // Determine if any non-default alignment is set to attach the library.
    $is_v_align_set = $v_align !== $defaults['vertical_alignment'];
    $is_h_align_set = $h_align !== $defaults['horizontal_alignment'];

    // Apply vertical alignment class if set.
    $this->applyVerticalAlignmentClass($build, $configuration, $is_v_align_set);

    // Apply horizontal alignment class if set.
    $this->applyHorizontalAlignmentClass($build, $configuration, $is_h_align_set);

    // Attach the library only if alignment options are used.
    $this->attachAlignmentLibrary($build, $is_v_align_set, $is_h_align_set);
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

  /**
   * Applies the vertical alignment CSS class to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   * @param bool $is_v_align_set
   *   TRUE if a non-default vertical alignment is set, FALSE otherwise.
   */
  private function applyVerticalAlignmentClass(array &$build, array $configuration, bool $is_v_align_set): void {
    if ($is_v_align_set) {
      $this->applyClassFromConfig($build, 'kl-align-content-', 'vertical_alignment', $configuration);
    }
  }

  /**
   * Applies the horizontal alignment CSS class to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   * @param bool $is_h_align_set
   *   TRUE if a non-default horizontal alignment is set, FALSE otherwise.
   */
  private function applyHorizontalAlignmentClass(array &$build, array $configuration, bool $is_h_align_set): void {
    if ($is_h_align_set) {
      $this->applyClassFromConfig($build, 'kl-justify-content-', 'horizontal_alignment', $configuration);
    }
  }

  /**
   * Attaches the alignment library if any alignment option is active.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param bool $is_v_align_set
   *   TRUE if vertical alignment is set.
   * @param bool $is_h_align_set
   *   TRUE if horizontal alignment is set.
   */
  private function attachAlignmentLibrary(array &$build, bool $is_v_align_set, bool $is_h_align_set): void {
    if ($is_v_align_set || $is_h_align_set) {
      $build['#attached']['library'][] = 'kingly_layouts/alignment';
    }
  }

}

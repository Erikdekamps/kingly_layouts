<?php

namespace Drupal\kingly_layouts\Service\DisplayOption;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\Service\ResponsiveFieldService;

/**
 * Service to manage spacing options for Kingly Layouts.
 *
 * This service now uses the ResponsiveFieldService to provide spacing controls
 * for different breakpoints.
 */
class SpacingService extends DisplayOptionBase {

  /**
   * The responsive field service.
   *
   * @var \Drupal\kingly_layouts\Service\ResponsiveFieldService
   */
  protected ResponsiveFieldService $responsiveFieldService;

  /**
   * Constructs a new SpacingService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\kingly_layouts\Service\ResponsiveFieldService $responsive_field_service
   *   The service for creating responsive fields.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation, ResponsiveFieldService $responsive_field_service) {
    parent::__construct($current_user, $string_translation);
    $this->responsiveFieldService = $responsive_field_service;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey(): string {
    return 'spacing';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form_key = $this->getFormKey();
    $form[$form_key] = [
      '#type' => 'details',
      '#title' => $this->t('Spacing'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts spacing'),
    ];

    // Define base fields for reuse.
    $padding_h_base = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Padding'),
      '#options' => $this->getScaleOptions(),
      '#description' => $this->t('Select padding for different breakpoints.'),
    ];
    $padding_v_base = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Padding'),
      '#options' => $this->getScaleOptions(),
      '#description' => $this->t('Select padding for different breakpoints.'),
    ];
    $gap_base = [
      '#type' => 'select',
      '#title' => $this->t('Gap'),
      '#options' => $this->getScaleOptions(),
      '#description' => $this->t('Select gap for different breakpoints.'),
    ];
    $margin_h_base = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Margin'),
      '#options' => $this->getScaleOptions(),
      '#description' => $this->t('Select margin for different breakpoints.'),
    ];
    $margin_v_base = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Margin'),
      '#options' => $this->getScaleOptions(),
      '#description' => $this->t('Select margin for different breakpoints.'),
    ];

    // Build responsive fields using the service.
    $form[$form_key]['horizontal_padding_option'] = $this->responsiveFieldService->buildResponsiveFields('horizontal_padding_option', $padding_h_base, $configuration);
    $form[$form_key]['vertical_padding_option'] = $this->responsiveFieldService->buildResponsiveFields('vertical_padding_option', $padding_v_base, $configuration);
    $form[$form_key]['gap_option'] = $this->responsiveFieldService->buildResponsiveFields('gap_option', $gap_base, $configuration);
    $form[$form_key]['horizontal_margin_option'] = $this->responsiveFieldService->buildResponsiveFields('horizontal_margin_option', $margin_h_base, $configuration);
    $form[$form_key]['vertical_margin_option'] = $this->responsiveFieldService->buildResponsiveFields('vertical_margin_option', $margin_v_base, $configuration);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $form_key = $this->getFormKey();
    $spacing_values = $form_state->getValue($form_key, []);

    // Let the responsive service handle the submission for each field.
    $this->responsiveFieldService->submitResponsiveFields($form_state, $configuration, 'horizontal_padding_option', $spacing_values['horizontal_padding_option']);
    $this->responsiveFieldService->submitResponsiveFields($form_state, $configuration, 'vertical_padding_option', $spacing_values['vertical_padding_option']);
    $this->responsiveFieldService->submitResponsiveFields($form_state, $configuration, 'gap_option', $spacing_values['gap_option']);
    $this->responsiveFieldService->submitResponsiveFields($form_state, $configuration, 'horizontal_margin_option', $spacing_values['horizontal_margin_option']);
    $this->responsiveFieldService->submitResponsiveFields($form_state, $configuration, 'vertical_margin_option', $spacing_values['vertical_margin_option']);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    // The configuration is now nested for responsiveness.
    return [
      'horizontal_padding_option' => [],
      'vertical_padding_option' => [],
      'gap_option' => [],
      'horizontal_margin_option' => [],
      'vertical_margin_option' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    $has_spacing = FALSE;

    // Use the responsive service to process classes for each property.
    $has_spacing = $this->responsiveFieldService->processResponsiveClasses($build, $configuration, 'horizontal_padding_option', 'kl-padding-x-') || $has_spacing;
    $has_spacing = $this->responsiveFieldService->processResponsiveClasses($build, $configuration, 'vertical_padding_option', 'kl-padding-y-') || $has_spacing;
    $has_spacing = $this->responsiveFieldService->processResponsiveClasses($build, $configuration, 'gap_option', 'kl-gap-') || $has_spacing;
    $has_spacing = $this->responsiveFieldService->processResponsiveClasses($build, $configuration, 'horizontal_margin_option', 'kl-margin-x-') || $has_spacing;
    $has_spacing = $this->responsiveFieldService->processResponsiveClasses($build, $configuration, 'vertical_margin_option', 'kl-margin-y-') || $has_spacing;

    if ($has_spacing) {
      // Attach the base library, which provides the consumer styles for
      // padding, margin, and gap variables.
      $build['#attached']['library'][] = 'kingly_layouts/base';
      // Also attach the spacing library, which provides the utility classes
      // that *set* the CSS variables.
      $build['#attached']['library'][] = 'kingly_layouts/spacing';
    }
  }

  /**
   * Returns the available padding scale options.
   *
   * @return array
   *   An associative array of padding scale options.
   */
  protected function getScaleOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('None'),
      'xs' => $this->t('Extra Small (0.25rem)'),
      'sm' => $this->t('Small (0.5rem)'),
      'md' => $this->t('Medium (1rem)'),
      'lg' => $this->t('Large (2rem)'),
      'xl' => $this->t('Extra Large (4rem)'),
    ];
  }

}

<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;
use Drupal\kingly_layouts\KinglyLayoutsUtilityTrait;

/**
 * Service to manage spacing options for Kingly Layouts.
 */
class SpacingService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;
  use KinglyLayoutsUtilityTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * Constructs a new SpacingService object.
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

    $form[$form_key]['horizontal_padding_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Padding'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $configuration['horizontal_padding_option'],
      '#description' => $this->t('Select the horizontal padding for the layout. For "Full Width (Background Only)" layouts, this padding is added to the default content alignment. For "Edge to Edge" layouts, this padding is applied from the viewport edge.'),
    ];

    $form[$form_key]['vertical_padding_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Padding'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $configuration['vertical_padding_option'],
      '#description' => $this->t('Select the desired vertical padding (top and bottom) for the layout container.'),
    ];

    $form[$form_key]['gap_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Gap'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $configuration['gap_option'],
      '#description' => $this->t('Select the desired gap between layout columns/regions.'),
    ];

    $form[$form_key]['horizontal_margin_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Margin'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $configuration['horizontal_margin_option'],
      '#description' => $this->t('Select the horizontal margin for the layout. This margin will not be applied if "Full Width" or "Edge to Edge" is selected.'),
    ];

    $form[$form_key]['vertical_margin_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Margin'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $configuration['vertical_margin_option'],
      '#description' => $this->t('Select the desired vertical margin (top and bottom) for the layout container.'),
    ];

    return $form;
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

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $form_key = $this->getFormKey();
    $spacing_values = $form_state->getValue($form_key, []);
    foreach ([
      'horizontal_padding_option',
      'vertical_padding_option',
      'gap_option',
      'horizontal_margin_option',
      'vertical_margin_option',
    ] as $key) {
      $configuration[$key] = $spacing_values[$key] ?? self::defaultConfiguration()[$key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'horizontal_padding_option' => self::NONE_OPTION_KEY,
      'vertical_padding_option' => self::NONE_OPTION_KEY,
      'gap_option' => self::NONE_OPTION_KEY,
      'horizontal_margin_option' => self::NONE_OPTION_KEY,
      'vertical_margin_option' => self::NONE_OPTION_KEY,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    $has_spacing = $this->determineLibraryAttachment($configuration);

    // Apply spacing utility classes.
    $this->applyPaddingClasses($build, $configuration);
    $this->applyGapClass($build, $configuration);
    $this->applyMarginClasses($build, $configuration);

    if ($has_spacing) {
      $build['#attached']['library'][] = 'kingly_layouts/spacing';
    }
  }

  /**
   * Determines if the spacing library should be attached.
   *
   * Checks if any non-default spacing options are set.
   *
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return bool
   *   TRUE if the spacing library should be attached, FALSE otherwise.
   */
  private function determineLibraryAttachment(array $configuration): bool {
    $defaults = self::defaultConfiguration();
    $spacing_options_keys = [
      'horizontal_padding_option',
      'vertical_padding_option',
      'gap_option',
      'horizontal_margin_option',
      'vertical_margin_option',
    ];

    foreach ($spacing_options_keys as $option_key) {
      if (($configuration[$option_key] ?? $defaults[$option_key]) !== $defaults[$option_key]) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Applies padding-related CSS classes to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   */
  private function applyPaddingClasses(array &$build, array $configuration): void {
    // Determine effective horizontal padding based on container type.
    $container_type = $configuration['container_type'];
    $h_padding_effective = $configuration['horizontal_padding_option'];

    // For 'full' and 'edge-to-edge' container types, the horizontal padding
    // is handled differently by CSS. The `kl-padding-x-` class
    // sets a CSS variable, which the container CSS then consumes.
    // 'hero' also handles horizontal padding differently.
    $this->applyClassFromConfig($build, 'kl-padding-x-', $h_padding_effective, $configuration);
    $this->applyClassFromConfig($build, 'kl-padding-y-', 'vertical_padding_option', $configuration);
  }

  /**
   * Applies gap-related CSS classes to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   */
  private function applyGapClass(array &$build, array $configuration): void {
    $this->applyClassFromConfig($build, 'kl-gap-', 'gap_option', $configuration);
  }

  /**
   * Applies margin-related CSS classes to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   */
  private function applyMarginClasses(array &$build, array $configuration): void {
    $container_type = $configuration['container_type'];
    $apply_horizontal_margin = TRUE;

    // Horizontal margins are typically not applied to full-width or hero
    // layouts.
    switch ($container_type) {
      case 'full':
      case 'edge-to-edge':
      case 'hero':
        $apply_horizontal_margin = FALSE;
        break;
    }

    if ($apply_horizontal_margin) {
      $this->applyClassFromConfig($build, 'kl-margin-x-', 'horizontal_margin_option', $configuration);
    }
    $this->applyClassFromConfig($build, 'kl-margin-y-', 'vertical_margin_option', $configuration);
  }

}

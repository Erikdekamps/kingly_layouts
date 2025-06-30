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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form['spacing'] = [
      '#type' => 'details',
      '#title' => $this->t('Spacing'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts spacing'),
    ];

    $form['spacing']['horizontal_padding_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Padding'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $configuration['horizontal_padding_option'],
      '#description' => $this->t('Select the horizontal padding for the layout. For "Full Width (Background Only)" layouts, this padding is added to the default content alignment. For "Edge to Edge" layouts, this padding is applied from the viewport edge.'),
    ];

    $form['spacing']['vertical_padding_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Padding'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $configuration['vertical_padding_option'],
      '#description' => $this->t('Select the desired vertical padding (top and bottom) for the layout container.'),
    ];

    $form['spacing']['gap_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Gap'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $configuration['gap_option'],
      '#description' => $this->t('Select the desired gap between layout columns/regions.'),
    ];

    $form['spacing']['horizontal_margin_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Margin'),
      '#options' => $this->getScaleOptions(),
      '#default_value' => $configuration['horizontal_margin_option'],
      '#description' => $this->t('Select the horizontal margin for the layout. This margin will not be applied if "Full Width" or "Edge to Edge" is selected.'),
    ];

    $form['spacing']['vertical_margin_option'] = [
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
    $spacing_values = $form_state->getValue('spacing', []);
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
    $has_spacing = FALSE;
    $defaults = self::defaultConfiguration();
    $spacing_options = [
      'horizontal_padding_option',
      'vertical_padding_option',
      'gap_option',
      'horizontal_margin_option',
      'vertical_margin_option',
    ];

    foreach ($spacing_options as $option) {
      if (($configuration[$option] ?? $defaults[$option]) !== $defaults[$option]) {
        $has_spacing = TRUE;
        break;
      }
    }

    if ($has_spacing) {
      $build['#attached']['library'][] = 'kingly_layouts/spacing';
    }

    // Determine effective padding and margin based on container type.
    $container_type = $configuration['container_type'];
    $h_padding_effective = $configuration['horizontal_padding_option'];
    $apply_horizontal_margin = TRUE;

    switch ($container_type) {
      case 'full':
        $apply_horizontal_margin = FALSE;
        break;

      case 'edge-to-edge':
      case 'hero':
        // The CSS for these container types handles padding differently.
        // We only apply the class, and CSS variables do the rest.
        $apply_horizontal_margin = FALSE;
        break;
    }

    // Apply spacing utility classes.
    $this->applyClassFromConfig($build, 'kingly-layout-padding-x-', $h_padding_effective, $configuration);
    $this->applyClassFromConfig($build, 'kingly-layout-padding-y-', 'vertical_padding_option', $configuration);
    $this->applyClassFromConfig($build, 'kingly-layout-gap-', 'gap_option', $configuration);
    $this->applyClassFromConfig($build, 'kingly-layout-margin-y-', 'vertical_margin_option', $configuration);

    if ($apply_horizontal_margin) {
      $this->applyClassFromConfig($build, 'kingly-layout-margin-x-', 'horizontal_margin_option', $configuration);
    }
  }

}

<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;
use Drupal\kingly_layouts\KinglyLayoutsUtilityTrait;

/**
 * Service to manage alignment options for Kingly Layouts.
 */
class AlignmentService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;
  use KinglyLayoutsUtilityTrait;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * The options service.
   */
  protected OptionsService $optionsService;

  /**
   * Constructs a new AlignmentService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\kingly_layouts\Service\OptionsService $options_service
   *   The options service.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation, OptionsService $options_service) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
    $this->optionsService = $options_service;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form['alignment'] = [
      '#type' => 'details',
      '#title' => $this->t('Alignment'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts alignment'),
    ];

    $form['alignment']['vertical_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Alignment'),
      '#options' => $this->optionsService->getOptions('vertical_alignment'),
      '#default_value' => $configuration['vertical_alignment'],
      '#description' => $this->t('Align content vertically within the layout. This assumes the layout uses Flexbox or Grid. "Stretch" makes columns in the same row equal height.'),
    ];

    $form['alignment']['horizontal_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Alignment'),
      '#options' => $this->optionsService->getOptions('horizontal_alignment'),
      '#default_value' => $configuration['horizontal_alignment'],
      '#description' => $this->t('Justify content horizontally within the layout. This assumes the layout uses Flexbox or Grid.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $alignment_values = $form_state->getValue('alignment', []);
    $defaults = self::defaultConfiguration();
    $configuration['vertical_alignment'] = $alignment_values['vertical_alignment'] ?? $defaults['vertical_alignment'];
    $configuration['horizontal_alignment'] = $alignment_values['horizontal_alignment'] ?? $defaults['horizontal_alignment'];
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    $this->applyClassFromConfig($build, 'kingly-layout-align-content-', 'vertical_alignment', $configuration);
    $this->applyClassFromConfig($build, 'kingly-layout-justify-content-', 'horizontal_alignment', $configuration);
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

}

<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;

/**
 * Service to manage responsiveness options for Kingly Layouts.
 */
class ResponsivenessService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * The options service.
   */
  protected OptionsService $optionsService;

  /**
   * Constructs a new ResponsivenessService object.
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
    $form['responsiveness'] = [
      '#type' => 'details',
      '#title' => $this->t('Responsiveness'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts responsiveness'),
    ];

    $form['responsiveness']['hide_on_breakpoint'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Hide on Breakpoint'),
      '#options' => $this->optionsService->getOptions('hide_on_breakpoint'),
      '#default_value' => $configuration['hide_on_breakpoint'],
      '#description' => $this->t('Hide this entire layout section on specific screen sizes.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $values = $form_state->getValue('responsiveness', []);
    $configuration['hide_on_breakpoint'] = array_filter($values['hide_on_breakpoint'] ?? []);
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    if (!empty($configuration['hide_on_breakpoint'])) {
      $build['#attached']['library'][] = 'kingly_layouts/responsiveness';
      foreach ($configuration['hide_on_breakpoint'] as $breakpoint) {
        if ($breakpoint) {
          $build['#attributes']['class'][] = 'kingly-layout-hide-on-' . $breakpoint;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'hide_on_breakpoint' => [],
    ];
  }

}

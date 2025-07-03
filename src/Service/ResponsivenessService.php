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
   * Constructs a new ResponsivenessService object.
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
    return 'responsiveness';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form_key = $this->getFormKey();
    $form[$form_key] = [
      '#type' => 'details',
      '#title' => $this->t('Responsiveness'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts responsiveness'),
    ];

    $form[$form_key]['hide_on_breakpoint'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Hide on Breakpoint'),
      '#options' => $this->getBreakpointOptions(),
      '#default_value' => $configuration['hide_on_breakpoint'],
      '#description' => $this->t('Hide this entire layout section on specific screen sizes.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $form_key = $this->getFormKey();
    $values = $form_state->getValue($form_key, []);
    $configuration['hide_on_breakpoint'] = array_filter($values['hide_on_breakpoint'] ?? []);
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    if ($this->shouldApplyResponsiveness($configuration)) {
      // Attach the necessary library for responsiveness.
      $this->attachResponsivenessLibrary($build);
      // Apply the CSS classes to hide the element on specified breakpoints.
      $this->applyHideOnBreakpointClasses($build, $configuration);
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

  /**
   * Gets the options for the breakpoint checkboxes.
   *
   * @return array
   *   An array of breakpoint options.
   */
  private function getBreakpointOptions(): array {
    return [
      'mobile' => $this->t('Mobile'),
      'tablet' => $this->t('Tablet'),
      'desktop' => $this->t('Desktop'),
    ];
  }

  /**
   * Determines if responsiveness features should be applied.
   *
   * @param array $configuration
   *   The layout's current configuration.
   *
   * @return bool
   *   TRUE if any responsiveness option is configured, FALSE otherwise.
   */
  private function shouldApplyResponsiveness(array $configuration): bool {
    return !empty($configuration['hide_on_breakpoint']);
  }

  /**
   * Attaches the responsiveness library to the build array.
   *
   * @param array &$build
   *   The render array, passed by reference.
   */
  private function attachResponsivenessLibrary(array &$build): void {
    $build['#attached']['library'][] = 'kingly_layouts/responsiveness';
  }

  /**
   * Applies CSS classes to hide the element on specific breakpoints.
   *
   * @param array &$build
   *   The render array, passed by reference.
   * @param array $configuration
   *   The layout's current configuration.
   */
  private function applyHideOnBreakpointClasses(array &$build, array $configuration): void {
    foreach ($configuration['hide_on_breakpoint'] as $breakpoint) {
      if ($breakpoint) {
        $build['#attributes']['class'][] = 'kl-hide-on-' . $breakpoint;
      }
    }
  }

}

<?php

namespace Drupal\kingly_layouts\Service\DisplayOption;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;

/**
 * Service to manage responsiveness options for Kingly Layouts.
 *
 * This service uses a simple checkbox model to hide sections on specific
 * breakpoints, providing a more intuitive user experience.
 */
class ResponsivenessService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
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
  public function __construct(
    AccountInterface $current_user,
    TranslationInterface $string_translation,
  ) {
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
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state,
    array $configuration,
  ): array {
    $form_key = $this->getFormKey();
    $form[$form_key] = [
      '#type' => 'details',
      '#title' => $this->t('Responsiveness'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission(
        'administer kingly layouts responsiveness'
      ),
    ];

    $form[$form_key]['hide_on_breakpoints'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Visibility'),
      '#options' => $this->getBreakpointOptions(),
      '#default_value' => $configuration['hide_on_breakpoints'],
      '#description' => $this->t(
        'Select the breakpoints where this section should be completely hidden
        (using CSS <code>display: none</code>).'
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(
    array $form,
    FormStateInterface $form_state,
    array &$configuration,
  ): void {
    $form_key = $this->getFormKey();
    $values = $form_state->getValue($form_key, []);
    // We filter the array to only store the keys of the checked boxes.
    $configuration['hide_on_breakpoints'] = array_filter(
      $values['hide_on_breakpoints'] ?? []
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    $breakpoints_to_hide = $configuration['hide_on_breakpoints'] ?? [];

    if (!empty($breakpoints_to_hide)) {
      // Attach the library needed for the responsive classes.
      $build['#attached']['library'][] = 'kingly_layouts/responsiveness';

      // Add a CSS class for each breakpoint that was selected.
      foreach ($breakpoints_to_hide as $breakpoint_id) {
        if ($breakpoint_id) {
          $build['#attributes']['class'][] = 'kl-hide-on-' . $breakpoint_id;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'hide_on_breakpoints' => [],
    ];
  }

  /**
   * Gets the options for the breakpoint checkboxes.
   *
   * @return array
   *   An array of breakpoint options.
   */
  private function getBreakpointOptions(): array {
    // The keys ('mobile', 'md', 'lg') directly map to the CSS class suffixes.
    return [
      'mobile' => $this->t('Hide on Mobile (up to 767px)'),
      'md' => $this->t('Hide on Medium (768px - 1023px)'),
      'lg' => $this->t('Hide on Large (1024px and up)'),
    ];
  }

}

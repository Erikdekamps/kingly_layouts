<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;
use Drupal\kingly_layouts\KinglyLayoutsUtilityTrait;

/**
 * Service to manage container type options for Kingly Layouts.
 */
class ContainerTypeService implements KinglyLayoutsDisplayOptionInterface {

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
   * Constructs a new ContainerTypeService object.
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
    $form['container_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Container Type'),
      '#options' => $this->optionsService->getOptions('container_type'),
      '#default_value' => $configuration['container_type'],
      '#description' => $this->t("Select how the layout container should behave: <br> <strong>Boxed:</strong> Standard container with a maximum width. <br> <strong>Full Width (Background Only):</strong> The background spans the full viewport width, but the content remains aligned with the site's main content area. Horizontal padding will be applied *within* this content area. <br> <strong>Edge to Edge (Full Bleed):</strong> Both the background and content span the full viewport width. <br> <strong>Full Screen Hero:</strong> The section fills the entire viewport height and width."),
      '#weight' => -9,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts container type'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $configuration['container_type'] = $form_state->getValue('container_type', 'boxed');
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    if (!empty($configuration['container_type'])) {
      // The container library is always needed as 'boxed' is the default.
      $build['#attached']['library'][] = 'kingly_layouts/containers';
      $build['#attributes']['class'][] = 'kingly-layout--' . $configuration['container_type'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'container_type' => 'boxed',
    ];
  }

}

<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Service to manage color options for Kingly Layouts.
 */
class ColorService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;

  /**
   * The key used for the "None" option in select lists.
   */
  protected const NONE_OPTION_KEY = '_none';

  /**
   * The ID of the taxonomy vocabulary used for CSS colors.
   */
  protected const KINGLY_CSS_COLOR_VOCABULARY = 'kingly_css_color';

  /**
   * The field name on the taxonomy term that stores the hex color value.
   */
  protected const KINGLY_CSS_COLOR_FIELD = 'field_kingly_css_color';

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * The options service.
   */
  protected OptionsService $optionsService;

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs a new ColorService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\kingly_layouts\Service\OptionsService $options_service
   *   The options service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation, OptionsService $options_service, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
    $this->optionsService = $options_service;
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $color_options = $this->optionsService->getColorOptions();

    $form['colors'] = [
      '#type' => 'details',
      '#title' => $this->t('Colors'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts colors'),
    ];

    if (count($color_options) > 1) {
      $form['colors']['foreground_color'] = [
        '#type' => 'select',
        '#title' => $this->t('Foreground Color'),
        '#options' => $color_options,
        '#default_value' => $configuration['foreground_color'],
      ];
      $form['colors']['color_info'] = [
        '#type' => 'item',
        '#markup' => $this->t('Colors are managed in the <a href="/admin/structure/taxonomy/manage/@vocab_id/overview" target="_blank">Kingly CSS Color</a> vocabulary.', ['@vocab_id' => self::KINGLY_CSS_COLOR_VOCABULARY]),
      ];
    }
    else {
      $form['colors']['color_info'] = [
        '#type' => 'item',
        '#title' => $this->t('Color Options'),
        '#markup' => $this->t('No colors defined. Please <a href="/admin/structure/taxonomy/manage/@vocab_id/add" target="_blank">add terms</a> to the "Kingly CSS Color" vocabulary.', ['@vocab_id' => self::KINGLY_CSS_COLOR_VOCABULARY]),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $values = $form_state->getValue('colors', []);
    $configuration['foreground_color'] = $values['foreground_color'] ?? self::NONE_OPTION_KEY;
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    if ($color_hex = $this->getTermColorHex($configuration['foreground_color'])) {
      $build['#attributes']['style'][] = 'color: ' . $color_hex . ';';
    }
  }

  /**
   * Retrieves the hex color value from a Kingly CSS Color taxonomy term.
   *
   * @param string $term_id
   *   The ID of the taxonomy term.
   *
   * @return string|null
   *   The hex color string if found and valid, NULL otherwise.
   */
  public function getTermColorHex(string $term_id): ?string {
    if (empty($term_id) || $term_id === self::NONE_OPTION_KEY) {
      return NULL;
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->termStorage->load($term_id);

    if ($term instanceof TermInterface &&
      $term->bundle() === self::KINGLY_CSS_COLOR_VOCABULARY &&
      $term->hasField(self::KINGLY_CSS_COLOR_FIELD) &&
      !$term->get(self::KINGLY_CSS_COLOR_FIELD)->isEmpty()) {
      return $term->get(self::KINGLY_CSS_COLOR_FIELD)->value;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'foreground_color' => self::NONE_OPTION_KEY,
    ];
  }

}

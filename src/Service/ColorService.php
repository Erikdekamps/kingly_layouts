<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Cache\CacheBackendInterface;
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
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The cache backend.
   */
  protected CacheBackendInterface $cache;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * A static cache for hex values to avoid redundant loads within a request.
   *
   * @var array
   */
  private static array $hexCache = [];

  /**
   * Constructs a new ColorService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
    $this->entityTypeManager = $entity_type_manager;
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $color_options = $this->getColorOptions();

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
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'foreground_color' => self::NONE_OPTION_KEY,
    ];
  }

  /**
   * Returns color options from the 'kingly_css_color' vocabulary.
   *
   * @return array
   *   An associative array of color options.
   */
  public function getColorOptions(): array {
    $cid = 'kingly_layouts:color_options';
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $options = [
      self::NONE_OPTION_KEY => $this->t('None'),
    ];

    // The vocabulary config entity itself is a cache dependency.
    $cache_tags = ['config:taxonomy.vocabulary.' . self::KINGLY_CSS_COLOR_VOCABULARY];

    if ($this->entityTypeManager->getStorage('taxonomy_vocabulary')
      ->load(self::KINGLY_CSS_COLOR_VOCABULARY)) {
      // The list of terms in the vocabulary is also a cache dependency.
      $cache_tags[] = 'taxonomy_term_list:' . self::KINGLY_CSS_COLOR_VOCABULARY;
      $terms = $this->termStorage->loadTree(self::KINGLY_CSS_COLOR_VOCABULARY, 0, NULL, TRUE);
      foreach ($terms as $term) {
        $options[$term->id()] = $term->getName();
      }
    }

    $this->cache->set($cid, $options, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);

    return $options;
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

    // Return from static cache if available.
    if (isset(self::$hexCache[$term_id])) {
      return self::$hexCache[$term_id];
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->termStorage->load($term_id);
    $hex_value = NULL;

    if ($term instanceof TermInterface &&
      $term->bundle() === self::KINGLY_CSS_COLOR_VOCABULARY &&
      $term->hasField(self::KINGLY_CSS_COLOR_FIELD) &&
      !$term->get(self::KINGLY_CSS_COLOR_FIELD)->isEmpty()) {
      $hex_value = $term->get(self::KINGLY_CSS_COLOR_FIELD)->value;
    }

    // Store the result (even if null) in the static cache and return it.
    self::$hexCache[$term_id] = $hex_value;
    return $hex_value;
  }

}

<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Service for resolving CSS colors from taxonomy terms.
 */
class ColorResolver {

  protected const NONE_OPTION_KEY = '_none';
  protected const KINGLY_CSS_COLOR_VOCABULARY = 'kingly_css_color';
  protected const KINGLY_CSS_COLOR_FIELD = 'field_kingly_css_color';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new ColorResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache_backend) {
    $this->entityTypeManager = $entity_type_manager;
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $this->cache = $cache_backend;
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
   * Converts a hex color string to an RGB array.
   *
   * @param string $hex
   *   The hex color string (e.g., "#RRGGBB" or "RRGGBB").
   *
   * @return array|null
   *   An array [R, G, B] if successful, NULL otherwise.
   */
  public function hexToRgb(string $hex): ?array {
    $hex = ltrim($hex, '#');

    if (strlen($hex) === 3) {
      $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
      $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
      $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
    }
    elseif (strlen($hex) === 6) {
      $r = hexdec(substr($hex, 0, 2));
      $g = hexdec(substr($hex, 2, 2));
      $b = hexdec(substr($hex, 4, 2));
    }
    else {
      return NULL;
    }

    return [$r, $g, $b];
  }

  /**
   * Returns the key used for the "None" option.
   *
   * @return string
   *   The "None" option key.
   */
  public function getNoneOptionKey(): string {
    return self::NONE_OPTION_KEY;
  }

}

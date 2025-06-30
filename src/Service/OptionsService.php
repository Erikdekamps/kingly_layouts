<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides centralized option lists for layout configuration forms.
 */
class OptionsService {

  use StringTranslationTrait;

  /**
   * The key used for the "None" option in select lists.
   */
  public const NONE_OPTION_KEY = '_none';

  /**
   * The ID of the taxonomy vocabulary used for CSS colors.
   */
  protected const KINGLY_CSS_COLOR_VOCABULARY = 'kingly_css_color';

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

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
   * Constructs a new OptionsService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache_backend, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $this->cache = $cache_backend;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Returns an array of options for a given key.
   *
   * @param string $key
   *   The key for the options array to retrieve.
   *
   * @return array
   *   An associative array of options.
   */
  public function getOptions(string $key): array {
    $none = [self::NONE_OPTION_KEY => $this->t('None')];

    // Special case for border_radius which combines scale options with another.
    if ($key === 'border_radius') {
      return $this->getScaleOptions() + ['full' => $this->t('Full (Pill/Circle)')];
    }

    $options = [
      'container_type' => [
        'boxed' => $this->t('Boxed'),
        'full' => $this->t('Full Width (Background Only)'),
        'edge-to-edge' => $this->t('Edge to Edge (Full Bleed)'),
        'hero' => $this->t('Full Screen Hero'),
      ],
      'border_width' => $none + [
        'sm' => $this->t('Small (1px)'),
        'md' => $this->t('Medium (2px)'),
        'lg' => $this->t('Large (4px)'),
      ],
      'border_style' => $none + [
        'solid' => $this->t('Solid'),
        'dashed' => $this->t('Dashed'),
        'dotted' => $this->t('Dotted'),
      ],
      'vertical_alignment' => [
        'stretch' => $this->t('Stretch'),
        'flex-start' => $this->t('Top'),
        'center' => $this->t('Center (Default)'),
        'flex-end' => $this->t('Bottom'),
        'baseline' => $this->t('Baseline'),
      ],
      'horizontal_alignment' => [
        'start' => $this->t('Start (Left)'),
        'center' => $this->t('Center'),
        'end' => $this->t('End (Right)'),
        'space-between' => $this->t('Space Between'),
        'space-around' => $this->t('Space Around'),
        'space-evenly' => $this->t('Space Evenly'),
      ],
      'animation_type' => $none + [
        'fade-in' => $this->t('Fade In'),
        'slide-in' => $this->t('Slide In'),
      ],
      'slide_direction' => $none + [
        'up' => $this->t('Bottom up'),
        'down' => $this->t('Top down'),
        'left' => $this->t('Right to Left'),
        'right' => $this->t('Left to Right'),
      ],
      'transition_property' => [
        self::NONE_OPTION_KEY => $this->t('Default (opacity, transform)'),
        'opacity' => $this->t('Opacity only'),
        'transform' => $this->t('Transform only'),
        'all' => $this->t('All properties'),
        'opacity, transform' => $this->t('Opacity and Transform'),
      ],
      'transition_duration' => [
        self::NONE_OPTION_KEY => $this->t('Default (600ms)'),
        '150ms' => $this->t('150ms'),
        '300ms' => $this->t('300ms'),
        '500ms' => $this->t('500ms'),
        '750ms' => $this->t('750ms'),
        '1s' => $this->t('1s'),
      ],
      'transition_timing_function' => [
        self::NONE_OPTION_KEY => $this->t('Default (ease-out)'),
        'ease' => $this->t('ease'),
        'ease-in' => $this->t('ease-in'),
        'ease-in-out' => $this->t('ease-in-out'),
        'linear' => $this->t('linear'),
      ],
      'transition_delay' => $none + [
        '150ms' => $this->t('150ms'),
        '300ms' => $this->t('300ms'),
        '500ms' => $this->t('500ms'),
        '750ms' => $this->t('750ms'),
        '1s' => $this->t('1s'),
      ],
      'background_type' => [
        'color' => $this->t('Color'),
        'image' => $this->t('Image'),
        'video' => $this->t('Video'),
        'gradient' => $this->t('Gradient'),
      ],
      'background_opacity' => [
        self::NONE_OPTION_KEY => $this->t('100% (Default)'),
        '90' => $this->t('90%'),
        '75' => $this->t('75%'),
        '50' => $this->t('50%'),
        '25' => $this->t('25%'),
        '0' => $this->t('0% (Transparent)'),
      ],
      'background_overlay_opacity' => $none + [
        '25' => $this->t('25%'),
        '50' => $this->t('50%'),
        '75' => $this->t('75%'),
        '90' => $this->t('90%'),
      ],
      'background_image_position' => [
        'center center' => $this->t('Center Center'),
        'center top' => $this->t('Center Top'),
        'center bottom' => $this->t('Center Bottom'),
        'left top' => $this->t('Left Top'),
        'left center' => $this->t('Left Center'),
        'left bottom' => $this->t('Left Bottom'),
        'right top' => $this->t('Right Top'),
        'right center' => $this->t('Right Center'),
        'right bottom' => $this->t('Right Bottom'),
      ],
      'background_image_repeat' => [
        'no-repeat' => $this->t('No Repeat'),
        'repeat' => $this->t('Repeat'),
        'repeat-x' => $this->t('Repeat Horizontally'),
        'repeat-y' => $this->t('Repeat Vertically'),
      ],
      'background_image_size' => [
        'cover' => $this->t('Cover'),
        'contain' => $this->t('Contain'),
        'auto' => $this->t('Auto'),
      ],
      'background_image_attachment' => [
        'scroll' => $this->t('Scroll'),
        'fixed' => $this->t('Fixed (Parallax)'),
        'local' => $this->t('Local'),
      ],
      'background_gradient_type' => [
        'linear' => $this->t('Linear'),
        'radial' => $this->t('Radial'),
      ],
      'background_gradient_linear_direction' => [
        'to bottom' => $this->t('To Bottom (Default)'),
        'to top' => $this->t('To Top'),
        'to right' => $this->t('To Right'),
        'to left' => $this->t('To Left'),
        'to bottom right' => $this->t('To Bottom Right'),
        'to top left' => $this->t('To Top Left'),
        '45deg' => $this->t('45 Degrees'),
        '90deg' => $this->t('90 Degrees (To Right)'),
        '135deg' => $this->t('135 Degrees'),
        '180deg' => $this->t('180 Degrees (To Top)'),
        '225deg' => $this->t('225 Degrees'),
        '270deg' => $this->t('270 Degrees (To Left)'),
        '315deg' => $this->t('315 Degrees'),
      ],
      'background_gradient_radial_shape' => [
        'ellipse' => $this->t('Ellipse (Default)'),
        'circle' => $this->t('Circle'),
      ],
      'background_gradient_radial_position' => [
        'center' => $this->t('Center (Default)'),
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
        'top left' => $this->t('Top Left'),
        'top right' => $this->t('Top Right'),
        'bottom left' => $this->t('Bottom Left'),
        'bottom right' => $this->t('Bottom Right'),
      ],
      'box_shadow' => $none + [
        'sm' => $this->t('Small'),
        'md' => $this->t('Medium'),
        'lg' => $this->t('Large'),
        'xl' => $this->t('Extra Large'),
        'inner' => $this->t('Inner'),
      ],
      'filter' => $none + [
        'grayscale' => $this->t('Grayscale'),
        'blur' => $this->t('Blur'),
        'sepia' => $this->t('Sepia'),
        'brightness' => $this->t('Brightness'),
      ],
      'opacity' => [
        self::NONE_OPTION_KEY => $this->t('100% (Default)'),
        '0.9' => $this->t('90%'),
        '0.75' => $this->t('75%'),
        '0.5' => $this->t('50%'),
        '0.25' => $this->t('25%'),
        '0' => $this->t('0% (Transparent)'),
      ],
      'transform_scale' => [
        self::NONE_OPTION_KEY => $this->t('None (100%)'),
        '0.9' => $this->t('90%'),
        '0.95' => $this->t('95%'),
        '1.05' => $this->t('105%'),
        '1.1' => $this->t('110%'),
        '1.25' => $this->t('125%'),
      ],
      'transform_rotate' => $none + [
        '1' => $this->t('1 degree'),
        '2' => $this->t('2 degrees'),
        '3' => $this->t('3 degrees'),
        '5' => $this->t('5 degrees'),
        '-1' => $this->t('-1 degree'),
        '-2' => $this->t('-2 degrees'),
        '-3' => $this->t('-3 degrees'),
        '-5' => $this->t('-5 degrees'),
      ],
      'hide_on_breakpoint' => [
        'mobile' => $this->t('Mobile'),
        'tablet' => $this->t('Tablet'),
        'desktop' => $this->t('Desktop'),
      ],
    ];

    return $options[$key] ?? [];
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
   * Returns the available padding scale options.
   *
   * @return array
   *   An associative array of padding scale options.
   */
  public function getScaleOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('None'),
      'xs' => $this->t('Extra Small (0.25rem)'),
      'sm' => $this->t('Small (0.5rem)'),
      'md' => $this->t('Medium (1rem)'),
      'lg' => $this->t('Large (2rem)'),
      'xl' => $this->t('Extra Large (4rem)'),
    ];
  }

}

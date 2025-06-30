<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;

/**
 * Provides centralized option lists for layout configuration forms.
 */
class OptionsService {

  use StringTranslationTrait;

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
    // Use the constant from the interface where it is defined.
    $none = [KinglyLayoutsDisplayOptionInterface::NONE_OPTION_KEY => $this->t('None')];

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
        KinglyLayoutsDisplayOptionInterface::NONE_OPTION_KEY => $this->t('Default (opacity, transform)'),
        'opacity' => $this->t('Opacity only'),
        'transform' => $this->t('Transform only'),
        'all' => $this->t('All properties'),
        'opacity, transform' => $this->t('Opacity and Transform'),
      ],
      'transition_duration' => [
        KinglyLayoutsDisplayOptionInterface::NONE_OPTION_KEY => $this->t('Default (600ms)'),
        '150ms' => $this->t('150ms'),
        '300ms' => $this->t('300ms'),
        '500ms' => $this->t('500ms'),
        '750ms' => $this->t('750ms'),
        '1s' => $this->t('1s'),
      ],
      'transition_timing_function' => [
        KinglyLayoutsDisplayOptionInterface::NONE_OPTION_KEY => $this->t('Default (ease-out)'),
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
        KinglyLayoutsDisplayOptionInterface::NONE_OPTION_KEY => $this->t('100% (Default)'),
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
        KinglyLayoutsDisplayOptionInterface::NONE_OPTION_KEY => $this->t('100% (Default)'),
        '0.9' => $this->t('90%'),
        '0.75' => $this->t('75%'),
        '0.5' => $this->t('50%'),
        '0.25' => $this->t('25%'),
        '0' => $this->t('0% (Transparent)'),
      ],
      'transform_scale' => [
        KinglyLayoutsDisplayOptionInterface::NONE_OPTION_KEY => $this->t('None (100%)'),
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
      'hover_transform_scale' => $none + [
        '0.9' => $this->t('Scale Down (90%)'),
        '0.95' => $this->t('Slightly Scale Down (95%)'),
        '1.05' => $this->t('Slightly Scale Up (105%)'),
        '1.1' => $this->t('Scale Up (110%)'),
        '1.25' => $this->t('Enlarge (125%)'),
      ],
      'hover_box_shadow' => $none + [
        'sm' => $this->t('Small Shadow'),
        'md' => $this->t('Medium Shadow'),
        'lg' => $this->t('Large Shadow'),
        'xl' => $this->t('Extra Large Shadow'),
        'inner' => $this->t('Inner Shadow'),
      ],
      'hover_filter' => $none + [
        'grayscale-to-color' => $this->t('Grayscale to Color'),
        'brightness-down' => $this->t('Brightness Down'),
        'brightness-up' => $this->t('Brightness Up'),
      ],
      'hide_on_breakpoint' => [
        'mobile' => $this->t('Mobile'),
        'tablet' => $this->t('Tablet'),
        'desktop' => $this->t('Desktop'),
      ],
      'font_family' => $none + [
        'sans-serif' => $this->t('Sans-serif (Generic)'),
        'serif' => $this->t('Serif (Generic)'),
        'monospace' => $this->t('Monospace (Generic)'),
        'custom-import' => $this->t('Custom Font (via URL)'),
          // Add specific web-safe fonts or common system fonts here.
        'Arial, sans-serif' => $this->t('Arial'),
        'Verdana, sans-serif' => $this->t('Verdana'),
        'Helvetica, sans-serif' => $this->t('Helvetica'),
        'Times New Roman, serif' => $this->t('Times New Roman'),
        'Georgia, serif' => $this->t('Georgia'),
        'Courier New, monospace' => $this->t('Courier New'),
      ],
      'font_size' => $none + [
        '0.75rem' => $this->t('Extra Small (0.75rem)'),
        '0.875rem' => $this->t('Small (0.875rem)'),
        '1rem' => $this->t('Base (1rem)'),
        '1.125rem' => $this->t('Large (1.125rem)'),
        '1.25rem' => $this->t('Extra Large (1.25rem)'),
        '1.5rem' => $this->t('2XL (1.5rem)'),
        '1.875rem' => $this->t('3XL (1.875rem)'),
        '2.25rem' => $this->t('4XL (2.25rem)'),
        '3rem' => $this->t('5XL (3rem)'),
      ],
      'font_weight' => $none + [
        '100' => $this->t('Thin (100)'),
        '200' => $this->t('Extra Light (200)'),
        '300' => $this->t('Light (300)'),
        '400' => $this->t('Normal (400)'),
        '500' => $this->t('Medium (500)'),
        '600' => $this->t('Semi Bold (600)'),
        '700' => $this->t('Bold (700)'),
        '800' => $this->t('Extra Bold (800)'),
        '900' => $this->t('Black (900)'),
      ],
      'line_height' => $none + [
        '1' => $this->t('1 (Tight)'),
        '1.25' => $this->t('1.25'),
        '1.5' => $this->t('1.5 (Normal)'),
        '1.75' => $this->t('1.75'),
        '2' => $this->t('2 (Loose)'),
      ],
      'letter_spacing' => $none + [
        '-0.05em' => $this->t('-0.05em (Tight)'),
        '-0.025em' => $this->t('-0.025em'),
        '0em' => $this->t('0em (Normal)'),
        '0.025em' => $this->t('0.025em'),
        '0.05em' => $this->t('0.05em (Loose)'),
        '0.1em' => $this->t('0.1em (Extra Loose)'),
      ],
      'text_transform' => $none + [
        'none' => $this->t('None'),
        'uppercase' => $this->t('Uppercase'),
        'lowercase' => $this->t('Lowercase'),
        'capitalize' => $this->t('Capitalize'),
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
      KinglyLayoutsDisplayOptionInterface::NONE_OPTION_KEY => $this->t('None'),
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
      KinglyLayoutsDisplayOptionInterface::NONE_OPTION_KEY => $this->t('None'),
      'xs' => $this->t('Extra Small (0.25rem)'),
      'sm' => $this->t('Small (0.5rem)'),
      'md' => $this->t('Medium (1rem)'),
      'lg' => $this->t('Large (2rem)'),
      'xl' => $this->t('Extra Large (4rem)'),
    ];
  }

  /**
   * Generates a CSS font-family value suitable for a custom font import.
   *
   * This extracts the font family name from the Google Fonts URL structure
   * or provides a generic fallback.
   *
   * @param string $url
   *   The custom font URL.
   *
   * @return string
   *   The CSS font-family value, including a fallback.
   */
  public function getCustomFontImportCssValue(string $url): string {
    // Attempt to parse font family from Google Fonts URL.
    if (preg_match('/family=([^&:]+)/', $url, $matches)) {
      $font_name = str_replace('+', ' ', $matches[1]);
      // Add a generic fallback.
      return "'" . $font_name . "', sans-serif";
    }
    // Generic fallback if URL doesn't match a known pattern.
    return 'sans-serif';
  }

}

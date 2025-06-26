<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Kingly layouts with sizing and background options.
 */
abstract class KinglyLayoutBase extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface {

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
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
   * Constructs a new KinglyLayoutBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache_backend) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
    $this->cache = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = parent::defaultConfiguration();

    $sizing_options = $this->getSizingOptions();
    $configuration['sizing_option'] = key($sizing_options);

    // Add defaults for padding options.
    $padding_options = $this->getPaddingScaleOptions();
    $default_padding = key($padding_options);
    $configuration['horizontal_padding_option'] = $default_padding;
    $configuration['vertical_padding_option'] = $default_padding;

    // Add default for gap option.
    $gap_options = $this->getGapOptions();
    $configuration['gap_option'] = key($gap_options);

    // Default to no background or foreground color.
    $configuration['background_color'] = self::NONE_OPTION_KEY;
    $configuration['foreground_color'] = self::NONE_OPTION_KEY;

    // Add default for full width option.
    $configuration['full_width'] = FALSE;
    $configuration['edge_to_edge'] = FALSE;

    return $configuration;
  }

  /**
   * Returns the available sizing options for this layout.
   *
   * @return array
   *   An associative array of sizing options.
   */
  abstract protected function getSizingOptions(): array;

  /**
   * Returns the available padding scale options.
   *
   * @return array
   *   An associative array of padding scale options.
   */
  protected function getPaddingScaleOptions(): array {
    return [
      self::NONE_OPTION_KEY => $this->t('None'),
      'xs' => $this->t('Extra Small (0.125rem)'),
      'sm' => $this->t('Small (0.25rem)'),
      'md' => $this->t('Medium (0.5rem)'),
      'lg' => $this->t('Large (1rem)'),
      'xl' => $this->t('Extra Large (2rem)'),
    ];
  }

  /**
   * Returns the available gap options for this layout.
   *
   * @return array
   *   An associative array of gap options.
   */
  protected function getGapOptions(): array {
    return $this->getPaddingScaleOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['sizing_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Column sizing'),
      '#options' => $this->getSizingOptions(),
      '#default_value' => $this->configuration['sizing_option'],
      '#description' => $this->t('Select the desired column width distribution.'),
    ];

    $form['horizontal_padding_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Padding'),
      '#options' => $this->getHorizontalPaddingOptions(),
      '#default_value' => $this->configuration['horizontal_padding_option'],
      '#description' => $this->t('Select the horizontal padding for the layout. For "Edge to Edge" layouts, this padding is applied from the viewport edge.'),
    ];

    $form['vertical_padding_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Padding'),
      '#options' => $this->getVerticalPaddingOptions(),
      '#default_value' => $this->configuration['vertical_padding_option'],
      '#description' => $this->t('Select the desired vertical padding (top and bottom) for the layout container.'),
    ];

    $form['gap_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Gap'),
      '#options' => $this->getGapOptions(),
      '#default_value' => $this->configuration['gap_option'],
      '#description' => $this->t('Select the desired gap between layout columns/regions.'),
    ];

    $color_options = $this->getColorOptions();
    if (count($color_options) > 1) {
      $form['background_color'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Color'),
        '#options' => $color_options,
        '#default_value' => $this->configuration['background_color'],
        '#description' => $this->t('Select a background color.'),
      ];
      $form['foreground_color'] = [
        '#type' => 'select',
        '#title' => $this->t('Foreground Color'),
        '#options' => $color_options,
        '#default_value' => $this->configuration['foreground_color'],
        '#description' => $this->t('Select a foreground (text) color.'),
      ];
      $form['color_info'] = [
        '#type' => 'item',
        '#markup' => $this->t('Colors are managed in the <a href="/admin/structure/taxonomy/manage/@vocab_id/overview" target="_blank">Kingly CSS Color</a> vocabulary.', ['@vocab_id' => self::KINGLY_CSS_COLOR_VOCABULARY]),
      ];
    }
    else {
      $form['color_info'] = [
        '#type' => 'item',
        '#title' => $this->t('Color Options'),
        '#markup' => $this->t('No colors defined. Please <a href="/admin/structure/taxonomy/manage/@vocab_id/add" target="_blank">add terms</a> to the "Kingly CSS Color" vocabulary.', ['@vocab_id' => self::KINGLY_CSS_COLOR_VOCABULARY]),
      ];
    }

    // Edge to Edge option.
    $form['edge_to_edge'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Edge to Edge (Full Bleed)'),
      '#description' => $this->t('When checked, this layout and its content will span the full width of the viewport, breaking out of any parent containers. Horizontal padding will be applied relative to the viewport edges. This option disables "Full Width (Background Only)".'),
      '#default_value' => $this->configuration['edge_to_edge'],
      '#id' => 'kingly-layout-edge-to-edge-checkbox',
      '#states' => [
        'disabled' => [
          '#kingly-layout-full-width-checkbox' => ['checked' => TRUE],
        ],
      ],
    ];

    // Full width option.
    $form['full_width'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Full Width (Background Only)'),
      '#description' => $this->t('When checked, the background of this layout will span the full width of the viewport, breaking out of its container. The content within the layout will remain aligned with the site\'s main content grid. This option disables "Edge to Edge (Full Bleed)".'),
      '#default_value' => $this->configuration['full_width'],
      '#id' => 'kingly-layout-full-width-checkbox',
      '#states' => [
        'disabled' => [
          '#kingly-layout-edge-to-edge-checkbox' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Returns the available horizontal padding options for this layout.
   *
   * @return array
   *   An associative array of padding options.
   */
  protected function getHorizontalPaddingOptions(): array {
    return $this->getPaddingScaleOptions();
  }

  /**
   * Returns the available vertical padding options for this layout.
   *
   * @return array
   *   An associative array of padding options.
   */
  protected function getVerticalPaddingOptions(): array {
    return $this->getPaddingScaleOptions();
  }

  /**
   * Returns color options from the 'kingly_css_color' vocabulary.
   *
   * @return array
   *   An associative array of color options.
   */
  protected function getColorOptions(): array {
    $cid = 'kingly_layouts:color_options';
    // New: Try to load from cache.
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $options = [self::NONE_OPTION_KEY => $this->t('None')];
    if (!$this->entityTypeManager->getStorage('taxonomy_vocabulary')
      ->load(self::KINGLY_CSS_COLOR_VOCABULARY)) {
      // New: Cache empty options if vocabulary doesn't exist.
      $this->cache->set($cid, $options, CacheBackendInterface::CACHE_PERMANENT, ['taxonomy_term_list']);
      return $options;
    }
    $terms = $this->termStorage->loadTree(self::KINGLY_CSS_COLOR_VOCABULARY, 0, NULL, TRUE);
    foreach ($terms as $term) {
      $options[$term->id()] = $term->getName();
    }

    // New: Store in cache with a cache tag for invalidation.
    $this->cache->set($cid, $options, CacheBackendInterface::CACHE_PERMANENT, ['taxonomy_term_list']);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);

    $edge_to_edge = $form_state->getValue('edge_to_edge');
    $full_width = $form_state->getValue('full_width');

    // Ensure that only one of "Edge to Edge" or "Full Width" is selected.
    if ($edge_to_edge && $full_width) {
      $message = $this->t('You cannot select both "Edge to Edge" and "Full Width (Background Only)". Please choose only one or neither.');
      $form_state->setErrorByName('edge_to_edge', $message);
      $form_state->setErrorByName('full_width', $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['sizing_option'] = $form_state->getValue('sizing_option');
    $this->configuration['horizontal_padding_option'] = $form_state->getValue('horizontal_padding_option');
    $this->configuration['vertical_padding_option'] = $form_state->getValue('vertical_padding_option');
    $this->configuration['gap_option'] = $form_state->getValue('gap_option');
    $this->configuration['background_color'] = $form_state->getValue('background_color');
    $this->configuration['foreground_color'] = $form_state->getValue('foreground_color');
    $this->configuration['full_width'] = $form_state->getValue('full_width');
    $this->configuration['edge_to_edge'] = $form_state->getValue('edge_to_edge');
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $build = parent::build($regions);

    $build['#attached']['library'][] = 'kingly_layouts/kingly_utilities';

    $plugin_definition = $this->getPluginDefinition();
    $layout_id = $plugin_definition->id();

    // Add layout-specific sizing class.
    if (!empty($this->configuration['sizing_option'])) {
      $build['#attributes']['class'][] = 'layout--' . $layout_id . '--' . $this->configuration['sizing_option'];
    }

    // Determine effective horizontal padding and apply full width/edge-to-edge
    // classes.
    $h_padding_effective = $this->configuration['horizontal_padding_option'];
    if (!empty($this->configuration['edge_to_edge'])) {
      $build['#attributes']['class'][] = 'kingly-layout--edge-to-edge';
      // Edge to Edge uses custom padding from the viewport edge, so the utility
      // class for horizontal padding will be applied below based on
      // $h_padding_effective.
    }
    elseif (!empty($this->configuration['full_width'])) {
      $build['#attributes']['class'][] = 'kingly-layout--full-width';
      // Full Width uses its own `calc()` padding to align with the site grid,
      // so we prevent the padding utility class from being added.
      $h_padding_effective = self::NONE_OPTION_KEY;
    }

    // Apply horizontal padding class.
    if (!empty($h_padding_effective) && $h_padding_effective !== self::NONE_OPTION_KEY) {
      $build['#attributes']['class'][] = 'kingly-layout-padding-x-' . $h_padding_effective;
    }

    // Apply vertical padding class.
    $v_padding = $this->configuration['vertical_padding_option'];
    if (!empty($v_padding) && $v_padding !== self::NONE_OPTION_KEY) {
      $build['#attributes']['class'][] = 'kingly-layout-padding-y-' . $v_padding;
    }

    // Apply gap class.
    $gap = $this->configuration['gap_option'];
    if (!empty($gap) && $gap !== self::NONE_OPTION_KEY) {
      $build['#attributes']['class'][] = 'kingly-layout-gap-' . $gap;
    }

    // Apply background color.
    $background_color_hex = $this->getTermColorHex($this->configuration['background_color']);
    if ($background_color_hex) {
      $build['#attributes']['style'][] = 'background-color: ' . $background_color_hex . ';';
    }

    // Apply foreground color.
    $foreground_color_hex = $this->getTermColorHex($this->configuration['foreground_color']);
    if ($foreground_color_hex) {
      $build['#attributes']['style'][] = 'color: ' . $foreground_color_hex . ';';
    }

    return $build;
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
  protected function getTermColorHex(string $term_id): ?string {
    if (empty($term_id) || $term_id === self::NONE_OPTION_KEY) {
      return NULL;
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $this->termStorage->load($term_id);

    // Ensure the term exists, is of the correct bundle, has the color field,
    // and the field is not empty.
    if ($term instanceof TermInterface &&
      $term->bundle() === self::KINGLY_CSS_COLOR_VOCABULARY &&
      $term->hasField(self::KINGLY_CSS_COLOR_FIELD) &&
      !$term->get(self::KINGLY_CSS_COLOR_FIELD)->isEmpty()) {
      return $term->get(self::KINGLY_CSS_COLOR_FIELD)->value;
    }

    return NULL;
  }

}

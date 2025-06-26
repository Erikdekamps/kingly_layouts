<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Kingly layouts with sizing and background options.
 */
abstract class KinglyLayoutBase extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface {

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
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

    // Default to no background color.
    $configuration['background_color'] = '_none';

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
      '_none' => $this->t('None'),
      'xs' => $this->t('Extra Small (0.125rem)'),
      'sm' => $this->t('Small (0.25rem)'),
      'md' => $this->t('Medium (0.5rem)'),
      'lg' => $this->t('Large (1rem)'),
      'xl' => $this->t('Extra Large (2rem)'),
    ];
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
   * Returns background color options from the 'background_color' vocabulary.
   *
   * @return array
   *   An associative array of background color options.
   */
  protected function getBackgroundOptions(): array {
    $options = ['_none' => $this->t('None')];
    $terms = $this->termStorage->loadTree('background_color', 0, NULL, TRUE);
    foreach ($terms as $term) {
      $options[$term->id()] = $term->getName();
    }
    return $options;
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

    // Add the horizontal padding option.
    $form['horizontal_padding_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal Padding'),
      '#options' => $this->getHorizontalPaddingOptions(),
      '#default_value' => $this->configuration['horizontal_padding_option'],
      '#description' => $this->t('Select the desired horizontal padding (left and right) for the layout container.'),
    ];

    // Add the vertical padding option.
    $form['vertical_padding_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical Padding'),
      '#options' => $this->getVerticalPaddingOptions(),
      '#default_value' => $this->configuration['vertical_padding_option'],
      '#description' => $this->t('Select the desired vertical padding (top and bottom) for the layout container.'),
    ];

    $background_options = $this->getBackgroundOptions();
    if (count($background_options) > 1) {
      $form['background_color'] = [
        '#type' => 'select',
        '#title' => $this->t('Background Color'),
        '#options' => $background_options,
        '#default_value' => $this->configuration['background_color'],
        '#description' => $this->t('Select a background color. Colors are managed in the <a href="/admin/structure/taxonomy/manage/background_color/overview" target="_blank">Background Color</a> vocabulary.'),
      ];
    }
    else {
      $form['background_color_info'] = [
        '#type' => 'item',
        '#title' => $this->t('Background Color'),
        '#markup' => $this->t('No background colors defined. Please <a href="/admin/structure/taxonomy/manage/background_color/add" target="_blank">add terms</a> to the "Background Color" vocabulary.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['sizing_option'] = $form_state->getValue('sizing_option');
    // Save the new padding options.
    $this->configuration['horizontal_padding_option'] = $form_state->getValue('horizontal_padding_option');
    $this->configuration['vertical_padding_option'] = $form_state->getValue('vertical_padding_option');
    $this->configuration['background_color'] = $form_state->getValue('background_color');
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $build = parent::build($regions);

    // Attach the shared padding library.
    $build['#attached']['library'][] = 'kingly_layouts/padding';

    $plugin_definition = $this->getPluginDefinition();
    // e.g., 'kingly_fourcol'.
    $layout_id = $plugin_definition->id();

    // Add the sizing option as a class (this remains layout-specific).
    if (!empty($this->configuration['sizing_option'])) {
      $build['#attributes']['class'][] = 'layout--' . $layout_id . '--' . $this->configuration['sizing_option'];
    }

    // Add the horizontal padding option as a generic utility class.
    $h_padding = $this->configuration['horizontal_padding_option'];
    if (!empty($h_padding) && $h_padding !== '_none') {
      $build['#attributes']['class'][] = 'kingly-layout-padding-x-' . $h_padding;
    }

    // Add the vertical padding option as a generic utility class.
    $v_padding = $this->configuration['vertical_padding_option'];
    if (!empty($v_padding) && $v_padding !== '_none') {
      $build['#attributes']['class'][] = 'kingly-layout-padding-y-' . $v_padding;
    }

    // Add the background color as an inline style.
    $background_tid = $this->configuration['background_color'];
    if (!empty($background_tid) && $background_tid !== '_none') {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $this->termStorage->load($background_tid);
      if ($term && $term->bundle() === 'background_color' && $term->hasField('field_css_color') && !$term->get('field_css_color')->isEmpty()) {
        $hex_color = $term->get('field_css_color')->value;
        $build['#attributes']['style'][] = 'background-color: ' . $hex_color . ';';
        // Set text color for contrast.
        $build['#attributes']['style'][] = 'color: ' . $this->getContrastColor($hex_color) . ';';
      }
    }

    return $build;
  }

  /**
   * Determines if text should be black or white based on background color.
   *
   * @param string $hex_color
   *   The hex color code (e.g., '#RRGGBB').
   *
   * @return string
   *   Returns '#000000' (black) or '#ffffff' (white).
   */
  protected function getContrastColor(string $hex_color): string {
    $hex_color = ltrim($hex_color, '#');
    if (strlen($hex_color) === 3) {
      $hex_color = str_repeat($hex_color[0], 2) . str_repeat($hex_color[1], 2) . str_repeat($hex_color[2], 2);
    }
    if (strlen($hex_color) !== 6) {
      // Default to black for invalid hex.
      return '#000000';
    }
    $r = hexdec(substr($hex_color, 0, 2));
    $g = hexdec(substr($hex_color, 2, 2));
    $b = hexdec(substr($hex_color, 4, 2));
    // Formula from http://www.w3.org/TR/AERT#color-contrast
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($yiq >= 128) ? '#000000' : '#ffffff';
  }

}

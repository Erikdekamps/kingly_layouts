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

    // Add default for gap option.
    $gap_options = $this->getGapOptions();
    $configuration['gap_option'] = key($gap_options);

    // Default to no background or foreground color.
    $configuration['background_color'] = '_none';
    $configuration['foreground_color'] = '_none';

    // Add default for full width option.
    $configuration['full_width'] = FALSE;

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
      '#description' => $this->t('Select the desired horizontal padding (left and right) for the layout container.'),
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
        '#markup' => $this->t('Colors are managed in the <a href="/admin/structure/taxonomy/manage/kingly_css_color/overview" target="_blank">Kingly CSS Color</a> vocabulary.'),
      ];
    }
    else {
      $form['color_info'] = [
        '#type' => 'item',
        '#title' => $this->t('Color Options'),
        '#markup' => $this->t('No colors defined. Please <a href="/admin/structure/taxonomy/manage/kingly_css_color/add" target="_blank">add terms</a> to the "Kingly CSS Color" vocabulary.'),
      ];
    }

    // ADDED: Full width option.
    $form['full_width'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Full Width'),
      '#description' => $this->t('Check this to make the layout span the full width of the viewport, breaking out of its container.'),
      '#default_value' => $this->configuration['full_width'],
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
    $options = ['_none' => $this->t('None')];
    if (!$this->entityTypeManager->getStorage('taxonomy_vocabulary')
      ->load('kingly_css_color')) {
      return $options;
    }
    $terms = $this->termStorage->loadTree('kingly_css_color', 0, NULL, TRUE);
    foreach ($terms as $term) {
      $options[$term->id()] = $term->getName();
    }
    return $options;
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
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $build = parent::build($regions);

    $build['#attached']['library'][] = 'kingly_layouts/kingly_utilities';

    $plugin_definition = $this->getPluginDefinition();
    $layout_id = $plugin_definition->id();

    if (!empty($this->configuration['sizing_option'])) {
      $build['#attributes']['class'][] = 'layout--' . $layout_id . '--' . $this->configuration['sizing_option'];
    }

    $h_padding = $this->configuration['horizontal_padding_option'];
    if (!empty($h_padding) && $h_padding !== '_none') {
      $build['#attributes']['class'][] = 'kingly-layout-padding-x-' . $h_padding;
    }

    $v_padding = $this->configuration['vertical_padding_option'];
    if (!empty($v_padding) && $v_padding !== '_none') {
      $build['#attributes']['class'][] = 'kingly-layout-padding-y-' . $v_padding;
    }

    $gap = $this->configuration['gap_option'];
    if (!empty($gap) && $gap !== '_none') {
      $build['#attributes']['class'][] = 'kingly-layout-gap-' . $gap;
    }

    $background_tid = $this->configuration['background_color'];
    if (!empty($background_tid) && $background_tid !== '_none') {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $this->termStorage->load($background_tid);
      if ($term && $term->bundle() === 'kingly_css_color' && $term->hasField('field_kingly_css_color') && !$term->get('field_kingly_css_color')
        ->isEmpty()) {
        $hex_color = $term->get('field_kingly_css_color')->value;
        $build['#attributes']['style'][] = 'background-color: ' . $hex_color . ';';
      }
    }

    $foreground_tid = $this->configuration['foreground_color'];
    if (!empty($foreground_tid) && $foreground_tid !== '_none') {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $this->termStorage->load($foreground_tid);
      if ($term && $term->bundle() === 'kingly_css_color' && $term->hasField('field_kingly_css_color') && !$term->get('field_kingly_css_color')
        ->isEmpty()) {
        $hex_color = $term->get('field_kingly_css_color')->value;
        $build['#attributes']['style'][] = 'color: ' . $hex_color . ';';
      }
    }

    // Apply full width class if enabled.
    if (!empty($this->configuration['full_width'])) {
      // We add a class instead of an inline style. This is cleaner and
      // allows us to manage the full-width behavior and the inner container
      // padding in one place in our CSS.
      $build['#attributes']['class'][] = 'kingly-layout--full-width';
    }

    return $build;
  }

}

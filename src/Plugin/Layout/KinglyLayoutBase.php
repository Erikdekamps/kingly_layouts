<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\kingly_layouts\Service\DisplayOptionCollector;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Kingly layouts with sizing and background options.
 */
abstract class KinglyLayoutBase extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Constructs a new KinglyLayoutBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\kingly_layouts\Service\DisplayOptionCollector $displayOptionCollector
   *   The service that collects all display option services.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AccountInterface $currentUser,
    protected DisplayOptionCollector $displayOptionCollector,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('kingly_layouts.display_option_collector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Sizing option logic is now handled directly here.
    $sizing_options = $this->getSizingOptions();
    if (count($sizing_options) > 1 && $this->currentUser->hasPermission('administer kingly layouts sizing')) {
      $form['sizing_option'] = [
        '#type' => 'select',
        '#title' => $this->t('Column sizing'),
        '#options' => $sizing_options,
        '#default_value' => $this->configuration['sizing_option'] ?? key($sizing_options),
        '#description' => $this->t('Select the desired column width distribution.'),
      // Ensure this appears first.
        '#weight' => -100,
      ];
    }

    $this->ensureDisplayOptionDefaults();
    $form_state->set('layout_instance', $this);

    foreach ($this->displayOptionCollector->getAll() as $service) {
      $form = $service->buildConfigurationForm($form, $form_state, $this->configuration);
    }

    return $form;
  }

  /**
   * Ensures display option defaults are loaded into configuration.
   */
  protected function ensureDisplayOptionDefaults(): void {
    foreach ($this->displayOptionCollector->getAll() as $service) {
      $defaults = $service->defaultConfiguration();
      foreach ($defaults as $key => $value) {
        if (!isset($this->configuration[$key])) {
          $this->configuration[$key] = $value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = parent::defaultConfiguration();
    $sizing_options = $this->getSizingOptions();
    $configuration['sizing_option'] = key($sizing_options);
    return $configuration;
  }

  /**
   * Returns the available sizing options for this layout.
   */
  abstract public function getSizingOptions(): array;

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);

    // Sizing option submission is handled here.
    if ($form_state->hasValue('sizing_option')) {
      $this->configuration['sizing_option'] = $form_state->getValue('sizing_option');
    }

    foreach ($this->displayOptionCollector->getAll() as $service) {
      $service->submitConfigurationForm($form, $form_state, $this->configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $build = parent::build($regions);

    // Apply sizing class directly.
    if (!empty($this->configuration['sizing_option']) && $this->configuration['sizing_option'] !== 'default') {
      $build['#attributes']['class'][] = 'layout--' . $this->getPluginId() . '--' . $this->configuration['sizing_option'];
    }

    $build['#attributes'] = array_merge($build['#attributes'] ?? [], ['class' => $build['#attributes']['class'] ?? []]);
    $build['#layout'] = $this;

    $all_defaults = [];
    foreach ($this->displayOptionCollector->getAll() as $service) {
      $all_defaults = array_merge($all_defaults, $service::defaultConfiguration());
    }

    $final_configuration = array_merge($all_defaults, $this->configuration);

    foreach ($this->displayOptionCollector->getAll() as $service) {
      $service->processBuild($build, $final_configuration);
    }

    $build['#attributes'] = array_filter($build['#attributes']);

    return $build;
  }

}

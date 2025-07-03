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
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * The collector for all display option services.
   */
  protected DisplayOptionCollector $displayOptionCollector;

  /**
   * Constructs a new KinglyLayoutBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\kingly_layouts\Service\DisplayOptionCollector $display_option_collector
   *   The service that collects all display option services.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, DisplayOptionCollector $display_option_collector) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->displayOptionCollector = $display_option_collector;
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

    // Ensure display option defaults are set before building the form.
    $this->ensureDisplayOptionDefaults();

    // Store layout instance for services that need it.
    $form_state->set('layout_instance', $this);

    // Delegate form building to each collected service.
    // The services are injected by the container already sorted by the
    // `priority` defined on their service tag.
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

    // Set default sizing option based on available options from the different
    // layouts' getSizingOptions() methods.
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

    // Delegate form submission to each collected service.
    // The order of submission processing does not matter.
    foreach ($this->displayOptionCollector->getAll() as $service) {
      $service->submitConfigurationForm($form, $form_state, $this->configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $build = parent::build($regions);

    // Initialize attributes once.
    $build['#attributes'] = array_merge($build['#attributes'] ?? [], [
      'class' => $build['#attributes']['class'] ?? [],
    ]);

    // Store layout reference for services that need it.
    $build['#layout'] = $this;

    // Delegate build processing to each collected service.
    // The order of build processing does not matter.
    foreach ($this->displayOptionCollector->getAll() as $service) {
      $service->processBuild($build, $this->configuration);
    }

    // Clean up empty attributes.
    $build['#attributes'] = array_filter($build['#attributes']);

    return $build;
  }

}

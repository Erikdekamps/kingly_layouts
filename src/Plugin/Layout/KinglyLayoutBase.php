<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\kingly_layouts\Service\AlignmentService;
use Drupal\kingly_layouts\Service\AnimationService;
use Drupal\kingly_layouts\Service\BackgroundService;
use Drupal\kingly_layouts\Service\BorderService;
use Drupal\kingly_layouts\Service\ColorService;
use Drupal\kingly_layouts\Service\ContainerTypeService;
use Drupal\kingly_layouts\Service\CustomAttributesService;
use Drupal\kingly_layouts\Service\DisplayOptionCollector;
use Drupal\kingly_layouts\Service\ResponsivenessService;
use Drupal\kingly_layouts\Service\ShadowsEffectsService;
use Drupal\kingly_layouts\Service\SpacingService;
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
    // Correctly get services from the passed-in $container object.
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

    $sizing_options = $this->getSizingOptions();
    $default_sizing = $this->configuration['sizing_option'] ?? key($sizing_options);

    $form['sizing_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Column sizing'),
      '#options' => $sizing_options,
      '#default_value' => $default_sizing,
      '#description' => $this->t('Select the desired column width distribution.'),
      '#weight' => -10,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts sizing'),
    ];

    // Delegate form building to each collected service.
    foreach ($this->displayOptionCollector->getAll() as $service) {
      $form = $service->buildConfigurationForm($form, $form_state, $this->configuration);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['sizing_option'] = $form_state->getValue('sizing_option');

    // Delegate form submission to each collected service.
    foreach ($this->displayOptionCollector->getAll() as $service) {
      $service->submitConfigurationForm($form, $form_state, $this->configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = parent::defaultConfiguration();
    $configuration['sizing_option'] = 'default';

    // This is the one place where we need to know the service class names.
    // This is the necessary compromise to satisfy the plugin lifecycle, as
    // this method is called before services are injected.
    $service_classes = [
      ContainerTypeService::class,
      SpacingService::class,
      ColorService::class,
      BorderService::class,
      AlignmentService::class,
      AnimationService::class,
      BackgroundService::class,
      ShadowsEffectsService::class,
      ResponsivenessService::class,
      CustomAttributesService::class,
    ];

    foreach ($service_classes as $class) {
      $configuration = array_merge($configuration, $class::defaultConfiguration());
    }

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $build = parent::build($regions);
    $build['#attributes']['class'] = $build['#attributes']['class'] ?? [];
    $build['#attributes']['style'] = $build['#attributes']['style'] ?? [];
    $build['#attached']['library'][] = 'kingly_layouts/kingly_utilities';

    if (!empty($this->configuration['sizing_option']) && $this->configuration['sizing_option'] !== 'default') {
      $layout_id = $this->getPluginDefinition()->id();
      $build['#attributes']['class'][] = 'layout--' . $layout_id . '--' . $this->configuration['sizing_option'];
    }

    // Delegate build processing to each collected service.
    foreach ($this->displayOptionCollector->getAll() as $service) {
      $service->processBuild($build, $this->configuration);
    }

    if (empty($build['#attributes']['style'])) {
      unset($build['#attributes']['style']);
    }
    if (empty($build['#attributes']['class'])) {
      unset($build['#attributes']['class']);
    }

    return $build;
  }

  /**
   * Returns the available sizing options for this layout.
   */
  abstract protected function getSizingOptions(): array;

}

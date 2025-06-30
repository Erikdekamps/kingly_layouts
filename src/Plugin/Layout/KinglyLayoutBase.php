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
   * An array of all display option services.
   *
   * @var \Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface[]
   */
  protected array $displayOptionServices;

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
   * @param \Drupal\kingly_layouts\Service\ContainerTypeService $container_type_service
   *   The container type service.
   * @param \Drupal\kingly_layouts\Service\SpacingService $spacing_service
   *   The spacing service.
   * @param \Drupal\kingly_layouts\Service\AlignmentService $alignment_service
   *   The alignment service.
   * @param \Drupal\kingly_layouts\Service\ColorService $color_service
   *   The color service.
   * @param \Drupal\kingly_layouts\Service\BorderService $border_service
   *   The border service.
   * @param \Drupal\kingly_layouts\Service\AnimationService $animation_service
   *   The animation service.
   * @param \Drupal\kingly_layouts\Service\BackgroundService $background_service
   *   The background service.
   * @param \Drupal\kingly_layouts\Service\ShadowsEffectsService $shadows_effects_service
   *   The shadows and effects service.
   * @param \Drupal\kingly_layouts\Service\ResponsivenessService $responsiveness_service
   *   The responsiveness service.
   * @param \Drupal\kingly_layouts\Service\CustomAttributesService $custom_attributes_service
   *   The custom attributes service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, ContainerTypeService $container_type_service, SpacingService $spacing_service, AlignmentService $alignment_service, ColorService $color_service, BorderService $border_service, AnimationService $animation_service, BackgroundService $background_service, ShadowsEffectsService $shadows_effects_service, ResponsivenessService $responsiveness_service, CustomAttributesService $custom_attributes_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;

    // Assign all the display option services in a consistent order.
    $this->displayOptionServices = [
      'container_type' => $container_type_service,
      'spacing' => $spacing_service,
      'colors' => $color_service,
      'border' => $border_service,
      'alignment' => $alignment_service,
      'animation' => $animation_service,
      'background' => $background_service,
      'shadows_effects' => $shadows_effects_service,
      'responsiveness' => $responsiveness_service,
      'custom_attributes' => $custom_attributes_service,
    ];
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
      $container->get('kingly_layouts.container_type'),
      $container->get('kingly_layouts.spacing'),
      $container->get('kingly_layouts.alignment'),
      $container->get('kingly_layouts.color'),
      $container->get('kingly_layouts.border'),
      $container->get('kingly_layouts.animation'),
      $container->get('kingly_layouts.background'),
      $container->get('kingly_layouts.shadows_effects'),
      $container->get('kingly_layouts.responsiveness'),
      $container->get('kingly_layouts.custom_attributes')
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

    // Delegate form building to each service.
    foreach ($this->displayOptionServices as $service) {
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
    foreach ($this->displayOptionServices as $service) {
      $service->submitConfigurationForm($form, $form_state, $this->configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = parent::defaultConfiguration();
    $configuration['sizing_option'] = 'default';

    // Merge defaults from each service's static method.
    $configuration = array_merge(
      $configuration,
      ContainerTypeService::defaultConfiguration(),
      SpacingService::defaultConfiguration(),
      ColorService::defaultConfiguration(),
      BorderService::defaultConfiguration(),
      AlignmentService::defaultConfiguration(),
      AnimationService::defaultConfiguration(),
      BackgroundService::defaultConfiguration(),
      ShadowsEffectsService::defaultConfiguration(),
      ResponsivenessService::defaultConfiguration(),
      CustomAttributesService::defaultConfiguration()
    );

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

    foreach ($this->displayOptionServices as $service) {
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

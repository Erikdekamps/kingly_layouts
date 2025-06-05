<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Base class for Kingly layouts with sizing options.
 */
abstract class KinglyLayoutBase extends LayoutDefault implements PluginFormInterface {

  /**
   * Returns the available sizing options for this layout.
   *
   * @return array
   *   An associative array of sizing options, where keys are machine names
   *   and values are human-readable labels.
   */
  abstract protected function getSizingOptions(): array;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = parent::defaultConfiguration();
    $options = $this->getSizingOptions();
    // Default to the first option.
    $configuration['sizing_option'] = key($options);
    return $configuration;
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['sizing_option'] = $form_state->getValue('sizing_option');
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $build = parent::build($regions);

    // Add the sizing option as a class to the layout wrapper.
    if (!empty($this->configuration['sizing_option'])) {
      $plugin_definition = $this->getPluginDefinition();
      $build['#attributes']['class'][] = 'layout--' . $plugin_definition->id() . '--' . $this->configuration['sizing_option'];
    }

    return $build;
  }

}

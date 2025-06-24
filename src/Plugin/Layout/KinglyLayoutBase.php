<?php

namespace Drupal\kingly_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Base class for Kingly layouts with sizing and background options.
 */
abstract class KinglyLayoutBase extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = parent::defaultConfiguration();

    // Set default for sizing option.
    $sizing_options = $this->getSizingOptions();
    $configuration['sizing_option'] = key($sizing_options);

    // Set default for background color option.
    $background_options = $this->getBackgroundOptions();
    // Defaults to 'none'.
    $configuration['background_color'] = key($background_options);

    return $configuration;
  }

  /**
   * Returns the available sizing options for this layout.
   *
   * @return array
   *   An associative array of sizing options, where keys are machine names
   *   and values are human-readable labels.
   */
  abstract protected function getSizingOptions(): array;

  /**
   * Returns the available background color options for this layout.
   *
   * @return array
   *   An associative array of background color options, where keys are machine
   *   names and values are human-readable labels.
   */
  protected function getBackgroundOptions(): array {
    return [
      'none' => $this->t('None'),
      'light-grey' => $this->t('Light Grey'),
      'dark-grey' => $this->t('Dark Grey'),
      'blue' => $this->t('Blue'),
      'green' => $this->t('Green'),
      'red' => $this->t('Red'),
      // Add more colors as needed.
    ];
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

    $form['background_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Color'),
      '#options' => $this->getBackgroundOptions(),
      '#default_value' => $this->configuration['background_color'],
      '#description' => $this->t('Select a background color for this layout section.'),
      '#empty_option' => $this->t('- Select -'),
      // Optional: Adds a "Select -" option.
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['sizing_option'] = $form_state->getValue('sizing_option');
    $this->configuration['background_color'] = $form_state->getValue('background_color');
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $build = parent::build($regions);

    $plugin_definition = $this->getPluginDefinition();
    $layout_id = $plugin_definition->id();

    // Add the sizing option as a class to the layout wrapper.
    if (!empty($this->configuration['sizing_option'])) {
      $build['#attributes']['class'][] = 'layout--' . $layout_id . '--' . $this->configuration['sizing_option'];
    }

    // Add the background color option as a class to the layout wrapper.
    // Only add the class if a specific color is chosen (not 'none').
    if (!empty($this->configuration['background_color']) && $this->configuration['background_color'] !== 'none') {
      $build['#attributes']['class'][] = 'layout--background-color--' . $this->configuration['background_color'];
    }

    return $build;
  }

}

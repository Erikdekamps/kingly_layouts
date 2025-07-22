<?php

namespace Drupal\kingly_layouts\Service\DisplayOption;

use Drupal\Core\Form\FormStateInterface;

/**
 * Service to manage container type options for Kingly Layouts.
 */
class ContainerTypeService extends DisplayOptionBase {

  /**
   * {@inheritdoc}
   */
  public function getFormKey(): string {
    // This form element is not a details group, so its key is the element key.
    return 'container_type';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form_key = $this->getFormKey();
    $form[$form_key] = [
      '#type' => 'select',
      '#title' => $this->t('Container Type'),
      '#options' => $this->getContainerTypeOptions(),
      '#default_value' => $configuration['container_type'],
      '#description' => $this->t("Select how the layout container should behave: <br> <strong>Boxed:</strong> Standard container with a maximum width. <br> <strong>Full Width (Background Only):</strong> The background spans the full viewport width, but the content remains aligned with the site's main content area. Horizontal padding will be applied *within* this content area. <br> <strong>Edge to Edge (Full Bleed):</strong> Both the background and content span the full viewport width. <br> <strong>Full Screen Hero:</strong> The section fills the entire viewport height and width."),
      '#weight' => -9,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts container type'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $form_key = $this->getFormKey();
    $configuration['container_type'] = $form_state->getValue($form_key, 'boxed');
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    if (!empty($configuration['container_type'])) {
      // Attach the base library. Drupal will automatically include its
      // dependencies (variables) and the containers library which now
      // depends on base.
      $build['#attached']['library'][] = 'kingly_layouts/base';
      $build['#attached']['library'][] = 'kingly_layouts/containers';
      $build['#attributes']['class'][] = 'kl--' . $configuration['container_type'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'container_type' => 'boxed',
    ];
  }

  /**
   * Gets the options for the container type select list.
   *
   * @return array
   *   An array of container type options.
   */
  private function getContainerTypeOptions(): array {
    return [
      'boxed' => $this->t('Boxed'),
      'full' => $this->t('Full Width (Background Only)'),
      'edge-to-edge' => $this->t('Edge to Edge (Full Bleed)'),
      'hero' => $this->t('Full Screen Hero'),
    ];
  }

}

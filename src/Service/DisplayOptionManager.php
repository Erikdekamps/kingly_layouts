<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\kingly_layouts\Annotation\KinglyLayoutsDisplayOption;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;

/**
 * Manages Kingly Layouts display option plugins.
 *
 * This manager discovers services tagged with 'kingly_layouts.display_option'
 * and allows them to be instantiated and used to manage layout settings.
 *
 * @see \Drupal\kingly_layouts\Annotation\KinglyLayoutsDisplayOption
 * @see \Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface
 */
class DisplayOptionManager extends DefaultPluginManager {

  /**
   * Constructs a new DisplayOptionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    // We are managing services, so the directory is not used.
    $service_interface = KinglyLayoutsDisplayOptionInterface::class;
    $plugin_annotation = KinglyLayoutsDisplayOption::class;

    parent::__construct('Service', $namespaces, $module_handler, $service_interface, $plugin_annotation);

    $this->alterInfo('kingly_layouts_display_option_info');
    $this->setCacheBackend($cache_backend, 'kingly_layouts_display_option_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    if (empty($definition['id'])) {
      throw new PluginException(sprintf('The display option plugin "%s" must define an "id" property in its annotation.', $plugin_id));
    }
    if (empty($definition['label'])) {
      throw new PluginException(sprintf('The display option plugin "%s" must define a "label" property in its annotation.', $plugin_id));
    }
  }

}

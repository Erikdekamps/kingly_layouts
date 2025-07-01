<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides a method to dynamically discover service classes with a tag.
 *
 * This utility is used to get all service class names that are tagged with
 * 'kingly_layouts.display_option' so that KinglyLayoutBase can call their
 * static defaultConfiguration() method before dependency injection is
 * available.
 */
class DisplayOptionServiceCollector {

  /**
   * The cache ID for the collected service class names.
   */
  private const CACHE_ID = 'kingly_layouts.display_option_service_classes';

  /**
   * Get the class names of all services tagged 'kingly_layouts.display_option'.
   *
   * @return string[]
   *   An array of fully qualified class names.
   */
  public static function getClassNames(): array {
    // Statically cache the result for the duration of the request.
    static $classNames = NULL;
    if ($classNames !== NULL) {
      return $classNames;
    }

    /** @var \Drupal\Core\Cache\CacheBackendInterface $cache */
    $cache = \Drupal::cache();
    if ($cached = $cache->get(self::CACHE_ID)) {
      $classNames = $cached->data;
      return $classNames;
    }

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler */
    $moduleHandler = \Drupal::service('module_handler');
    $classNames = [];

    // The module path is needed to find the .services.yml file.
    $modulePath = $moduleHandler->getModule('kingly_layouts')->getPath();
    $servicesFile = $modulePath . '/kingly_layouts.services.yml';

    if (file_exists($servicesFile)) {
      // Enable parsing of Drupal-specific YAML tags like !tagged_iterator.
      // This is the critical fix for the ParseException.
      $services = Yaml::parseFile($servicesFile, Yaml::PARSE_CUSTOM_TAGS);
      if (isset($services['services']) && is_array($services['services'])) {
        foreach ($services['services'] as $service) {
          // Check if the service has tags.
          if (isset($service['tags']) && is_array($service['tags'])) {
            foreach ($service['tags'] as $tag) {
              // If the service is tagged correctly and has a class defined.
              if (isset($tag['name']) && $tag['name'] === 'kingly_layouts.display_option' && isset($service['class'])) {
                $classNames[] = $service['class'];
                // Move to the next service once a match is found.
                break;
              }
            }
          }
        }
      }
    }

    // Cache the result. The cache will be invalidated if the module's info
    // file changes, which is a good proxy for code/config changes.
    $cache->set(self::CACHE_ID, $classNames, CacheBackendInterface::CACHE_PERMANENT, ['config:core.extension']);

    return $classNames;
  }

}

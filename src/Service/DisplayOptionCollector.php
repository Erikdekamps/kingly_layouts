<?php

namespace Drupal\kingly_layouts\Service;

/**
 * Collects and manages all Kingly Layouts display option services.
 */
class DisplayOptionCollector {

  /**
   * An iterator containing all the display option services.
   *
   * @var \Traversable|\Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface[]
   */
  protected \Traversable $services;

  /**
   * Constructs a DisplayOptionCollector object.
   *
   * @param \Traversable $services
   *   An iterator of all services tagged with 'kingly_layouts.display_option'.
   */
  public function __construct(\Traversable $services) {
    $this->services = $services;
  }

  /**
   * Gets all collected display option services.
   *
   * @return \Traversable|\Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface[]
   *   An iterator of all collected display option services.
   */
  public function getAll(): \Traversable {
    return $this->services;
  }

}

<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides a centralized definition of responsive breakpoints.
 *
 * This service defines the breakpoints used across Kingly Layouts for
 * responsive design controls. It ensures consistency and makes it easy to
 * manage breakpoints from a single location.
 */
class BreakpointService {

  use StringTranslationTrait;

  /**
   * An array of breakpoint definitions.
   *
   * @var array
   */
  protected array $breakpoints;

  /**
   * Constructs a BreakpointService object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
    $this->initializeBreakpoints();
  }

  /**
   * Initializes the breakpoint definitions.
   *
   * This method defines the standard breakpoints used in the system. Each
   * breakpoint includes a machine name, a human-readable label, and a CSS
   * class prefix.
   */
  protected function initializeBreakpoints(): void {
    // Defines the breakpoints in a mobile-first order.
    // 'mobile' is the base and has no prefix.
    $this->breakpoints = [
      'mobile' => [
        'id' => 'mobile',
        'label' => $this->t('Mobile (Default)'),
        'prefix' => '',
      ],
      'md' => [
        'id' => 'md',
        'label' => $this->t('Medium (md)'),
        // Use a double-hyphen as a safe, standard separator.
        'prefix' => 'md--',
      ],
      'lg' => [
        'id' => 'lg',
        'label' => $this->t('Large (lg)'),
        // Use a double-hyphen as a safe, standard separator.
        'prefix' => 'lg--',
      ],
    ];
  }

  /**
   * Gets all defined breakpoints.
   *
   * @return array
   *   An associative array of breakpoint definitions, keyed by breakpoint ID.
   */
  public function getBreakpoints(): array {
    return $this->breakpoints;
  }

}

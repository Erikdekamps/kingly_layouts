<?php

namespace Drupal\kingly_layouts\Service\DisplayOption;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;
use Drupal\kingly_layouts\KinglyLayoutsUtilityTrait;
use Drupal\kingly_layouts\KinglyLayoutsValidationTrait;

/**
 * Base class for Kingly Layouts display option services.
 */
abstract class DisplayOptionBase implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;
  use KinglyLayoutsUtilityTrait;
  use KinglyLayoutsValidationTrait;

  /**
   * The current user.
   * We will continue to use property promotion for this, as it's unique to our class.
   */
  protected AccountInterface $currentUser;

  /**
   * Constructs a new DisplayOptionBase object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    AccountInterface $current_user,
    TranslationInterface $string_translation,
  ) {
    $this->currentUser = $current_user;
    // Use the setter method provided by StringTranslationTrait to initialize it.
    // This avoids the property definition conflict.
    $this->setStringTranslation($string_translation);
  }

}

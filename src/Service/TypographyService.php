<?php

namespace Drupal\kingly_layouts\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\kingly_layouts\KinglyLayoutsDisplayOptionInterface;
use Drupal\kingly_layouts\KinglyLayoutsUtilityTrait;

/**
 * Service to manage typography options for Kingly Layouts.
 */
class TypographyService implements KinglyLayoutsDisplayOptionInterface {

  use StringTranslationTrait;
  use KinglyLayoutsUtilityTrait;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * The options service.
   */
  protected OptionsService $optionsService;

  /**
   * Constructs a new TypographyService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\kingly_layouts\Service\OptionsService $options_service
   *   The options service.
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation, OptionsService $options_service) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
    $this->optionsService = $options_service;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form['typography'] = [
      '#type' => 'details',
      '#title' => $this->t('Typography'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts typography'),
    ];

    $form['typography']['font_family_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Family'),
      '#options' => $this->optionsService->getOptions('font_family'),
      '#default_value' => $configuration['font_family_option'],
      '#description' => $this->t('Select a pre-defined font family.'),
    ];

    $form['typography']['custom_font_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Custom Font URL'),
      '#default_value' => $configuration['custom_font_url'],
      '#description' => $this->t('Enter a URL for a custom font (e.g., from Google Fonts or a font hosting service). This will be imported using @import.'),
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[typography][font_family_option]"]' => ['value' => 'custom-import'],
        ],
      ],
    ];

    $form['typography']['font_size_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Size'),
      '#options' => $this->optionsService->getOptions('font_size'),
      '#default_value' => $configuration['font_size_option'],
      '#description' => $this->t('Set the base font size for text within this section.'),
    ];

    $form['typography']['font_weight_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Weight'),
      '#options' => $this->optionsService->getOptions('font_weight'),
      '#default_value' => $configuration['font_weight_option'],
      '#description' => $this->t('Set the boldness of the text.'),
    ];

    $form['typography']['line_height_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Line Height'),
      '#options' => $this->optionsService->getOptions('line_height'),
      '#default_value' => $configuration['line_height_option'],
      '#description' => $this->t('Adjust the spacing between lines of text.'),
    ];

    $form['typography']['letter_spacing_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Letter Spacing'),
      '#options' => $this->optionsService->getOptions('letter_spacing'),
      '#default_value' => $configuration['letter_spacing_option'],
      '#description' => $this->t('Adjust the spacing between individual letters.'),
    ];

    $form['typography']['text_transform_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Text Transform'),
      '#options' => $this->optionsService->getOptions('text_transform'),
      '#default_value' => $configuration['text_transform_option'],
      '#description' => $this->t('Change the capitalization of the text.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $values = $form_state->getValue('typography', []);

    // Explicitly set each configuration value from the submitted form values.
    $configuration['font_family_option'] = $values['font_family_option'] ?? self::NONE_OPTION_KEY;
    $configuration['font_size_option'] = $values['font_size_option'] ?? self::NONE_OPTION_KEY;
    $configuration['font_weight_option'] = $values['font_weight_option'] ?? self::NONE_OPTION_KEY;
    $configuration['line_height_option'] = $values['line_height_option'] ?? self::NONE_OPTION_KEY;
    $configuration['letter_spacing_option'] = $values['letter_spacing_option'] ?? self::NONE_OPTION_KEY;
    $configuration['text_transform_option'] = $values['text_transform_option'] ?? self::NONE_OPTION_KEY;

    // Handle custom_font_url conditionally based on the chosen
    // font_family_option. If 'custom-import' is selected, store the submitted
    // URL; otherwise, clear it.
    if ($configuration['font_family_option'] === 'custom-import') {
      $configuration['custom_font_url'] = trim($values['custom_font_url'] ?? '');
    }
    else {
      $configuration['custom_font_url'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    $has_typography_styles = FALSE;

    // Apply inline styles for various typography properties.
    if ($configuration['font_family_option'] !== self::NONE_OPTION_KEY) {
      if ($configuration['font_family_option'] === 'custom-import' && !empty($configuration['custom_font_url'])) {
        // For custom font imports, add the @import rule directly to the HTML
        // head.
        // This ensures the font is loaded.
        $build['#attached']['html_head'][] = [
          [
            '#tag' => 'style',
            '#attributes' => [
              'type' => 'text/css',
            ],
            // Ensure the @import URL is properly quoted.
            '#value' => '@import url("' . $configuration['custom_font_url'] . '");',
          ],
          // Use a unique key based on the URL to prevent duplicates if the same
          // font is used in multiple sections.
          'kingly_layouts_custom_font_' . hash('sha256', $configuration['custom_font_url']),
        ];
        // Apply the font family using the helper to infer a CSS-safe value.
        $build['#attributes']['style'][] = 'font-family: ' . $this->optionsService->getCustomFontImportCssValue($configuration['custom_font_url']) . ';';
        $has_typography_styles = TRUE;
      }
      else {
        // Apply the pre-defined font family as an inline style.
        $this->applyInlineStyleFromOption($build, 'font-family', 'font_family_option', $configuration);
        $has_typography_styles = TRUE;
      }
    }

    $inline_style_map = [
      'font_size_option' => 'font-size',
      'font_weight_option' => 'font-weight',
      'line_height_option' => 'line-height',
      'letter_spacing_option' => 'letter-spacing',
      'text_transform_option' => 'text-transform',
    ];

    foreach ($inline_style_map as $config_key => $property) {
      if ($configuration[$config_key] !== self::NONE_OPTION_KEY) {
        $this->applyInlineStyleFromOption($build, $property, $config_key, $configuration);
        $has_typography_styles = TRUE;
      }
    }

    // Attach the typography library only if any typography option is active.
    if ($has_typography_styles) {
      $build['#attached']['library'][] = 'kingly_layouts/typography';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration(): array {
    return [
      'font_family_option' => self::NONE_OPTION_KEY,
      'custom_font_url' => '',
      'font_size_option' => self::NONE_OPTION_KEY,
      'font_weight_option' => self::NONE_OPTION_KEY,
      'line_height_option' => self::NONE_OPTION_KEY,
      'letter_spacing_option' => self::NONE_OPTION_KEY,
      'text_transform_option' => self::NONE_OPTION_KEY,
    ];
  }

}

<?php

namespace Drupal\kingly_layouts\Service\DisplayOption;

use Drupal\Core\Form\FormStateInterface;

/**
 * Service to manage typography options for Kingly Layouts.
 */
class TypographyService extends DisplayOptionBase {

  /**
   * {@inheritdoc}
   */
  public function getFormKey(): string {
    return 'typography';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $configuration): array {
    $form_key = $this->getFormKey();
    $form[$form_key] = [
      '#type' => 'details',
      '#title' => $this->t('Typography'),
      '#open' => FALSE,
      '#access' => $this->currentUser->hasPermission('administer kingly layouts typography'),
    ];

    $form[$form_key]['font_family_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Family'),
      '#options' => $this->getTypographyOptions('font_family'),
      '#default_value' => $configuration['font_family_option'],
      '#description' => $this->t('Select a pre-defined font family.'),
    ];

    $form[$form_key]['font_size_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Size'),
      '#options' => $this->getTypographyOptions('font_size'),
      '#default_value' => $configuration['font_size_option'],
      '#description' => $this->t('Set the base font size for text within this section.'),
    ];

    $form[$form_key]['font_weight_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Weight'),
      '#options' => $this->getTypographyOptions('font_weight'),
      '#default_value' => $configuration['font_weight_option'],
      '#description' => $this->t('Set the boldness of the text.'),
    ];

    $form[$form_key]['line_height_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Line Height'),
      '#options' => $this->getTypographyOptions('line_height'),
      '#default_value' => $configuration['line_height_option'],
      '#description' => $this->t('Adjust the spacing between lines of text.'),
    ];

    $form[$form_key]['letter_spacing_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Letter Spacing'),
      '#options' => $this->getTypographyOptions('letter_spacing'),
      '#default_value' => $configuration['letter_spacing_option'],
      '#description' => $this->t('Adjust the spacing between individual letters.'),
    ];

    $form[$form_key]['text_transform_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Text Transform'),
      '#options' => $this->getTypographyOptions('text_transform'),
      '#default_value' => $configuration['text_transform_option'],
      '#description' => $this->t('Change the capitalization of the text.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, array &$configuration): void {
    $form_key = $this->getFormKey();
    $values = $form_state->getValue($form_key, []);

    // Explicitly set each configuration value from the submitted form values.
    $configuration['font_family_option'] = $values['font_family_option'] ?? self::NONE_OPTION_KEY;
    $configuration['font_size_option'] = $values['font_size_option'] ?? self::NONE_OPTION_KEY;
    $configuration['font_weight_option'] = $values['font_weight_option'] ?? self::NONE_OPTION_KEY;
    $configuration['line_height_option'] = $values['line_height_option'] ?? self::NONE_OPTION_KEY;
    $configuration['letter_spacing_option'] = $values['letter_spacing_option'] ?? self::NONE_OPTION_KEY;
    $configuration['text_transform_option'] = $values['text_transform_option'] ?? self::NONE_OPTION_KEY;
  }

  /**
   * {@inheritdoc}
   */
  public function processBuild(array &$build, array $configuration): void {
    $has_typography_styles = FALSE;

    // Apply inline styles for various typography properties.
    if ($configuration['font_family_option'] !== self::NONE_OPTION_KEY) {
      // Apply the pre-defined font family as an inline style.
      $this->applyInlineStyleFromOption($build, 'font-family', 'font_family_option', $configuration);
      $has_typography_styles = TRUE;
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
      'font_size_option' => self::NONE_OPTION_KEY,
      'font_weight_option' => self::NONE_OPTION_KEY,
      'line_height_option' => self::NONE_OPTION_KEY,
      'letter_spacing_option' => self::NONE_OPTION_KEY,
      'text_transform_option' => self::NONE_OPTION_KEY,
    ];
  }

  /**
   * Gets typography-related options.
   *
   * @param string $key
   *   The key for the specific options to retrieve.
   *
   * @return array
   *   An array of options.
   */
  private function getTypographyOptions(string $key): array {
    $none = [self::NONE_OPTION_KEY => $this->t('None')];
    $options = [
      'font_family' => $none + [
        'sans-serif' => $this->t('Sans-serif (Generic)'),
        'serif' => $this->t('Serif (Generic)'),
        'monospace' => $this->t('Monospace (Generic)'),
        'cursive' => $this->t('Cursive (Generic)'),
        'fantasy' => $this->t('Fantasy (Generic)'),
        'Arial, Helvetica, sans-serif' => $this->t('Arial'),
        'Verdana, Geneva, sans-serif' => $this->t('Verdana'),
        'Tahoma, Geneva, sans-serif' => $this->t('Tahoma'),
        '"Trebuchet MS", Helvetica, sans-serif' => $this->t('Trebuchet MS'),
        '"Gill Sans", "Gill Sans MT", Calibri, sans-serif' => $this->t('Gill Sans'),
        'Times, "Times New Roman", serif' => $this->t('Times New Roman'),
        'Georgia, serif' => $this->t('Georgia'),
        'Palatino, "Palatino Linotype", "Book Antiqua", serif' => $this->t('Palatino'),
        '"Courier New", Courier, monospace' => $this->t('Courier New'),
        '"Lucida Console", Monaco, monospace' => $this->t('Lucida Console'),
      ],
      'font_size' => $none + [
        '0.75rem' => $this->t('Extra Small (0.75rem)'),
        '0.875rem' => $this->t('Small (0.875rem)'),
        '1rem' => $this->t('Base (1rem)'),
        '1.125rem' => $this->t('Large (1.125rem)'),
        '1.25rem' => $this->t('Extra Large (1.25rem)'),
        '1.5rem' => $this->t('2XL (1.5rem)'),
        '1.875rem' => $this->t('3XL (1.875rem)'),
        '2.25rem' => $this->t('4XL (2.25rem)'),
        '3rem' => $this->t('5XL (3rem)'),
      ],
      'font_weight' => $none + [
        '100' => $this->t('Thin (100)'),
        '200' => $this->t('Extra Light (200)'),
        '300' => $this->t('Light (300)'),
        '400' => $this->t('Normal (400)'),
        '500' => $this->t('Medium (500)'),
        '600' => $this->t('Semi Bold (600)'),
        '700' => $this->t('Bold (700)'),
        '800' => $this->t('Extra Bold (800)'),
        '900' => $this->t('Black (900)'),
      ],
      'line_height' => $none + [
        '1' => $this->t('1 (Tight)'),
        '1.25' => $this->t('1.25'),
        '1.5' => $this->t('1.5 (Normal)'),
        '1.75' => $this->t('1.75'),
        '2' => $this->t('2 (Loose)'),
      ],
      'letter_spacing' => $none + [
        '-0.05em' => $this->t('-0.05em (Tight)'),
        '-0.025em' => $this->t('-0.025em'),
        '0em' => $this->t('0em (Normal)'),
        '0.025em' => $this->t('0.025em'),
        '0.05em' => $this->t('0.05em (Loose)'),
        '0.1em' => $this->t('0.1em (Extra Loose)'),
      ],
      'text_transform' => $none + [
        'none' => $this->t('None'),
        'uppercase' => $this->t('Uppercase'),
        'lowercase' => $this->t('Lowercase'),
        'capitalize' => $this->t('Capitalize'),
      ],
    ];

    return $options[$key] ?? [];
  }

}

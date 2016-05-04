<?php

/**
 * @file
 * Contains \Drupal\weeklytime\Plugin\Field\FieldType\WeeklyTimeField.
 */

namespace Drupal\weeklytime\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'weeklytime' field type.
 *
 * @FieldType(
 *   id = "weeklytime",
 *   label = @Translation("Weekly time"),
 *   description = @Translation("Weekly Time Field is a repeating time that occurs on the same time and day every week"),
 *   default_widget = "weeklytime_widget",
 *   default_formatter = "weeklytime_widget"
 * )
 */
class WeeklyTimeField extends FieldItemBase {
  /**
   * Return keyed array of Days of the Week.
   *
   * @return array
   */
  public static function weekDays() {
    return [
      'sat' => 'Saturday',
      'sun' => 'Sunday',
      'mon' => 'Monday',
      'tue' => 'Tuesday',
      'wed' => 'Wednesday',
      'thu' => 'Thursday',
      'fri' => 'Friday',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Time'))
      ->setRequired(TRUE);

    foreach (WeeklyTimeField::weekDays() as $key => $label) {
      $properties[$key] = DataDefinition::create('boolean')
        // Prevent early t() calls by using the TranslatableMarkup.
        ->setLabel(new TranslatableMarkup($label))
        ->setRequired(TRUE);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'type' => 'int',
          'not null' => FALSE,
        ],
      ],
    ];
    foreach (WeeklyTimeField::weekDays() as $key => $label) {
      $schema['columns'][$key] = [
        'type' => 'int',
        'size' => 'tiny',
        'default' => 0,
        'not null' => TRUE,
        'unsigned' => TRUE,
      ];
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = mt_rand(0, 1440);
    foreach (WeeklyTimeField::weekDays() as $key => $label) {
      $values[$key] = mt_rand(0, 1);
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
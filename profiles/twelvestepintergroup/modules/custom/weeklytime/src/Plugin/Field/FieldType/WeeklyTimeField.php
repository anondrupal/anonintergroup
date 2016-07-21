<?php

/**
 * @file
 * Contains \Drupal\weeklytime\Plugin\Field\FieldType\WeeklyTimeField.
 *
 * @todo: clean up the class architecture so that the data and functions make
 * more sense. Also, we shouldn't be doing all this date math.
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

  const DEFAULT_DAY = 'today';
  const DEFAULT_TIME = 'next';

  /**
   * Return keyed array of Days of the Week.
   *
   * @return array
   */
  public static function dayOptions() {
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
   * Return keyed array of Days of the Week, with the default option.
   *
   * @return array
   */
  public static function dayLabels() {
    $labels = [];
    $labels[self::DEFAULT_DAY] = t('- Today -');
    $labels += WeeklyTimeField::dayOptions();
    return $labels;
  }

  /**
   * Return the key representing the default day in WeeklyTimeField::dayOptions().
   *
   * @return string
   */
  public static function defaultDay() {
    return strtolower(date('D'));
  }

  /**
   * Return the key representing the default time in WeeklyTimeField::timeOptions().
   *
   * @return string
   */
  public static function defaultTime() {
    $now = self::stringToTime(date('Hi'));
    foreach (self::timeOptions() as $key => $option) {
      foreach ($option['ranges'] as $range) {
        if ($now >= self::stringToTime($range[0]) && $now < self::stringToTime($range[1])) {
          return $key;
        }
      }
    }
    return NULL;
  }

  /**
   * Convert the time string into minutes since midnight.
   *
   * @param $value
   */
  public static function stringToTime($value) {
    $hh = substr($value, 0, 2);
    $mm = substr($value, 2, 2);
    return $hh * 60 + $mm;
  }

  /**
   * Return keyed array of times of the day.
   *
   * @return array
   */
  public static function timeOptions() {
    return [
      'earlymorning' => [
        'label' => t('Early morning'),
        'ranges' => [
          ['0400', '0700'],
        ],
      ],
      'morning' => [
        'label' => t('Morning'),
        'ranges' => [
          ['0700', '1030'],
        ],
      ],
      'midday' => [
        'label' => t('Mid-day'),
        'ranges' => [
          ['1030', '1330'],
        ],
      ],
      'afternoon' => [
        'label' => t('Afternon'),
        'ranges' => [
          ['1330', '1600'],
        ],
      ],
      'rushhour' => [
        'label' => t('Rush-hour'),
        'ranges' => [
          ['1600', '1830'],
        ],
      ],
      'evening' => [
        'label' => t('Evening'),
        'ranges' => [
          ['1830', '2130'],
        ],
      ],
      'latenight' => [
        'label' => t('Late night'),
        'ranges' => [
          ['2130', '2400'],
          ['0000', '0400'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function timeLabels() {
    $labels = [];
    $labels[self::DEFAULT_TIME] = t('- Next available -');
    foreach (self::timeOptions() as $key => $option) {
      $start = reset($option['ranges'])[0];
      $end = end($option['ranges'])[1];
      $labels[$key] = $option['label'] . ' (' . t('%start to %end', [
          '%start' => self::formatStringTime($start),
          '%end' => self::formatStringTime($end),
        ]) . ')';
    }
    return $labels;
  }

  /**
   * Return the string time of day formatted.
   *
   * @param $value
   *
   * @return string
   */
  protected static function formatStringTime($time) {
    return self::formatTime(self::stringToTime($time));
  }

  /**
   * Return the time of day formatted.
   *
   * @param $time
   *   Time of day in minutes past midnight.
   */
  public static function formatTime($time) {
    $hh = floor($time / 60);
    $mm = $time % 60;
    $meridian = 'am';
    if ($hh == 12) {
      $meridian = 'pm';
    }
    elseif ($hh > 12) {
      if ($hh != 24) {
        $meridian = 'pm';
      }
      $hh -= 12;
    }
    return sprintf("%02d:%02d $meridian", $hh, $mm);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['time'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Time'))
      ->setRequired(TRUE);

    $properties['length'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Length'))
      ->setRequired(TRUE);

    foreach (WeeklyTimeField::dayOptions() as $key => $label) {
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
        'time' => [
          'type' => 'int',
          'not null' => FALSE,
        ],
        'length' => [
          'type' => 'int',
          'not null' => FALSE,
        ],
      ],
    ];
    foreach (WeeklyTimeField::dayOptions() as $key => $label) {
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
    $values = [];
    $values['time'] = mt_rand(0, 48) * 30;
    $values['length'] = 60 + mt_rand(0, 2) * 15;
    foreach (WeeklyTimeField::dayOptions() as $key => $label) {
      $values[$key] = mt_rand(0, 1);
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // This time is empty if there is no time-of-day value.
    $time = $this->getTimeValue();
    if ($time === NULL) {
      return TRUE;
    }

    // Since there is a time, this time is NOT empty if it is assigned to a day.
    foreach (array_keys(self::dayOptions()) as $day) {
      if ($this->get($day)->getValue()) {
        return FALSE;
      }
    }

    // And empty if it is not assigned to any day.
    return TRUE;
  }

  /**
   * Return the numeric time value.
   *
   * @return int
   */
  public function getTimeValue() {
    $time = $this->get('time')->getValue();
    return $time === '' ? NULL : $time;
  }

}

<?php

namespace Drupal\business_rules\Plugin\BusinessRulesVariable;

use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesVariablePlugin;
use Drupal\business_rules\VariableObject;
use Drupal\Core\Form\FormStateInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;

/**
 * Class EntityValue.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesVariable
 *
 * @BusinessRulesVariable(
 *   id = "entity_filed_variable",
 *   label = @Translation("Value from Entity field"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Set an variable value with a value from entity field."),
 *   reactsOnIds = {},
 *   isContextDependent = TRUE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = TRUE,
 * )
 */
class EntityFieldVariable extends BusinessRulesVariablePlugin {

  const CURRENT_DATA  = 'current_data';
  const ORIGINAL_DATA = 'original_data';

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['data'] = [
      '#type'          => 'select',
      '#title'         => t('Data'),
      '#required'      => TRUE,
      '#options'       => [
        ''                  => t('- Select -'),
        self::CURRENT_DATA  => t('Current value'),
        self::ORIGINAL_DATA => t('Original value'),
      ],
      '#description'   => t('Current value is the value that is being saved.') .
      '<br>' . t('Original value is the previous saved value.'),
      '#default_value' => empty($item->getSettings('data')) ? '' : $item->getSettings('data'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(Variable $variable, BusinessRulesEvent $event) {

    $field_name = $variable->getSettings('field');
    $data       = $variable->getSettings('data');

    switch ($data) {
      case self::CURRENT_DATA:
        $entity = $event->getArgument('entity');
        break;

      case self::ORIGINAL_DATA:
        $entity = $event->getArgument('entity_unchanged');
        break;
    }

    try {
      $value = $entity->get($field_name)->getValue();
    }
    catch (\Exception $e) {
      throw $e;
    }

    if (count($value) === 1) {
      $value = $value[0]['value'];
    }
    else {
      $arr_value = [];
      foreach ($value as $item) {
        $arr_value[] = $item['value'];
      }
      $value = $arr_value;
    }

    $variableObject = new VariableObject($variable->id(), $value, $variable->getType());

    return $variableObject;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    unset($form['variables']);
  }

}

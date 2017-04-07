<?php

namespace Drupal\business_rules\Plugin\BusinessRulesCondition;

use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesConditionPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VariableComparison.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesCondition
 *
 * @BusinessRulesCondition(
 *   id = "variable_comparison",
 *   label = @Translation("Variable comparison"),
 *   group = @Translation("Variable"),
 *   description = @Translation("Compare two variables values."),
 *   isContextDependent = FALSE,
 * )
 */
class VariableComparison extends BusinessRulesConditionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $description = t('The first variable to compare the value. Notice that both variables need to be at the same context or be context independent.');

    $settings['variable_1'] = [
      '#type'          => 'textfield',
      '#title'         => t('Variable 1'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('variable_1'),
      '#description'   => $description,
    ];

    $settings['operator'] = [
      '#type'          => 'select',
      '#required'      => TRUE,
      '#title'         => t('Operator'),
      '#description'   => t('The operation to be performed on this data comparison.'),
      '#default_value' => $item->getSettings('operator'),
      '#options'       => $this->util->getCriteriaMetOperatorsOptions(),
    ];

    $settings['variable_2'] = [
      '#type'          => 'textfield',
      '#title'         => t('Variable 1'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('variable_2'),
      '#description'   => $description,
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {
    $event_variables = $event->getArgument('variables');
    $variable_1      = $condition->getSettings('variable_1');
    $variable_2      = $condition->getSettings('variable_2');
    $operator        = $condition->getSettings('operator');

    $variable_1 = $this->processVariables($variable_1, $event_variables);
    $variable_2 = $this->processVariables($variable_2, $event_variables);

    $result = $this->util->criteriaMet($variable_1, $operator, $variable_2);

    return $result;
  }

}

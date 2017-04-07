<?php

namespace Drupal\business_rules\Plugin\BusinessRulesVariable;

use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesVariablePlugin;
use Drupal\business_rules\VariableObject;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConstantVariable.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesVariable
 *
 * @BusinessRulesVariable(
 *   id = "custom_value_variable",
 *   label = @Translation("Custom value"),
 *   group = @Translation("Variable"),
 *   description = @Translation("Set an variable with a constant value."),
 *   reactsOnIds = {},
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class CustomValueVariable extends BusinessRulesVariablePlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['value_type'] = [
      '#type'          => 'select',
      '#title'         => t('Variable type'),
      '#default_value' => $item->getSettings('value_type'),
      '#required'      => TRUE,
      '#options'       => [
        'string' => t('String'),
        'number' => t('Number'),
      ],
    ];

    $settings['value'] = [
      '#type'          => 'textarea',
      '#title'         => t('Custom value'),
      '#description'   => t("If you are using another's variables inside this custom value, make sure they are in the right context and include all of them at the Business Rule. 
      <br>As this variable type does not have context, all variables are being shown as available, but only the ones in the same context as the Business Rule will be processed."),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('value'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('value_type') == 'number') {
      if (!empty($form_state->getValue('value')) && !is_numeric($form_state->getValue('value'))) {

        // Allow variables even if the type is numeric. The user needs to know
        // what he is doing. If the variable value is not numeric, then he will
        // have issues in the future.
        $value     = $form_state->getValue('value');
        $variables = $this->pregMatch($value);
        if (count($variables) > 1 || !count($variables) || $value != '{{' . $variables[0] . '}}') {
          $form_state->setErrorByName('value', t('Only numbers are acceptable if the type is numeric.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(Variable $variable, BusinessRulesEvent $event) {

    $custom_value = $variable->getSettings('value');
    $variables    = $event->getArgument('variables');

    // Search for another's variables inside the original value.
    preg_match_all('{{((\w+)|(\w+\-\>+\w+)+?)}}', $custom_value, $inside_variables);
    $varObjects = [];
    if (count($inside_variables)) {
      $inside_variables = $inside_variables[1];
      if (count($inside_variables)) {
        foreach ($inside_variables as $inside_variable) {
          $var = Variable::load($inside_variable);

          if ($var instanceof Variable) {
            $varObjects[$var->id()] = $variables->getVariables()[$var->id()];
          }
          // Check if it's a entity variable with the field variable_id->field.
          elseif (stristr($inside_variable, '->')) {
            $arr_temp   = explode('->', $inside_variable);
            $var_name   = $arr_temp[0];
            $field_name = $arr_temp[1];
            $var        = Variable::load($var_name);

            if ($var instanceof Variable) {
              $entity                       = $variables->getVariables()[$var->id()]->getValue();
              $field                        = $entity->get($field_name);
              $value                        = $field->value;
              $varObjects[$inside_variable] = new VariableObject($var_name, $value, $var->getType());
            }

          }
        }
      }
    }

    // Replace the variables tokens for the variable value.
    if (count($varObjects)) {
      foreach ($varObjects as $key => $var) {
        if (is_string($var->getValue())) {
          $custom_value = str_replace('{{' . $key . '}}', $var->getValue(), $custom_value);
        }
      }
    }

    $variableObject = new VariableObject($variable->id(), $custom_value, 'custom_value_variable');

    return $variableObject;
  }

}

<?php

namespace Drupal\business_rules\Plugin\BusinessRulesVariable;

use Drupal\business_rules\BusinessRulesEvent;
use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesVariablePlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ArrayVariable.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesVariable
 *
 * @BusinessRulesVariable(
 *   id = "array_variable",
 *   label = @Translation("Array variable"),
 *   group = @Translation("Variable"),
 *   description = @Translation("Create an array variable. Variables array ara useful to set value to a multi-value field."),
 * )
 */
class ArrayVariable extends BusinessRulesVariablePlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings = [];
    // @TODO add fields to the variable array.
    // Variables array ara useful to set value to a multi-value field.
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    unset($form['variables']);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(Variable $variable, BusinessRulesEvent $event) {
    return [];
  }

}

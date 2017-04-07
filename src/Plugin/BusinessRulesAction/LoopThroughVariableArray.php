<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LoopThroughVariableArray.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "loop_through_variable_array",
 *   label = @Translation("Loop through a variable array"),
 *   group = @Translation("Variables"),
 *   description = @Translation("Loop through a variable array and execute actions and/or conditions: NOT IMPLEMENTED YET."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class LoopThroughVariableArray extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings['help'] = [
      '#type' => 'markup',
      '#markup' => '<h2>THIS ACTION IS NOT IMPLEMENTED YET.</h2>',
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    // TODO: Implement execute() method.
  }

}

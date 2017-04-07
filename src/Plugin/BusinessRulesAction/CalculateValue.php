<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CalculateValue.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "calculate_value",
 *   label = @Translation("Calculate a value"),
 *   group = @Translation("Variables"),
 *   description = @Translation("Calculate a variable value: NOT IMPLEMENTED YET."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class CalculateValue extends BusinessRulesActionPlugin {

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

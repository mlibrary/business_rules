<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class FormAlter.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "form_alter",
 *   label = @Translation("Entity form alter"),
 *   description = @Translation("Reacts when entity form is being prepared."),
 *   group = @Translation("Entity"),
 *   eventName = "business_rules.form_alter",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1000,
 * )
 */
class FormAlter extends BusinessRulesReactsOnPlugin {

}

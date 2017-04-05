<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\Core\Form\FormStateInterface;
use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class KernelRequest.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "kernel_request",
 *   label = @Translation("Drupal is initializing"),
 *   description = @Translation("Reacts on every request when Drupal is being initialized."),
 *   group = @Translation("System"),
 *   eventName = "business_rules.kernel_request",
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   priority = 1000,
 * )
 */
class KernelRequest extends BusinessRulesReactsOnPlugin {

  /**
   * {@inheritdoc}
   */
  public function processForm(array &$form, FormStateInterface $form_state) {
    parent::processForm($form, $form_state);

    unset($form['target_entity_type']);
    unset($form['target_bundle']);
  }

}

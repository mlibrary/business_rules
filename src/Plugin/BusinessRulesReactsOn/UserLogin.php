<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\Core\Form\FormStateInterface;
use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class UserLogin.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "user_login",
 *   label = @Translation("User has logged in"),
 *   description = @Translation("Reacts after the user has logged in."),
 *   group = @Translation("User"),
 *   eventName = "business_rules.user_login",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1000,
 * )
 */
class UserLogin extends BusinessRulesReactsOnPlugin {

  /**
   * {@inheritdoc}
   */
  public function processForm(array &$form, FormStateInterface $form_state) {
    parent::processForm($form, $form_state);

    $form['target_entity_type']['#required'] = FALSE;
    $form['target_entity_type']['#value']    = 'user';
    $form['target_entity_type']['#options']  = [
      'user' => $form['target_entity_type']['#options']['user'],
    ];

    $form['target_bundle']['#options'] = ['user' => t('User')];
    $form['target_bundle']['#required'] = FALSE;
    $form['target_bundle']['#value'] = 'user';

  }

}

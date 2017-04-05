<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\Core\Form\FormStateInterface;
use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;

/**
 * Class UserLogout.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "user_logout",
 *   label = @Translation("User has logged out"),
 *   description = @Translation("Reacts after the user has logged out."),
 *   group = @Translation("User"),
 *   eventName = "business_rules.user_logout",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1000,
 * )
 */
class UserLogout extends BusinessRulesReactsOnPlugin {

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

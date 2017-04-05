<?php

namespace Drupal\business_rules\Plugin\BusinessRulesVariable;

use Drupal\business_rules\BusinessRulesEvent;
use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesVariablePlugin;
use Drupal\business_rules\VariableObject;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * A variable representing one user account.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesVariable
 *
 * @BusinessRulesVariable(
 *   id = "user_variable",
 *   label = @Translation("User variable"),
 *   group = @Translation("User"),
 *   description = @Translation("Variable representing one user account."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 * )
 */
class UserVariable extends BusinessRulesVariablePlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['current_or_defined'] = [
      '#type'          => 'select',
      '#title'         => t('Current user or defined user?'),
      '#description'   => t('Current user or load user by user id.'),
      '#required'      => TRUE,
      '#options'       => [
        'current' => t('Current'),
        'defined' => t('Defined'),
      ],
      '#default_value' => $item->getSettings('current_or_defined'),
    ];

    $settings['user_id'] = [
      '#type'          => 'textfield',
      '#title'         => t('User id. You may use a variable to set this value.'),
      '#description'   => t('The numeric value for the user id.'),
      '#default_value' => $item->getSettings('user_id'),
      '#states'        => [
        'visible' => [
          'select[name="current_or_defined"]' => ['value' => 'defined'],
        ],
      ],
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    $form['settings']['context']['target_entity_type']['#value']    = 'user';
    $form['settings']['context']['target_entity_type']['#options']  = [
      'user' => $form['settings']['context']['target_entity_type']['#options']['user'],
    ];
    $form['settings']['context']['target_entity_type']['#disabled'] = TRUE;

    $form['settings']['context']['target_bundle']['#options']  = ['user' => t('User')];
    $form['settings']['context']['target_bundle']['#value']    = 'user';
    $form['settings']['context']['target_bundle']['#disabled'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings) {
    if ($settings['current_or_defined'] == 'current') {
      unset($settings['user_id']);
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(Variable $variable, BusinessRulesEvent $event) {

    $user = NULL;

    if ($variable->getSettings('current_or_defined') == 'current') {
      // Get the current user.
      $account = $this->util->container->get('current_user');
      $user = User::load($account->id());
    }
    elseif ($variable->getSettings('current_or_defined') == 'defined') {
      // Load user by id.
      $user_id = $variable->getSettings('user_id');
      $user_id = $this->processVariables($user_id, $event->getArgument('variables'));
      $user    = User::load($user_id);

      // Add log error if user id not found.
      if (empty($user)) {
        $this->util->logger->error('User id: $id not found. Variable: %variable', ['%id' => $user_id, '%variable' => $variable->id()]);
      }
    }

    $varObj = new VariableObject($variable->id(), $user, $variable->getType());

    return $varObj;
  }

}

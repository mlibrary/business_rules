<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\business_rules\VariableObject;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Class AddRoleToUser.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "add_user_role",
 *   label = @Translation("Add role to user"),
 *   group = @Translation("User"),
 *   description = @Translation("Add role(s) to user."),
 * )
 */
class AddRoleToUser extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings['user_container'] = [
      '#type'          => 'select',
      '#title'         => $this->t('User container'),
      '#default_value' => $item->getSettings('user_container'),
      '#options'       => [
        'current'  => $this->t('Current user'),
        'by_id'    => $this->t('By user id'),
        'variable' => $this->t('User variable'),
      ],
    ];

    $settings['uid'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('User id'),
      '#default_value' => $item->getSettings('uid'),
      '#description'   => $this->t('You can use variables here.'),
      '#states'        => [
        'visible' => [
          'select[name="user_container"]' => ['value' => 'by_id'],
        ],
      ],
    ];

    $settings['variable'] = [
      '#type'           => 'select',
      '#title'          => $this->t('User variable'),
      '#default_option' => $item->getSettings('variable'),
      '#description'    => $this->t('The variable containing the user. Only variables type: "User variable".'),
      '#options'        => $this->util->getVariablesOptions(['user_variable']),
      '#states'         => [
        'visible' => [
          'select[name="user_container"]' => ['value' => 'variable'],
        ],
      ],
    ];

    $settings['roles'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Roles'),
      '#required'      => TRUE,
      '#options'       => $this->util->getUserRolesOptions(),
      '#default_value' => is_array($item->getSettings('roles')) ? $item->getSettings('roles') : [],
      '#description'   => $this->t('Roles to add.'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariables(ItemInterface $item) {
    $variableSet = parent::getVariables($item);

    if ($item->getSettings('user_container') == 'variable') {
      $variableObj = new VariableObject($item->getSettings('variable'), NULL, $item->getType());
      $variableSet->append($variableObj);
    }

    return $variableSet;
  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings, ItemInterface $item) {
    $roles = $settings['roles'];
    foreach ($roles as $key => $role) {
      if ($key !== $role) {
        unset($roles[$key]);
      }
    }
    $settings['roles'] = $roles;

    switch ($settings['user_container']) {
      case 'current':
        unset($settings['variable']);
        unset($settings['uid']);
        break;

      case 'by_id':
        unset($settings['variable']);
        break;

      case 'variable':
        unset($settings['uid']);
        break;
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    /** @var \Drupal\business_rules\VariablesSet $event_variables */
    $event_variables = $event->getArgument('variables');
    $user_container  = $action->getSettings('user_container');
    $uid             = $action->getSettings('uid');
    $uid             = $this->processVariables($uid, $event_variables);
    $variable        = $action->getSettings('variable');
    $roles           = $action->getSettings('roles');

    switch ($user_container) {
      case 'current':
        /** @var \Drupal\Core\Session\AccountProxyInterface $account */
        /** @var \Drupal\user\Entity\User $user */
        $account = $this->util->container->get('current_user');
        $user    = User::load($account->id());
        break;

      case 'by_id':
        $user = User::load($uid);
        break;

      case 'variable':
        $user = $event_variables->getVariable($variable);
        break;
    }

    foreach ($roles as $role) {
      $user->addRole($role);
    }

    $user->save();

    $result = [
      '#type'   => 'markup',
      '#markup' => $this->t('User: %user<br>Roles added: %roles', [
        '%user'  => $user->getAccountName(),
        '%roles' => implode(', ', $roles),
      ]),
    ];

    return $result;

  }

}

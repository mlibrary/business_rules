<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\BusinessRulesEvent;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\business_rules\VariableObject;
use Drupal\business_rules\VariablesSet;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FetchEntityVariable.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "fetch_entity_variable",
 *   label = @Translation("Fetch entity variable by id"),
 *   group = @Translation("Variables"),
 *   description = @Translation("Fetch an entity variable by id provided by another variable or a constant value."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = TRUE,
 * )
 */
class FetchEntityVariableAction extends BusinessRulesActionPlugin {

  const VARIABLE = 'variable';
  const CONSTANT = 'constant';

  /**
   * If the entity is fetched.
   *
   * @var bool
   */
  private $entityIsFetched = FALSE;

  /**
   * Fetch the entity.
   *
   * @param string $id
   *   The entity id.
   * @param VariableObject $variable
   *   The VariableObject.
   * @param string $id_field
   *   The field id.
   * @param Action $action
   *   The Business rule Action action.
   * @param string $bundle
   *   The bundle.
   * @param mixed $original_variable_value
   *   The original variable value.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity.
   */
  private function fetchEntity($id, VariableObject $variable, $id_field, Action $action, $bundle, $original_variable_value) {
    try {
      $var         = Variable::load($variable->getId());
      $entity_type = $var->getTargetEntityType();
      $entity      = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->load($id);

      if (is_object($entity)) {
        $new_entity            = clone $entity;
        $this->entityIsFetched = TRUE;

        return $new_entity;
      }
      else {
        drupal_set_message(t("Action: %action fail. It's not possible to fetch entity %entity, bundle %bundle, with id=%id", [
          '%action' => $action->label() . ' [' . $action->id() . ']',
          '%entity' => $entity_type,
          '%bundle' => $bundle,
          '%id'     => $id,
        ]), 'error');

        return $original_variable_value;
      }

    }
    catch (\Exception $e) {
      drupal_set_message($e, 'error');

      return $original_variable_value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings = [];

    if (!$item->isNew()) {

      $settings['empty_variable'] = [
        '#type'          => 'select',
        '#title'         => t('Empty variable'),
        '#required'      => TRUE,
        '#description'   => t('Empty variable to be filled in.'),
        '#options'       => $this->getAvailableEmptyVariables($item),
        '#default_value' => empty($item->getSettings('empty_variable')) ? '' : $item->getSettings('empty_variable'),
      ];

      $settings['value_type'] = [
        '#type'          => 'radios',
        '#title'         => t('Value type'),
        '#required'      => TRUE,
        '#default_value' => $item->getSettings('value_type') ? $item->getSettings('value_type') : self::VARIABLE,
        '#options'       => [
          self::VARIABLE => t('From variable'),
          self::CONSTANT => t('Manual value'),
        ],
      ];

      $settings['constant'] = [
        '#type'          => 'textfield',
        '#title'         => t('Value'),
        '#default_value' => $item->getSettings('constant'),
        '#description'   => t('The entity ID to fill the variable.'),
        '#states'        => [
          'invisible' => [
            'input[name="value_type"]' => ['value' => self::VARIABLE],
          ],
        ],
      ];

      $settings['id_variable'] = [
        '#type'          => 'select',
        '#title'         => t('Variable with the entity id'),
        '#required'      => TRUE,
        '#description'   => t('Select the variable with contains the entity id to fill this variable'),
        '#options'       => $this->getAvailableFieldsVariables($item),
        '#default_value' => empty($item->getSettings('id_variable')) ? '' : $item->getSettings('id_variable'),
        '#states'        => [
          'invisible' => [
            'input[name="value_type"]' => ['value' => self::CONSTANT],
          ],
        ],
      ];
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    unset($form['variables']);
    $form['settings']['field']['#description'] = t('Select the entity id field.');
    $form['settings']['field']['#title']       = t('Entity id field.');
  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings) {
    if ($settings['value_type'] == self::CONSTANT) {
      unset($settings['id_variable']);
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {

    /** @var VariablesSet $variables */
    $id_variable         = $action->getSettings('id_variable');
    $variables           = $event->getArgument('variables');
    $processed_variables = $this->processVariables($action, $variables);
    $event->setArgument('variables', $processed_variables);

    $result = [
      '#type'   => 'markup',
      '#markup' => t('Entity variable %variable fetched.', ['%variable' => $id_variable]),
    ];

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariables(ItemInterface $item) {
    $variableSet    = parent::getVariables($item);
    $id_variable    = new VariableObject($item->getSettings('id_variable'));
    $empty_variable = new VariableObject($item->getSettings('empty_variable'));
    if ($id_variable instanceof VariableObject && $id_variable->getType()) {
      $variableSet->append($id_variable);
    }
    $variableSet->append($empty_variable);

    return $variableSet;
  }

  /**
   * {@inheritdoc}
   */
  public function processVariables($action, VariablesSet $event_variables) {

    /** @var VariableObject $variable */
    /** @var Action $action */
    $id_variable = $action->getSettings('id_variable');
    $id_field    = $action->getSettings('field');
    $bundle      = $action->getTargetBundle();
    $value       = NULL;

    if ($event_variables->count()) {
      foreach ($event_variables->getVariables() as $variable) {
        if ($variable->getType() == 'entity_empty_variable') {

          $original_variable_value = $variable->getValue();

          if ($action->getSettings('value_type') == self::VARIABLE) {
            $var_which_id = $event_variables->getVariable($id_variable);
            $id           = $var_which_id->getValue();
          }
          elseif ($action->getSettings('value_type') == self::CONSTANT) {
            $id = $action->getSettings('constant');
          }

          if (!stristr($variable->getId(), '->')) {
            $entity = $this->fetchEntity($id, $variable, $id_field, $action, $bundle, $original_variable_value);
            $event_variables->replaceValue($variable->getId(), $entity);
          }
          else {
            if (!$this->entityIsFetched) {
              $entity = $this->fetchEntity($id, $variable, $id_field, $action, $bundle, $original_variable_value);
            }
            $field       = explode('->', $variable->getId())[1];
            $definition  = $entity->$field->getFieldDefinition();
            $field_type  = $definition->getType();
            $cardinality = $definition->getFieldStorageDefinition()
              ->getCardinality();

            if ($field_type == 'entity_reference') {
              $property_name = 'target_id';
            }
            else {
              $property_name = 'value';
            }

            if ($cardinality === 1) {
              $value = $entity->get($field)->$property_name;
            }
            else {
              $arr = $entity->$field->getValue();
              foreach ($arr as $key => $item) {
                $arr[$key] = $item['value'];
              }
              $value = $arr;
            }
            $event_variables->replaceValue($variable->getId(), $value);
          }

        }
      }
    }

    return $event_variables;
  }

  /**
   * Get the available empty variables for the context.
   *
   * @param \Drupal\business_rules\Entity\Action $item
   *   The action.
   *
   * @return array
   *   Array of available entities variables.
   */
  public function getAvailableEmptyVariables(Action $item) {
    $variables = Variable::loadMultiple();
    $output    = [];

    /** @var Variable $variable */

    foreach ($variables as $variable) {
      if ($item->getTargetEntityType() == $variable->getTargetEntityType() &&
        $item->getTargetBundle() == $variable->getTargetBundle() &&
        $variable->getType() == 'entity_empty_variable'
      ) {
        $output[$variable->id()] = $variable->label() . ' [' . $variable->id() . ']';
      }
    }
    asort($output);

    return $output;
  }

  /**
   * Get the available fields variables for the context.
   *
   * @param \Drupal\business_rules\Entity\Action $item
   *   The action.
   *
   * @return array
   *   Array of fields.
   */
  public function getAvailableFieldsVariables(Action $item) {
    $variables = Variable::loadMultiple();
    $output    = [];

    /** @var Variable $variable */

    foreach ($variables as $variable) {
      if ($item->getTargetEntityType() == $variable->getTargetEntityType() &&
        $item->getTargetBundle() == $variable->getTargetBundle() &&
        $variable->getType() == 'entity_filed_variable' &&
        $variable->getSettings('field') == $item->getSettings('field')
      ) {
        $output[$variable->id()] = $variable->label() . ' [' . $variable->id() . ']';
      }
    }

    return $output;
  }

}

<?php

namespace Drupal\business_rules\Util;

use Drupal\business_rules\BusinessRulesEvent;
use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\BusinessRule;
use Drupal\business_rules\Entity\Condition;
use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Plugin\BusinessRulesActionManager;
use Drupal\business_rules\Plugin\BusinessRulesConditionManager;
use Drupal\business_rules\Plugin\BusinessRulesVariableManager;
use Drupal\business_rules\VariableObject;
use Drupal\business_rules\VariablesSet;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Link;
use Drupal\dbug\Dbug;

/**
 * Class BusinessRulesProcessor.
 *
 * Process the business rules.
 *
 * @package Drupal\business_rules\Util
 */
class BusinessRulesProcessor {
  /**
   * The action manager.
   *
   * @var BusinessRulesActionManager
   */
  protected $actionManager;

  /**
   * The condition manager.
   *
   * @var \Drupal\business_rules\Plugin\BusinessRulesConditionManager
   */
  private $conditionManager;

  /**
   * A configuration object with business_rules settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The config factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The array for debug.
   *
   * @var array
   */
  protected $debugArray = [];

  /**
   * Array of already evaluated variables.
   *
   * @var array
   */
  protected $evaluatedVariables = [];

  /**
   * Array of already processed rules.
   *
   * @var array
   */
  protected $processedRules = [];

  /**
   * The business rule id being executed.
   *
   * @var BusinessRule
   */
  public $ruleBeingExecuted;

  /**
   * The storage.
   *
   * @var StorageInterface
   */
  private $storage;

  /**
   * The Business Rules Util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  private $util;

  /**
   * The variable manager.
   *
   * @var \Drupal\business_rules\Plugin\BusinessRulesVariableManager
   */
  protected $variableManager;

  /**
   * BusinessRulesProcessor constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The ConfigFactory.
   * @param StorageInterface $storage
   *   The Storage.
   * @param \Drupal\business_rules\Util\BusinessRulesUtil $util
   *   The Business Rules Util.
   * @param \Drupal\business_rules\Plugin\BusinessRulesActionManager $actionManager
   *   The action manager.
   * @param \Drupal\business_rules\Plugin\BusinessRulesConditionManager $conditionManager
   *   The condition manager.
   * @param \Drupal\business_rules\Plugin\BusinessRulesVariableManager $variableManager
   *   The variable manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              StorageInterface $storage,
                              BusinessRulesUtil $util,
                              BusinessRulesActionManager $actionManager,
                              BusinessRulesConditionManager $conditionManager,
                              BusinessRulesVariableManager $variableManager) {

    $this->configFactory    = $config_factory;
    $this->storage          = $storage;
    $this->util             = $util;
    $this->actionManager    = $actionManager;
    $this->conditionManager = $conditionManager;
    $this->variableManager  = $variableManager;
    $this->config           = $config_factory->get('business_rules.settings');
  }

  /**
   * Process rules.
   *
   * @param \Drupal\business_rules\BusinessRulesEvent $event
   *   The event.
   */
  public function process(BusinessRulesEvent $event) {

    if (!$event->hasArgument('variables')) {
      $event->setArgument('variables', new VariablesSet());
    }

    $reacts_on_definition = $event->getArgument('reacts_on');
    $trigger              = $reacts_on_definition['id'];
    $triggered_rules      = $this->getTriggeredRules($event, $trigger);
    $this->processTriggeredRules($triggered_rules, $event);

    $this->saveDebugInfo();
  }

  /**
   * Check if there is a Business rule configured for the given event.
   *
   * @param \Drupal\business_rules\BusinessRulesEvent $event
   *   The event.
   * @param string $trigger
   *   The trigger.
   *
   * @return array
   *   Array of triggered rules.
   */
  public function getTriggeredRules(BusinessRulesEvent $event, $trigger) {
    $entity_type     = $event->getArgument('entity_type_id');
    $bundle          = $event->getArgument('bundle');
    $rule_names      = $this->storage->listAll('business_rules.business_rule');
    $rules           = $this->storage->readMultiple($rule_names);
    $triggered_rules = [];

    foreach ($rules as $rule) {
      $rule = new BusinessRule($rule);
      if ($rule->isEnabled() && $trigger == $rule->getReactsOn() &&
        ($entity_type == $rule->getTargetEntityType() || empty($rule->getTargetEntityType())) &&
        ($bundle == $rule->getTargetBundle() || empty($rule->getTargetBundle()))
      ) {
        if (!in_array($rule->id(), array_keys($this->processedRules))) {
          $triggered_rules[$rule->id()] = $rule;
        }
      }
    }

    return $triggered_rules;
  }

  /**
   * Process the triggered rules.
   *
   * @param array $triggered_rules
   *   Array of triggered rules.
   * @param \Drupal\business_rules\BusinessRulesEvent $event
   *   The event.
   */
  public function processTriggeredRules(array $triggered_rules, BusinessRulesEvent $event) {
    /** @var BusinessRule $rule */
    foreach ($triggered_rules as $rule) {
      $items = $rule->getItems();
      if (!in_array($rule->id(), array_keys($this->processedRules))) {
        $this->ruleBeingExecuted = $rule;
        $this->processItems($items, $event, $rule->id());
        $this->processedRules[$rule->id()]     = $rule->id();
        $this->debugArray['triggered_rules'][] = $rule;
      }
    }

  }

  /**
   * Save the debug information.
   */
  public function saveDebugInfo() {

    if ($this->config->get('debug_screen')) {
      $array      = $this->getDebugRenderArray();
      $key_value  = $this->util->getKeyValueExpirable('debug');
      $session_id = session_id();

      $current = $key_value->get($session_id);
      if (isset($current['triggered_rules']) && count($current['triggered_rules'])) {
        foreach ($current['triggered_rules'] as $key => $item) {
          $array['triggered_rules'][$key] = $item;
        }
      }

      $key_value->set($session_id, $array);
    }

  }

  /**
   * Process the items.
   *
   * @param array $items
   *   Array of items to pe processed. Each item must be a instance of
   *   BusinessRulesItemObject.
   * @param \Drupal\business_rules\BusinessRulesEvent $event
   *   The event.
   * @param string $parent_id
   *   The Item parent Id. It can be the Business Rule or other item.
   */
  public function processItems(array $items, BusinessRulesEvent $event, $parent_id) {
    /** @var BusinessRulesItemObject $item */
    foreach ($items as $item) {
      if ($item->getType() == BusinessRulesItemObject::ACTION) {
        $action = Action::load($item->getId());
        $this->executeAction($action, $event);

        $this->debugArray['actions'][$this->ruleBeingExecuted->id()][] = [
          'item'   => $action,
          'parent' => $parent_id,
        ];
      }
      elseif ($item->getType() == BusinessRulesItemObject::CONDITION) {
        $condition = Condition::load($item->getId());
        $success   = $this->isConditionValid($condition, $event);
        if ($success) {
          $condition_items = $condition->getSuccessItems();

          $this->debugArray['conditions'][$this->ruleBeingExecuted->id()]['success'][] = [
            'item'   => $condition,
            'parent' => $parent_id,
          ];
        }
        else {
          $condition_items = $condition->getFailItems();

          $this->debugArray['conditions'][$this->ruleBeingExecuted->id()]['fail'][] = [
            'item'   => $condition,
            'parent' => $parent_id,
          ];
        }

        if (is_array($condition_items)) {
          $this->processItems($condition_items, $event, $condition->id());
        }
      }
    }

  }

  /**
   * Generates the render array for business_rules debug.
   *
   * @return array
   *   The render array.
   */
  public function getDebugRenderArray() {
    /** @var BusinessRule $rule */

    $triggered_rules     = isset($this->debugArray['triggered_rules']) ? $this->debugArray['triggered_rules'] : [];
    $evaluates_variables = isset($this->debugArray['variables']) ? $this->debugArray['variables'] : [];
    $output              = [];

    if (!count($triggered_rules)) {
      return $output;
    }

    foreach ($triggered_rules as $rule) {
      $rule_link = Link::createFromRoute($rule->id(), 'entity.business_rule.edit_form', ['business_rule' => $rule->id()]);

      $output['triggered_rules'][$rule->id()] = [
        '#type'        => 'details',
        '#title'       => $rule->label(),
        '#description' => $rule_link->toString() . '<br>' . $rule->getDescription(),
        '#collapsible' => TRUE,
        '#collapsed'   => TRUE,
      ];

      $output['triggered_rules'][$rule->id()]['variables'] = [
        '#type'        => 'details',
        '#title'       => t('Variables'),
        '#collapsible' => TRUE,
        '#collapsed'   => TRUE,
      ];

      if (isset($evaluates_variables[$rule->id()]) && is_array($evaluates_variables[$rule->id()])) {
        /** @var VariableObject $evaluates_variable */
        foreach ($evaluates_variables[$rule->id()] as $evaluates_variable) {
          $variable = Variable::load($evaluates_variable->getId());
          if ($variable instanceof Variable) {
            $variable_link  = Link::createFromRoute($variable->id(), 'entity.business_rules_variable.edit_form', ['business_rules_variable' => $variable->id()]);
            $variable_value = empty($evaluates_variable->getValue()) ? 'NULL' : $evaluates_variable->getValue();

            if (!is_string($variable_value)) {
              $serialized = serialize($variable_value);
              if (is_object($variable_value)) {
                // Transform the serialized object into serialized array.
                $arr    = explode(':', $serialized);
                $arr[0] = 'a';
                unset($arr[1]);
                unset($arr[2]);
                $serialized = implode(':', $arr);
              }
              $unserialized   = unserialize($serialized);
              $variable_value = Dbug::debug($unserialized, 'array');
            }

            $output['triggered_rules'][$rule->id()]['variables'][$evaluates_variable->getId()] = [
              '#type'        => 'details',
              '#title'       => $variable->label(),
              '#description' => $variable_link->toString() . '<br>' . $variable->getDescription() . '<br>' . t('Value:') . '<br>',
              '#collapsible' => TRUE,
              '#collapsed'   => TRUE,
            ];

            $output['triggered_rules'][$rule->id()]['variables'][$evaluates_variable->getId()]['value'] = [
              '#type'   => 'markup',
              '#markup' => $variable_value,
            ];
          }
        }
      }

      $output['triggered_rules'][$rule->id()]['items'] = [
        '#type'        => 'details',
        '#title'       => t('Items'),
        '#collapsible' => TRUE,
        '#collapsed'   => TRUE,
      ];

      $items = $rule->getItems();

      $output['triggered_rules'][$rule->id()]['items'][] = $this->getDebugItems($items, $rule->id());
    }

    return $output;
  }

  /**
   * Executes one Action.
   *
   * @param Action $action
   *   The action.
   * @param \Drupal\business_rules\BusinessRulesEvent $event
   *   The event.
   *
   * @return array
   *   Render array to display action result on debug block.
   */
  public function executeAction(Action $action, BusinessRulesEvent $event) {

    $action_variables = $action->getVariables();
    $this->evaluateVariables($action_variables, $event);
    $result = $action->execute($event);

    $this->debugArray['action_result'][$this->ruleBeingExecuted->id()][$action->id()] = $result;

    return $result;
  }

  /**
   * Checks if one condition is valid.
   *
   * @param \Drupal\business_rules\Entity\Condition $condition
   *   The condition.
   * @param \Drupal\business_rules\BusinessRulesEvent $event
   *   The event.
   *
   * @return bool
   *   True if the condition is valid or False if not.
   */
  public function isConditionValid(Condition $condition, BusinessRulesEvent $event) {

    $condition_variables = $condition->getVariables();
    $this->evaluateVariables($condition_variables, $event);
    $result = $condition->process($event);
    $result = $condition->isReverse() ? !$result : $result;

    return $result;

  }

  /**
   * Helper function to prepare the render array for the Business Rules Items.
   *
   * @param array $items
   *   Array of items.
   * @param string $parent_id
   *   The parent item id.
   *
   * @return array
   *   The render array.
   */
  protected function getDebugItems(array $items, $parent_id) {
    /** @var BusinessRulesItemObject $item */
    /** @var Action $executed_action */
    /** @var Condition $executed_condition */
    $actions_executed   = isset($this->debugArray['actions'][$this->ruleBeingExecuted->id()]) ? $this->debugArray['actions'][$this->ruleBeingExecuted->id()] : [];
    $conditions_success = isset($this->debugArray['conditions'][$this->ruleBeingExecuted->id()]['success']) ? $this->debugArray['conditions'][$this->ruleBeingExecuted->id()]['success'] : [];
    $output             = [];

    foreach ($items as $item) {
      if ($item->getType() == BusinessRulesItemObject::ACTION) {
        $action      = Action::load($item->getId());
        $action_link = Link::createFromRoute($action->id(), 'entity.business_rules_action.edit_form', ['business_rules_action' => $action->id()]);

        $style = 'fail';
        foreach ($actions_executed as $executed) {
          $action_parent   = $executed['parent'];
          $executed_action = $executed['item'];
          if ($action_parent == $parent_id) {
            $style = ($executed_action->id() == $action->id()) ? 'success' : 'fail';
            if ($style == 'success') {
              break;
            }
          }
        }

        $action_label           = t('Action');
        $output[$item->getId()] = [
          '#type'        => 'details',
          '#title'       => $action_label . ': ' . $action->label(),
          '#description' => $action_link->toString() . '<br>' . $action->getDescription(),
          '#attributes'  => ['class' => [$style]],
          '#collapsible' => TRUE,
          '#collapsed'   => TRUE,
        ];

        if (isset($this->debugArray['action_result'][$this->ruleBeingExecuted->id()][$item->getId()])) {
          $output[$item->getId()]['action_result'][$this->ruleBeingExecuted->id()] = $this->debugArray['action_result'][$this->ruleBeingExecuted->id()][$item->getId()];
        }
      }
      elseif ($item->getType() == BusinessRulesItemObject::CONDITION) {
        $condition      = Condition::load($item->getId());
        $condition_link = Link::createFromRoute($condition->id(), 'entity.business_rules_condition.edit_form', ['business_rules_condition' => $condition->id()]);

        $style = 'fail';
        foreach ($conditions_success as $success) {
          $condition_parent   = $success['parent'];
          $executed_condition = $success['item'];
          if ($condition_parent == $parent_id) {
            $style = ($executed_condition->id() == $condition->id()) ? 'success' : 'fail';
            if ($style == 'success') {
              break;
            }
          }
        }

        $title                  = $condition->isReverse() ? t('(Not)') . ' ' . $condition->label() : $condition->label();
        $condition_label        = t('Condition');
        $output[$item->getId()] = [
          '#type'        => 'details',
          '#title'       => $condition_label . ': ' . $title,
          '#description' => $condition_link->toString() . '<br>' . $condition->getDescription(),
          '#attributes'  => ['class' => [$style]],
          '#collapsible' => TRUE,
          '#collapsed'   => TRUE,
        ];

        $success_items = $condition->getSuccessItems();
        if (is_array($success_items) && count($success_items)) {
          $output[$item->getId()]['success']   = [
            '#type'        => 'details',
            '#title'       => t('Success items'),
            '#attributes'  => ['class' => [$style]],
            '#collapsible' => TRUE,
            '#collapsed'   => TRUE,
          ];
          $output[$item->getId()]['success'][] = $this->getDebugItems($success_items, $condition->id());
        }

        $fail_items = $condition->getFailItems();
        if (is_array($fail_items) && count($fail_items)) {
          $output[$item->getId()]['fail']   = [
            '#type'        => 'details',
            '#title'       => t('Fail items'),
            '#attributes'  => ['class' => [$style == 'success' ? 'fail' : 'success']],
            '#collapsible' => TRUE,
            '#collapsed'   => TRUE,
          ];
          $output[$item->getId()]['fail'][] = $this->getDebugItems($fail_items, $condition->id());
        }
      }
    }

    return $output;
  }

  /**
   * Evaluate all variables from a VariableSet for a given event.
   *
   * @param \Drupal\business_rules\VariablesSet $variablesSet
   *   The variable set.
   * @param \Drupal\business_rules\BusinessRulesEvent $event
   *   The event.
   */
  public function evaluateVariables(VariablesSet $variablesSet, BusinessRulesEvent $event) {
    /** @var VariableObject $variable */
    /** @var VariablesSet $eventVariables */

    if ($variablesSet->count()) {
      foreach ($variablesSet->getVariables() as $variable) {
        $varObject = Variable::load($variable->getId());
        if ($varObject instanceof Variable) {
          // Do note evaluate the same variable twice to avid overload.
          if (!array_key_exists($variable->getId(), $this->evaluatedVariables)) {
            $this->evaluateVariable($varObject, $event);
          }
        }
      }
    }

  }

  /**
   * Evaluate the variable value.
   *
   * @param \Drupal\business_rules\Entity\Variable $variable
   *   The variable.
   * @param \Drupal\business_rules\BusinessRulesEvent $event
   *   The event.
   *
   * @return VariableObject|VariablesSet
   *   The evaluated variable or a VariableSet which processed variables.
   *
   * @throws \Exception
   */
  public function evaluateVariable(Variable $variable, BusinessRulesEvent $event) {

    // Do note evaluate the same variable twice to avid overload.
    if (array_key_exists($variable->id(), $this->evaluatedVariables)) {
      return NULL;
    }

    /** @var \Drupal\business_rules\Plugin\BusinessRulesVariablePlugin $defined_variable */
    /** @var VariablesSet $eventVariables */
    /** @var VariableObject $item */

    $eventVariables     = $event->getArgument('variables');
    $variable_variables = $variable->getVariables();

    $this->evaluateVariables($variable_variables, $event);
    $value = $variable->evaluate($event);

    if ($value instanceof VariableObject) {
      $this->evaluatedVariables[$variable->id()] = $variable->id();
      $eventVariables->append($value);
      $this->debugArray['variables'][$this->ruleBeingExecuted->id()][$variable->id()] = $value;
      return $value;
    }
    elseif ($value instanceof VariablesSet) {
      if ($value->count()) {
        foreach ($value->getVariables() as $item) {
          $this->evaluatedVariables[$item->getId()] = $item->getId();
          $eventVariables->append($item);
          $this->debugArray['variables'][$this->ruleBeingExecuted->id()][$item->getId()] = $item;
        }
      }
      return $value;
    }
    else {
      throw new \Exception(get_class($defined_variable) . '::evaluate should return instance of ' . get_class(new VariableObject()) . ' or ' . get_class(new VariablesSet()) . '.');
    }
  }

}

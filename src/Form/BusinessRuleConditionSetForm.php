<?php

namespace Drupal\business_rules\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\business_rules\Entity\Condition;
use Drupal\business_rules\Plugin\BusinessRulesCondition\ConditionSet;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class BusinessRuleConditionSetForm.
 *
 * @package Drupal\business_rules\Form
 */
class BusinessRuleConditionSetForm extends FormBase {
  /**
   * The business rules util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  protected $util;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container) {
    $this->util = $container->get('business_rules.util');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'business_rules_condition_set_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /** @var Condition $condition */
    $condition_id   = $this->util->request->get('business_rules_condition');
    $condition      = Condition::load($condition_id);
    $items          = ConditionSet::getAvailableItems($condition);
    $selected_items = ConditionSet::getConditions($condition);
    $label          = $this->t('Condition');
    $label_plural   = $this->t('Conditions');
    $back_url       = Url::fromRoute('entity.business_rules_condition.edit_form', ['business_rules_condition' => $condition->id()]);

    $form = $this->util->getAddItemsForm($condition, $items, $selected_items, $label, $label_plural, $back_url);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var Condition $condition */
    $condition_id = $this->util->request->get('business_rules_condition');
    $condition    = Condition::load($condition_id);
    $saved_items  = ConditionSet::getConditions($condition);

    $items  = [];
    $weight = 100;
    foreach ($form_state->getValue('items') as $key => $value) {
      if ($value !== 0) {
        $items[$key] = [
          'condition' => $key,
          // Keep o weight according to previously saved item.
          'weight'           => isset($saved_items[$key]['weight']) ? $saved_items[$key]['weight'] : $weight,
        ];
        $weight++;
      }
    }

    $condition->set('settings', $items);
    $condition->save();

    $form_state->setRedirect('entity.business_rules_condition.edit_form', ['business_rules_condition' => $condition->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static ($container);
  }

  /**
   * Remove condition from conditionSet.
   *
   * @param string $business_rules_condition
   *   The condition id.
   * @param string $remove_id
   *   The item id.
   *
   * @return RedirectResponse
   *   The RedirectResponse.
   */
  public function removeCondition($business_rules_condition, $remove_id) {
    $condition  = Condition::load($business_rules_condition);
    $conditions = ConditionSet::getConditions($condition);
    unset($conditions[$remove_id]);
    $condition->set('settings', $conditions);
    $condition->save();

    $redirect = new RedirectResponse(Url::fromRoute('entity.business_rules_condition.edit_form', ['business_rules_condition' => $business_rules_condition])
      ->toString());

    return $redirect;
  }

}

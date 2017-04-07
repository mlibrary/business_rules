<?php

namespace Drupal\business_rules\Plugin\BusinessRulesVariable;

use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesVariablePlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ArrayVariable.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesVariable
 *
 * @BusinessRulesVariable(
 *   id = "array_variable",
 *   label = @Translation("Array variable"),
 *   group = @Translation("Variable"),
 *   description = @Translation("Create an array variable. Variables array ara useful to set value to a multi-value field."),
 * )
 */
class ArrayVariable extends BusinessRulesVariablePlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    if ($item->isNew()) {
      return [];
    }

    $settings['fields'] = [
      '#type'       => 'table',
      '#header'     => [
        'type'       => t('Type'),
        'label'      => t('Label'),
        'id'         => t('Field id'),
        'operations' => t('Operations'),
      ],
      '#attributes' => ['id' => 'array_variable_fields_table'],
    ];

    $this->getRows($item, $settings['fields']);

    return $settings;
  }

  /**
   * Get the current fields on the variable array.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The variable.
   *
   * @return array
   *   The rows.
   */
  public function getRows(ItemInterface $item, &$settings) {

    $fields = $item->getSettings('fields');

    if (count($fields)) {
      foreach ($fields as $key => $field) {
        $settings[$key] = [
          'type'       => [
            '#type'   => 'markup',
            '#markup' => $field['type'],
          ],
          'label'      => [
            '#type'   => 'markup',
            '#markup' => $field['label'],
          ],
          'id'         => [
            '#type'   => 'markup',
            '#markup' => $field['id'],
          ],
          'operations' => [
            '#type'   => 'markup',
            '#markup' => 'operations',
          ],
        ];
      }
    }

    $settings['new.field'] = [
      'type'       => [
        '#type'     => 'select',
        '#required' => FALSE,
        '#options'  => [
          ''        => t('-Select-'),
          'numeric' => t('Numeric'),
          'string'  => t('String'),
        ],
      ],
      'label'      => [
        '#type'      => 'textfield',
        '#required'  => FALSE,
        '#maxlength' => 64,
      ],
      'id'         => [
        '#type'         => 'machine_name',
        '#maxlength'    => 64,
        '#title'        => NULL,
        '#description'  => '',
        '#disabled'     => FALSE,
        '#required'     => FALSE,
        '#machine_name' => [
          'source' => ['settings', 'fields', 'new.field', 'label'],
          'standalone'   => TRUE,
        ],
      ],
      'operations' => [
        '#type'   => 'submit',
        '#value'  => t('Add'),
        '#submit' => ['::submitForm', '::save'],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $field = $form_state->getValue('fields');
    $type  = $field['new.field']['type'];
    $label = $field['new.field']['label'];
    $id    = $field['new.field']['id'];

    if (!($id && $label && $type) && ($id || $label || $type)) {
      $form_state->setErrorByName('fields', t("Please, fill all field's fields or none of them."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings, ItemInterface $item) {

    $settings['fields'] += $item->getSettings('fields');

    $settings['fields'][$settings['fields']['new.field']['id']] = $settings['fields']['new.field'];
    unset($settings['fields']['new.field']);
    uasort($settings['fields'], function ($a, $b) {
      return ($a['label'] > $b['label']) ? 1 : -1;
    });

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    unset($form['variables']);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(Variable $variable, BusinessRulesEvent $event) {
    return [];
    // @TODO parei aqui.
  }

}

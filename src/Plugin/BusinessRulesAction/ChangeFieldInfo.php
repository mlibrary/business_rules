<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ChangeFieldInfo.
 *
 * Changes on multiple value fields can't be done via hooks yet.
 *
 * @see https://www.drupal.org/node/1592814
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "change_field_info",
 *   label = @Translation("Change entity form field"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Change a form field: Make required/optional/ready only/hidden/dependent/change field options values."),
 *   isContextDependent = TRUE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = FALSE,
 * )
 */
class ChangeFieldInfo extends BusinessRulesActionPlugin {
  // @TODO develop it.
  // Make field required/optional
  // Make field dependent
  // Change field list of values

  const MAKE_REQUIRED        = 'make_required';
  const MAKE_OPTIONAL        = 'make_optional';
  const MAKE_READY_ONLY      = 'make_ready_only';
  const MAKE_DEPENDENT       = 'make_dependant';
  const MAKE_HIDDEN          = 'make_hidden';
  const CHANGE_OPTIONS_VALUE = 'change_options_value';

  protected $actionOptions = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration = [], $plugin_id = 'change_field_info', $plugin_definition = []) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->actionOptions = [
      ''                    => t('-Select-'),
      self::MAKE_REQUIRED   => t('Make field required'),
      self::MAKE_OPTIONAL   => t('Make field optional'),
      self::MAKE_READY_ONLY => t('Make field ready only'),
      self::MAKE_HIDDEN     => t('Make field hidden'),
      self::MAKE_DEPENDENT  => t('Make field dependent'),
    ];

  }

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
        'field'      => t('Filed'),
        'action'     => t('Action'),
        'info'       => t('Info'),
        'operations' => t('Operations'),
      ],
      '#attributes' => ['id' => 'array_variable_fields_table'],
    ];

    $settings['info'] = [
      '#type'   => 'markup',
      '#markup' => t('Multiple value fields cannot be changed to Required or Optional by this module. Create a new rule as "Entity form validation" to achieve this purpose see this issue on https://www.drupal.org/node/1592814.
        <br>Title field is always required. You can hide it or make it ready-only to stored entities, but never optional.'),
    ];

    $this->getRows($item, $settings['fields']);

    return $settings;
  }

  /**
   * Get the current fields on the variable array.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The variable.
   * @param array $settings
   *   The current setting to add rows.
   */
  private function getRows(ItemInterface $item, array &$settings) {

    $fields          = $item->getSettings('fields');
    $availableFields = $this->util->getBundleEditableFields($item->getTargetEntityType(), $item->getTargetBundle());

    if (count($fields)) {
      foreach ($fields as $key => $field) {

        $links['remove'] = [
          'title'  => t('Remove'),
          'url'    => Url::fromRoute('business_rules.plugins.action.change_field_info.remove_field', [
            'action' => $item->id(),
            'field'  => $field['id'],
            'method' => 'nojs',
          ],
            [
              'attributes' => [
                'class' => ['use-ajax'],
              ],
            ]
          ),
          'weight' => 1,
        ];

        $settings[$key] = [
          'field'       => [
            '#type'   => 'markup',
            '#markup' => $availableFields[$field['field']],
          ],
          'action'      => [
            '#type'   => 'markup',
            '#markup' => $this->actionOptions[$field['action']],
          ],
          'info'        => [
            '#type'   => 'markup',
            '#markup' => !empty($field['info']) ? $field['info'] : '',
          ],
          'operations'  => [
            '#type'  => 'operations',
            '#links' => $links,
          ],
          '#attributes' => ['id' => 'field-' . $field['id']],
        ];
      }
    }

    $settings['new.field'] = [
      'field'      => [
        '#type'     => 'select',
        '#required' => FALSE,
        '#options'  => array_merge(['' => t('-Select-')], $availableFields),
      ],
      'action'     => [
        '#type'     => 'select',
        '#required' => FALSE,
        '#options'  => $this->actionOptions,
      ],
      'info'       => [],
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
  public function buildForm(array &$form, FormStateInterface $form_state) {
    unset($form['variables']);
  }

  /**
   * Remove one field from the action.
   *
   * @param string $action
   *   The action id.
   * @param string $field
   *   The field id.
   * @param string $method
   *   The method: ajax|nojs.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   */
  public static function removeField($action, $field, $method) {
    $action = Action::load($action);
    $fields = $action->getSettings('fields');
    unset($fields[$field]);
    $action->setSetting('fields', $fields);
    $action->save();

    if ($method == 'ajax') {
      $response = new AjaxResponse();
      $response->addCommand(new RemoveCommand('#field-' . $field));

      return $response;
    }
    else {
      $url = new Url('entity.business_rules_action.edit_form', ['business_rules_action' => $action->id()]);

      return new RedirectResponse($url->toString());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $field        = $form_state->getValue('fields');
    $field_field  = $field['new.field']['field'];
    $field_action = $field['new.field']['action'];

    if ((empty($field_action) && !empty($field_field)) || (!empty($field_action) && empty($field_field))) {
      $form_state->setErrorByName('fields', t("Please, fill all field data or none of them."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings, ItemInterface $item) {

    if ($item->isNew()) {
      return [];
    }

    if (count($item->getSettings('fields'))) {
      $settings['fields'] += $item->getSettings('fields');
    }

    $availableFields               = $this->util->getBundleEditableFields($item->getTargetEntityType(), $item->getTargetBundle());
    $id                            = $settings['fields']['new.field']['field'] . '__' . $settings['fields']['new.field']['action'];
    $settings['fields'][$id]       = $settings['fields']['new.field'];
    $settings['fields'][$id]['id'] = $id;
    unset($settings['fields']['new.field']);
    uasort($settings['fields'], function ($a, $b) use ($availableFields) {
      return ($availableFields[$a['field']] > $availableFields[$b['field']]) ? 1 : -1;
    });

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {

    $element = $event->getArgument('element');
    $context = $event->getArgument('context');
    $fields  = $action->getSettings('fields');
    $widget  = $context['widget'];
    $form_state = $event->getArgument('form_state');
    $entity = $event->getArgument('entity');

    if (!count($fields)) {
      return [
        '#type'   => 'markup',
        '#markup' => t('Nothing to do.'),
      ];
    }

    /** @var \Drupal\Core\Field\FieldItemList $fieldItemList */
    $fieldItemList = $context['items'];
    /** @var \Drupal\field\Entity\FieldConfig $field_definition */
    $field_definition = $fieldItemList->getFieldDefinition();

    foreach ($fields as $field) {
      if ($field['field'] == $field_definition->getName()) {
        // Check if element is an entity reference field.
        if (isset($element['target_id'])) {
          $this->changeFieldInfo($element['target_id'], $field['action'], $entity, $field['field']);
        }
        // Check if it's the title field.
        elseif ($field_definition->getName() == 'title') {
          $this->changeFieldInfo($element['value'], $field['action'], $entity, $field['field']);
        }
        // Check if element is a multi-value field.
        //elseif (isset($element['#delta']) && isset($element['value'])) {
          // @TODO wait for the core issue fix make changes in a multi value field.
          // $this->changeFieldInfo($element, $field['action', $element]);
          // $this->changeFieldInfo($element['value'], $field['action'], $element);
        //}
        else {
          $this->changeFieldInfo($element, $field['action'], $entity, $field['field']);
        }
      }
    }

    $event->setArgument('element', $element);

    // Prepare the debug message.
    $availableFields = $this->util->getBundleEditableFields($action->getTargetEntityType(), $action->getTargetBundle());
    $result          = [];

    foreach ($fields as $field) {
      $debug_message = t('Field %field, setting: %setting<br>', [
        '%field'   => $availableFields[$field['field']],
        '%setting' => $this->actionOptions[$field['action']],
      ]);

      $result[] = [
        '#type'   => 'markup',
        '#markup' => $debug_message,
      ];
    }

    return $result;
  }

  protected function changeFieldInfo(&$field, $change, Entity $entity, $field_name) {
    switch ($change) {
      case self::MAKE_REQUIRED:
        $field['#required'] = TRUE;
        break;

      case self::MAKE_OPTIONAL:
        $field['#required'] = FALSE;
        break;

      case self::MAKE_READY_ONLY:
        $field['#disabled'] = TRUE;
        break;

      case self::MAKE_HIDDEN:
        //unset($field);
//        $field = [
//          '#attributes' => ['style' => ''],
//        ];
    }
  }

}
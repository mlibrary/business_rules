<?php

namespace Drupal\business_rules\Form;

use Drupal\business_rules\Entity\Action;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChangeFieldInfoForm extends FormBase {

  /**
   * The BusinessRuleUtil.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  protected $util;

  /**
   * ChangeFieldInfoForm constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal container.
   */
  public function __construct(ContainerInterface $container) {
    $this->util = $container->get('business_rules.util');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'business_rules.change_field_info_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $action = NULL, $field = NULL) {
    $action          = Action::load($action);
    $field           = $action->getSettings('fields')[$field];
    $availableFields = $this->util->getBundleEditableFields($action->getTargetEntityType(), $action->getTargetBundle());
    unset($availableFields[$field['field']]);

    $form_state->set('action', $action);
    $form_state->set('field', $field);

    $form['#title'] = t('Configure additional info to field %field', ['%field' => $field['field']]);

    $form['parent_field'] = [
      '#type'          => 'select',
      '#title'         => t('Parent field'),
      '#options'       => ['' => $this->t('-Select-')] + $availableFields,
      '#required'      => TRUE,
      '#default_value' => isset($field['info']['parent_field']) ? $field['info']['parent_field'] : '',
      '#suffix'        => '<div class="description">' . t('The field of who this field is dependent.') . '</div>',
    ];

    $form['view_display'] = [
      '#type'          => 'select',
      '#title'         => t('View used to select the entities'),
      '#options'       => ['' => $this->t('-Select-')] + $this->util->getViewsOptions('entity_reference'),
      '#required'      => TRUE,
      '#default_value' => isset($field['info']['view_display']) ? $field['info']['view_display'] : '',
      '#suffix'        => '<div class="description">' . t('Choose se view and display that the selected entities can be referenced. Only views with a display of type "Entity Reference" are eligible.') . '</div>',
    ];

    $form['use_parent_as_argument'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Use the parent field value as first argument'),
      '#default_value' => isset($field['info']['use_parent_as_argument']) ? $field['info']['use_parent_as_argument'] : '',
    ];

    $form['view_arguments'] = [
      '#type'          => 'textfield',
      '#title'         => t('View additional arguments'),
      '#required'      => FALSE,
      '#default_value' => isset($field['info']['view_arguments']) ? $field['info']['view_arguments'] : '',
      '#suffix'        => '<div class="description">' . t('Provide a comma separated list of arguments to pass to the view. You can use any available variable here as {{{variable_id}}.') . '</div>',
    ];

    $form['variables'] = $this->util->getVariablesDetailsBox($action);

    $form['save'] = [
      '#type'       => 'submit',
      '#value'      => $this->t('Save'),
      '#attributes' => ['class' => ['button--primary']],
    ];

    $form['back'] = [
      '#type'        => 'link',
      '#title'       => t('Back'),
      '#button_type' => 'danger',
      '#attributes'  => ['class' => ['button', 'button--danger']],
      '#url'         => Url::fromRoute('entity.business_rules_action.edit_form', ['business_rules_action' => $action->id()]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\business_rules\Entity\Action $action */
    $action = $form_state->get('action');
    $field  = $form_state->get('field');

    $parent_field           = $form_state->getValue('parent_field');
    $view_display           = $form_state->getValue('view_display');
    $use_parent_as_argument = $form_state->getValue('use_parent_as_argument');
    $view_arguments         = $form_state->getValue('view_arguments');

    $info = [
      'parent_field'           => $parent_field,
      'view_display'           => $view_display,
      'use_parent_as_argument' => $use_parent_as_argument,
      'view_arguments'         => $view_arguments,
    ];

    $fields                       = $action->getSettings('fields');
    $fields[$field['id']]['info'] = $info;

    $action->setSetting('fields', $fields);
    $action->save();

    $form_state->setRedirect('entity.business_rules_action.edit_form', ['business_rules_action' => $action->id()]);
  }

}

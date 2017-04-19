<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Class ChangeFormMode.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "change_form_display",
 *   label = @Translation("Change entity form display"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Change the entity form display"),
 *   isContextDependent = TRUE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = FALSE,
 * )
 */
class ChangeFormDisplay extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    if ($item->isNew()) {
      return [];
    }

    $form_modes         = $this->util->entityTypeManager->getStorage('entity_form_mode')
      ->loadMultiple();
    $options['default'] = $this->t('Default');
    /** @var \Drupal\Core\Entity\EntityFormModeInterface $form_mode */
    foreach ($form_modes as $key => $form_mode) {
      if ($form_mode->getTargetType() == $item->getTargetEntityType()) {
        $options[$form_mode->id()] = $form_mode->label();
      }
    }

    $link = Link::createFromRoute($this->t('Click here to create a new form mode.'), 'entity.entity_form_mode.collection');

    $settings['form_display'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Form display'),
      '#description'   => $this->t('Select the form display you want to present.') . ' ' . $link->toString(),
      '#options'       => $options,
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('form_display'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    // We don't need variables or tokens for this action type.
    unset($form['variables']);
    unset($form['tokens']);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity        = $event->getArgument('entity');
    $display_mode  = $action->getSettings('form_display');
    $display_mode  = explode('.', $display_mode)[1];
    $original_form = $event->getArgument('form');

    $new_form = $this->util->container->get('entity.form_builder')
      ->getForm($entity, $display_mode);

    //@TODO parei aqui. tentando fazer o form_mode funcionar

    //$form = $this->arrayIntersectKeyRecursive($new_form, $original_form);
//    $form['field_city'] = $original_form['field_city'];
//    $form['field_state'] = $original_form['field_state'];

    $form = $new_form;
    foreach ($new_form as $key => $value) {
      if (substr($key, 0, 1) == '#' && isset($original_form[$key])) {
//        $form[$key] = $original_form[$key];
      }
      elseif (isset($original_form[$key])) {
//        unset($form[$key]);
      }
    }

//    foreach ($new_form as $key => $value) {
//      if (isset($original_form[$key])) {
//        $form[$key] = $original_form[$key];
//        if (isset($new_form[$key]['#weight'])) {
//          $form[$key]['#weight'] = $new_form[$key]['#weight'];
//        }
//      }
//      else {
//        $form[$key] = $new_form[$key];
//      }
//      if (isset($new_form[$key]['widget'])) {
//        $form[$key]['widget'] = $new_form[$key]['widget'];
//      }
//    }

    $form['actions'] = $original_form['actions'];
//    $form['form_build_id'] = $original_form['form_build_id'];
    //$form['form_id'] = $original_form['form_id'];
    foreach ($form['form_id'] as $key => $item) {
      if (!isset($original_form['form_id'][$key])) {
        unset($form['form_id'][$key]);
      }
    }
//    $form['form_token'] = $original_form['form_token'];

    $event->setArgument('form', $form);

    $result = [
      '#type'   => 'markup',
      '#markup' => $this->t('Form display: %form_display applied for entity type: %entity_type.', [
        '%form_display' => $display_mode,
        '%entity_type'  => $entity->getEntityTypeId(),
      ]),
    ];

    return $result;
  }

  private function arrayIntersectKeyRecursive(array $array1, array $array2) {
    $array1 = array_intersect_key($array1, $array2);
    foreach ($array1 as $key => &$value) {
      if (is_array($value)) {
        if (is_array($array2[$key])) {
          $value = $this->arrayIntersectKeyRecursive($value, $array2[$key]);
        }
        else {
          $value = $array2[$key];
        }
      }
    }
    return $array1;
  }

}

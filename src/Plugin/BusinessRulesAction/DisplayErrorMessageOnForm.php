<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ValidateFormField.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "display_error_message_on_form",
 *   label = @Translation("Display error message in a form"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Generates a validation error in entity a form."),
 *   reactsOnIds = {"form_validation"},
 *   isContextDependent = TRUE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = TRUE,
 * )
 */
class DisplayErrorMessageOnForm extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings['message'] = [
      '#type'          => 'textarea',
      '#title'         => t('Message'),
      '#description'   => t('To use variables on the message, just type the variable machine name as {{variable_id}}.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('message'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {

    if (!empty($event['form_state'])) {
      $field     = $action->getSettings('field');
      $message   = nl2br($action->getSettings('message'));
      $variables = $event->getArgument('variables');
      $message   = $this->processVariables($message, $variables);
      $message   = new FormattableMarkup($message, []);

      $form_state = $event->getArgument('form_state');
      if (count($form_state->getValues()) > 0) {
        $form_state->setErrorByName($field, $message);

        $result = [
          '#type' => 'markup',
          '#markup' => t('Error setted on form. Field: %field, message: %message', [
            '%field' => $field,
            '%message' => $message,
          ]),
        ];
      }
      else {
        $result = [
          '#type' => 'markup',
          '#markup' => t('No errors setted on form.'),
        ];
      }
    }

    return $result;
  }

}

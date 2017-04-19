<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ShowMessage.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "show_message",
 *   label = @Translation("Show a message"),
 *   group = @Translation("System"),
 *   description = @Translation("Show a system message"),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class ShowMessage extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings['message_type'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Message Type'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('message_type'),
      '#options'       => [
        'status'  => $this->t('Status message'),
        'warning' => $this->t('Warning message'),
        'error'   => $this->t('Error message'),
      ],
    ];

    $settings['message'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Message'),
      '#description'   => $this->t('To use variables on the message, just type the variable machine name as {{variable_id}}.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('message'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    $variables    = $event->getArgument('variables');
    $message      = nl2br($action->getSettings('message'));
    $message_type = $action->getSettings('message_type');
    $message      = $this->processVariables($message, $variables);
    $message      = new FormattableMarkup($message, []);

    drupal_set_message($message, $message_type);

    $result = [
      '#type'   => 'markup',
      '#markup' => $this->t('Message type: %type, text: %message showed.', [
        '%message' => $message,
        '%type' => $message_type,
      ]),
    ];

    return $result;;
  }

}

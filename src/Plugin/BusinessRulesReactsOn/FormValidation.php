<?php

namespace Drupal\business_rules\Plugin\BusinessRulesReactsOn;

use Drupal\business_rules\Plugin\BusinessRulesReactsOnPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormBuild.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesReactsOn
 *
 * @BusinessRulesReactsOn(
 *   id = "form_validation",
 *   label = @Translation("Entity form validation"),
 *   description = @Translation("Reacts when entity form is being validated."),
 *   group = @Translation("Entity"),
 *   eventName = "business_rules.form_validation",
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   priority = 1000,
 * )
 */
class FormValidation extends BusinessRulesReactsOnPlugin {

  /**
   * Performs the BusinessRule form validation.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function validateForm(array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\business_rules\Util\BusinessRulesProcessor $processor */
    /** @var \Drupal\business_rules\BusinessRulesEvent $event */

    // The BusinessRulesProcessor process the items after form submission.
    // To form validation we need to process the rule's items before it.
    // In this case we need to call the processor right now.
    $event = $form_state->get('business_rules_event');
    $event->setArgument('form_state', $form_state);
    $processor     = \Drupal::getContainer()->get('business_rules.processor');
    $entityManager = \Drupal::entityTypeManager();

    $entity                = $form_state->getFormObject()->getEntity();
    $entity_values         = $form_state->getValues();
    $entity_values['type'] = $entity->bundle();

    // Set a new entity to be the current entity with the values entered in the
    // form. The comparison will gonna be against this entity.
    $new_entity = $entityManager->getStorage($entity->getEntityTypeId())
      ->create($entity_values);

    $event->setArgument('entity', $new_entity);
    $event->setArgument('form_state', $form_state);

    $processor->process($event);
  }

}

<?php

namespace Drupal\business_rules;

/**
 * Provides an interface for defining Action entities.
 */
interface ActionInterface extends ItemInterface {

  /**
   * Execute the action.
   *
   * @param \Drupal\business_rules\BusinessRulesEvent $event
   *   The event that has triggered the action.
   *
   * @return array
   *   The render array to be showed on debug block.
   */
  public function execute(BusinessRulesEvent $event);

}

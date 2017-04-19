<?php

namespace Drupal\business_rules\Plugin;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Business rules Condition plugins.
 */
abstract class BusinessRulesConditionPlugin extends BusinessRulesItemPluginBase implements BusinessRulesConditionPluginInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  abstract public function process(ConditionInterface $condition, BusinessRulesEvent $event);

}

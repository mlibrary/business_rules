<?php

namespace Drupal\business_rules\Plugin;

use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Business rules variable plugins.
 */
abstract class BusinessRulesVariablePlugin extends BusinessRulesItemPluginBase implements BusinessRulesVariablePluginInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  abstract public function evaluate(Variable $variable, BusinessRulesEvent $event);

  /**
   * {@inheritdoc}
   */
  public function changeDetails(Variable $variable, array &$row) {}

}

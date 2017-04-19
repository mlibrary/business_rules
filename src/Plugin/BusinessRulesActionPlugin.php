<?php

namespace Drupal\business_rules\Plugin;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Business rules Action plugins.
 */
abstract class BusinessRulesActionPlugin extends BusinessRulesItemPluginBase implements BusinessRulesActionPluginInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  abstract public function execute(ActionInterface $action, BusinessRulesEvent $event);

}

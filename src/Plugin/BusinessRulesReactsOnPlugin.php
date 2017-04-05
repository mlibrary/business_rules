<?php

namespace Drupal\business_rules\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for Business rules reacts on plugins.
 */
abstract class BusinessRulesReactsOnPlugin extends PluginBase implements BusinessRulesReactsOnInterface {

  /**
   * {@inheritdoc}
   */
  public function processForm(array &$form, FormStateInterface $form_state) {
    return [];
  }

}

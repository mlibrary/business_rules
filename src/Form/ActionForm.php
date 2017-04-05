<?php

namespace Drupal\business_rules\Form;

use Drupal\business_rules\ConditionListBuilder;
use Drupal\business_rules\Entity\Condition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ActionForm.
 *
 * @package Drupal\business_rules\Form
 */
class ActionForm extends ItemForm {

  /**
   * {@inheritdoc}
   */
  public function getItemManager() {
    $container = \Drupal::getContainer();

    return $container->get('plugin.manager.business_rules.action');
  }

}

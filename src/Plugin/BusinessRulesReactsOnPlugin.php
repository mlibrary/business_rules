<?php

namespace Drupal\business_rules\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Business rules reacts on plugins.
 */
abstract class BusinessRulesReactsOnPlugin extends PluginBase implements BusinessRulesReactsOnInterface {
  use StringTranslationTrait;

  /**
   * The business rules processor.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesProcessor
   */
  protected $processor;

  /**
   * The business rules util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  protected $util;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContainerInterface $container) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->processor = $container->get('business_rules.processor');
    $this->util      = $container->get('business_rules.util');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processForm(array &$form, FormStateInterface $form_state) {

  }

}

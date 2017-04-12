<?php

namespace Drupal\business_rules\EventSubscriber;

use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\Util\BusinessRulesProcessor;
use Drupal\business_rules\Util\BusinessRulesUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class BusinessRulesListener.
 *
 * @package Drupal\business_rules\EventSubscriber
 */
class BusinessRulesListener implements EventSubscriberInterface {

  /**
   * The business rule processor.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesProcessor
   */
  private $processor;

  /**
   * The Business Rules Util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  private $util;

  /**
   * BusinessRulesListener constructor.
   *
   * @param \Drupal\business_rules\Util\BusinessRulesProcessor $processor
   *   The business rule processor service.
   * @param \Drupal\business_rules\Util\BusinessRulesUtil $util
   *   The business rule util.
   */
  public function __construct(BusinessRulesProcessor $processor, BusinessRulesUtil $util) {
    $this->util      = $util;
    $this->processor = $processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $return['business_rules.item_pos_delete'] = 'itemPosDelete';

    // If there is no container service there is not possible to load any event.
    // As this method sometimes is called before the container is ready, the
    // Container might not be available.
    // In this case, it's necessary to manually rebuild the cache in order to
    // get all subscribed events.
    if (!\Drupal::hasContainer() || !\Drupal::hasService('plugin.manager.business_rules.reacts_on')) {
      return $return;
    }

    $container         = \Drupal::getContainer();
    $reactionEvents    = $container->get('plugin.manager.business_rules.reacts_on');
    $eventsDefinitions = $reactionEvents->getDefinitions();

    foreach ($eventsDefinitions as $event) {
      $return[$event['eventName']] = [
        'process',
        $event['priority'],
      ];
    }

    return $return;

  }

  /**
   * Process the rules.
   *
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The event to be processed.
   */
  public function process(BusinessRulesEvent $event) {
    $this->processor->process($event);
  }

  /**
   * Remove the item references.
   *
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The event.
   */
  public function itemPosDelete(BusinessRulesEvent $event) {
    $this->util->removeItemReferences($event);
  }

}

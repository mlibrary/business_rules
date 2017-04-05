<?php

namespace Drupal\business_rules\EventSubscriber;

use Drupal\business_rules\Util\BusinessRulesProcessor;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\business_rules\BusinessRulesEvent;

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
   * BusinessRulesListener constructor.
   *
   * @param \Drupal\business_rules\Util\BusinessRulesProcessor $processor
   *   The business rule processor service.
   */
  public function __construct(BusinessRulesProcessor $processor) {

    $this->processor = $processor;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    // If there is no container service there is not possible to load any event.
    // As this method sometimes is called before the container is ready, the
    // Container might not be available.
    // In this case, it's necessary to manually rebuild the cache in order to
    // get all subscribed events.
    if (!\Drupal::hasContainer() || !\Drupal::hasService('plugin.manager.business_rules.reacts_on')) {
      return [];
    }

    $container         = \Drupal::getContainer();
    $reactionEvents    = $container->get('plugin.manager.business_rules.reacts_on');
    $eventsDefinitions = $reactionEvents->getDefinitions();

    $return = [];
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
   * @param \Drupal\business_rules\BusinessRulesEvent $event
   *   The event to be processed.
   */
  public function process(BusinessRulesEvent $event) {
    $this->processor->process($event);
  }

}

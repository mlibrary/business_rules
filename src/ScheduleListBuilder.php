<?php

namespace Drupal\business_rules;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Schedule entities.
 *
 * @ingroup business_rules
 */
class ScheduleListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // TODO: show links for schedules tasks and executed tasks.
    $header['id'] = $this->t('Schedule ID');
    $header['triggered_by'] = $this->t('Triggered by');
    $header['name'] = $this->t('Name');
    $header['scheduled_date'] = $this->t('Scheduled Date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\business_rules\Entity\Schedule */
    $row['id'] = $entity->id();
    $row['triggered_by'] = Link::createFromRoute($entity->getTriggeredBy()->id(),
      'entity.business_rules_action.edit_form',
      ['business_rules_action' => $entity->getTriggeredBy()->id()]
    );
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.business_rules_schedule.edit_form',
      ['business_rules_schedule' => $entity->id()]
    );
    $scheduled = \Drupal::service('date.formatter')->format($entity->getScheduled(), 'medium');
    $row['scheduled_date'] = $scheduled;
    return $row + parent::buildRow($entity);
  }

}

<?php

namespace Drupal\business_rules;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Rule entities.
 */
class BusinessRuleListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label']       = $this->t('Rule');
    $header['id']          = $this->t('Machine name');
    $header['event']       = $this->t('Reacts on event');
    $header['enabled']     = $this->t('Enabled');
    $header['entity']      = $this->t('Entity');
    $header['bundle']      = $this->t('Bundle');
    $header['description'] = $this->t('Description');
    $header['filter']      = [
      'data'  => ['#markup' => 'filter'],
      'style' => 'display: none',
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\business_rules\Entity\BusinessRule $entity */
    $status = $entity->isEnabled() ? $this->t('Enabled') : $this->t('Disabled');

    $row['label']       = $entity->label();
    $row['id']          = $entity->id();
    $row['event']       = $entity->getReactsOnLabel();
    $row['enabled']     = $status;
    $row['entity']      = $entity->getTargetEntityTypeLabel();
    $row['bundle']      = $entity->getTargetBundleLabel();
    $row['description'] = $entity->getDescription();

    $search_string = $entity->label() . ' ' .
      $entity->id() . ' ' .
      $entity->getReactsOnLabel() . ' ' .
      $status . ' ' .
      $entity->getTargetEntityTypeLabel() . ' ' .
      $entity->getTargetBundleLabel() . ' ' .
      $entity->getDescription();

    $row['filter'] = [
      'data'  => [['#markup' => '<span class="table-filter-text-source">' . $search_string . '</span>']],
      'style' => ['display: none'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    $output['#attached']['library'][] = 'system/drupal.system.modules';

    $output['filters'] = [
      '#type'       => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $output['filters']['text'] = [
      '#type'        => 'search',
      '#title'       => $this->t('Search'),
      '#size'        => 30,
      '#placeholder' => $this->t('Search for a rule'),
      '#attributes'  => [
        'class'        => ['table-filter-text'],
        'data-table'   => '.searchable-list',
        'autocomplete' => 'off',
        'title'        => $this->t('Enter a part of the rule to filter by.'),
      ],
    ];

    $output += parent::render();
    if (!isset($output['table']['#attributes']['class'])) {
      $output['table']['#attributes']['class'] = ['searchable-list'];
    }
    else {
      $output['table']['#attributes']['class'][] = ['searchable-list'];
    }

    return $output;
  }

}

<?php

namespace Drupal\business_rules;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Business rules Items entities.
 */
abstract class ItemListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label']       = ['data' => ['#markup' => $this->t('Label')]];
    $header['id']          = ['data' => ['#markup' => $this->t('Machine name')]];
    $header['type']        = ['data' => ['#markup' => $this->t('Type')]];
    $header['description'] = ['data' => ['#markup' => $this->t('Description')]];
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
    $row['label']       = ['data' => ['#markup' => $entity->label()]];
    $row['id']          = ['data' => ['#markup' => $entity->id()]];
    $row['type']        = ['data' => ['#markup' => $entity->getTypeLabel()]];
    $row['description'] = ['data' => ['#markup' => $entity->getDescription()]];

    $search_string = $entity->label() . ' ' .
      $entity->id() . ' ' .
      $entity->getTypeLabel() . ' ' .
      $entity->getTypeLabel() . ' ' .
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
      '#placeholder' => $this->t('Search for a item'),
      '#attributes'  => [
        'class'        => ['table-filter-text'],
        'data-table'   => '.searchable-list',
        'autocomplete' => 'off',
        'title'        => $this->t('Enter a part of the item to filter by.'),
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

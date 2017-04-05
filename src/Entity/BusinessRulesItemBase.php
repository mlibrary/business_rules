<?php

namespace Drupal\business_rules\Entity;

use Drupal\business_rules\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Class Item.
 *
 * @package Drupal\business_rules\Entity
 */
abstract class BusinessRulesItemBase extends ConfigEntityBase implements ItemInterface {
  /**
   * The Item description.
   *
   * @var string
   */
  protected $description;

  /**
   * The Item ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Item plugin manager.
   *
   * @var \Drupal\Core\Plugin\DefaultPluginManager
   */
  protected $itemManager;

  /**
   * The Item label.
   *
   * @var string
   */
  protected $label;

  /**
   * The item settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The target entity bundle id which this item is applicable.
   *
   * @var string
   */
  protected $target_bundle;

  /**
   * The entity type id which this item is applicable.
   *
   * @var string
   */
  protected $target_entity_type;

  /**
   * The item type.
   *
   * @var string
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    $this->itemManager = $this->getItemManager();
  }

  /**
   * Get the plugin manager.
   *
   * @return \Drupal\Core\Plugin\DefaultPluginManager
   *   The plugin manager to be used.
   */
  public abstract function getItemManager();

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getReactOnEvents() {
    $definition = $this->itemManager->getDefinition($this->getType());
    if (array_key_exists('reactsOnIds', $definition)) {
      return $definition['reactsOnIds'];
    }
    else {
      return [];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getSettings($settingId = '') {
    if ($settingId == '') {
      return $this->settings;
    }
    elseif (empty($this->settings[$settingId])) {
      if (array_key_exists($settingId, $this->settings)) {
        if ($this->settings[$settingId] === 0 || $this->settings[$settingId] === "0") {
          $value = 0;
        }
        else {
          $value = NULL;
        }
      }
      else {
        $value = NULL;
      }
    }
    else {
      $value = $this->settings[$settingId];
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($settingId, $value) {
    if (!empty($settingId)) {
      $this->settings[$settingId] = $value;
    }
    else {
      throw new \Exception('You must enter a value to the settingId');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle() {
    return $this->target_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType() {
    return $this->target_entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel() {
    $types = $this->getTypes();

    foreach ($types as $type) {
      foreach ($type as $key => $value) {
        if ($key == $this->getType()) {
          return $value;
        }
      }
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    $types = [];
    $items = $this->itemManager->getDefinitions();

    uasort($items, function ($a, $b) {
      return ($a['label']->render() > $b['label']->render()) ? 1 : -1;
    });

    foreach ($items as $item) {
      if (isset($types[$item['group']->render()])) {
        $types[$item['group']->render()] += [$item['id'] => $item['label']];
      }
      else {
        $types[$item['group']->render()] = [$item['id'] => $item['label']];
      }
    }

    ksort($types);

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function isContextDependent() {
    $type       = $this->getType();
    $definition = $this->getItemManager()->getDefinition($type);

    return $definition['isContextDependent'];
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultipleByType($type, array $ids = NULL) {
    $items  = self::loadMultiple($ids);
    $result = [];
    /** @var ItemInterface $item */
    foreach ($items as $item) {
      if ($item->getType() == $type) {
        $result[] = $item;
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariables() {
    $item_type    = $this->itemManager->getDefinition($this->getType());
    $reflection   = new \ReflectionClass($item_type['class']);
    $defined_item = $reflection->newInstance($item_type, $item_type['id'], $item_type);
    $variables    = $defined_item->getVariables($this);

    return $variables;
  }
}
// @TODO add tags to Business Rules and it's items.

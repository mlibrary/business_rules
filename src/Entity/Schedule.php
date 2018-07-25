<?php

namespace Drupal\business_rules\Entity;

use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\Util\BusinessRulesUtil;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Schedule entity.
 *
 * @ingroup business_rules
 *
 * @ContentEntityType(
 *   id = "business_rules_schedule",
 *   label = @Translation("Schedule"),
 *   handlers = {
 *     "storage" = "Drupal\business_rules\ScheduleStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\business_rules\ScheduleListBuilder",
 *     "views_data" = "Drupal\business_rules\Entity\ScheduleViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\business_rules\Form\ScheduleForm",
 *       "add" = "Drupal\business_rules\Form\ScheduleForm",
 *       "edit" = "Drupal\business_rules\Form\ScheduleForm",
 *       "delete" = "Drupal\business_rules\Form\ScheduleDeleteForm",
 *     },
 *     "access" = "Drupal\business_rules\ScheduleAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\business_rules\ScheduleHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "br_schedule",
 *   revision_table = "br_schedule_revision",
 *   admin_permission = "administer business rules schedule entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/workflow/business_rules/schedule/{business_rules_schedule}",
 *     "add-form" = "/admin/config/workflow/business_rules/schedule/add",
 *     "edit-form" = "/admin/config/workflow/business_rules/schedule/{business_rules_schedule}/edit",
 *     "delete-form" = "/admin/config/workflow/business_rules/schedule/{business_rules_schedule}/delete",
 *     "version-history" = "/admin/config/workflow/business_rules/schedule/{business_rules_schedule}/revisions",
 *     "revision" = "/admin/config/workflow/business_rules/schedule/{schedule}/revisions/{schedule_revision}/view",
 *     "revision_revert" = "/admin/config/workflow/business_rules/schedule/{schedule}/revisions/{schedule_revision}/revert",
 *     "revision_delete" = "/admin/config/workflow/business_rules/schedule/{schedule}/revisions/{schedule_revision}/delete",
 *     "collection" = "/admin/config/workflow/business_rules/schedule",
 *   },
 * )
 */
class Schedule extends RevisionableContentEntityBase implements ScheduleInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function isExecuted() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setExecuted($executed) {
    $this->set('status', $executed ? TRUE : FALSE);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', $description);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getScheduled() {
    return $this->get('scheduled')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setScheduled($timestamp) {
    $this->set('scheduled', $timestamp);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTriggeredBy() {
    $a = $this->get('triggered_by');
    $e = $a->entity;

    return $e;
  }

  /**
   * {@inheritdoc}
   */
  public function setTriggeredBy(BusinessRulesItemBase $businessRuleItem) {
    $this->set('triggered_by', $businessRuleItem->id());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Generate UUID.
    if (!count($this->get('uuid')->getValue())) {
      $uuid = \Drupal::service('uuid')->generate();
      $this->set('uuid', $uuid);
    }

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the schedule owner
    // the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Schedule entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The unique name of the Schedule entity. Schedules with same name are replaced.'))
      ->setRevisionable(FALSE)
      ->setSettings([
        'max_length'      => 50,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setRequired(TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('The schedule description.'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Executed status'))
      ->setDescription(t('A boolean indicating whether the Scheduled event has been executed.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['scheduled'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Scheduled date'))
      ->setDescription(t('The date and time which it is scheduled.'));

    $fields['triggered_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Action'))
      ->setDescription(t('Business Rule Action which has triggered this schedule.'))
      ->setSetting('target_type', 'business_rules_action');

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function executeSchedule(BusinessRulesEvent $event) {

    // Load non-executed tasks from the action.
    $ids = \Drupal::entityTypeManager()
      ->getStorage('business_rules_schedule')
      ->getQuery()
      ->condition('status', 0)
      ->condition('scheduled', time(), '<=')
      ->execute();

    $tasks     = self::loadMultiple($ids);
    $container = \Drupal::getContainer();
    $util      = new BusinessRulesUtil($container);
    if (count($tasks)) {
      /** @var \Drupal\business_rules\Entity\Schedule $task */
      foreach ($tasks as $task) {
        /** @var \Drupal\business_rules\Entity\Action $action */
        $action = $task->getTriggeredBy();
        $items  = $action->getSettings('items');

        try {
          foreach ($items as $item) {
            $action_item = Action::load($item['id']);
            $action_item->execute($event);
          }
          $task->setExecuted(1);
          $task->save();
          $util->logger->notice(t('Scheduled task id: @id, name: "@name", triggered by: "@by" has been executed at: @time', [
            '@id'   => $task->id(),
            '@name' => $task->getName(),
            '@by'   => $task->getTriggeredBy()->id(),
            '@time' => $container->get('date.formatter')
              ->format(time(), 'medium'),
          ]));
        }
        catch (\Exception $e) {
          $util->logger->error($e->getMessage());
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByNameAndTriggeredBy($name, $triggeredBy) {
    $query = \Drupal::entityQuery('business_rules_schedule')
      ->condition('name', $name)
      ->condition('triggered_by', $triggeredBy);

    $id = $query->execute();

    if (count($id) > 0) {
      $schedule = self::load(array_keys($id)[0]);
    }
    else {
      $schedule = new self([], 'business_rules_schedule');
    }

    return $schedule;
  }

}

The Business rules is inspired on Rules module and allows site administrators to
define conditionally executed actions based on occurring events. It's based on 
variables and completely build for Drupal 8.

This module has a fully featured user interface to completely allow the site 
administrator to understand and create the site business rules.

It's also possible to extend it by creating new ReactsOn Events, Variables, 
Actions and Conditions via plugins.

### Known issues
* There are some occasions that the subscribed events will not be available. it
happens because the getSubscribed Events in some occasions is called before 
Drupal has prepared the container. I.e.: When user add new language. If it 
happens just clear your cache.

* The reactsOn event for Entity is viewed is triggered only if Drupal is loading
 the entity from database but not from cache. If you need to trigger this type 
 of rules every time entity is being viewed, you need to disable caches for 
 entities.

#####Project homepage: http://www.drupal.org/business_rules

On this module, there is no Conditions or Actions inside Business rules, all 
component needs to be created outside the Business rule and can be reusable 
across many Business rules.

## How to create custom reaction events:
To create a new custom reaction event create a new php class on 
my_module\Plugin\BusinessRulesReactsOn
 
 The new class needs to extends BusinessRulesReactsOnBase class
 
#### Add the following annotation:
    * @BusinessRulesReactsOn(
    *   id = "reaction_event_id",
    *   label = @Translation("Reaction event label"),
    *   description = @Translation("Reaction event description."),
    *   group = @Translation("Reaction event group"),
    *   eventName = "business_rules.reaction_event_id",
    *   priority = 1000,
    * )

  Where:
    
    id: the reaction event id
    label: the reaction event label
    group: the reaction event group to be groupped at the type listbox
    description: the reaction event description
    eventName: the event name triggered by the reaction event. It shoud starts 
    with business_rules. followed by the reaction_event_id
    priority: the event priority. Bigger runs first
    
  Additionally you need to dispatch the event in some way. Here is one example
  of reaction event dispatched on entity_presave on my_module.module.
  
#### Dispatch example:
  
    /**
     * Implements hook_entity_presave().
     */
    function my_module_entity_presave(EntityInterface $entity) {
      // Only handle content entities and ignore config entities.
      if ($entity instanceof ContentEntityInterface) {
        $reacts_on_definition = \Drupal::getContainer()
          ->get('plugin.manager.business_rules.reacts_on')
          ->getDefinition('reaction_event_id');
    
        $entity_type_id = $entity->getEntityTypeId();
        $event          = new BusinessRulesEvent($entity, [
          'entity_type_id'   => $entity_type_id,
          'bundle'           => $entity->bundle(),
          'entity'           => $entity,
          'entity_unchanged' => $entity->original,
          'reacts_on'        => $reacts_on_definition,
        ]);
        
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch($reacts_on_definition['eventName'], $event);
      }
    
    }

Remember to rebuild the cache after create or change any annotation or YML file.

## How to create custom conditions:
To create a new custom condition create a new php class on 
my_module\Plugin\BusinessRulesCondition
 
 The new class needs to extends BusinessRulesConditionBase class
 
#### Add the following annotation:
    * @BusinessRulesCondition(
    *   id = "condition_id",
    *   label = @Translation("Condition label"),
    *   group = @Translation("Condition group"),
    *   description = @Translation("Condition description"),
    *   reactsOnIds = {},
    *   hasTargetEntity = TRUE,
    *   hasTargetBundle = TRUE,
    *   hasTargetField = TRUE,
    * )

  Where:
    
    id: the condition id
    label: the condition label
    group: the condition group to be groupped at the condition type listbox
    description: the condition description
    reactsOnIds: the IDs of the BusinessRulesReactsOn evets. Leave empty if it's
    applicable for all events
    hasTargetEntity: if the condition is applicable to specific entity types
    hasTargetBundle: if the condition is applicable to specific bundle
    hasTargetField: if the condition is applicable to specific field

#### Create the schema file
  The schema file must live in my_module\config\schema and the file name should
  be condition_id.schema.yml
  
  Schema file content example:
  
    business_rules.condition.type.condition_id:
      type: mapping
      label: 'Data Comparison'
      mapping:
        field:
          type: string
          label: 'Field'
        data_to_compare:
          type: string
          label: 'Data to compare'
        operator:
          type: string
          label: 'Operator'
        value_to_compare:
          type: string
          label: 'Value(s) to Compare'


## How to create custom actions:
To create a new custom action create a new php class on 
my_module\Plugin\BusinessRulesAction
 
 The new class needs to extends BusinessRulesActionPlugin class
 
#### Add the following annotation:
     * @BusinessRulesAction(
     *   id = "action_id",
     *   label = @Translation("Action label"),
     *   group = @Translation("Action group"),
     *   description = @Translation("Action description."),
     *   reactsOnIds = {},
     *   hasTargetEntity = TRUE,
     *   hasTargetBundle = TRUE,
     *   hasTargetField = FALSE,
     * )

  Where:
    
    id: the action id
    label: the action label
    group: the action group to be groupped at the action type listbox
    description: the action description
    reactsOnIds: the IDs of the BusinessRulesReactsOn evets. Leave empty if it's
    applicable for all events
    hasTargetEntity: if the action is applicable to specific entity types
    hasTargetBundle: if the action is applicable to specific bundle
    hasTargetField: if the action is applicable to speci field

#### Create the schema file
  The schema file must live in my_module\config\schema and the file name should
  be action_id.schema.yml
  
  Schema file content example:
  
    business_rules.action.type.action_id:
      type: mapping
      label: 'Show message'
      mapping:
        message:
          type: string
          label: 'Message'
        message_type:
          type: string
          label: 'Message type'

#Reserved names on schema:
    -type -> reserved word. Nerver use on plugin definition.
    -field -> use only if you are referencing to a entity field.
    -items -> use always the plugin has another business rule items. i.e. 
     actions or conditions.
     
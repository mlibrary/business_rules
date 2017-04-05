<?php

namespace Drupal\business_rules\Util;

use Drupal\business_rules\Entity\BusinessRulesItemBase;
use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\VariableListBuilder;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Url;
use Drupal\user\Entity\Role;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BusinessRulesUtil.
 *
 * @package Drupal\business_rules\Util
 */
class BusinessRulesUtil {

  const BIGGER            = '>';
  const BIGGER_OR_EQUALS  = '>=';
  const SMALLER           = '<';
  const SMALLER_OR_EQUALS = '<=';
  const EQUALS            = '==';
  const DIFFERENT         = '!=';
  const IS_EMPTY          = 'empty';
  const CONTAINS          = 'contains';
  const STARTS_WITH       = 'starts_with';
  const ENDS_WITH         = 'ends_with';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * Drupal Container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public $container;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  public $entityFieldManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  public $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  public $fieldTypePluginManager;

  /**
   * The Business Rules Flowchart.
   *
   * @var \Drupal\business_rules\Util\Flowchart\Flowchart
   */
  public $flowchart;

  /**
   * The KeyValueExpirableFactory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $keyValueExpirable;

  /**
   * The Business Rules logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  public $logger;

  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  public $request;

  /**
   * The variable manager.
   *
   * @var \Drupal\business_rules\Plugin\BusinessRulesVariableManager
   */
  protected $variableManager;

  /**
   * BusinessRulesUtil constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The services container.
   */
  public function __construct(ContainerInterface $container) {

    $this->container              = $container;
    $this->entityFieldManager     = $container->get('entity_field.manager');
    $this->fieldTypePluginManager = $container->get('plugin.manager.field.field_type');
    $this->entityTypeBundleInfo   = $container->get('entity_type.bundle.info');
    $this->configFactory          = $container->get('config.factory');
    $this->entityTypeManager      = $container->get('entity_type.manager');
    $this->entityTypeBundleInfo   = $container->get('entity_type.bundle.info');
    $this->variableManager        = $container->get('plugin.manager.business_rules.variable');
    $this->request                = $container->get('request_stack')
      ->getCurrentRequest();
    $this->logger                 = $container->get('logger.factory')
      ->get('business_rules');
    $this->keyValueExpirable      = $container->get('keyvalue.expirable');
    $this->flowchart              = $container->get('business_rules.flowchart');
  }

  /**
   * Criteria checker.
   *
   * @param string $value1
   *   The value to be compared.
   * @param string $operator
   *   The operator.
   * @param string $value2
   *   The value to test against.
   *
   * @return bool
   *   Criteria met/not met.
   */
  public function criteriaMet($value1, $operator, $value2) {
    switch ($operator) {
      case self::EQUALS:
        if ($value1 === $value2) {
          return TRUE;
        }
        break;

      case self::CONTAINS:
        if (strpos($value1, $value2) !== FALSE) {
          return TRUE;
        }
        break;

      case self::BIGGER:
        if ($value1 > $value2) {
          return TRUE;
        }
        break;

      case self::BIGGER_OR_EQUALS:
        if ($value1 >= $value2) {
          return TRUE;
        }
        break;

      case self::SMALLER:
        if ($value1 < $value2) {
          return TRUE;
        }
        break;

      case self::SMALLER_OR_EQUALS:
        if ($value1 <= $value2) {
          return TRUE;
        }
        break;

      case self::DIFFERENT:
        if ($value1 != $value2) {
          return TRUE;
        }
        break;

      case self::IS_EMPTY:
        if (empty($value1)) {
          return TRUE;
        }
        break;

      case self::STARTS_WITH:
        if (strpos($value1, $value2) === 0) {
          return TRUE;
        }
        break;

      case self::ENDS_WITH:
        if (substr($value1, strlen($value2) * -1) === $value2) {
          return TRUE;
        }
        break;

      default:
        return FALSE;
    }

    return FALSE;
  }

  /**
   * Get an render array for add items form.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The Business Rule Item.
   * @param array $items
   *   The array of items to render inside the table form.
   * @param array $selected_items
   *   The current selected items.
   * @param string $label
   *   The item label.
   * @param string $label_plural
   *   The item label in plural.
   * @param \Drupal\Core\Url $back_url
   *   The return Url.
   *
   * @return array
   *   The render array.
   */
  public function getAddItemsForm(ItemInterface $item, array $items, array $selected_items, $label, $label_plural, Url $back_url) {
    $form['#title'] = t('Add @label_plural on %parent', [
      '%parent'       => $item->label(),
      '@label_plural' => $label_plural,
    ]);

    $form['#attached']['library'][] = 'system/drupal.system.modules';

    $form['filters'] = [
      '#type'       => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $form['filters']['text'] = [
      '#type'        => 'search',
      '#title'       => t('Search'),
      '#size'        => 30,
      '#placeholder' => t('Search for a @label key', ['@label' => $label]),
      '#attributes'  => [
        'class'        => ['table-filter-text'],
        'data-table'   => '.searchable-list',
        'autocomplete' => 'off',
        'title'        => t('Enter a part of the @label key to filter by.', ['@label' => $label]),
      ],
    ];

    $header = [
      'label'       => $label,
      'id'          => t('Machine Name'),
      'type'        => t('Type'),
      'description' => t('Description'),
      'filter'      => [
        'data'  => ['#markup' => 'filter'],
        'style' => 'display: none',
      ],
    ];

    $rows = [];

    foreach ($items as $item) {
      $search_string = $item->label() . ' ' .
        $item->id() . ' ' .
        $item->getTypeLabel() . ' ' .
        $item->getDescription();

      $rows[$item->id()] = [
        'label'       => ['data' => ['#markup' => $item->label()]],
        'id'          => ['data' => ['#markup' => $item->id()]],
        'type'        => ['data' => ['#markup' => $item->getTypeLabel()]],
        'description' => ['data' => ['#markup' => $item->getDescription()]],
        'filter'      => [
          'data'  => [['#markup' => '<span class="table-filter-text-source">' . $search_string . '</span>']],
          'style' => ['display: none'],
        ],
      ];
    }

    $form['items'] = [
      '#type'          => 'tableselect',
      '#header'        => $header,
      '#options'       => $rows,
      '#js_select'     => FALSE,
      '#default_value' => $selected_items,
      '#attributes'    => [
        'class' => [
          'searchable-list',
        ],
      ],
    ];

    $form['actions'] = [
      '#type'  => 'actions',
      'submit' => [
        '#type'        => 'submit',
        '#value'       => t('Save'),
        '#button_type' => 'primary',
      ],
      'back'   => [
        '#type'        => 'link',
        '#title'       => t('Back'),
        '#button_type' => 'danger',
        '#attributes'  => ['class' => ['button', 'button--danger']],
        '#url'         => $back_url,
      ],
    ];

    return $form;
  }

  /**
   * Helper function to return all fields from one bundle.
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return array
   *   Array of fields ['type' => 'description']
   */
  public function getBundleFields($entityType, $bundle) {

    if (empty($entityType) || empty($bundle)) {
      return [];
    }

    $fields      = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);
    $field_types = $this->fieldTypePluginManager->getDefinitions();
    foreach ($fields as $field_name => $field_storage) {

      $field_type           = $field_storage->getType();
      $options[$field_name] = t('@type: @field', [
        '@type'  => $field_types[$field_type]['label'],
        '@field' => $field_storage->getLabel() . " [$field_name]",
      ]);

    }
    asort($options);

    return $options;
  }

  /**
   * Return an array with all bundles related to one content type.
   *
   * @param string $entity_type
   *   The content type ID.
   *
   * @return array
   *   Array of bundles.
   */
  public function getBundles($entity_type) {
    $output = [
      '' => t('- Select -'),
    ];

    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    foreach ($bundles as $key => $value) {
      $output[$key] = $value['label'];
    }

    asort($output);

    return $output;
  }

  /**
   * Get a options array to use with criteriaMet method.
   *
   * @return array
   *   Array of operators.
   */
  public function getCriteriaMetOperatorsOptions() {
    $operators = [
      self::BIGGER            => '>',
      self::BIGGER_OR_EQUALS  => '>=',
      self::SMALLER           => '<',
      self::SMALLER_OR_EQUALS => '<=',
      self::EQUALS            => '=',
      self::DIFFERENT         => '!=',
      self::IS_EMPTY          => t('Data value is empty'),
      self::CONTAINS          => t('Contains'),
      self::STARTS_WITH       => t('Starts with'),
      self::ENDS_WITH         => t('Ends with'),
    ];

    return $operators;
  }

  /**
   * Return the current Url.
   *
   * @return \Drupal\Core\Url|null
   *   The Url.
   */
  public function getCurrentUri() {
    $current      = \Drupal::request()->server->get('REQUEST_URI');
    $fake_request = Request::create($current);
    $url_object   = \Drupal::service('path.validator')
      ->getUrlIfValid($fake_request->getRequestUri());
    if ($url_object) {
      return $url_object;
    }

    return NULL;
  }

  /**
   * Return all content entity types.
   *
   * @return array
   *   Array of entity types. [id => label]
   */
  public function getEntityTypes() {
    $output = [];

    $types = $this->entityTypeManager->getDefinitions();
    foreach ($types as $key => $type) {
      if ($type instanceof ContentEntityType) {
        $output[$key] = $type->getLabel();
      }
    }

    asort($output);

    return $output;
  }

  /**
   * Get the Business Rules keyValueExpirable collection.
   *
   * @param string $collection
   *   The keyvalue collection.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   *   The keyValueFactory.
   */
  public function getKeyValueExpirable($collection) {
    return $this->keyValueExpirable->get('business_rules.' . $collection);
  }

  /**
   * Return the previous Url.
   *
   * @return \Drupal\Core\Url|null
   *   The Url.
   */
  public function getPreviousUri() {
    $previousUrl  = \Drupal::request()->server->get('HTTP_REFERER');
    $fake_request = Request::create($previousUrl);
    $url_object   = \Drupal::service('path.validator')
      ->getUrlIfValid($fake_request->getRequestUri());
    if ($url_object) {
      return $url_object;
    }

    return NULL;
  }

  /**
   * Get all user roles.
   *
   * @return array
   *   Options array.
   */
  public function getUserRolesOptions() {
    $roles   = Role::loadMultiple();
    $options = [];

    /**@var  Role $role */
    foreach ($roles as $key => $role) {
      $options[$role->id()] = $role->label();
    }
    asort($options);

    return $options;
  }

  /**
   * Return a details box which the available variables for use on this context.
   *
   * @param \Drupal\business_rules\Entity\BusinessRulesItemBase $item
   *   The business Rule Item.
   * @param string $plugin_type
   *   The variable plugin type id.
   *
   * @return array
   *   The render array.
   */
  public function getVariablesDetailsBox(BusinessRulesItemBase $item, $plugin_type = '') {

    $target_entity_type  = $item->getTargetEntityType();
    $target_bundle       = $item->getTargetBundle();
    $variables           = Variable::loadMultiple();
    $available_variables = [];
    $details             = [];

    if (is_array($variables)) {
      /** @var Variable $variable */
      foreach ($variables as $variable) {
        // Check targetBundle.
        if (((($variable->getTargetBundle() == $target_bundle || empty($target_bundle) || empty($variable->getTargetBundle()))
              // Check targetEntity.
              && ($variable->getTargetEntityType() == $target_entity_type || empty($target_entity_type) || empty($variable->getTargetEntityType())))
            // Check context dependency.
            || (!$variable->isContextDependent()))
          // Check plugin type.
          && ($plugin_type == '' || $plugin_type == $variable->getType())
          // Check if it's the variable being edited.
          && (($item instanceof Variable && $item->id() != $variable->id()) || !$item instanceof Variable)
        ) {
          $available_variables[] = $variable;
        }
      }
    }

    if (is_array($available_variables) && count($available_variables)) {
      $storage = $this->entityTypeManager->getStorage('business_rules_variable');
      $list    = new VariableListBuilder($variable->getEntityType(), $storage);

      $details = [
        '#type'        => 'details',
        '#title'       => t('Available Variables for this context'),
        '#collapsed'   => TRUE,
        '#collapsable' => TRUE,
      ];

      $header           = $list->buildHeader();
      $new_header['id'] = t('Variable');
      unset($header['id']);
      foreach ($header as $key => $item) {
        $new_header[$key] = $item;
      }
      $header = $new_header;

      $rows = [];
      foreach ($available_variables as $variable) {
        $row           = $list->buildRow($variable);
        $new_row['id'] = '{{' . $row['id']['data']['#markup'] . '}}';
        unset($row['id']);
        foreach ($row as $key => $item) {
          $new_row[$key] = $item;
        }

        // Give a chance to the variable plugin change the details about this
        // availability.
        $type             = $variable->getType();
        $variable_type    = $this->variableManager->getDefinition($type);
        $reflection       = new \ReflectionClass($variable_type['class']);
        $defined_variable = $reflection->newInstance($variable_type, $variable_type['id'], $variable_type);
        $defined_variable->changeDetails($variable, $new_row);

        $rows[] = $new_row;
      }

      $details['variables'] = [
        '#type'   => 'table',
        '#header' => $header,
        '#rows'   => $rows,
      ];
    }

    return $details;

  }

  /**
   * Get a variables options array.
   *
   * @param array $variable_types
   *   The variable type. Leave empty if you need all variables..
   * @param array $entity_type
   *   Variable entity type. Empty for all.
   * @param array $bundle
   *   Variable bundle. Emoty for all.
   *
   * @return array
   *   Options array.
   */
  public function getVariablesOptions(array $variable_types = [], array $entity_type = [], array $bundle = []) {
    $options = [];

    $variables = Variable::loadMultiple();
    /** @var Variable $variable */
    foreach ($variables as $variable) {
      if ((!count($variable_types) || in_array($variable->getType(), $variable_types))
        && (!count($entity_type) || in_array($variable->getTargetEntityType(), $entity_type))
        && (!count($bundle) || in_array($variable->getTargetBundle(), $bundle))
      ) {
        $options[$variable->id()] = $variable->label() . ' [' . $variable->id() . ']';
      }
    }
    asort($options);

    return $options;
  }

  /**
   * Get a list of views to display in a option box.
   *
   * @return array
   *   Options array.
   */
  public function getViewsOptions() {

    $views   = Views::getAllViews();
    $options = [];

    foreach ($views as $view) {
      $id              = $view->id();
      $big_description = strlen($view->get('description') > 100) ? '...' : '';
      foreach ($view->get('display') as $display) {
        $options[$view->label() . ' : ' .
        substr($view->get('description'), 0, 100) .
        $big_description][$id . ':' . $display['id']] = t('@view : @display_id : @display_title', [
          '@view'          => $view->label(),
          '@display_id'    => $display['id'],
          '@display_title' => $display['display_title'],
        ]);
      }
    }
    ksort($options);

    return $options;
  }

}

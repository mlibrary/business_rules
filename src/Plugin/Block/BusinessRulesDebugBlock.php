<?php

namespace Drupal\business_rules\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Class BusinessRulesDebugBlock.
 *
 * @package Drupal\business_rules\Plugin\Block
 *
 * @Block(
 *   id = "business_rules_debug_block",
 *   admin_label = @Translation("Business rules debug"),
 *   definition={}
 *
 * )
 */
class BusinessRulesDebugBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::configFactory()->get('business_rules.settings');
    $output = [];
    if ($config->get('debug_screen')) {
      $keyvalue   = \Drupal::keyValueExpirable('business_rules.debug');
      $session_id = session_id();
      $output     = $keyvalue->get($session_id);
      $keyvalue->set($session_id, NULL);

      $output['#attached']['library'][] = 'business_rules/style';
      $output['#attached']['library'][] = 'dbug/dbug';
    }

    return $output;
  }

  /**
   * This block cannot be cacheable.
   *
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'administer site configuration');
  }

}

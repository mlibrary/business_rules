<?php

namespace Drupal\business_rules\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax command to update form options.
 *
 * @package Drupal\business_rules\Ajax
 */
class UpdateOptionsCommand implements CommandInterface {

  protected $elementId;

  protected $options;

  public function __construct($elementId, $options) {
    $this->elementId = $elementId;
    $this->options = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'updateOptionsCommand',
      'method' => 'html',
      'elementId' => $this->elementId,
      'options' => $this->options,
    ];
  }

}

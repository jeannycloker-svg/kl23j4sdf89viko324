<?php

namespace Drupal\component_blocks_test;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\node\NodeInterface;

/**
 * Defines a class for a test node context.
 */
class TestNodeContext implements ContextProviderInterface {

  /**
   * Node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = EntityContextDefinition::create('node')->setRequired(FALSE);

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route'])->setCacheMaxAge(0);
    $context = new Context($context_definition, $this->node);
    $context->addCacheableDependency($cacheability);
    $result['node'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('node', 'A node');
    return ['node' => $context];
  }

  /**
   * Sets value of Node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Value for Node.
   */
  public function setNode(NodeInterface $node): void {
    $this->node = $node;
  }

}

<?php

namespace Drupal\metastore\NodeWrapper;

use Drupal\common\Exception\DataNodeLifeCycleEntityValidationException;
use Drupal\common\LoggerTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\metastore\MetastoreItemInterface;
use Drupal\node\Entity\Node;

/**
 * MetastoreItem object that wraps a data node, provides additional methods.
 */
class Data implements MetastoreItemInterface {
  use LoggerTrait;

  /**
   * Node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * Referenced raw metadata string.
   *
   * @var string
   */
  protected $rawMetadata;

  /**
   * Constructor.
   */
  public function __construct(EntityInterface $entity) {
    $this->validate($entity);
    $this->node = $entity;
  }

  public function getCacheContexts() {
    return $this->node->getCacheContexts();
  }

  public function getCacheTags() {
    $cacheTags = $this->node->getCacheTags();
    return $cacheTags;
  }

  public function getCacheMaxAge() {
    return $this->node->getCacheMaxAge();
  }

  /**
   * Private.
   */
  private function fix() {
    $this->fixDataType();
    $this->saveRawMetadata();
  }

  /**
   * Getter.
   */
  public function getModifiedDate() {
    $this->fix();
    return $this->node->getChangedTime();
  }

  /**
   * Getter.
   */
  public function getIdentifier() {
    $this->fix();

    return $this->node->uuid();
  }

  /**
   * The unaltered version of the metadata.
   */
  public function getRawMetadata() {
    $this->fix();
    if (isset($this->node->rawMetadata)) {
      return json_decode($this->node->rawMetadata);
    }
  }

  /**
   * Protected.
   */
  public function getDataType() {
    $this->fix();
    return $this->node->get('field_data_type')->value;
  }

  /**
   * Protected.
   */
  public function getMetaData() {
    $this->fix();
    return json_decode($this->node->get('field_json_metadata')->getString());
  }

  /**
   * Protected.
   */
  public function setMetadata($metadata) {
    $this->fix();
    $this->node->set('field_json_metadata', json_encode($metadata));
  }

  /**
   * Setter.
   */
  public function setIdentifier($identifier) {
    $this->fix();
    $this->node->set('uuid', $identifier);
  }

  /**
   * Setter.
   */
  public function setTitle($title) {
    $this->fix();
    $this->node->set('title', $title);
  }

  /**
   * Is New.
   */
  public function isNew() {
    return $this->node->isNew();
  }

  /**
   * Private.
   */
  private function validate(EntityInterface $entity) {
    if (!($entity instanceof Node)) {
      throw new DataNodeLifeCycleEntityValidationException("We only work with nodes.");
    }

    if ($entity->bundle() != "data") {
      throw new DataNodeLifeCycleEntityValidationException("We only work with data nodes.");
    }
  }

  /**
   * Private.
   */
  private function fixDataType() {
    if (empty($this->node->get('field_data_type')->getString())) {
      $this->node->set('field_data_type', 'dataset');
    }
  }

  /**
   * Protected.
   */
  public function getSchemaId() {
    $this->fix();
    $schemaId = $this->node->get('field_data_type')->getString();
    return $schemaId;
  }

  /**
   * Private.
   */
  private function saveRawMetadata() {
    // Temporarily save the raw json metadata, for later use.
    if (!isset($this->node->rawMetadata)) {
      $raw = $this->node->get('field_json_metadata')->value;
      $this->node->rawMetadata = $raw;
    }
  }

  /**
   * Getter.
   */
  public function getOriginal() {
    if (isset($this->node->original)) {
      return new Data($this->node->original);
    }
  }

}

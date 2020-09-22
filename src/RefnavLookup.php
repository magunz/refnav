<?php

namespace Drupal\refnav;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Handle parent node lookups.
 */
class RefnavLookup {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity Type Manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Look up to find incoming references to the current entity.
   *
   * Borrows ideas and concepts from
   * https://gist.github.com/grayside/a7b8aba74ccf36ff984b0b9499b3a188.
   */
  public function reverseLookup($entity, $entity_type, $field_name) {
    $field = FieldStorageConfig::loadByName($entity_type, $field_name);
    $target_id = $entity->id();
    $storage = $this->entityTypeManager->getStorage($field->getTargetEntityTypeId());

    $query = $storage->getQuery()->get($field->getTargetEntityTypeId(), 'AND')
      ->condition($field->getName(), $target_id)
      ->condition('status', 1)
      ->addTag('node_access');
    $ids = $query->execute();
    return $storage
      ->loadMultiple($ids);
  }

}

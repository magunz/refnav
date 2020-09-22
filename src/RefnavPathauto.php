<?php

namespace Drupal\refnav;

use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Handle Pathauto integration.
 */
class RefnavPathauto {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\pathauto\PathautoGeneratorInterface $generator
   *   Pathauto generator.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity Type Manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   Entity Field Manager.
   */
  public function __construct(PathautoGeneratorInterface $generator, EntityTypeManager $entity_type_manager, EntityFieldManager $entity_field_manager) {
    $this->generator = $generator;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Given a node, update all referrered children using pathauto functions.
   */
  public function updateChildAliases($entity) {
    $entity_type_id = $entity->getEntityTypeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if (!$entity_type->isSubclassOf(FieldableEntityInterface::class)) {
      // Not fieldable so nothing to do here.
      return;
    }
    $field_defs = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $entity->bundle());
    foreach ($field_defs as $fieldname => $field_def) {
      if ($field_def->getType() == 'entity_reference') {
        $field = $entity->$fieldname;
        foreach ($field as $delta) {
          $child = $delta->entity;
          if ($child) {
            $this->generator->createEntityAlias($child, 'update');
          }
        }
      }
    }
  }

}

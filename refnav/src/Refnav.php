<?php

namespace Drupal\refnav;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFieldManager;

class Refnav {
    /**
    *
    * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
    *   Entity Field Query.
    * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
    *   Entity Type Manager.
    * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
    *   Entity Field Manager.
    */
    public function __construct(QueryFactory $entity_query, EntityTypeManager $entity_type_manager, EntityFieldManager $entity_field_manager) {
        $this->entityQuery = $entity_query;
        $this->entityTypeManager = $entity_type_manager;
        $this->entityFieldManager = $entity_field_manager;
    }

    /**
    * Look up to find incoming (arbitrary parent) references to the current entity.
    * borrows ideas and concepts from https://gist.github.com/grayside/a7b8aba74ccf36ff984b0b9499b3a188
    *
    * @param $entity
    * @param $entity_type eg 'node'.
    * @param $field_name name of the incoming field.
    *
    * @return list of incoming reference objects 
    */
    function reverse_lookup($entity, $entity_type, $field_name) {
        $field = FieldStorageConfig::loadByName($entity_type, $field_name);
        $target_id = $entity->id();

        $query = $this->entityQuery->get($field->getTargetEntityTypeId(), 'AND')
            ->condition($field->getName(), $target_id)
            ->condition('status', 1)
            ->addTag('node_access');
        $ids = $query->execute();
        return $this->entityTypeManager->getStorage($field->getTargetEntityTypeId())
            ->loadMultiple($ids);
    }
}
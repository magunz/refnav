<?php

namespace Drupal\refnav;

use Drupal\field\Entity\FieldStorageConfig;

class Refnav {
    /**
    * Look up to find incoming references to the current entity.
    *
    * @param $entity
    * @param $entity_type eg 'node'.
    * @param $field_name name of the incoming field.
    *
    * @return list of descriptions of incoming reference objects (not fully loaded).
    *   keys include [entity_type, bundle, entity_id, revision_id, delta]
    */
    static function refnav_reverse_lookup($entity, $entity_type, $field_name) {
        $incoming_references = [];
        $field = FieldStorageConfig::loadByName($entity_type, $field_name);
        // There is sure to be an API way to do this, but?
        // Use parts of the API to find the DB table that I should be looking into.
        // Gah - get views to do it or what?
        foreach ($field->bundles as $entity_type => $bundles) {
            $target_entity_info = Drupal::entityTypeManager()->getDefinition($field['settings']['target_type']);
            if (isset($target_entity_info['base table'])) {
                // The parent/referring thing is a:
                $entity_info = Drupal::entityTypeManager()->getDefinition($entity_type);
                // The child/referred thing is a:
                $target_entity = $target_entity_info['label'];
                // eg 'nid';
                $target_id_key = $target_entity_info['entity keys']['id'];
                $target_id = $entity->$target_id_key;

                // Figured this stuff out by inspecting entityreference_field_views_data()
                // TODO - join to the base field and find the appropriate revisions!
                $base = $entity_info['base table'];
                $base_field = $entity_info['entity keys']['id'];

                $field_data_table = _field_sql_storage_tablename($field);
                $target_id_column = $field['field_name'] . '_target_id';
                $result = \Drupal::database()->select($field_data_table, 'ref')
                    ->fields('ref', ['entity_type', 'bundle', 'entity_id', 'revision_id', 'delta'])
                    ->condition($target_id_column, [$target_id])
                    ->execute();
                if (!empty($result)) {
                    foreach ($result as $record) {
                        $incoming_references[] = $record;
                    }
                }
            }
        }
        return $incoming_references;
    }
}
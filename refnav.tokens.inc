<?php

/**
 * @file
 * Token callbacks for the refnav module.
 */

/**
 * Implements hook_token_info().
 */
function refnav_token_info() {
  // Build our own compound field type, as the values we look for
  // are selected by field name
  $type = array(
    'name' => t('Incoming references'),
    'description' => t('Tokens from reverse entityreferences.'),
    'needs-data' => 'incoming_references',
  );
  $incoming_references = array();

  // For each entityreference in the system, advertise a new token branch.
  $entityreference_fields = get_fields_by_type('entityreference');
  foreach ($entityreference_fields as $field_id => $field_def) {
    // Figure what type of entity has this field. Almost always 'node'.
    $entity_types = array_keys($field_def['bundles']);
    $entity_type = reset($entity_types);
    // THis will define what sort of stuff is available when expanding
    // the deeper token chain.
    $incoming_references[$field_id] = array(
      'name' => $field_def['field_name'],
      'description' => t('Entity referencing this one through field %field_label', array('%field_label' => $field_def['field_name'])),
      'dynamic' => TRUE,
      'type' => $entity_type,
    );
  }

  $node = array(
    'incoming_references' => array(
      'name' => t('Incoming references'),
      'description' => t("Entities that reference this one. (If there are multiple references, [?] is the iteration, starting with 0)"),
      'type' => 'incoming_references',
    ),
  );
  $user = array(
    'incoming_references' => array(
      'name' => t('Incoming references'),
      'description' => t("Entities that reference this one. (If there are multiple references, [?] is the iteration, starting with 0)"),
      'type' => 'incoming_references',
    ),
  );

  return array(
    'types' => array(
      'incoming_references' => $type,
    ),
    'tokens' => array(
      'incoming_references' => $incoming_references,
      'user' => $user,
      'node' => $node,
    ),
  );

  return $info;
}

/**
 * Implements hook_tokens().
 *
 * A token that pulls in the title of the first referring page could look like.
 * [node:incoming_references:field_link_to_children:0:title]
 * as [node:incoming_references:field_link_to_children:0] resolves to the linked
 * referring node.
 * [node:incoming_references:field_link_to_children:0:url:path]
 */
function refnav_tokens($type, $tokens, array$data = array(), array$options = array()) {
  $replacements = array();
  if ($type == 'entity' && ($refnav_tokens = token_find_with_prefix($tokens, 'incoming_references'))) {
    // Just pass the whole data context through to the lookup.
    // All the work is done in there.
    $replacements += token_generate('incoming_references', $refnav_tokens, $data, $options);
  }
  elseif ($type == 'incoming_references' && isset($data['entity'])) {
    // An entity has asked for us to generate tokens for incoming refs.
    // The remaining token chain will actually identify a token deeper within
    // the structure of the referred entity, such as pulling back its title
    // or url.
    // We fetch our data, then get the thing we fetched to resolve its own
    // tokens for us.

    $token_keys = array_keys($tokens);
    $field_name = reset($token_keys);
    // Did something change? the field name is still messy with suffixes.
    list($field_name) = explode(':', $field_name);

    // Find the incoming entities, then ask THEM for their values.
    // We already know the delta we want, so may as well get the lookup to sort
    // that out for us.?
    $referees = refnav_reverse_lookup($data['entity'], $data['entity_type'], $field_name);
    // $referees will be a 0-indexed list
    // We expect to be given a numeric index to look for..

    // Remove the current prefix from the list of tokens. Localize it.
    $localized_tokens = token_find_with_prefix($tokens, $field_name);

    // Looks like I need to deal with array deltas myself?
    // Token should have something built in for this but I can't see how to
    // trigger it. Token API has traces of 'first' 'last' keys etc?

    // In our uses, this is almost always 0, but lets try to support deltas.
    foreach ($localized_tokens as $local_token => $original) {
      list($delta, $inner_token) = explode(':', $local_token, 2);
      if (is_numeric($delta) && isset($referees[$delta])) {
        // Found the indexed entity!
        $referrer_info = $referees[$delta];
        // Bloody multiples from entity_load() are boring.
        $loaded_entities = entity_load($referrer_info->entity_type, array($referrer_info->entity_id));
        $loaded_entity = reset($loaded_entities);

        // We know the referrers $entity_type to generate deeper tokens for now.
        $entity_type = $referrer_info->entity_type;
        // This is almost always just $entity_type='node', but don't assume.
        // Build a data array that either node_tokens or entity_tokens can use
        // to do their thing.
        $deeper_data = array(
          'entity_type' => $entity_type,
          'entity' => $loaded_entity,
          $entity_type => $loaded_entity,
          'token_type' => $entity_type,
        );
        // Get each of these higher entities to generate whatever tokens we ask of them.
        $deeper_tokens = token_find_with_prefix($localized_tokens, $delta);
        $replacements += token_generate($entity_type, $deeper_tokens, $deeper_data, $options);
      }
    }
  }
  return $replacements;
}


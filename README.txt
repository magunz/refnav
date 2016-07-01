Referential Navigation
======================

Forked from https://www.drupal.org/sandbox/dman/2209055; see discussion on https://www.drupal.org/node/1815276

Construct pathauto and breadcrumb rules for structures that are held together 
with entityreference links.
For those that don't like 'menus' or 'books' and their heirarchical structure.

Assuming a site structure where one content type (Publication Series) may
refer to another content type (Publication Issue) and that may refer to another
(Publication Article). 
The URL patterns need to reflect the heirarchy, even though each layer is
not a menu or anything. To achive this, a pathauto token of '[referers-path]'
witll return the PATH OF THE ENTITY THAT LINKS TO THIS ONE.
Effectively the 'parent' node.
Chaining these together should produce a meaningful URL heirearchy.

Additional side effects are triggered such that :
* whenever a parent item is renamed, the paths of each of its children are 
  rebuilt
* whenever a parent item is published, each child item is published. 

Originally built for NIWA on Drupal6 with a lot of customizations, refactored 
in D7 to be less ad-hoc.

Behavior
--------

Incoming reference Token
------------------------

This makes a new token tree available called "incoming_references". it wull be
of the form [node:incoming_references:field_child_pages:0:*]

Underneath 'incoming_references:' will be each fieldname for any entityreference
that may refer *to* the current node context.
The next part of the token path is a numeric index, as there may be many 
incoming references and you may just want the first.
(sort order is undetermined)
At that point you can access the token tree of the entity type that may be 
found by that lookup, eg 'title' for a referring node.

Edit propogation
----------------

Editing a parent may be expected to produce effects on the children.

Usage
-----

Given a content type that links to 'child' pages via an entityreference field
called 'child_pages', 
The pathauto rules for child pages may be set as
  [node:incoming_references:field_child_pages:0:url:path]/[node:title]
to use the first parents path as a base.

Notes
-----

* This does not use or require entity_tokens.module, though it should not clash.

* The database lookup fo rthe reverse relationship may need to be improved
  as it doesn't use the full field API. 
  An alternative is adding views as a dependency and just getting views to run
  the lookup for us.
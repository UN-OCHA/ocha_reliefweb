ReliefWeb integration
=====================

This module provides integration with ReliefWeb via its API.

Uninstalling
------------

To be able to uninstall this module you need to

1. Delete completely the stored reliefweb resource entities at `/admin/modules/uninstall/entity/reliefweb_resource`
2. Disable the `Ocha ReliefWeb Editor` text format at `/admin/config/content/formats`

References:

- https://www.drupal.org/project/drupal/issues/2348925

TODO
----

- [ ] Generate forms based on the JSON API specs (check if there is some modules to do that).

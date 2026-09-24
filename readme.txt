=== Perxel Toolkit ===
Contributors: phucbm
Tags: admin, block editor, featured image, disable comments, image sizes
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Small admin and block-editor features plus integrations for popular plugins - each one off until you turn it on.

== Description ==

Perxel Toolkit bundles a set of small admin and block-editor features that
many sites end up adding by hand, plus a few integrations for popular plugins,
behind one settings screen at **Tools -> Perxel Toolkit**.

Every module is **off after activation**. Nothing on your site changes until
you switch a module on, and switching it off again stops it (Media Sizes
leaves its Settings -> Media values in place - see the FAQ).

**Features**

* Editor Restrictions - hide block locking, the code editor and unfiltered HTML in the block editor for the roles you choose (administrators are never restricted)
* Featured Image Column - a featured-image column on the post list; click it to set or change the image
* Featured Posts - a "Featured" checkbox on posts (edit screen and Quick Edit), stored as post meta you can query
* Disable Comments - close and hide comments site-wide and remove the related admin UI (existing comments are kept, not deleted)
* Media Sizes - a larger default image-size ladder, with every other registered size removed

**Access control**

* Admin Page Guard - hide selected admin pages from the menu and redirect everyone except the users you allow, with a "View as not-allowed user" preview

**Integrations** (listed once the plugin they extend is active)

* Gravity Forms - render the same form more than once on a page
* Advanced Custom Fields / Secure Custom Fields - save field-group JSON next to each block, or to per-location files in the theme
* Nectar Blocks - hide the default core blocks from the inserter

A **Recommended Plugins** screen lists plugins that pair well with the
toolkit, with WordPress's own install button for those hosted on
WordPress.org and a plain link for the rest. Nothing is installed unless you
click install.

Developers can fine-tune most modules through `pxtk_*` filters documented in
the source.

== External services ==

This plugin does not connect to any external services.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/perxel-toolkit`, or install it from the Plugins screen.
2. Activate it.
3. Go to **Tools -> Perxel Toolkit** and turn on the modules you want.

== Frequently Asked Questions ==

= Does activating the plugin change my site? =

No. Every module starts off. Each one only takes effect while it is switched on.

= Why is an integration missing from the settings screen? =

An integration only appears once the plugin it extends is active. The
Recommended Plugins screen lists every supported plugin.

= Admin Page Guard is on but nothing is restricted. =

Add at least one user to "Allowed users". While that list is empty the guard
restricts nothing, so it can never lock every administrator out.

= Does Media Sizes regenerate my existing images? =

No. While it is on it owns the Settings -> Media image sizes (changes made
there are overwritten) and removes sizes other plugins register, such as
WooCommerce's - use the `pxtk_media_sizes_keep` filter to keep one. It changes the sizes generated for new uploads and updates the Settings ->
Media values for the core sizes (thumbnail, medium, medium_large, large). Use a thumbnail
regeneration plugin to rebuild existing images. Turning the module off does
not restore the previous Settings -> Media values.

= What happens to my data if I delete the plugin? =

Deleting the plugin removes its settings option. Content it created in your
posts or media library, such as the Featured Posts flag, is left untouched.

== Screenshots ==

1. Settings: turn features and integrations on or off, with per-feature options.
2. Recommended Plugins: the plugins Perxel recommends for every project, with one-click install.
3. Admin Page Guard: restrict selected admin pages to allowed users, with a "view as" preview.

== Changelog ==

= 0.0.7 =
* Every module is now off by default on a fresh install; existing sites keep their saved on/off state.
* Admin Page Guard: no default allowed user, and the guard restricts nothing while the allowed-users list is empty, so it can never lock every administrator out.
* Settings forms read only their own fields from the request instead of the whole `$_POST`.
* ACF/SCF: the field-group data used to pick a save path is sanitised on read.
* Featured Posts: Quick Edit no longer writes the `_featured` flag on post types that don't use it (pages, products, ...).
* Nectar Blocks: core blocks are only hidden in the post editor, not in the Site Editor or widget editors, where block themes need them.

= 0.0.6 =
* Featured Posts: the Posts list CSS and Quick Edit script now load as enqueued files instead of inline `<style>` / `<script>` tags. No behaviour change.

= 0.0.5 =
* Admin screens now escape their output at the point of output with `wp_kses()` (shared UI kit 0.23.0), with no escaping suppressions left. No behaviour change.

= 0.0.4 =
* Add Disable Comments - turns off commenting site-wide (closed, hidden, not deleted) and strips the related admin UI (menu, dashboard widget, admin bar, list-table column, widget).
* Add Media Sizes - replaces WordPress's small default image sizes with a larger ladder and removes every other registered size (core's extras, plugins', the theme's), with filters for per-project overrides.

= 0.0.3 =
* Admin Page Guard: "Allowed users" is now a checkbox list of your site's actual users instead of free text.
* Admin Page Guard: "Restricted pages" is now built-in defaults you can toggle individually, plus a separate free-text list for custom URLs.
* Admin Page Guard: a "View as not-allowed user" button next to Save changes previews the effect without switching accounts - a stateless URL flag, nothing persisted.
* Fix: Admin Page Guard's menu-hiding didn't recognise bare top-level pages such as Plugins.
* Fix: saving a checkbox-list field could silently drop an option whose value contained a percent-encoded character (e.g. Akeeba Backup's default restricted page).

= 0.0.2 =
* Add a Recommended Plugins section to the settings screen - Perxel's curated per-project plugin list, with a one-click install for anything on wordpress.org.
* ACF/SCF integration now suggests installing Secure Custom Fields (the free official successor) instead of the original Advanced Custom Fields plugin.
* Add per-module settings fields (toggle, roles, free-text list), starting with Editor Restrictions.
* Move Admin Page Guard into its own "Access Control" group with a dedicated settings screen for allowed users and restricted pages, including a "View as" preview.

= 0.0.1 =
* First release.

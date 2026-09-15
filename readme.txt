=== Perxel Toolkit ===
Contributors: phucbm
Tags: tag-one, tag-two
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Toggleable admin/editor features and third-party plugin integrations shared across Perxel projects.

== Description ==

Perxel Toolkit bundles the small admin/editor features and plugin
integrations we re-add on every project (editor restrictions, admin page
guarding, featured images/posts, Gravity Forms and ACF/SCF tweaks, Nectar
Blocks visibility) into one plugin with a single settings screen, so updating
them across client sites is a plugin update instead of a file diff.

**Key features**

* Editor Restrictions - lock down block-editor capabilities for non-admin roles
* Admin Page Guard - restrict selected admin pages to allowed users
* Featured Image Column - featured-image column with quick-edit on the post list
* Featured Posts - a featured checkbox on posts with native query support
* Gravity Forms integration - run multiple instances of the same form per page
* ACF/SCF integration - route field-group JSON to per-block/per-location paths
* Nectar Blocks integration - hide default core blocks when Nectar Blocks is active

== External services ==

<!--
Delete this whole section if the plugin does not contact any third-party
service. If it does, WordPress.org requires you to list every service, what
data is sent, when, and links to that service's terms and privacy policy.
-->

This plugin does not connect to any external services.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/perxel-toolkit`, or install it from the Plugins screen.
2. Activate it.
3. Go to **Tools -> Perxel Toolkit** to configure it.

== Frequently Asked Questions ==

= A question people actually ask? =

The answer.

= What happens to my data if I delete the plugin? =

Deleting the plugin removes its settings option. Content it created in your
posts or media library is left untouched.

== Screenshots ==

1. The Settings screen.

== Changelog ==

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

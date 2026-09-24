# Changelog

All notable changes to this plugin are documented here. This file mirrors the
`== Changelog ==` section of `readme.txt` (keep the two in sync).

## 0.0.7

* Every module is now off by default on a fresh install; existing sites keep their saved on/off state.
* Admin Page Guard: no default allowed user, and the guard restricts nothing while the allowed-users list is empty, so it can never lock every administrator out.
* Settings forms read only their own fields from the request instead of the whole `$_POST`.
* ACF/SCF: the field-group data used to pick a save path is sanitised on read.
* The hidden UI-kit showcase is now opt-in with `define( 'PXTK_UI_SHOWCASE', true );` instead of a hard-coded user check.

## 0.0.6

* Featured Posts: the Posts list CSS and Quick Edit script now load as enqueued files instead of inline `<style>` / `<script>` tags. No behaviour change.

## 0.0.5

* Admin screens now escape their output at the point of output with `wp_kses()` (shared UI kit 0.23.0), with no escaping suppressions left. No behaviour change.

## 0.0.4

* Add Disable Comments - turns off commenting site-wide (closed, hidden, not deleted) and strips the related admin UI (menu, dashboard widget, admin bar, list-table column, widget).
* Add Media Sizes - replaces WordPress's small default image sizes with a larger ladder and removes every other registered size (core's extras, plugins', the theme's), with filters for per-project overrides.

## 0.0.3

* Admin Page Guard: "Allowed users" is now a checkbox list of your site's actual users instead of free text.
* Admin Page Guard: "Restricted pages" is now built-in defaults you can toggle individually, plus a separate free-text list for custom URLs.
* Admin Page Guard: a "View as not-allowed user" button next to Save changes previews the effect without switching accounts - a stateless URL flag, nothing persisted.
* Fix: Admin Page Guard's menu-hiding didn't recognise bare top-level pages such as Plugins.
* Fix: saving a checkbox-list field could silently drop an option whose value contained a percent-encoded character (e.g. Akeeba Backup's default restricted page).

## 0.0.2

* Add a Recommended Plugins section to the settings screen - Perxel's curated per-project plugin list, with a one-click install for anything on wordpress.org.
* ACF/SCF integration now suggests installing Secure Custom Fields (the free official successor) instead of the original Advanced Custom Fields plugin.
* Add per-module settings fields (toggle, roles, free-text list), starting with Editor Restrictions.
* Move Admin Page Guard into its own "Access Control" group with a dedicated settings screen for allowed users and restricted pages, including a "View as" preview.

## 0.0.1

* First release.

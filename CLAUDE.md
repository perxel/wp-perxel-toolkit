# CLAUDE.md

Guidance for working on this repository. This is the **only** agent/maintainer
document (see "Documentation rules" below).

## What this is

`perxel-toolkit` - a **public** WordPress plugin (repo
`github.com/perxel/wp-perxel-toolkit`, WordPress.org slug `perxel-toolkit`,
published under the `phucbm` .org account, branded Perxel).

It was scaffolded from
[`perxel/wp-plugin-starter`](https://github.com/perxel/wp-plugin-starter) and
extended with a module system (see "Modules" below) - the toolkit bundles
several small admin/editor features and plugin integrations behind one
settings screen instead of shipping them as separate mu-plugins per client.

**Upstream rule:** the starter is the source of truth for shared process - CI,
release/deploy, WordPress.org compliance rules, `.distignore`, build scripts, and
the "Releasing" and "Compliance" sections of this file. If you improve or fix one of
those while working here, make the same change in the starter too (or tell the
maintainer), so the next plugin inherits it. Plugin-specific code and listing art
stay here.

## Documentation rules

Every Perxel plugin follows these; they are owned by the starter.

- **`README.md` is public-facing only**: what the plugin does, screenshots,
  install, requirements, what data it stores / external services, license. No
  architecture, folder layout, build/lint/release steps, or "how to extend" -
  none of that belongs on the public page.
- **`CLAUDE.md` is the one and only file for developers and agents**:
  architecture, conventions, compliance, releasing. There is **no `AGENTS.md`**
  (and no second "playbook" file) - do not recreate it or duplicate content
  across the two. Claude Code reads `CLAUDE.md`; other agents can be pointed at it.
- `readme.txt` is the WordPress.org listing, `CHANGELOG.md` (optional) the
  changelog. Neither carries developer guidance.
- Master/source art for `.wordpress-org/` lives in `.claude/assets-src/`.
- `.env.local` holds credentials: never commit it (it is in `.gitignore`).
- `bin/*.sh` derive the slug from the main plugin file, so they are byte-identical
  across plugins - never hard-code a slug in them. Per-plugin Plugin Check
  suppressions go in `lint.yml` -> `ignore-codes`.
- `languages/` is optional; `.org` auto-loads translations.

## Layout

```
perxel-toolkit.php      Main file: header, constants, autoloader, UI-kit loader, boot
uninstall.php               Deletes the option (and any custom tables) on delete
includes/*.php              One PSR-4-ish class per concern, namespace Perxel_Toolkit\
includes/Modules/*.php      One class per module (Module, Registry, and each feature/integration)
includes/views/*.php        Dumb admin templates, fed vars by the screen classes
assets/css, assets/js       Admin-only CSS/JS (plugin-specific; layout comes from the kit)
vendor/perxel-ui/           Shared admin-UI kit - vendored, see below
languages/                  .pot template
readme.txt                  WordPress.org listing (keep in sync with README.md + version)
README.md                   Public-facing GitHub page only (see "Documentation rules")
.wordpress-org/             Listing assets (icon, banner, screenshots) - not shipped
.github/workflows/          lint.yml (PHPCS + Plugin Check), release.yml
bin/                        build-zip.sh, update-ui.sh - identical in every plugin
.claude/assets-src/         Master/source art for the listing assets - committed, not shipped
```

`includes/` is loaded by the `spl_autoload_register` in the main file (not
Composer). `Plugin::instance()->boot()` runs on `plugins_loaded` and wires
`Admin`.

## Architecture

- **`Plugin`** - singleton. `boot()` wires the admin surface; `activate()` is
  the activation hook (seed options, create tables).
- **`Admin`** - owns the menu (one `Tools ->` screen), the shared layout args,
  asset loading, and the plain-form / `admin-post` handlers. Each screen is a
  `render_*()` method + a view under `includes/views/`; heavier per-screen logic
  goes in its own class.
- **`Settings`** - the one option (`PXTK_OPTION_KEY`), read through typed
  accessors, written through `update()` / `sanitize()`. Never call
  `get_option()` for it directly elsewhere.

## Modules

Everything the toolkit actually *does* lives under `includes/Modules/` as a
class extending `Modules\Module`, listed in `Modules\Registry::MODULES`.
`Registry::boot()` (called from `Plugin::boot()`) instantiates and calls
`register()` on every module that is both enabled in `Settings` and
`is_available()`.

- **Feature module** (`group()` returns `'feature'`) - self-contained, no
  third-party dependency, always available. E.g. `Editor_Restrictions`,
  `Featured_Image_Column`, `Featured_Posts`, `Disable_Comments`, `Media_Sizes`.
- **Integration module** (`group()` returns `'integration'`) - config for a
  specific third-party plugin or theme convention. Overrides `dependency()`
  with a `label`, a `check` callable, and either `wporg_slug` (renders a
  real one-click "Install Now" button on the settings screen) or
  `install_url` (a plain link - use this when the dependency isn't on
  wordpress.org, e.g. Gravity Forms). `is_available()` calls `check()`; an
  unavailable integration is left off the settings screen entirely, and the
  Recommended Plugins screen (`Recommended_Plugins`) lists every supported
  plugin with its install action. E.g. `Gravity_Forms`, `Acf`, `Nectarblocks`.
- **Security module** (`group()` returns `'security'`) - restricts or gates
  wp-admin access, shown under "Access Control". E.g. `Admin_Page_Guard`.

Every module defaults to **off** (`Settings::default_modules()`): a fresh
install changes nothing until the site owner opts in. Keep it that way for new
modules - this is a public plugin, and a module that changes site behaviour on
activation is a support (and review) problem. Likewise never ship a default
that names a specific user (Admin Page Guard's allow-list starts empty and the
guard is inert while it is).

**Adding a module:** add `includes/Modules/<Name>.php` extending
`Modules\Module` (`slug()`, `label()`, `description()`, `group()`, `register()`;
an integration also overrides `dependency()`), then add the class to
`Modules\Registry::MODULES`. The settings screen, availability detection and
enable/disable persistence follow from that - no other wiring.

A module can declare `settings_fields()` (types `toggle`, `roles`, `users`,
`checkbox_group`, `list`); they render in a "Configure" disclosure on its
settings row, or on a dedicated screen when `settings_page()` returns one
(Admin Page Guard). Per-project overrides beyond those fields are WordPress
filters, documented in each class's docblock. Form handlers read only the
`modules` / `module_settings` keys from `$_POST` (`Admin::posted_settings()`),
never the whole array.

`Settings::sanitize()` skips reading a module's checkbox from `$_POST` when
`is_available()` is false - a disabled `<input>` is never submitted by the
browser, so treating a missing field as "off" would silently disable a
module the moment its dependency goes away. The stored value is preserved
instead.

### Custom tables

The template ships none. When you add them:

- One `includes/Db.php` for schema (`dbDelta` on `Plugin::activate()`, a stored
  `pxtk_db_version` option, `Db::maybe_upgrade()` on `init`), and one repository
  class that is the *only* code touching the tables.
- **Bind the table name with the `%i` placeholder - never concatenate it.**
  `%i` needs WP 6.2+ (the template's floor is already 6.5).

  ```php
  // Right:
  $wpdb->get_results(
      $wpdb->prepare( 'SELECT * FROM %i WHERE run_id = %d', Db::items(), $run_id ),
      ARRAY_A
  );
  // Wrong - WordPress.DB.PreparedSQL.NotPrepared (error-level, blocks .org):
  $wpdb->get_results( "SELECT * FROM {$table} WHERE run_id = {$run_id}" );
  ```

- A `SELECT` with only the table (no other args) still goes through
  `prepare()`: `$wpdb->prepare( 'SELECT COUNT(*) FROM %i', Db::runs() )`.
- `DROP TABLE`: `$wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table )`.
- Direct `$wpdb` on your own table is expected; annotate the call:
  `// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- <reason>`.
  `dbDelta()` CREATE and uninstall DROP add `WordPress.DB.DirectDatabaseQuery.SchemaChange`.
- A dynamic `IN (...)` list is the one case with no clean placeholder: build it
  with `implode( ', ', array_fill( 0, count( $ids ), '%d' ) )` and wrap that one
  statement in `// phpcs:disable ... WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber` / `// phpcs:enable`.
- Uncomment the `WordPress.DB.DirectDatabaseQuery` block in `phpcs.xml.dist`.
- Don't put a bare SQL keyword like `'create'` as a column *value* inside a
  `$wpdb->insert()`/`update()` call - PHPCS reads it as DDL. Assign it to a
  variable first.

## Conventions

- **Namespace** `Perxel_Toolkit\` - the slug (`perxel-toolkit`) in
  `Ucfirst_Snake` form, so `WordPress.NamingConventions.PrefixAllGlobals`
  accepts it as the plugin prefix (Plugin Check does not read `phpcs.xml.dist`,
  so a `Vendor\Package`-style namespace would be flagged there). Sub-namespaces
  are fine (`Perxel_Toolkit\Admin\Foo` -> `includes/Admin/Foo.php`). Hooks,
  option keys and CSS classes stay `pxtk_` / `pxtk-`; constants `PXTK_`. Product
  name is the constant `PXTK_NAME` (no rebrand option).
- **Text domain** `perxel-toolkit` (= the slug). JS i18n via `wp.i18n`
  (`wp_set_script_translations`); script deps include `wp-i18n`.
- **Escape at output, no blanket suppressions.** Never `phpcs:disable` a
  `WordPress.Security.*` sniff (EscapeOutput, NonceVerification) for a file or
  block - the WordPress.org review bot flags it as an escaping/nonce failure
  even when every value is escaped. **Escape late**: kit markup is echoed
  through `Admin::kit( \Perxel_UI::rows( ... ) )`, i.e.
  `echo wp_kses( $html, \Perxel_UI::allowed_html() )`; any other built HTML gets
  its own `wp_kses( $html, <narrow allowlist> )`. Never an `EscapeOutput`
  suppression, not even per line - the 2026-09-23 review of perxel-ai-translate
  rejected `echo $html; // phpcs:ignore ... escaped earlier`. No inline `on*`
  handlers in kit markup (kses strips them) - wire them in JS. Read-only
  `$_GET` flags get a per-line `NonceVerification.Recommended` ignore.
  `composer run lint` runs `bin/check-suppressions.sh`, which fails on blanket
  `WordPress.Security` disables and on any `EscapeOutput` suppression.
- Admin screens render inside `Perxel_UI_Layout::open()/close()` via
  `Admin::screen()`, which falls back to a plain notice if the kit is not
  vendored. Use the kit components (`rows()`, `notice()`, `toggle()`, `code()`,
  `meter()`, `progress_bar()`, `checkbox_group()`, `card()`) rather than
  hand-rolled markup. A bare `<input type="checkbox">` renders as a square box;
  the iOS switch is `Perxel_UI::toggle()` / the `.pxui-toggle` class. Figures
  (counts, totals) are a `rows()` group - label left, value as `content` right,
  `sub` for the qualifier, `tone` for good/warn/bad.
- Forms that can lose unsaved edits carry `data-pxui-dirty-guard` (kit >=
  0.20.0).

## The `vendor/perxel-ui/` kit

Standalone repo [`perxel/wp-plugin-ui`](https://github.com/perxel/wp-plugin-ui),
vendored via `bin/update-ui.sh <version>` (curl a tagged tarball into
`vendor/perxel-ui/`, Action Scheduler style - no Composer). Committed;
`.gitignore` keeps it out of the general `vendor/` ignore, `.distignore` strips
only its dev-only `showcase/`. Overwriting it can never change plugin behaviour
- the `loader.php` "highest version wins" negotiation picks the newest copy
across every active plugin, and a second copy is inert.

The version passed to `Perxel_UI_Loader::register()` in the main file **and** the
`vendor/perxel-ui/` contents must match the tag you vendored. Update both when
you run `bin/update-ui.sh`.

We host the kit's component showcase as a hidden maintainer-only screen
(`PERXEL_UI_SHOWCASE_HOSTED` + `Admin::can_see_showcase()`), so its own Tools
page is suppressed. It is opt-in per site with
`define( 'PXTK_UI_SHOWCASE', true );` in `wp-config.php` - no hard-coded user
check - and the release zip strips `showcase/` anyway.

## Before committing

```bash
php -l <changed files>
composer run lint          # check-suppressions.sh + phpcs - must stay green
composer run build         # bin/build-zip.sh - installable zip in dist/
```

`phpcs.xml.dist` curates the base `WordPress` standard: a terse-docblock house
style, and `PrefixAllGlobals` is told about both the plugin prefix and the
kit's (`perxel_ui` / `PERXEL_UI` / `Perxel_UI` / `pxui`). CI also runs the
official **Plugin Check** action against the built zip (not the raw checkout).

There are no automated tests and no WP in the lint environment - `phpcs` and
`php -l` verify syntax and style only. Behaviour must be smoke-tested on a real
WordPress site.

## WordPress.org / Plugin Check compliance

Rules that are not obvious and cost real time when re-derived per plugin:

| Rule | Why |
|---|---|
| Namespace root = slug in `Ucfirst_Snake` (`Perxel_Toolkit`) | `PrefixAllGlobals` accepts it as the prefix; a `Vendor\Package` namespace is flagged (`NonPrefixedNamespaceFound`) and Plugin Check ignores the `phpcs.xml.dist` prefix list |
| Custom-table names via `%i`, never string-concatenated | `WordPress.DB.PreparedSQL.NotPrepared` is **error-level** and blocks .org (see "Custom tables") |
| No `load_plugin_textdomain()` | .org auto-loads translations (slug == text domain); calling it on `plugins_loaded` is "too early" on WP 6.7+ |
| Prefix any variable you **assign** in a view (`$pxtk_url`); vars passed in via `extract()` are fine | `NonPrefixedVariableFound` fires on template-scope assignments |
| No `phpcs:disable WordPress.Security.*` anywhere in `includes/` or the main file, and no `EscapeOutput` suppression at all: escape late via `Admin::kit()` (`wp_kses` + `Perxel_UI::allowed_html()`) or `wp_kses()` with a narrow allowlist | Reviewers flag file-wide security disables (perxel-image-optimizer, perxel-ai-translate 2026-09-22) and per-line "escaped earlier" echoes (perxel-ai-translate 2026-09-23); `bin/check-suppressions.sh` enforces both |
| `set_time_limit()` etc.: `function_exists()` guard + inline `// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- <reason>` | discouraged-function warning |
| Calling another plugin's hooks (WPML `wpml_*`, WooCommerce): scope a `phpcs.xml.dist` exclude to the wrapper file **and** add the code to `lint.yml` -> `ignore-codes` | `NonPrefixedHooknameFound`; the two tools don't share config |
| No `'suppress_filters' => true` (Plugin Check **error**); `get_posts()` already defaults to it; for WPML use `do_action( 'wpml_switch_language', 'all' )` and restore | error-level `WordPressVIPMinimum...SuppressFilters_suppress_filters` |
| A MySQL `GET_LOCK` result must be checked; skip the guarded work when it isn't `1` | reviewer flagged an ignored lock result as a race condition |

The split that bites: **Plugin Check runs its own ruleset, not `phpcs.xml.dist`.**
Any suppression for a documented false positive goes in *both* places -
`phpcs.xml.dist` (for `composer run lint`) and `lint.yml` -> `ignore-codes`.

## Releasing

1. Bump the version in `perxel-toolkit.php` (header + `PXTK_VERSION`) and
   `readme.txt` (`Stable tag`); add a changelog entry to both `readme.txt` and
   `CHANGELOG.md`. Merge to `main` first. Tag, plugin `Version:` and `Stable tag`
   must all be equal or the deploy fails before touching SVN.
2. Create the tag on `main` and publish a GitHub Release. `release.yml`'s `zip`
   job attaches `perxel-toolkit.zip`; the `deploy` job commits trunk +
   `tags/<version>` + `.wordpress-org/` (-> SVN `assets/`) with the SHA-pinned
   10up action. It only runs when the repo variable `DEPLOY_TO_WPORG` is `true`.
3. Verify `https://wordpress.org/plugins/<slug>/` and
   `https://api.wordpress.org/plugins/info/1.0/<slug>.json` show the new version.
   Assets can 404 on `ps.w.org` for a while after the first commit (CDN lag).

### First release of a new plugin (the only manual bit is the review)

1. Upload `dist/<slug>.zip` at <https://wordpress.org/plugins/developers/add/>.
   No SVN repo exists until the review team approves it.
2. Secrets `SVN_USERNAME` / `SVN_PASSWORD`: set once as **org** secrets and grant
   this repo access (org -> Settings -> Secrets -> Repository access). Use an
   SVN-specific password if the wordpress.org profile offers one. Never paste it
   in chat or commit it.
3. Once approved: set the repo variable `DEPLOY_TO_WPORG=true`, run **Actions ->
   Release -> Run workflow** with the tag and `dry_run` on (default) to check the
   staging without committing, then publish the Release. The very first version
   deploys the same way as every later one - no manual SVN commit.
4. If automation ever breaks, plain `svn` works: check out
   `https://plugins.svn.wordpress.org/<slug>`, copy the `.distignore`-filtered
   build into `trunk/`, `.wordpress-org/*` into `assets/`, `svn cp trunk
   tags/<version>`, `svn ci`.

Notes: a large first commit (hundreds of vendored files) sits on "Committing
transaction..." for minutes - normal. The action strips the `v` from a `vX.Y.Z`
tag itself; on a manual run it can't, hence the explicit `VERSION`. Do not bump
versions, tag or publish releases without the maintainer asking.

Build artifacts (`dist/`) are never committed.

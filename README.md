# wp-plugin-starter

A GitHub **template repository** for a Perxel WordPress plugin. It ships the
house layout every Perxel plugin shares - a namespaced, autoloaded codebase, the
[`perxel/wp-plugin-ui`](https://github.com/perxel/wp-plugin-ui) admin kit wired
in, PHPCS + WordPress Plugin Check in CI, and a release workflow that builds the
installable zip and deploys to WordPress.org.

Out of the box it is a working plugin: activate it and **Tools -> Perxel
Example** shows a Settings screen (a text field + a toggle, saved through
`admin-post`) rendered in the shared UI layout, plus a hidden maintainer-only
"Perxel UI" component showcase.

## Creating a new plugin from it

### 1. Use this template

On GitHub: **Use this template -> Create a new repository**, named
`wp-<something>` under `perxel/`. Clone it.

### 2. Replace the placeholder tokens

There is no build step - personalising is a find-and-replace across the tree.
Every placeholder is one of these six tokens. Replace them **case-sensitively**,
`Perxel_Example` before `Perxel Example`:

| Token | What it is | Example value |
|---|---|---|
| `Perxel_Example` | PHP namespace root **and** `@package` tag - keep it `Ucfirst_Snake` of the slug so Plugin Check accepts it as the prefix | `Perxel_Seo_Helper` |
| `Perxel Example` | Display name (`Plugin Name` header, `PXEX_NAME`) | `Perxel SEO Helper` |
| `perxel-example` | Slug = text domain = wordpress.org slug | `perxel-seo-helper` |
| `wp-example` | GitHub repo name (Plugin URI, links) | `wp-seo-helper` |
| `PXEX` | Uppercase constant / hook prefix | `PXSH` |
| `pxex` | Lowercase hook / option / CSS-class prefix | `pxsh` |

One-liner (macOS `sed`; drop the `''` after `-i` on Linux) - edit the six
replacement values first:

```sh
git grep -lZ -e 'Perxel_Example' -e 'Perxel Example' \
             -e 'perxel-example' -e 'wp-example' -e 'PXEX' -e 'pxex' \
| xargs -0 sed -i '' \
  -e 's/Perxel_Example/Perxel_Seo_Helper/g' \
  -e 's/Perxel Example/Perxel SEO Helper/g' \
  -e 's/perxel-example/perxel-seo-helper/g' \
  -e 's/wp-example/wp-seo-helper/g' \
  -e 's/PXEX/PXSH/g' \
  -e 's/pxex/pxsh/g'
```

Then rename the two slug-named files:

```sh
git mv perxel-example.php perxel-seo-helper.php
git mv languages/perxel-example.pot languages/perxel-seo-helper.pot
```

### 3. Fill in the free text

Search for **`A short description of what this plugin does.`** (main file header,
`composer.json`, `readme.txt`) and write the real one-liner. Then work through
`readme.txt` - `Contributors`, `Tags`, `Tested up to`, Description, FAQ,
Screenshots, and the External services section (delete it if the plugin calls
nothing third-party) - and replace this README with the plugin's own.

### 4. Vendor the UI kit

```sh
bin/update-ui.sh 0.21.0        # newest tag at github.com/perxel/wp-plugin-ui
```

Set that same version in the main file's `Perxel_UI_Loader::register( '0.21.0',
... )` call - it is what the "highest version wins" loader compares. (The plugin
still activates without this step - it just shows a "UI library could not be
loaded" notice until the kit is vendored.)

### 5. Wire up the repo

- `composer install` - pulls PHPCS + the WordPress standard.
- `php -l <mainfile>.php && composer run lint` - both must be green.
- `bin/plugin-check.sh` - the official WordPress Plugin Check (needs wp-cli +
  `wp package install wordpress/plugin-check-cli`). Run it before the first
  submission; see the "WordPress.org / Plugin Check compliance" table in
  `CLAUDE.md` for the rules it enforces.
- `composer run build` - produces the installable zip in `dist/`.
- Reserve the slug at <https://wordpress.org/plugins/developers/add/> (the first
  submission is a manual review).
- After the .org review is approved: add repo secrets **`SVN_USERNAME`** /
  **`SVN_PASSWORD`** and the repo variable **`DEPLOY_TO_WPORG`** = `true` so
  `release.yml` deploys. Not going on .org? Delete the `deploy` / `assets` jobs.
- Add the real listing art to `.wordpress-org/` (see the README there), then
  delete that README.

### 6. Delete this section

Once the new repo builds green, remove "Creating a new plugin from it" from its
README.

## What's inside

See [CLAUDE.md](CLAUDE.md) for the full architecture, conventions, and release
process - it is written to travel with the generated plugin.

## Updating the template itself

Improvements to the shared layout (`includes/Admin.php`, `phpcs.xml.dist`, the
workflows, `bin/`) land here first. Existing plugins pull UI-kit changes through
`bin/update-ui.sh`; they do **not** auto-sync template changes - port those by
hand when they matter.

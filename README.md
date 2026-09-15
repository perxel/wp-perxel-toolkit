# Perxel Toolkit

Toggleable admin/editor features and third-party plugin integrations shared
across Perxel client projects, in one plugin with a single settings screen -
so rolling out a fix or a new feature across every site is a plugin update,
not a per-project file diff.

Activate it and go to **Tools -> Perxel Toolkit**. Two groups of toggles:

- **Features** - self-contained, no dependency: Editor Restrictions, Admin
  Page Guard, Featured Image Column, Featured Posts.
- **Integrations** - config for a specific third-party plugin or theme
  convention. Greyed out with an install link/message until that dependency
  is detected on the site: Gravity Forms, ACF/SCF, Nectarblocks (hides
  default core blocks on a theme built with a `blocks.json`).

Every module is currently a single on/off toggle; per-module settings
(which post types, which pages, etc.) are exposed as filters for now (see
each class under `includes/Modules/`) until they get their own UI here.

Scaffolded from [`perxel/wp-plugin-starter`](https://github.com/perxel/wp-plugin-starter);
see [CLAUDE.md](CLAUDE.md) for the full architecture, conventions, and
release process.

## Adding a module

1. Add `includes/Modules/<Name>.php` extending `Modules\Module`: `slug()`,
   `label()`, `description()`, `group()` (`feature` or `integration`), and
   `register()`. An integration module also overrides `dependency()` -
   `label`, a `check` callable, and either `wporg_slug` (one-click install)
   or `install_url` (plain link).
2. Add the class to `Modules\Registry::MODULES`.

The settings screen, availability detection, and enable/disable persistence
all follow from that - no other wiring needed.

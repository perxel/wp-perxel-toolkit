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
  is detected on the site: Gravity Forms, ACF/SCF, Nectar Blocks (when the
  Nectar Blocks plugin is active, hides default core blocks so the inserter
  only offers Nectar Blocks + the project's own custom blocks).

Every module is currently a single on/off toggle; finer per-module options
(which post types, which pages, etc.) are exposed as WordPress filters.

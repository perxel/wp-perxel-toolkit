# WordPress.org listing assets

Files here are pushed to the WordPress.org plugin SVN `assets/` directory by
`.github/workflows/release.yml` (the `assets` job). They are **not** shipped in
the plugin zip (`.distignore` excludes this folder).

Drop these in, using the exact filenames:

| File | Size | Purpose |
|---|---|---|
| `icon-128x128.png` | 128x128 | Plugin icon (small) |
| `icon-256x256.png` | 256x256 | Plugin icon (retina) |
| `banner-772x250.png` | 772x250 | Header banner |
| `banner-1544x500.png` | 1544x500 | Header banner (retina) |
| `screenshot-1.png` | any | Matches `== Screenshots ==` item 1 in `readme.txt` |
| `screenshot-2.png` | any | Matches item 2, and so on |

Keep master/source art out of the shipped build too - put it in `assets-src/`
(also `.distignore`d).

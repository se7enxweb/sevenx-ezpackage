# sevenx-ezpackage

The source of the 7x site packages of Exponential: one folder per package, unpacked. The setup wizard, the
kickstarter and `exp:install` download the built archives (`.ezpkg`) from the package server,
[exponential.packages.exponential.earth](https://exponential.packages.exponential.earth/exponential/6.0/6.0.15/index.xml);
this repository is where they are made.

The packages are built and tested for **Exponential 6.0.15**.

## What is in it

`packages/` holds 14 packages.

| Package | Type | What it brings |
|---|---|---|
| `sevenx_multisite` | site | The default site package: two public websites in one installation, Fit & Healthy (`site`) and Bold Agency (`bold`, `bold_ger`), plus the `admin` and `editor` siteaccesses. Its installer (`settings/`) writes the siteaccesses, the active extensions, roles and the post-install fixes |
| `sevenx_multisite_clean` | site | The same installer, with the site structure, forms and legal pages and no demo articles, recipes or images |
| `sevenx_multisite_democontent` | contentobject | The content classes of the media site (`ng_*`) and the demo content of both websites, with its images |
| `sevenx_multisite_democontent_clean` | contentobject | The same classes and only the structure, forms and legal pages |
| `sevenx_classes` | contentclass | The general content classes (article, folder, image, product ...) every 7x site package requires |
| `sevenx_site` | site | 7x Simple Website Interface, a single website with the classic website interface design |
| `sevenx_site_clean` | site | The same without demo content |
| `sevenx_democontent`, `sevenx_democontent_clean`, `sevenx_banners` | contentobject | Content of `sevenx_site` and `sevenx_site_clean` |
| `sevenx_design_blue`, `sevenx_design_cleangray`, `sevenx_design_gray` | sitestyle | The three site styles of `sevenx_site` |
| `sevenx_extension` | extension | The extensions `sevenx_site` installs: `ezwebin`, `sevenx_themes_simple`, `xrowmetadata` and `bcwebshop` |

Each folder has a `package.xml` (name, version, dependencies, install items), a `documents/LICENCE` and the files
the package installs.

### Dependencies

| Package | Requires |
|---|---|
| `sevenx_multisite` | `sevenx_classes`, `sevenx_multisite_democontent` |
| `sevenx_multisite_clean` | `sevenx_classes`, `sevenx_multisite_democontent_clean` |
| `sevenx_site` | `sevenx_extension`, `sevenx_classes`, `sevenx_banners`, `sevenx_democontent`, the three `sevenx_design_*` styles, and `ezwt_extension`, `ezstarrating_extension`, `ezgmaplocation_extension`, `ezpaypal_extension` from their own repositories |
| `sevenx_site_clean` | as `sevenx_site`, with `sevenx_democontent_clean` and without `sevenx_banners` |

The page layouts of the two multisite websites are not in these packages. The installer seeds them from the
`share/*.dba` files of the `sevenx_themes_media` and `explayouts` extensions, which the Exponential installation
must have.

`cjw_multisite`, the CJW starter site, also requires `sevenx_classes` from here; its own source is
[se7enxweb/cjw_multisite-ezpackage](https://github.com/se7enxweb/cjw_multisite-ezpackage).

## Installing

You do not need this repository to install a site. Choose the package in the setup wizard, or install it from
the command line (`exp:install --package`, or `Site_package` in `kickstart.ini` for the kickstarter); both
download it from the package server. The help pages of an Exponential installation describe every way, including
an offline install from a local `index.xml`: `doc/help/site-packages/`.

## Building

```bash
cd packages
./build.sh
```

`build.sh` packs every folder of `packages/` into `<folder>.ezpkg` next to it. It leaves out version control
folders and files (`.git`, `.gitignore`, `.svn` ...), editor and system leftovers (`.DS_Store`, `.idea`, `*~`,
`*.swp`), backup copies (`*.bak`, `*.bak.*`) and `node_modules`. The built archives are not committed here.

`bin/pack.sh --output-dir=<dir>` does the same into another folder; `bin/repack_extension.sh` rebuilds
`sevenx_extension` from an installation, and `ant/createzpkg.sh` packs a single folder.

To publish a new build, copy the changed `.ezpkg` files into `exponential/6.0/6.0.15/` of
[se7enxweb/exponential.packages.exponential.earth](https://github.com/se7enxweb/exponential.packages.exponential.earth)
and commit them there. Test a package by importing and installing it in a throwaway installation first.

## Changing a package

- Keep the `md5sum` attributes in `package.xml` equal to the files they name.
- Keep the PHP files in `settings/` of `sevenx_multisite` and `sevenx_multisite_clean` byte-identical; only their
  `package.xml` differs. The same goes for `sevenx_site` and `sevenx_site_clean`.
- The version of each package is in its `package.xml` (`<number>6.0</number>`, `<release>15-stable</release>` for
  the site packages). The package server's `index.xml` lists a site package with the same text
  (`version="6.0-15-stable"`), so the setup wizard sees an imported copy as current.
- Commit subjects start with `Added:`, `Updated:`, `Removed:` or `Renamed:`.

## Demo content

The demo content packages carry pictures and texts for demonstration. Where a piece came from a third party, its
credit is part of the content object (the recipe credits, for example). Replace the demo content before you
publish a site built from it, or use a `_clean` package.

## License

GNU General Public License, version 2 or (at your option) any later version. The full text is in
[LICENSE](LICENSE); every package states the same in its `package.xml` (`<licence>GPL</licence>`) and in
`documents/LICENCE`. The extensions bundled in `sevenx_extension` are under the same license.

## Problems and contributions

Report problems in the issues of this repository. Pull requests are welcome; how to submit a site package of your
own to the package server is described in the
[contributing guide of the package server](https://github.com/se7enxweb/exponential.packages.exponential.earth/blob/master/CONTRIBUTING.md).

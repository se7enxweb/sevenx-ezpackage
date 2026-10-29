# Content licenses

xrowmetadata lets an editor state under which license the content of a page is
published. The license is chosen from a catalogue of known licenses: open source,
free software, copyleft, permissive, content licenses (every Creative Commons
license ever published), public domain tools, and non-free terms such as "all
rights reserved" or non-commercial licenses. The page head prints it, and by
default every response for that page carries it as an HTTP header.

## Levels and precedence

A license can be set at three levels. The first one that applies wins:

1. **Object (node)**: the "License" block of the metadata attribute in the edit
   form. Only used when the class allows a per-object license.
2. **Class**: the class attribute's "Default license" (class edit).
3. **Installation**: `[LicenseSettings] DefaultLicense` in `xrowmetadata.ini`,
   shipped as `GPL-2.0-or-later` (GNU General Public License v2.0 or later).

An object or class license that is unknown to the catalogue or not allowed on
the installation (`AllowedGroups` / `AllowedLicenses`) is passed over and the
next level applies. The installation default is the administrator's choice and
is not checked against the allowlist. When nothing applies, the page states no
license.

"Allow a per-object license" (class edit, on by default): when it is cleared,
the class sets the license. The object edit form shows the class default
read-only; a license stored earlier on the object is kept but not used, and
checking the box again brings it back. The object's holder and year are used
in either case.

## The catalogue

`share/licenses/licenses.json` holds every license, one per line. Each entry
has an `id` (the SPDX id where SPDX has one, otherwise `LicenseRef-...`), a
`name`, a canonical `url`, `groups`, a `type`, the `copyleft` strength
(`strong`, `weak`, `network`, `none`), `osi`, `fsf_libre`, `deprecated` and a
`terms` summary (`commercial`, `derivatives`, `share_alike`, `attribution`).

| Group           | Contains                                                              |
|-----------------|-----------------------------------------------------------------------|
| `open_source`   | approved by the Open Source Initiative                                |
| `free_software` | free (libre) according to the Free Software Foundation                |
| `copyleft`      | changed versions must keep the license (strong, weak, network)        |
| `permissive`    | few conditions besides keeping the notice                             |
| `content`       | texts, images, media, data: Creative Commons, GFDL, Open Data Commons |
| `public_domain` | dedications and marks: CC0, Public Domain Mark, PDDL, Unlicense, ...  |
| `non_free`      | proprietary, non-commercial, no-derivatives, source-available, custom |

**Creative Commons**: every tool CC has published is listed: versions 1.0 to
4.0, all six combinations, the old 1.0 orderings, every jurisdiction port, IGO,
and the retired licenses (Sampling, Sampling+, Developing Nations, the 1.0
single-element licenses, the CC-GNU wrappers, the Public Domain Dedication and
Certification). Ports without an SPDX id are named
`LicenseRef-CC-<code>-<version>[-<jurisdiction>]`, e.g. `LicenseRef-CC-BY-2.0-DE`.
Retired entries have `deprecated: true` and `retired_by_cc: true`. They stay in
the catalogue so content that uses them keeps showing them.

**Public domain**: `CC0-1.0`, `CC-PDM-1.0` (alias `LicenseRef-PDM-1.0`),
`CC-PDDC` (retired), `Unlicense`, `0BSD`, `PDDL-1.0`, `LicenseRef-USGov-PD`
(works of the US government), `LicenseRef-PD-Expired` (copyright expired) and
`LicenseRef-NoKnownCopyright`.

**Pseudo entries**: `LicenseRef-AllRightsReserved` (no URL) and
`LicenseRef-Custom`, whose address and terms the object gives in the edit form.

### Refreshing the catalogue

The catalogue is generated and never fetched at runtime. Download the SPDX
license list (`json/licenses.json` of a tagged release of
https://github.com/spdx/license-list-data) and the Creative Commons legal tools
index (`config/cc-legal-tools.csv` of
https://github.com/creativecommons/cc-legal-tools-data), then run:

    php extension/xrowmetadata/bin/php/xrowmetadatalicenses.php \
        --spdx=<spdx licenses.json> --cc=<cc-legal-tools.csv> --cc-ref="<commit> <date>" \
        --output=extension/xrowmetadata/share/licenses/licenses.json
    php extension/xrowmetadata/bin/php/xrowmetadatalicenses.php --check

The versions used are recorded under `sources` in the file. The classification
rules are in `share/licenses/README.md`. After a refresh, clear the content view
cache and the HTTP cache.

## Edit view

- **Object**: the "License" block below the Open Graph image. The list is
  grouped (each license once, in its first group) and has a search field (name
  or SPDX id, e.g. "cc by 4.0" or "gpl 3.0 or later"; Enter picks the first
  match), a group filter and a "Show retired licenses" switch. Retired licenses
  are hidden until it is switched on, except the one already chosen. The first
  option, "Use inherited", names what applies without a choice. Below the list
  are a summary of the terms (commercial use, derivatives, share-alike,
  attribution) and a link to the license text. Holder and year are optional; a
  custom license needs the address or the text of its terms.
- **Class**: "Default license" and "Allow a per-object license".
- The adminui design (exp_adminui) includes the same block with Bootstrap
  classes.
- **Validation**: an unknown or not allowed id, a custom license without
  address and terms, an address that is not http(s), and a year that is not
  `YYYY` or `YYYY-YYYY` are refused. An unchanged stored id is not checked
  again, so a narrower allowlist never blocks saving other changes.

Storage: `<license>`, `<license_holder>`, `<license_year>`,
`<license_custom_url>` and `<license_custom_terms>` in the attribute's XML. Data
stored before 1.4.9 has none of them, which means "use inherited". The class
settings are kept in the class attribute's defaults array (`data_text5`:
`license`, `allow_object_license`).

## Installation settings

`xrowmetadata.ini`, `[LicenseSettings]`:

| Setting              | Default            | Meaning                                                       |
|----------------------|--------------------|---------------------------------------------------------------|
| `Enabled`            | `true`             | `false`: no license is resolved, printed or sent              |
| `DefaultLicense`     | `GPL-2.0-or-later` | the installation default; empty in an override = none         |
| `DefaultHolder`      | empty              | copyright holder when the object names none                  |
| `AllowedGroups[]`    | empty (all)        | the groups editors may choose from                            |
| `AllowedLicenses[]`  | empty (all)        | the ids editors may choose; when set, `AllowedGroups` is not used |
| `ShowInOpenGraph`    | `enabled`          | Dublin Core `dcterms.license` next to the Open Graph tags     |
| `ShowLinkRelLicense` | `enabled`          | `<link rel="license">`                                        |
| `ShowJsonLd`         | `enabled`          | schema.org `license` in the JSON-LD                           |

Change the installation default in `settings/override/xrowmetadata.ini.append.php`
or in a siteaccess, not in the extension.

## Output

In the page head (`design:xrowmetadata/license_head.tpl`, included by
`page_head.tpl.sample`):

    <link rel="license" href="https://www.gnu.org/licenses/old-licenses/gpl-2.0.html">
    <link rel="schema.dcterms" href="http://purl.org/dc/terms/">
    <meta name="dcterms.license" content="https://www.gnu.org/licenses/old-licenses/gpl-2.0.html">
    <script type="application/ld+json">{"@context":"https://schema.org","@type":"WebPage","url":"...","license":"https://..."}</script>

Why these formats:

- **Open Graph has no license property** (https://ogp.me/), so none is
  invented; the `og:` and `article:` tags stay as they are.
- `rel="license"` is an HTML link type: "the main content of the current
  document is covered by the copyright license described by the referenced
  document". Creative Commons marks its works the same way.
- schema.org `license` (on CreativeWork, so also WebPage, Article,
  NewsArticle, Recipe ...) is what search engines read. A theme with its own
  JSON-LD can merge it into that block (`json_ld_property`) instead of printing
  a second script (`json_ld=false()`).
- Dublin Core in HTML is cheap and is read by library and repository harvesters.
- A **mark** (Public Domain Mark, a US government work, expired copyright, no
  known copyright) is a label, not a license. It gets `dcterms.rights` and no
  `rel="license"`. A dedication (CC0) is a legal tool and keeps `rel="license"`.
- Retired licenses are printed like any other.

The operator `xrowmetadata_license( node )` (also `$node|xrowmetadata_license`,
or without a node for the page of the request) returns the resolved license:
`id`, `name`, `url`, `groups`, `type`, `source` (`node`, `class`,
`installation`), `holder`, `year`, `attribution`, `kind` (`license`,
`dedication`, `mark`, `reserved`), `show`, `meta_name`, `meta_content`, and
ready-made JSON (`json_ld_value`, `json_ld_property`, `json_ld_node`,
`json_ld_script`). It returns false when nothing applies.

A visible notice for a footer or below an article:

    {include uri='design:xrowmetadata/license_notice.tpl' node=$node}

prints e.g. "© 2026 Holder · Licensed under CC BY 4.0", "Dedicated to the
public domain with CC0 1.0 Universal", "Public Domain Mark 1.0 · Free of known
copyright restrictions" or "All rights reserved". The copyright sign is never
printed for public domain content. Parameters: `node`, `license`,
`rel_license`, `class`, `tag`.

## HTTP header

`[LicenseHeaderSettings]`, on by default. Every request that views a node with
a license gets:

    Link: <https://www.gnu.org/licenses/old-licenses/gpl-2.0.html>; rel="license"
    X-Content-License: GPL-2.0-or-later

| Setting            | Default             | Meaning                                              |
|--------------------|---------------------|------------------------------------------------------|
| `Enabled`          | `enabled`           | send the headers                                     |
| `HeaderLink`       | `enabled`           | the `Link` header (RFC 8288, relation `license`)     |
| `HeaderName`       | `X-Content-License` | the header with the id; empty = not sent             |
| `IncludePaths[]`   | empty (all)         | globs on the address after the siteaccess            |
| `ExcludePaths[]`   | `/user/*`, `/ezjscore/*`, `/content/edit/*` | never for these              |
| `OnlyContentViews` | `enabled`           | only when the request views a node                   |

Globs are matched case-insensitively against the address as typed (the URL
alias) and the system address (`content/view/full/<id>`); `/news/*` covers
`/news` too. ExcludePaths is also matched against the address the server
received (with and without its first segment, a siteaccess in the URI), so
a page is excluded whichever form of its address matches. Edit, AJAX, browse
and login contexts and error pages (not found, access denied) never get the
headers. Other `Link` headers (preload) are kept. A mark (Public Domain Mark,
no known copyright ...) is not a license: it gets `X-Content-License` but no
`Link` with `rel="license"`, as in the page head.
To switch the headers off in the admin, set `Enabled=disabled` in the admin
siteaccess's `xrowmetadata.ini.append.php`.

How it works: listeners in `site.ini` `[Event]`. `request/preinput` resets the
state, `content/view` notes the node (also on a view cache hit), and
`response/preoutput` sends the headers. The state belongs to one request.

Caching:

- View cache hits always carry the headers.
- The role-aware HTTP cache (exphttpcache) stores headers through the kernel
  filter `httpcache/store/headers` in `ezpHttpCacheListener::storeOrSay()`. The
  listener `xrowMetaDataLicenseHeader::cacheHeaders` adds the license to them,
  so a cache hit carries it. This needs an Exponential kernel with that filter;
  without it, only pages the kernel renders get the headers.
- Velocity's response cache keeps the headers of the response it stored.
- Changing a node's license: publishing purges its cache entries. Changing a
  class or the installation default: clear the content view cache and the HTTP
  cache (`php bin/php/ezcache.php --clear-id=content,exphttpcache`).

## Troubleshooting

- **No license in the page**: check `[LicenseSettings] Enabled`, that the class
  has the metadata attribute, and that the theme's page head includes
  `xrowmetadata/license_head.tpl` or uses the operator.
- **A chosen license is not shown**: the class may have "Allow a per-object
  license" cleared, or the license is not in `AllowedGroups` /
  `AllowedLicenses`; the next level applies then. A license that is not in the
  catalogue is named in the debug output.
- **Old license after changing a default**: clear the content view cache and
  the HTTP cache.
- **No header**: the address may match `ExcludePaths`, the request may not view
  a node (`OnlyContentViews`), or the response headers were already sent.
  Check with `curl -sI https://<host>/<page>`.
- **New settings are not read**: clear the INI cache with `--clear-tag=ini`.

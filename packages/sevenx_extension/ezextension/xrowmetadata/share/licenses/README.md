# The license catalogue

`licenses.json` lists every license an editor can give a page in the metadata
attribute: the whole SPDX License List, every legal tool Creative Commons has
published (all versions, all ported licenses, the retired ones), the public
domain statuses, and the non-free pseudo licenses (all rights reserved, custom
terms). The class `xrowMetaDataLicense` (`classes/xrowmetadatalicense.php`)
reads it; nothing fetches it at runtime.

## Sources

| Source | Version used | Where |
|---|---|---|
| SPDX License List | 3.29.0 (tag `v3.29.0`, released 2026-09-16) | `json/licenses.json` of https://github.com/spdx/license-list-data |
| Creative Commons legal tools index | commit `a0dac18d1a773b4cdb7d8dd5d9bf4efab7901030` (2026-10-01), 639 tools | `config/cc-legal-tools.csv` of https://github.com/creativecommons/cc-legal-tools-data |
| Creative Commons retired legal tools | read 2026-10-11 | https://creativecommons.org/retiredlicenses/ |

Both versions are recorded in the file (`sources`).

## Regenerating

Download the two files (pin the SPDX release tag), then:

```bash
php extension/xrowmetadata/bin/php/xrowmetadatalicenses.php \
    --spdx=var/tmp/spdx-licenses-v3.29.0.json \
    --cc=var/tmp/cc-legal-tools.csv \
    --cc-ref="<commit> <date>"
```

`--check` only compares and exits 1 when the file would change. The output has
one license per line, so a diff shows the licenses that changed.

## An entry

```json
{"id":"GPL-3.0-or-later","name":"GNU General Public License v3.0 or later",
 "url":"https://www.gnu.org/licenses/gpl-3.0.html","spdx":true,
 "groups":["open_source","free_software","copyleft"],"type":"copyleft",
 "copyleft":"strong","osi":true,"fsf_libre":true,"deprecated":false,
 "terms":{"commercial":true,"derivatives":true,"share_alike":true,"attribution":true}}
```

| Key | Meaning |
|---|---|
| `id` | SPDX id, else `LicenseRef-…` (ported and retired CC tools SPDX does not list: `LicenseRef-CC-<CODE>-<version>[-<JURISDICTION>]`, e.g. `LicenseRef-CC-BY-2.0-DE`) |
| `name` | the SPDX name, else the English name (`name_local`: the CC title in the port's language) |
| `url` | the license's own page: gnu.org for GNU licenses, creativecommons.org (deed) for CC, the steward's page (Apache, Mozilla, Eclipse, Open Data Commons, …), else opensource.org, else the SPDX page; `''` when there is none (all rights reserved, custom terms) |
| `spdx` | the id is an SPDX id |
| `groups` | any of `open_source`, `free_software`, `copyleft`, `permissive`, `content`, `public_domain`, `non_free`; never empty: every entry is classified (an entry the rules could not place would have no groups and `reviewed: false`) |
| `type` | the main kind: `copyleft`, `permissive`, `public_domain`, or for `non_free` `proprietary`, `non_commercial`, `no_derivatives`, `custom`, `source_available`; `other` when unclassified |
| `copyleft` | `strong`, `weak`, `network` or `none` |
| `osi`, `fsf_libre` | the SPDX flags (OSI approved; FSF free/libre) |
| `deprecated` | a deprecated SPDX id (`replaced_by` gives the expression to use) or a tool Creative Commons retired |
| `terms` | `commercial`, `derivatives`, `share_alike`, `attribution`; `null` = not known |

Optional keys: `aliases` (ids `byID()` also accepts, e.g. `LicenseRef-PDM-1.0`
for `CC-PDM-1.0`), `legalcode`, `cc` (`unit`, `version`, `jurisdiction`),
`ported`, `retired_by_cc`, `retired_on`, `retired_reason`, `free_culture`
(approved for Free Cultural Works), `same_as`, `see_also`, `per_object`,
`note`, `reviewed`.

## Classification

The rules are in `bin/php/xrowmetadatalicenses.php`, next to the lists they use.

- **open_source / free_software**: the SPDX `isOsiApproved` and `isFsfLibre` flags, never guessed.
- **copyleft strength**:
  - *network*: the copyleft also reaches users over a network: AGPL, and the licenses with an
    "external deployment" or "on-line access" clause: OSL, NPOSL, RPL, RPSL, APSL 2.0, CPAL,
    Watcom, EUPL 1.0–1.2 (its scope is the work and its derivatives; linking for
    interoperability is not covered), SSPL (but see non_free).
  - *strong*: the whole derived work: GPL family, GFDL, Sleepycat, CeCILL 1.x/2.x,
    copyleft-next, Free Art (LAL), ODbL, CERN-OHL-S, TAPR-OHL, LiLiQ-R+, SimPL, Parity;
    and the Creative Commons ShareAlike licenses (BY-SA, SA 1.0).
  - *weak*: only the files or library and their changes: LGPL, MPL, EPL, CDDL, CPL, IPL, NPL,
    SPL, SISSL, APSL 1.x, Ms-RL, Ms-PL, CeCILL-C, OFL, LiLiQ-R, CERN-OHL-W, CDLA-Sharing, QPL 1.0
    and its INRIA 2004 variant (changes only as patches and under the QPL; linked software must
    ship with its source; the FSF calls the QPL non-copyleft but free; only QPL-1.0 is OSI- and
    FSF-listed, the variant differs only in its choice of law), and
    GPL with a linking exception (Classpath, GCC, font), eCos, wxWindows.
- **permissive**: OSI- or FSF-approved without copyleft, and the many short notices of the
  MIT, BSD, ISC, X11, HPND families and the like that SPDX lists without a flag (among them
  the Informatica License: use and redistribution "without restriction", no conditions).
- **content**: every Creative Commons tool, GFDL, Open Data Commons (ODbL, ODC-By, PDDL),
  CDLA, government data licenses (OGL, DL-DE, Etalab, NLOD), font and documentation licenses.
- **public_domain**: CC0, Public Domain Mark (`CC-PDM-1.0`, alias `LicenseRef-PDM-1.0`), CC
  Public Domain Dedication and Certification (`CC-PDDC`, retired), Unlicense, 0BSD
  (public-domain-equivalent, also permissive), PDDL, the SPDX public domain notices (NIST-PD,
  SAX-PD, …), and `LicenseRef-NoKnownCopyright`, `LicenseRef-USGov-PD` (17 U.S.C. § 105),
  `LicenseRef-PD-Expired`. WTFPL is permissive, *not* public domain. ODC-By and ODbL are data
  licenses, not public domain.
- **non_free**: CC NonCommercial (`non_commercial`), NoDerivs (`no_derivatives`), Sampling,
  Sampling Plus and Developing Nations (`custom`); source-available licenses (SSPL, BUSL,
  Elastic, FSL, PolyForm Small Business: `source_available`); use-restricted licenses
  (Hippocratic, JSON, BSD No-Military / No-Nuclear-License: `custom`); the licenses the FSF
  marks non-free without OSI approval; `LicenseRef-AllRightsReserved` (`proprietary`) and
  `LicenseRef-Custom` (`custom`, its URL and terms come from the object).

## Creative Commons

All 639 tools of the CC index are in the catalogue: versions 1.0, 2.0, 2.1, 2.5, 3.0, 4.0 of
BY, BY-SA, BY-ND, BY-NC, BY-NC-SA, BY-NC-ND, the 1.0 ordering BY-ND-NC, every port (IGO
included), CC0, the Public Domain Mark, and the retired tools. A tool with an SPDX id keeps it
(56 do, e.g. `CC-BY-3.0-DE`, `CC-BY-SA-2.0-UK`, `CC-BY-NC-3.0-IGO`); the others are
`LicenseRef-CC-…`. The deed URL is the `url` (CC's canonical license URI), the legal code is
`url` + `legalcode` — every one exists in the CC data repository, and every deed URL answered
200 when checked on 2026-10-11.

Retired (from https://creativecommons.org/retiredlicenses/; every port of these carries CC's
"retired" notice): Sampling+ and NonCommercial Sampling+ (2011-09-12), Public Domain
Dedication and Certification (2010-10-11), Developing Nations and Sampling (2007-06-04),
NoDerivs, NoDerivs-NonCommercial, NonCommercial, NonCommercial-ShareAlike and ShareAlike
(2004-05-25). Not in the CC index and added by hand, all retired: CC-GNU GPL 2.0 and CC-GNU
LGPL 2.1, the Music Sharing License (the terms of BY-NC-ND), Founders' Copyright; their old
pages are linked through the Internet Archive. Retired tools have `deprecated: true` and
`retired_by_cc: true`: the picker hides them by default, existing content still shows them.

## URLs

Checked 2026-10-11 from the alpha server: every URL that is not an spdx.org page answers 200,
except `license.coscl.org.cn` (Mulan PSL), which the server cannot reach; `www.cecill.info`
serves no valid https certificate and keeps its http URLs. A handful of URLs are set by hand
where SPDX's first page is gone or is not the license (`URL_OVERRIDES` in the generator).

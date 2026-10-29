<?php
/**
 * Builds the license catalogue share/licenses/licenses.json of xrowmetadata
 * from two downloaded files, never from the network:
 *
 *   --spdx=<licenses.json>   the SPDX License List, json/licenses.json of
 *                            https://github.com/spdx/license-list-data at a
 *                            release tag (e.g. v3.29.0)
 *   --cc=<cc-legal-tools.csv> config/cc-legal-tools.csv of
 *                            https://github.com/creativecommons/cc-legal-tools-data,
 *                            the index of every legal tool Creative Commons
 *                            has published (all versions, ports, retired ones)
 *   --cc-ref=<text>          the commit (and date) of that CSV, recorded in the file
 *   --output=<file>          default: share/licenses/licenses.json of this extension
 *   --check                  build and compare with --output, write nothing;
 *                            exit 1 when the file would change
 *
 * Usage (from anywhere, plain PHP, no kernel needed):
 *
 *   php extension/xrowmetadata/bin/php/xrowmetadatalicenses.php \
 *       --spdx=var/tmp/spdx-licenses-v3.29.0.json \
 *       --cc=var/tmp/cc-legal-tools.csv --cc-ref="a0dac18d 2026-10-01"
 *
 * The classification (open source, free software, copyleft strength,
 * permissive, content, public domain, non-free type and terms) is kept in
 * this file next to the reasons for it; see share/licenses/README.md.
 *
 * @license GNU General Public License v2.0 (or any later version)
 */

if ( PHP_SAPI !== 'cli' )
{
    exit( 1 );
}

$options = getopt( '', array( 'spdx:', 'cc:', 'cc-ref:', 'output:', 'check', 'help' ) );
if ( isset( $options['help'] ) || empty( $options['spdx'] ) || empty( $options['cc'] ) )
{
    fwrite( STDERR, "Usage: php xrowmetadatalicenses.php --spdx=<licenses.json> --cc=<cc-legal-tools.csv> [--cc-ref=<commit date>] [--output=<file>] [--check]\n" );
    exit( isset( $options['help'] ) ? 0 : 2 );
}
$output = isset( $options['output'] ) ? $options['output'] : dirname( __DIR__, 2 ) . '/share/licenses/licenses.json';

$generator = new xrowMetaDataLicenseCatalogueGenerator();
try
{
    $catalogue = $generator->build( $options['spdx'], $options['cc'], isset( $options['cc-ref'] ) ? $options['cc-ref'] : '' );
}
catch ( Exception $e )
{
    fwrite( STDERR, "FAIL: " . $e->getMessage() . "\n" );
    exit( 1 );
}
$json = $generator->encode( $catalogue );

foreach ( $generator->warnings as $warning )
{
    fwrite( STDERR, "WARN: $warning\n" );
}
$stats = $generator->stats( $catalogue );
if ( isset( $options['check'] ) )
{
    $current = is_file( $output ) ? file_get_contents( $output ) : '';
    if ( $current !== $json )
    {
        echo "FAIL: $output is not what the sources give (" . $stats . ")\n";
        exit( 1 );
    }
    echo "PASS: $output is current ($stats)\n";
    exit( 0 );
}
if ( !is_dir( dirname( $output ) ) )
{
    mkdir( dirname( $output ), 0755, true );
}
if ( file_put_contents( $output, $json ) === false )
{
    echo "FAIL: cannot write $output\n";
    exit( 1 );
}
echo "PASS: wrote $output ($stats)\n";
exit( 0 );

class xrowMetaDataLicenseCatalogueGenerator
{
    public $warnings = array();

    /** Groups an entry can have, in the order they are listed. */
    const GROUPS = array( 'open_source', 'free_software', 'copyleft', 'permissive', 'content', 'public_domain', 'non_free' );

    /**
     * Copyleft strength of software (and documentation, data, hardware)
     * licenses, by SPDX id or id prefix (a key ending in '*').
     *
     *   network: the copyleft also reaches users of the work over a network
     *            (AGPL, and the licenses with an "external deployment" or
     *            "providing on-line access" clause: OSL, RPL, APSL 2.0,
     *            CPAL, Watcom, EUPL, NPOSL, RPSL)
     *   strong:  the whole work that includes or is derived from the
     *            licensed work (GPL family, GFDL, Sleepycat, CeCILL 1/2,
     *            copyleft-next, Free Art, ODbL, CERN-OHL-S, TAPR)
     *   weak:    only the licensed files / library and their changes; a
     *            larger work may use another license (LGPL, MPL, EPL, CDDL,
     *            CPL, IPL, Ms-RL, Ms-PL, CeCILL-C, OFL, GPL with a linking
     *            exception such as Classpath or GCC)
     * Everything else is 'none'.
     */
    const COPYLEFT = array(
        // network
        'AGPL-*' => 'network', 'OSL-*' => 'network', 'NPOSL-3.0' => 'network', 'RPL-*' => 'network',
        'APSL-2.0' => 'network', 'CPAL-1.0' => 'network', 'Watcom-1.0' => 'network', 'EUPL-*' => 'network',
        'RPSL-1.0' => 'network', 'SSPL-1.0' => 'network',
        // GPL with a linking exception: weak
        'GPL-2.0-with-classpath-exception' => 'weak', 'GPL-2.0-with-GCC-exception' => 'weak',
        'GPL-3.0-with-GCC-exception' => 'weak', 'GPL-2.0-with-font-exception' => 'weak',
        'eCos-2.0' => 'weak', 'wxWindows' => 'weak',
        // strong
        'GPL-*' => 'strong', 'GFDL-*' => 'strong', 'Sleepycat' => 'strong', 'CECILL-1.0' => 'strong',
        'CECILL-1.1' => 'strong', 'CECILL-2.0' => 'strong', 'CECILL-2.1' => 'strong', 'copyleft-next-*' => 'strong',
        'LAL-*' => 'strong', 'ODbL-1.0' => 'strong', 'CERN-OHL-S-2.0' => 'strong', 'CERN-OHL-1.1' => 'strong',
        'CERN-OHL-1.2' => 'strong', 'TAPR-OHL-1.0' => 'strong', 'SMAIL-GPL' => 'strong', 'LiLiQ-Rplus-1.1' => 'strong',
        'ESA-PL-strong-copyleft-2.4' => 'strong', 'SimPL-2.0' => 'strong', 'Parity-*' => 'strong', 'TGPPL-1.0' => 'strong',
        'UCL-1.0' => 'strong', 'GPL-CC-1.0' => 'strong',
        // weak
        'LGPL-*' => 'weak', 'LGPLLR' => 'weak', 'MPL-*' => 'weak', 'EPL-*' => 'weak', 'CDDL-*' => 'weak',
        'CPL-1.0' => 'weak', 'IPL-1.0' => 'weak', 'NPL-*' => 'weak', 'SPL-1.0' => 'weak', 'SISSL*' => 'weak',
        'APSL-1.*' => 'weak', 'MS-RL' => 'weak', 'MS-PL' => 'weak', 'CECILL-C' => 'weak', 'OFL-*' => 'weak',
        'CUA-OPL-1.0' => 'weak', 'Motosoto' => 'weak', 'Nokia' => 'weak', 'ErlPL-1.1' => 'weak', 'Interbase-1.0' => 'weak',
        'gSOAP-1.3b' => 'weak', 'OSET-PL-2.1' => 'weak', 'LiLiQ-R-1.1' => 'weak', 'CERN-OHL-W-2.0' => 'weak',
        'ESA-PL-weak-copyleft-2.4' => 'weak', 'OCCT-PL' => 'weak', 'CDLA-Sharing-1.0' => 'weak', 'Ubuntu-font-1.0' => 'weak',
        'Linux-man-pages-copyleft*' => 'weak', 'Vim' => 'weak', 'SugarCRM-1.1.3' => 'weak', 'Zimbra-*' => 'weak',
        'RSCPL' => 'weak', 'Frameworx-1.0' => 'weak', 'BSD-Protection' => 'weak', 'MMPL-1.0.1' => 'weak',
        'SUL-1.0' => 'network',
        // QPL (checked 2026-10-11 against the SPDX texts): changes may only be
        // distributed apart from the Software, as patches (3), changes in a binary
        // stay under the QPL (4c), and an item that links with the Software must be
        // distributed with its source and freely redistributable (6), under a license
        // of its own choosing. That is a file-level copyleft with a source condition
        // on linked items, so weak, although the FSF calls the QPL non-copyleft.
        // The INRIA 2004 variant has the same terms; only the choice of law differs.
        'QPL-1.0' => 'weak', 'QPL-1.0-INRIA-2004' => 'weak',
    );

    /**
     * Not free and not open source although SPDX lists them: they restrict
     * use (field of use, competitors, non-commercial, ethical clauses) or are
     * source-available only. id => type.
     */
    const NON_FREE = array(
        'SSPL-1.0' => 'source_available', 'BUSL-1.1' => 'source_available', 'Elastic-2.0' => 'source_available',
        'FSL-1.1-ALv2' => 'source_available', 'FSL-1.1-MIT' => 'source_available', 'SUL-1.0' => 'source_available',
        'PolyForm-Noncommercial-1.0.0' => 'non_commercial', 'PolyForm-Small-Business-1.0.0' => 'source_available',
        'Prosperity-3.0.0' => 'non_commercial', 'NCGL-UK-2.0' => 'non_commercial', 'PPL' => 'non_commercial',
        'Aladdin' => 'non_commercial', 'CC-BY-NC-*' => 'non_commercial', 'NCL' => 'custom',
        'Hippocratic-2.1' => 'custom', 'Hippocratic-3.0-core' => 'custom', 'JSON' => 'custom',
        'BSD-3-Clause-No-Military-License' => 'custom', 'BSD-3-Clause-No-Nuclear-License' => 'custom',
        'BSD-3-Clause-No-Nuclear-License-2014' => 'custom', 'CPOL-1.02' => 'custom', 'OPL-1.0' => 'custom',
        'RHeCos-1.1' => 'custom', 'UnRAR' => 'custom', 'CAPEC-tou' => 'custom', 'cve-tou' => 'custom',
        'Unicode-TOU' => 'custom', 'IEC-Code-Components-EULA' => 'proprietary', 'Commons-Clause' => 'source_available',
        'OpenMDW-1.0' => 'custom', 'AMPAS' => 'custom',
    );

    /** Public domain dedications, marks and equivalents among the SPDX ids. */
    const PUBLIC_DOMAIN = array(
        'CC0-1.0', 'CC-PDM-1.0', 'CC-PDDC', 'Unlicense', 'Unlicense-libtelnet', 'Unlicense-libwhirlpool', '0BSD',
        'PDDL-1.0', 'SAX-PD', 'SAX-PD-2.0', 'NIST-PD', 'NIST-PD-fallback', 'NIST-PD-TNT', 'NCBI-PD', 'NTIA-PD',
        'ANTLR-PD', 'ANTLR-PD-fallback', 'libselinux-1.0', 'blessing', 'DL-DE-ZERO-2.0',
    );

    /**
     * Licenses for content, documentation and data (besides the Creative
     * Commons tools, which are all content): id or prefix.
     */
    const CONTENT = array(
        'GFDL-*', 'FDL*', 'LAL-*', 'ODbL-1.0', 'ODC-By-1.0', 'PDDL-1.0', 'CDLA-*', 'C-UDA-1.0', 'O-UDA-1.0',
        'DL-DE-*', 'etalab-2.0', 'NLOD-*', 'OGC-1.0', 'OGDL-Taiwan-1.0', 'OGL-*', 'OPL-UK-3.0', 'OPUBL-1.0',
        'NCGL-UK-2.0', 'FreeBSD-DOC', 'Linux-man-pages-*', 'LPD-document', 'ASWF-Digital-Assets-*', 'CC-PDM-1.0',
        'CC-PDDC', 'CC0-1.0', 'Community-Spec-1.0', 'OFL-*', 'GCR-docs', 'AdaCore-doc', 'HPND-doc*', 'TPDL',
        'CDL-1.0', 'DocBook-*', 'Ubuntu-font-1.0', 'ParaType-Free-Font-1.3', 'Bitstream-*', 'Arphic-1999', 'Baekmuk',
        'Lucida-Bitmap-Fonts', 'mplus', 'OML',
    );

    /**
     * Families of short permissive notices SPDX lists without an OSI or FSF
     * flag (MIT, BSD, HPND, X11, ISC and their many variants, and notices of
     * the same kind: use, copy, modify, distribute, keep the notice).
     * A pattern is a regular expression on the id.
     */
    const PERMISSIVE_PATTERN = '/^(MIT|BSD|X11|HPND|ISC|OLDAP|Spencer|SGI-B|NIST-Software|Libpng|libpng|zlib|Zlib|TCL|Afmparse|Adobe|AMD|AML|Abstyles|ADSL|App-s2p|Aspell|atc-game|Bahyph|Barr|Beerware|bcrypt|Boehm|Borceux|Brian-Gladman|Buddy|Bugroff|bzip2|Caldera|Catharon|CFITSIO|check-cvs|checkmk|Clips|CMU-Mach|CNRI|Cornell|Cronyx|Crossword|CryptoSwift|CrystalStacker|Cube|DEC-3|diffmark|DOC$|Dotseqn|DSDP|dtoa|dvipdfm|eGenix|EPICS|Eurosym|FBM|Ferguson|FSFAP|FSFUL|Furuseth|fwlw|Game-Programming|generic-xts|Giftware|GL2PS|Glulxe|GLWTPL|Graphics-Gems|gtkbook|Gutmann|HaskellReport|HDF5|hdparm|HIDAPI|HP-19|HTMLTIDY|hyphen|IBM-pibs|IJG|ImageMagick|Info-ZIP|Inner-Net|Intel|ISO-permission|JasPer|jove|JPL-image|JPNIC|Kastrup|Kazlib|Knuth|Latex2e|Leptonica|libtiff|libutil|Linux-OpenIB|LOOP|lsof|LZMA|Mackerras|magaz|mailprio|MakeIndex|man2html|Martin-Birgmeier|McPhee|metamail|Minpack|MIPS|MITNFA|MMIXware|MPEG-SSG|mpi-permissive|mpich2|MTLL|Mup|NAIST|NetCDF|Newsletr|ngrep|NICTA|Noweb|NRL|NTP|OAR|OFFIS|OpenPBS|OpenSSL|OpenVision|OSSP|PADL|Pixar|pkgconf|Plexus|pnmstitch|psfrag|psutils|python-ldap|PSF-2.0|Qhull|radvd|Rdisc|RSA-MD|Ruby-pty|Saxpath|SchemeReport|SCEA|Sendmail|SGP4|SGMLUG|SHL-|SL$|snprintf|SNIA|SOFA|softSurfer|Soundex|ssh-keyscan|SSH-|SSLeay|Sun-PPP|SunPro|SWL|swrule|Symlinks|TCP-wrappers|TekHVC|TermReadKey|ThirdEye|threeparttable|TMate|TORQUE|TOSL|TPL-1.0|TrustedQSL|TTWL|TTYP0|TU-Berlin|UCAR|ulem|UMich|Unicode-DFS|UnixCrypt|URT-RLE|Vixie|VOSTROM|W3C|w3m|Widget|Wsuipa|WTFNMFPL|wwl|Xerox|Xfig|xkeyboard|xlock|xpp|XSkat|xzoom|YPL|Zed|Zeeff|ZPL|3D-Slicer|Advanced-Cryptics|ASWF|BitTorrent|BOLA|COIL|Community-Spec|D-FSL|DL-DE-BY|DRL|etalab|FDK-|Glide|GD$|GCR-docs|AdaCore-doc|LPD-document|Linux-man-pages-1-para|LPPL|NLOD|NLPL|OGC-1.0|OGDL|OGL-|OPL-UK|OPUBL|CDLA-Permissive|C-UDA|O-UDA|ODC-By|CDL-1.0|DocBook|Bitstream|Arphic|Baekmuk|Lucida|mplus|OML|ParaType|FreeBSD-DOC|TPDL|Kastrup|Soundex|any-OSI|Artistic-dist|SMPPL|Sun|Xdebug|xinetd|NBPL|MS-LPL|MVT|OGTSL|psutils|FreeImage|Imlib2|IJG|Linux-man-pages|APAFML|ESA-PL-permissive|MulanPSL|Net-SNMP|InnoSetup|SGI-OpenGL|Informatica$)/';

    /** The canonical page of the GNU licenses (gnu.org), by family and version. */
    const GNU_URL = array(
        'GPL-1.0' => 'https://www.gnu.org/licenses/old-licenses/gpl-1.0.html',
        'GPL-2.0' => 'https://www.gnu.org/licenses/old-licenses/gpl-2.0.html',
        'GPL-3.0' => 'https://www.gnu.org/licenses/gpl-3.0.html',
        'LGPL-2.0' => 'https://www.gnu.org/licenses/old-licenses/lgpl-2.0.html',
        'LGPL-2.1' => 'https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html',
        'LGPL-3.0' => 'https://www.gnu.org/licenses/lgpl-3.0.html',
        'AGPL-3.0' => 'https://www.gnu.org/licenses/agpl-3.0.html',
        'GFDL-1.1' => 'https://www.gnu.org/licenses/old-licenses/fdl-1.1.html',
        'GFDL-1.2' => 'https://www.gnu.org/licenses/old-licenses/fdl-1.2.html',
        'GFDL-1.3' => 'https://www.gnu.org/licenses/fdl-1.3.html',
    );

    /** Hosts whose page is the license's own (preferred over spdx.org), in order. */
    const OFFICIAL_HOSTS = array(
        'creativecommons.org', 'www.apache.org', 'www.mozilla.org', 'www.eclipse.org', 'opendatacommons.org', 'www.opendatacommons.org', 'unlicense.org', 'www.wtfpl.net',
        'joinup.ec.europa.eu', 'cecill.info', 'www.cecill.info', 'www.boost.org', 'www.python.org', 'www.php.net',
        'www.zope.org', 'www.openldap.org', 'www.latex-project.org', 'scripts.sil.org', 'openfontlicense.org',
        'www.nationalarchives.gov.uk', 'www.govdata.de', 'data.norge.no', 'ohwr.org', 'cern-ohl.web.cern.ch',
        'polyformproject.org', 'mariadb.com', 'www.elastic.co', 'firstdonoharm.dev', 'www.mongodb.com',
        'license.coscl.org.cn', 'www.etalab.gouv.fr', 'cdla.dev', 'cdla.io', 'artlibre.org', 'www.unicode.org',
        'www.json.org', 'fsl.software', 'www.ruby-lang.org',
        // the Open Source Initiative's page when the steward has none of the above
        'opensource.org', 'www.opensource.org',
    );

    /**
     * URLs set by hand where SPDX's first official page is gone, moved or not
     * the license (checked 2026-10-11, every one answering 200); '' means
     * the SPDX page.
     */
    const URL_OVERRIDES = array(
        'LPPL-1.3c' => 'https://www.latex-project.org/lppl/lppl-1-3c/',
        'SSPL-1.0' => 'https://www.mongodb.com/legal/licensing/server-side-public-license',
        'PSF-2.0' => 'https://docs.python.org/3/license.html',
        'CNRI-Python-GPL-Compatible' => '',
        'SSH-short' => '',
    );

    /** Hosts that serve no valid https: their URLs keep http. */
    const HTTP_ONLY_HOSTS = array( 'www.cecill.info', 'cecill.info' );

    /** The Creative Commons units, their English names and their terms. */
    const CC_UNITS = array(
        'by' => array( 'Attribution', 'BY', true, false, false, false ),
        'by-sa' => array( 'Attribution-ShareAlike', 'BY-SA', true, false, false, true ),
        'by-nd' => array( 'Attribution-NoDerivs', 'BY-ND', true, false, true, false ),
        'by-nc' => array( 'Attribution-NonCommercial', 'BY-NC', true, true, false, false ),
        'by-nc-sa' => array( 'Attribution-NonCommercial-ShareAlike', 'BY-NC-SA', true, true, false, true ),
        'by-nc-nd' => array( 'Attribution-NonCommercial-NoDerivs', 'BY-NC-ND', true, true, true, false ),
        'by-nd-nc' => array( 'Attribution-NoDerivs-NonCommercial', 'BY-ND-NC', true, true, true, false ),
        'sa' => array( 'ShareAlike', 'SA', false, false, false, true ),
        'nd' => array( 'NoDerivs', 'ND', false, false, true, false ),
        'nc' => array( 'NonCommercial', 'NC', false, true, false, false ),
        'nd-nc' => array( 'NoDerivs-NonCommercial', 'ND-NC', false, true, true, false ),
        'nc-sa' => array( 'NonCommercial-ShareAlike', 'NC-SA', false, true, false, true ),
        'sampling' => array( 'Sampling', 'SAMPLING', true, false, false, false ),
        'sampling+' => array( 'Sampling Plus', 'SAMPLING-PLUS', true, false, false, false ),
        'nc-sampling+' => array( 'NonCommercial Sampling Plus', 'NC-SAMPLING-PLUS', true, true, false, false ),
        'devnations' => array( 'Developing Nations', 'DEVNATIONS', true, false, false, false ),
        'zero' => array( 'CC0', 'CC0', false, false, false, false ),
        'mark' => array( 'Public Domain Mark', 'PDM', false, false, false, false ),
        'certification' => array( 'Public Domain Dedication and Certification', 'PDDC', false, false, false, false ),
    );

    /**
     * The tools Creative Commons retired, with the date and reason of
     * https://creativecommons.org/retiredlicenses/ (read 2026-10-11). Every
     * port of these carries the retired notice in its deed as well.
     */
    const CC_RETIRED = array(
        'sampling+' => array( '2011-09-12', 'Not compatible with any other CC license, inadequate demand' ),
        'nc-sampling+' => array( '2011-09-12', 'Inadequate demand' ),
        'certification' => array( '2010-10-11', 'US-specific; replaced by CC0 and the Public Domain Mark' ),
        'devnations' => array( '2007-06-04', 'Did not permit worldwide non-commercial verbatim sharing, inadequate demand' ),
        'sampling' => array( '2007-06-04', 'Did not permit non-commercial verbatim sharing, inadequate demand' ),
        'nd' => array( '2004-05-25', 'Inadequate demand' ),
        'nd-nc' => array( '2004-05-25', 'Inadequate demand' ),
        'nc' => array( '2004-05-25', 'Inadequate demand' ),
        'nc-sa' => array( '2004-05-25', 'Inadequate demand' ),
        'sa' => array( '2004-05-25', 'Inadequate demand' ),
    );

    /** English names of the jurisdictions of the ported CC licenses. */
    const CC_JURISDICTIONS = array(
        'am' => 'Armenia', 'ar' => 'Argentina', 'at' => 'Austria', 'au' => 'Australia', 'az' => 'Azerbaijan',
        'be' => 'Belgium', 'bg' => 'Bulgaria', 'br' => 'Brazil', 'ca' => 'Canada', 'ch' => 'Switzerland',
        'cl' => 'Chile', 'cn' => 'China Mainland', 'co' => 'Colombia', 'cr' => 'Costa Rica', 'cz' => 'Czech Republic',
        'de' => 'Germany', 'dk' => 'Denmark', 'ec' => 'Ecuador', 'ee' => 'Estonia', 'eg' => 'Egypt', 'es' => 'Spain',
        'fi' => 'Finland', 'fr' => 'France', 'ge' => 'Georgia', 'gr' => 'Greece', 'gt' => 'Guatemala',
        'hk' => 'Hong Kong', 'hr' => 'Croatia', 'hu' => 'Hungary', 'ie' => 'Ireland', 'igo' => 'IGO',
        'il' => 'Israel', 'in' => 'India', 'it' => 'Italy', 'jp' => 'Japan', 'kr' => 'Korea', 'lu' => 'Luxembourg',
        'mk' => 'Macedonia', 'mt' => 'Malta', 'mx' => 'Mexico', 'my' => 'Malaysia', 'nl' => 'Netherlands',
        'no' => 'Norway', 'nz' => 'New Zealand', 'pe' => 'Peru', 'ph' => 'Philippines', 'pl' => 'Poland',
        'pr' => 'Puerto Rico', 'pt' => 'Portugal', 'ro' => 'Romania', 'rs' => 'Serbia', 'scotland' => 'UK: Scotland',
        'se' => 'Sweden', 'sg' => 'Singapore', 'si' => 'Slovenia', 'th' => 'Thailand', 'tw' => 'Taiwan',
        'ug' => 'Uganda', 'uk' => 'UK: England & Wales', 'us' => 'United States', 've' => 'Venezuela',
        'vn' => 'Vietnam', 'za' => 'South Africa',
    );

    /**
     * Builds the catalogue array.
     */
    function build( $spdxFile, $ccFile, $ccRef )
    {
        $spdx = json_decode( (string) @file_get_contents( $spdxFile ), true );
        if ( !is_array( $spdx ) || empty( $spdx['licenses'] ) || empty( $spdx['licenseListVersion'] ) )
        {
            throw new Exception( "$spdxFile is not an SPDX licenses.json" );
        }
        $cc = $this->readCC( $ccFile );

        $entries = array();
        $spdxByID = array();
        foreach ( $spdx['licenses'] as $license )
        {
            $spdxByID[$license['licenseId']] = $license;
        }

        // Creative Commons first: every tool CC has published
        $ccSpdx = array();
        foreach ( $cc as $tool )
        {
            $entry = $this->ccEntry( $tool, $spdxByID );
            if ( $entry['spdx'] )
            {
                $ccSpdx[$entry['id']] = true;
            }
            $entries[$entry['id']] = $entry;
        }

        foreach ( $spdxByID as $id => $license )
        {
            if ( isset( $ccSpdx[$id] ) )
            {
                continue;
            }
            if ( strpos( $id, 'CC-' ) === 0 || strpos( $id, 'CC0' ) === 0 )
            {
                // An SPDX id for a CC tool that the CC index does not list
                $this->warnings[] = "SPDX $id has no Creative Commons tool in the CC index; classified as SPDX only";
            }
            $entries[$id] = $this->spdxEntry( $license );
        }

        foreach ( $this->extraEntries() as $entry )
        {
            if ( isset( $entries[$entry['id']] ) )
            {
                throw new Exception( "extra entry {$entry['id']} exists already" );
            }
            $entries[$entry['id']] = $this->normalise( $entry );
        }

        // Aliases must not shadow an id
        foreach ( $entries as $entry )
        {
            foreach ( isset( $entry['aliases'] ) ? $entry['aliases'] : array() as $alias )
            {
                if ( isset( $entries[$alias] ) )
                {
                    throw new Exception( "alias $alias of {$entry['id']} is an id" );
                }
            }
        }

        uksort( $entries, 'strcasecmp' );

        return array(
            'format' => 1,
            'generator' => 'extension/xrowmetadata/bin/php/xrowmetadatalicenses.php',
            'sources' => array(
                'spdx' => array(
                    'name' => 'SPDX License List',
                    'url' => 'https://github.com/spdx/license-list-data',
                    'version' => $spdx['licenseListVersion'],
                    'tag' => 'v' . $spdx['licenseListVersion'],
                    'release_date' => isset( $spdx['releaseDate'] ) ? substr( $spdx['releaseDate'], 0, 10 ) : '',
                ),
                'creative_commons' => array(
                    'name' => 'Creative Commons legal tools index (config/cc-legal-tools.csv)',
                    'url' => 'https://github.com/creativecommons/cc-legal-tools-data',
                    'ref' => $ccRef,
                    'tools' => count( $cc ),
                    'retired' => 'https://creativecommons.org/retiredlicenses/',
                ),
            ),
            'groups' => self::GROUPS,
            'licenses' => array_values( $entries ),
        );
    }

    /**
     * Rows of cc-legal-tools.csv as arrays keyed by the header.
     */
    function readCC( $file )
    {
        $handle = @fopen( $file, 'r' );
        if ( !$handle )
        {
            throw new Exception( "cannot read $file" );
        }
        $header = fgetcsv( $handle, 0, ',', '"', '\\' );
        if ( !$header || !in_array( 'CANONICAL_URL', $header ) || !in_array( 'UNIT', $header ) )
        {
            throw new Exception( "$file is not cc-legal-tools.csv" );
        }
        $rows = array();
        while ( ( $row = fgetcsv( $handle, 0, ',', '"', '\\' ) ) !== false )
        {
            if ( count( $row ) !== count( $header ) )
            {
                continue;
            }
            $rows[] = array_combine( $header, $row );
        }
        fclose( $handle );
        if ( count( $rows ) < 600 )
        {
            throw new Exception( "$file lists only " . count( $rows ) . " tools" );
        }
        return $rows;
    }

    function ccEntry( array $tool, array $spdxByID )
    {
        $unit = $tool['UNIT'];
        if ( !isset( self::CC_UNITS[$unit] ) )
        {
            throw new Exception( "unknown Creative Commons unit $unit" );
        }
        list( $unitName, $code, $by, $nc, $nd, $sa ) = self::CC_UNITS[$unit];
        $version = $tool['VERSION'];
        $jurisdiction = $tool['JURISDICTION'];
        $url = $this->https( $tool['CANONICAL_URL'] );
        $spdxID = $tool['SPDX_IDENTIFIER'];
        if ( $spdxID !== '' && !isset( $spdxByID[$spdxID] ) )
        {
            $this->warnings[] = "CC lists SPDX id $spdxID for $url, which SPDX does not have";
            $spdxID = '';
        }
        if ( $spdxID === '' )
        {
            // The CC index leaves some SPDX ids out (e.g. CC-BY-NC-3.0-IGO)
            $guess = 'CC-' . $code . '-' . $version . ( $jurisdiction !== '' ? '-' . strtoupper( $jurisdiction ) : '' );
            if ( isset( $spdxByID[$guess] ) )
            {
                $spdxID = $guess;
            }
        }
        $spdx = $spdxID !== '' ? $spdxByID[$spdxID] : null;

        if ( $spdxID !== '' )
        {
            $id = $spdxID;
        }
        else
        {
            $id = 'LicenseRef-CC-' . $code . '-' . $version
                . ( $jurisdiction !== '' ? '-' . strtoupper( $jurisdiction ) : '' );
        }

        if ( $unit === 'zero' || $unit === 'mark' || $unit === 'certification' )
        {
            $name = ( $unit === 'zero' ? 'Creative Commons Zero v1.0 Universal' : ( $unit === 'mark' ? 'Public Domain Mark 1.0 Universal' : 'Creative Commons Public Domain Dedication and Certification' ) );
        }
        else
        {
            if ( $version === '4.0' )
            {
                $unitName = str_replace( 'NoDerivs', 'NoDerivatives', $unitName );
            }
            $where = $jurisdiction !== ''
                ? ( isset( self::CC_JURISDICTIONS[$jurisdiction] ) ? self::CC_JURISDICTIONS[$jurisdiction] : strtoupper( $jurisdiction ) )
                : ( $version === '4.0' ? 'International' : ( $version === '3.0' ? 'Unported' : 'Generic' ) );
            $name = 'Creative Commons ' . $unitName . ' ' . $version . ' ' . $where;
        }

        $retired = isset( self::CC_RETIRED[$unit] );
        $publicDomain = in_array( $unit, array( 'zero', 'mark', 'certification' ) );
        $groups = array( 'content' );
        $type = null;
        $copyleft = 'none';
        if ( $publicDomain )
        {
            $groups[] = 'public_domain';
            $type = 'public_domain';
        }
        elseif ( $nc )
        {
            $groups[] = 'non_free';
            $type = 'non_commercial';
        }
        elseif ( $nd )
        {
            $groups[] = 'non_free';
            $type = 'no_derivatives';
        }
        elseif ( $unit === 'sampling' || $unit === 'sampling+' || $unit === 'devnations' )
        {
            // Sampling: no verbatim copying of the whole work; Developing
            // Nations: the freedoms hold only in developing nations
            $groups[] = 'non_free';
            $type = 'custom';
        }
        elseif ( $sa )
        {
            $groups[] = 'copyleft';
            $copyleft = 'strong';
            $type = 'copyleft';
        }
        else
        {
            $groups[] = 'permissive';
            $type = 'permissive';
        }
        $fsf = $spdx && isset( $spdx['isFsfLibre'] ) ? (bool) $spdx['isFsfLibre'] : false;
        if ( $fsf && !in_array( 'non_free', $groups ) )
        {
            $groups[] = 'free_software';
        }

        $entry = array(
            'id' => $id,
            'name' => $spdx ? preg_replace( '/\s+/', ' ', trim( $spdx['name'] ) ) : $name,
            'url' => $url,
            'spdx' => $spdx !== null,
            'groups' => $groups,
            'type' => $type,
            'copyleft' => $copyleft,
            'osi' => $spdx ? (bool) $spdx['isOsiApproved'] : false,
            'fsf_libre' => $fsf,
            'deprecated' => $retired || ( $spdx && $spdx['isDeprecatedLicenseId'] ),
            'terms' => array(
                'commercial' => !$nc,
                'derivatives' => !$nd,
                'share_alike' => $sa,
                'attribution' => $by,
            ),
            'legalcode' => $url . 'legalcode',
            'cc' => array( 'unit' => $unit, 'version' => $version, 'jurisdiction' => $jurisdiction ),
        );
        if ( $tool['TITLE'] !== '' && $tool['TITLE'] !== $entry['name'] && strpos( $tool['LANGUAGE_DEFAULT'], 'en' ) !== 0 )
        {
            $entry['name_local'] = $tool['TITLE'];
        }
        if ( $publicDomain && $unit === 'mark' )
        {
            $entry['note'] = 'A mark for works free of known copyright, not a license or a dedication.';
            $entry['aliases'] = array( 'LicenseRef-PDM-1.0' );
            $entry['terms'] = array( 'commercial' => true, 'derivatives' => true, 'share_alike' => false, 'attribution' => false );
        }
        if ( $publicDomain && $unit === 'certification' )
        {
            $entry['aliases'] = array( 'LicenseRef-CC-PD' );
            $entry['see_also'] = array( 'https://creativecommons.org/licenses/publicdomain/' );
        }
        if ( $publicDomain && $unit !== 'mark' )
        {
            $entry['terms'] = array( 'commercial' => true, 'derivatives' => true, 'share_alike' => false, 'attribution' => false );
        }
        if ( in_array( $unit, array( 'by', 'by-sa' ) ) || $unit === 'zero' || $unit === 'mark' )
        {
            // Approved for Free Cultural Works
            $entry['free_culture'] = true;
        }
        if ( $retired )
        {
            $entry['retired_by_cc'] = true;
            $entry['retired_on'] = self::CC_RETIRED[$unit][0];
            $entry['retired_reason'] = self::CC_RETIRED[$unit][1];
        }
        if ( $jurisdiction !== '' && $unit !== 'certification' )
        {
            $entry['ported'] = true;
        }
        return $this->normalise( $entry );
    }

    function spdxEntry( array $license )
    {
        $id = $license['licenseId'];
        $osi = (bool) $license['isOsiApproved'];
        $fsfKnown = array_key_exists( 'isFsfLibre', $license );
        $fsf = $fsfKnown ? (bool) $license['isFsfLibre'] : false;
        $deprecated = (bool) $license['isDeprecatedLicenseId'];

        $copyleft = $this->lookup( self::COPYLEFT, $id, 'none' );
        $nonFree = $this->lookup( self::NON_FREE, $id, null );
        $publicDomain = in_array( $id, self::PUBLIC_DOMAIN );
        $content = $this->matchesAny( self::CONTENT, $id );

        $groups = array();
        if ( $osi )
        {
            $groups[] = 'open_source';
        }
        if ( $fsf )
        {
            $groups[] = 'free_software';
        }
        $reviewed = true;
        if ( $nonFree !== null )
        {
            $type = $nonFree;
            $groups[] = 'non_free';
        }
        elseif ( $copyleft !== 'none' )
        {
            $groups[] = 'copyleft';
            $type = 'copyleft';
        }
        elseif ( $publicDomain )
        {
            $type = 'public_domain';
        }
        elseif ( $osi || $fsf || preg_match( self::PERMISSIVE_PATTERN, $id ) )
        {
            $type = 'permissive';
        }
        elseif ( $fsfKnown && !$fsf && !$osi )
        {
            // The FSF lists it as non-free
            $groups[] = 'non_free';
            $type = 'custom';
        }
        else
        {
            $type = 'other';
            $reviewed = false;
        }
        if ( $type === 'permissive' || ( $publicDomain && $id === '0BSD' ) )
        {
            $groups[] = 'permissive';
        }
        if ( $content )
        {
            $groups[] = 'content';
        }
        if ( $publicDomain )
        {
            $groups[] = 'public_domain';
        }

        $noAttribution = $publicDomain || in_array( $id, array( 'WTFPL', 'Unlicense', '0BSD', 'MIT-0', 'FSFAP', 'FSFAP-no-warranty-disclaimer', 'DL-DE-ZERO-2.0', 'Informatica' ) );
        $terms = $reviewed ? array(
            'commercial' => !in_array( $type, array( 'non_commercial' ) ),
            'derivatives' => $type !== 'no_derivatives',
            'share_alike' => $copyleft !== 'none',
            'attribution' => !$noAttribution,
        ) : array( 'commercial' => null, 'derivatives' => null, 'share_alike' => null, 'attribution' => null );
        if ( $type === 'proprietary' )
        {
            $terms = array( 'commercial' => false, 'derivatives' => false, 'share_alike' => false, 'attribution' => null );
        }

        $entry = array(
            'id' => $id,
            'name' => preg_replace( '/\s+/', ' ', trim( $license['name'] ) ),
            'url' => $this->spdxURL( $license ),
            'spdx' => true,
            'groups' => $groups,
            'type' => $type,
            'copyleft' => $copyleft,
            'osi' => $osi,
            'fsf_libre' => $fsf,
            'deprecated' => $deprecated,
            'terms' => $terms,
        );
        if ( !$reviewed )
        {
            $entry['reviewed'] = false;
        }
        if ( $deprecated )
        {
            $replacement = $this->replacement( $id );
            if ( $replacement !== null )
            {
                $entry['replaced_by'] = $replacement;
            }
        }
        if ( $id === 'WTFPL' )
        {
            $entry['note'] = 'A permissive license, not a public domain dedication.';
        }
        if ( $id === 'QPL-1.0' || $id === 'QPL-1.0-INRIA-2004' )
        {
            $entry['note'] = 'Changes may only be distributed separately, as patches, and stay under the QPL; software that links with it must be distributed with its source and be freely redistributable. Free but GPL-incompatible according to the FSF.'
                . ( $id === 'QPL-1.0-INRIA-2004' ? ' The terms of QPL-1.0 under French law; OSI and FSF list only QPL-1.0, not this variant.' : '' );
        }
        if ( $id === 'Informatica' )
        {
            $entry['note'] = 'Use, modification and redistribution "without restriction"; no conditions besides the warranty disclaimer. Neither OSI nor FSF has reviewed it.';
        }
        if ( $id === '0BSD' || $id === 'Unlicense' )
        {
            $entry['note'] = 'Public-domain-equivalent: no conditions at all.';
        }
        return $this->normalise( $entry );
    }

    /**
     * The non-SPDX entries: retired Creative Commons tools that are not in
     * the CC index, the public domain statuses that are not licenses, and
     * the non-free pseudo licenses.
     */
    function extraEntries()
    {
        $none = array( 'commercial' => true, 'derivatives' => true, 'share_alike' => false, 'attribution' => false );
        return array(
            array(
                'id' => 'LicenseRef-AllRightsReserved', 'name' => 'All rights reserved', 'url' => '', 'spdx' => false,
                'groups' => array( 'non_free' ), 'type' => 'proprietary', 'copyleft' => 'none',
                'terms' => array( 'commercial' => false, 'derivatives' => false, 'share_alike' => false, 'attribution' => null ),
                'note' => 'No license is granted: every use beyond what the law allows needs the holder\'s permission.',
            ),
            array(
                'id' => 'LicenseRef-Custom', 'name' => 'Custom terms', 'url' => '', 'spdx' => false,
                'groups' => array( 'non_free' ), 'type' => 'custom', 'copyleft' => 'none',
                'terms' => array( 'commercial' => null, 'derivatives' => null, 'share_alike' => null, 'attribution' => null ),
                'per_object' => true,
                'note' => 'The terms are given per object: license_custom_url and license_custom_terms of the metadata attribute.',
            ),
            array(
                'id' => 'LicenseRef-NoKnownCopyright', 'name' => 'No known copyright restrictions',
                'url' => 'https://www.flickr.com/commons/usage/', 'spdx' => false,
                'groups' => array( 'public_domain', 'content' ), 'type' => 'public_domain', 'copyleft' => 'none', 'terms' => $none,
                'note' => 'A statement by the publishing institution, not a license; prefer the Public Domain Mark when the status is known.',
            ),
            array(
                'id' => 'LicenseRef-USGov-PD', 'name' => 'Work of the United States Government (17 U.S.C. § 105)',
                'url' => 'https://www.law.cornell.edu/uscode/text/17/105', 'spdx' => false,
                'groups' => array( 'public_domain', 'content' ), 'type' => 'public_domain', 'copyleft' => 'none', 'terms' => $none,
                'note' => 'Public domain in the United States; other countries may differ.',
            ),
            array(
                'id' => 'LicenseRef-PD-Expired', 'name' => 'Public domain (copyright expired)',
                'url' => 'https://creativecommons.org/publicdomain/mark/1.0/', 'spdx' => false,
                'groups' => array( 'public_domain', 'content' ), 'type' => 'public_domain', 'copyleft' => 'none', 'terms' => $none,
                'note' => 'The term of protection has ended; it depends on the country (often 70 years after the author\'s death).',
            ),
            array(
                'id' => 'LicenseRef-CC-GNU-GPL-2.0', 'name' => 'Creative Commons GNU General Public License (CC-GNU GPL) 2.0',
                'url' => 'https://web.archive.org/web/20060104035536/http://creativecommons.org/licenses/GPL/2.0/', 'spdx' => false,
                'groups' => array( 'open_source', 'free_software', 'copyleft' ), 'type' => 'copyleft', 'copyleft' => 'strong',
                'osi' => true, 'fsf_libre' => true, 'deprecated' => true, 'retired_by_cc' => true, 'same_as' => 'GPL-2.0-or-later',
                'see_also' => array( 'https://www.gnu.org/licenses/old-licenses/gpl-2.0.html' ),
                'terms' => array( 'commercial' => true, 'derivatives' => true, 'share_alike' => true, 'attribution' => true ),
                'note' => 'The GNU GPL 2.0 with a Creative Commons deed and metadata; CC no longer serves it (the old URL is archived).',
            ),
            array(
                'id' => 'LicenseRef-CC-GNU-LGPL-2.1', 'name' => 'Creative Commons GNU Lesser General Public License (CC-GNU LGPL) 2.1',
                'url' => 'https://web.archive.org/web/20071025015108/http://creativecommons.org/licenses/LGPL/2.1/', 'spdx' => false,
                'groups' => array( 'open_source', 'free_software', 'copyleft' ), 'type' => 'copyleft', 'copyleft' => 'weak',
                'osi' => true, 'fsf_libre' => true, 'deprecated' => true, 'retired_by_cc' => true, 'same_as' => 'LGPL-2.1-or-later',
                'see_also' => array( 'https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html' ),
                'terms' => array( 'commercial' => true, 'derivatives' => true, 'share_alike' => true, 'attribution' => true ),
                'note' => 'The GNU LGPL 2.1 with a Creative Commons deed and metadata; CC no longer serves it (the old URL is archived).',
            ),
            array(
                'id' => 'LicenseRef-CC-MusicSharing', 'name' => 'Creative Commons Music Sharing License',
                'url' => 'https://web.archive.org/web/20090715051546/http://creativecommons.org/license/music', 'spdx' => false,
                'groups' => array( 'content', 'non_free' ), 'type' => 'non_commercial', 'copyleft' => 'none',
                'deprecated' => true, 'retired_by_cc' => true, 'same_as' => 'CC-BY-NC-ND-2.0',
                'terms' => array( 'commercial' => false, 'derivatives' => false, 'share_alike' => false, 'attribution' => true ),
                'note' => 'Identical terms to Attribution-NonCommercial-NoDerivs; a name CC used for music, no longer offered.',
            ),
            array(
                'id' => 'LicenseRef-CC-FoundersCopyright', 'name' => 'Creative Commons Founders\' Copyright',
                'url' => 'https://web.archive.org/web/20111123152540/http://creativecommons.org/projects/founderscopyright', 'spdx' => false,
                'groups' => array( 'non_free' ), 'type' => 'proprietary', 'copyleft' => 'none',
                'deprecated' => true, 'retired_by_cc' => true,
                'terms' => array( 'commercial' => false, 'derivatives' => false, 'share_alike' => false, 'attribution' => null ),
                'note' => 'Copyright kept for 14 (or 28) years, then the work is given to the public domain; CC no longer offers it.',
            ),
        );
    }

    /**
     * $entry with every key of the format, in a fixed order.
     */
    function normalise( array $entry )
    {
        $base = array(
            'id' => '', 'name' => '', 'url' => '', 'spdx' => false, 'groups' => array(), 'type' => 'other',
            'copyleft' => 'none', 'osi' => false, 'fsf_libre' => false, 'deprecated' => false,
            'terms' => array( 'commercial' => null, 'derivatives' => null, 'share_alike' => null, 'attribution' => null ),
        );
        $result = array();
        foreach ( $base as $key => $default )
        {
            $result[$key] = array_key_exists( $key, $entry ) ? $entry[$key] : $default;
        }
        foreach ( $entry as $key => $value )
        {
            if ( !array_key_exists( $key, $result ) )
            {
                $result[$key] = $value;
            }
        }
        // Groups in the order of GROUPS, once each
        $result['groups'] = array_values( array_intersect( self::GROUPS, array_unique( $result['groups'] ) ) );
        if ( !empty( $result['retired_by_cc'] ) )
        {
            $result['deprecated'] = true;
        }
        return $result;
    }

    function lookup( array $map, $id, $default )
    {
        if ( isset( $map[$id] ) )
        {
            return $map[$id];
        }
        foreach ( $map as $key => $value )
        {
            if ( substr( $key, -1 ) === '*' && strpos( $id, substr( $key, 0, -1 ) ) === 0 )
            {
                return $value;
            }
        }
        return $default;
    }

    function matchesAny( array $list, $id )
    {
        foreach ( $list as $key )
        {
            if ( $key === $id || ( substr( $key, -1 ) === '*' && strpos( $id, substr( $key, 0, -1 ) ) === 0 ) )
            {
                return true;
            }
        }
        return false;
    }

    /**
     * The SPDX expression that replaces a deprecated id, when there is one.
     */
    function replacement( $id )
    {
        if ( preg_match( '/^((?:A|L)?GPL|GFDL)-(\d\.\d)(\+)?$/', $id, $m ) )
        {
            return $m[1] . '-' . $m[2] . ( isset( $m[3] ) && $m[3] === '+' ? '-or-later' : '-only' );
        }
        $map = array(
            'GPL-2.0-with-autoconf-exception' => 'GPL-2.0-only WITH Autoconf-exception-2.0',
            'GPL-2.0-with-bison-exception' => 'GPL-2.0-or-later WITH Bison-exception-2.2',
            'GPL-2.0-with-classpath-exception' => 'GPL-2.0-only WITH Classpath-exception-2.0',
            'GPL-2.0-with-font-exception' => 'GPL-2.0-only WITH Font-exception-2.0',
            'GPL-2.0-with-GCC-exception' => 'GPL-2.0-only WITH GCC-exception-2.0',
            'GPL-3.0-with-autoconf-exception' => 'GPL-3.0-only WITH Autoconf-exception-3.0',
            'GPL-3.0-with-GCC-exception' => 'GPL-3.0-only WITH GCC-exception-3.1',
            'eCos-2.0' => 'GPL-2.0-or-later WITH eCos-exception-2.0',
            'wxWindows' => 'LGPL-2.0-or-later WITH WxWindows-exception-3.1',
            'BSD-2-Clause-FreeBSD' => 'BSD-2-Clause-Views',
            'BSD-2-Clause-NetBSD' => 'BSD-2-Clause',
            'bzip2-1.0.5' => 'bzip2-1.0.6',
            'Net-SNMP' => 'MIT-CMU AND BSD-3-Clause',
            'StandardML-NJ' => 'SMLNJ',
        );
        return isset( $map[$id] ) ? $map[$id] : null;
    }

    /**
     * The license's own page: gnu.org for the GNU licenses, the first page on
     * an official host, else the SPDX page.
     */
    function spdxURL( array $license )
    {
        $id = $license['licenseId'];
        if ( preg_match( '/^((?:A|L)?GPL|GFDL)-(\d\.\d)/', $id, $m ) && isset( self::GNU_URL[$m[1] . '-' . $m[2]] ) )
        {
            return self::GNU_URL[$m[1] . '-' . $m[2]];
        }
        if ( array_key_exists( $id, self::URL_OVERRIDES ) )
        {
            return self::URL_OVERRIDES[$id] !== '' ? self::URL_OVERRIDES[$id] : 'https://spdx.org/licenses/' . $id . '.html';
        }
        if ( $id === 'AGPL-1.0' || strpos( $id, 'AGPL-1.0-' ) === 0 )
        {
            return 'https://spdx.org/licenses/' . $id . '.html';
        }
        $seeAlso = isset( $license['seeAlso'] ) ? (array) $license['seeAlso'] : array();
        foreach ( self::OFFICIAL_HOSTS as $host )
        {
            foreach ( $seeAlso as $url )
            {
                $parts = parse_url( $url );
                if ( isset( $parts['host'] ) && strtolower( $parts['host'] ) === $host )
                {
                    // joinup.ec.europa.eu hosts the EUPL, and code of other projects
                    if ( $host === 'joinup.ec.europa.eu' && strpos( $id, 'EUPL-' ) !== 0 )
                    {
                        continue;
                    }
                    $url = in_array( $host, self::HTTP_ONLY_HOSTS, true ) ? trim( $url ) : $this->https( $url );
                    if ( $host === 'www.opensource.org' )
                    {
                        $url = str_replace( '://www.opensource.org/', '://opensource.org/', $url );
                    }
                    return $url;
                }
            }
        }
        return 'https://spdx.org/licenses/' . $id . '.html';
    }

    function https( $url )
    {
        return preg_replace( '#^http://#', 'https://', trim( $url ) );
    }

    /**
     * The catalogue as JSON: the header pretty-printed, one license per line
     * (a diff then shows the licenses that changed).
     */
    function encode( array $catalogue )
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $licenses = $catalogue['licenses'];
        unset( $catalogue['licenses'] );
        $head = json_encode( $catalogue, $flags | JSON_PRETTY_PRINT );
        $lines = array();
        foreach ( $licenses as $entry )
        {
            $lines[] = '    ' . json_encode( $entry, $flags );
        }
        return substr( $head, 0, -2 ) . ",\n    \"licenses\": [\n" . implode( ",\n", $lines ) . "\n    ]\n}\n";
    }

    function stats( array $catalogue )
    {
        $counts = array();
        $other = 0;
        foreach ( $catalogue['licenses'] as $entry )
        {
            foreach ( $entry['groups'] as $group )
            {
                $counts[$group] = isset( $counts[$group] ) ? $counts[$group] + 1 : 1;
            }
            if ( !$entry['groups'] )
            {
                $other++;
            }
        }
        $text = count( $catalogue['licenses'] ) . ' licenses, SPDX ' . $catalogue['sources']['spdx']['version'];
        foreach ( $counts as $group => $count )
        {
            $text .= ", $group $count";
        }
        return $text . ", unclassified $other";
    }
}

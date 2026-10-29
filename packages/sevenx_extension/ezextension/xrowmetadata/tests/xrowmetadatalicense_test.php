<?php
/**
 * Unit tests of xrowMetaDataLicense: the catalogue, its classification, the
 * allowlist, the precedence of resolve() and the per-request cache of
 * current(). Nothing is written: the nodes are stub objects, the settings are
 * set in memory and restored, and current() is asked for a node id that does
 * not exist (one read, no row).
 *
 *   php bin/php/ezexec.php extension/xrowmetadata/tests/xrowmetadatalicense_test.php -s admin
 *
 * Prints PASS/FAIL per check and a summary; the exit code is 1 on a failure.
 *
 * @license GNU General Public License v2.0 (or any later version)
 */

if ( !class_exists( 'xrowMetaDataLicense', false ) )
{
    // From the clone (or before the autoload array is regenerated)
    require_once dirname( __DIR__ ) . '/classes/xrowmetadatalicense.php';
}

/* Stubs with the methods resolve() uses of the kernel classes */
class xrowMetaDataLicenseTestClassAttribute
{
    public $values;
    function __construct( array $default ) { $this->values = array( 'data_text5' => serialize( $default ) ); }
    function attribute( $name ) { return isset( $this->values[$name] ) ? $this->values[$name] : null; }
}
class xrowMetaDataLicenseTestAttribute
{
    public $content;
    public $classAttribute;
    public $dataText;
    public $type;
    function __construct( $content, $classAttribute, $dataText = '', $type = 'xrowmetadata' )
    {
        $this->content = $content;
        $this->classAttribute = $classAttribute;
        $this->dataText = $dataText;
        $this->type = $type;
    }
    function attribute( $name )
    {
        if ( $name === 'data_type_string' ) return $this->type;
        if ( $name === 'data_text' ) return $this->dataText;
        return null;
    }
    function content() { return $this->content; }
    function contentClassAttribute() { return $this->classAttribute; }
}
class xrowMetaDataLicenseTestObject
{
    public $map;
    function __construct( array $map ) { $this->map = $map; }
    function dataMap() { return $this->map; }
    function attribute( $name ) { return $name === 'main_node_id' ? 4242 : null; }
}
class xrowMetaDataLicenseTestNode
{
    public $object;
    public $id;
    function __construct( $object, $id = 4242 ) { $this->object = $object; $this->id = $id; }
    function object() { return $this->object; }
    function attribute( $name ) { return $name === 'node_id' ? $this->id : null; }
}

$xmlt = array( 'pass' => 0, 'fail' => 0 );
$check = function ( $label, $ok, $detail = '' ) use ( &$xmlt ) {
    if ( $ok )
    {
        $xmlt['pass']++;
        echo "PASS: $label\n";
    }
    else
    {
        $xmlt['fail']++;
        echo "FAIL: $label" . ( $detail !== '' ? " ($detail)" : '' ) . "\n";
    }
};
$entry = function ( $id ) { return xrowMetaDataLicense::byID( $id ); };
$has = function ( $id, array $groups, array $notGroups = array() ) use ( $entry ) {
    $e = $entry( $id );
    if ( !$e ) return false;
    foreach ( $groups as $g ) if ( !in_array( $g, $e['groups'], true ) ) return false;
    foreach ( $notGroups as $g ) if ( in_array( $g, $e['groups'], true ) ) return false;
    return true;
};
$describe = function ( $id ) use ( $entry ) {
    $e = $entry( $id );
    return $e ? 'groups=' . implode( ',', $e['groups'] ) . ' type=' . $e['type'] . ' copyleft=' . $e['copyleft'] : 'missing';
};

/* Settings: every key set in memory, restored at the end */
$ini = eZINI::instance( 'xrowmetadata.ini' );
$keys = array( 'Enabled', 'DefaultLicense', 'DefaultHolder', 'AllowedGroups', 'AllowedLicenses' );
$saved = array();
foreach ( $keys as $key )
{
    $saved[$key] = $ini->hasVariable( 'LicenseSettings', $key ) ? $ini->variable( 'LicenseSettings', $key ) : null;
}
$setSettings = function ( array $values ) use ( $ini ) {
    $defaults = array( 'Enabled' => 'true', 'DefaultLicense' => '', 'DefaultHolder' => '', 'AllowedGroups' => array(), 'AllowedLicenses' => array() );
    foreach ( array_merge( $defaults, $values ) as $key => $value )
    {
        $ini->setVariable( 'LicenseSettings', $key, $value );
    }
};
$savedServer = array(
    'REQUEST_TIME_FLOAT' => isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ? $_SERVER['REQUEST_TIME_FLOAT'] : null,
    'REQUEST_URI' => isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null,
);
$savedURI = isset( $GLOBALS['eZURIRequestInstance'] ) ? $GLOBALS['eZURIRequestInstance'] : null;

xrowMetaDataLicense::reset();
// The catalogue of this checkout, also when the class was autoloaded from an installed copy
$savedCatalogueFile = xrowMetaDataLicense::$catalogueFile;
xrowMetaDataLicense::$catalogueFile = dirname( __DIR__ ) . '/share/licenses/licenses.json';
$setSettings( array() );

/* 1. The catalogue */
$all = xrowMetaDataLicense::all();
$info = xrowMetaDataLicense::catalogueInfo();
$check( 'catalogue loads (' . count( $all ) . ' licenses, file ' . xrowMetaDataLicense::catalogueFile() . ')', count( $all ) > 1200 );
$check( 'catalogue records the SPDX version and date',
        !empty( $info['sources']['spdx']['version'] ) && !empty( $info['sources']['spdx']['release_date'] ),
        json_encode( isset( $info['sources']['spdx'] ) ? $info['sources']['spdx'] : null ) );
$ids = array();
$bad = array();
$ccTools = 0;
foreach ( $all as $e )
{
    $ids[$e['id']] = true;
    foreach ( array( 'id', 'name', 'url', 'spdx', 'groups', 'type', 'copyleft', 'osi', 'fsf_libre', 'deprecated', 'terms' ) as $key )
    {
        if ( !array_key_exists( $key, $e ) ) $bad[] = $e['id'] . " lacks $key";
    }
    // https, or none; www.cecill.info serves no valid https and keeps http
    if ( $e['url'] !== '' && strpos( $e['url'], 'https://' ) !== 0 && strpos( $e['url'], 'http://www.cecill.info/' ) !== 0 ) $bad[] = $e['id'] . ' url ' . $e['url'];
    if ( !in_array( $e['copyleft'], array( 'strong', 'weak', 'network', 'none' ), true ) ) $bad[] = $e['id'] . ' copyleft';
    if ( array_diff( $e['groups'], xrowMetaDataLicense::$groupNames ) ) $bad[] = $e['id'] . ' unknown group';
    if ( isset( $e['cc'] ) ) $ccTools++;
}
$check( 'every entry has the contract keys, an https URL (or none) and known groups', !$bad, implode( '; ', array_slice( $bad, 0, 5 ) ) );
$check( 'ids are unique', count( $ids ) === count( $all ) );
$check( "every Creative Commons tool of the CC index is in it ($ccTools)", $ccTools === (int) $info['sources']['creative_commons']['tools'] && $ccTools >= 639 );
$groups = xrowMetaDataLicense::groups();
$check( 'groups() lists every group incl. public_domain and other', !array_diff( array_merge( xrowMetaDataLicense::$groupNames, array( 'other' ) ), array_keys( $groups ) ) && in_array( 'CC0-1.0', $groups['public_domain'], true ) );

/* 2. Classification of representative licenses */
$check( 'GPL-3.0-or-later: strong copyleft, free software, open source',
        $has( 'GPL-3.0-or-later', array( 'copyleft', 'free_software', 'open_source' ) ) && $entry( 'GPL-3.0-or-later' )['copyleft'] === 'strong'
        && $entry( 'GPL-3.0-or-later' )['url'] === 'https://www.gnu.org/licenses/gpl-3.0.html', $describe( 'GPL-3.0-or-later' ) );
$check( 'GPL-2.0-or-later: strong copyleft, GNU URL', $entry( 'GPL-2.0-or-later' )['copyleft'] === 'strong'
        && $entry( 'GPL-2.0-or-later' )['url'] === 'https://www.gnu.org/licenses/old-licenses/gpl-2.0.html', $describe( 'GPL-2.0-or-later' ) );
$check( 'AGPL-3.0-only: network copyleft', $entry( 'AGPL-3.0-only' )['copyleft'] === 'network' && $has( 'AGPL-3.0-only', array( 'copyleft', 'open_source' ) ), $describe( 'AGPL-3.0-only' ) );
$check( 'LGPL-2.1-only: weak copyleft', $entry( 'LGPL-2.1-only' )['copyleft'] === 'weak', $describe( 'LGPL-2.1-only' ) );
$check( 'MPL-2.0: weak copyleft', $entry( 'MPL-2.0' )['copyleft'] === 'weak', $describe( 'MPL-2.0' ) );
$check( 'EPL-2.0 and CDDL-1.0: weak copyleft', $entry( 'EPL-2.0' )['copyleft'] === 'weak' && $entry( 'CDDL-1.0' )['copyleft'] === 'weak' );
$check( 'MIT: permissive, open source, free software, not copyleft',
        $has( 'MIT', array( 'permissive', 'open_source', 'free_software' ), array( 'copyleft', 'non_free' ) ), $describe( 'MIT' ) );
$check( 'Apache-2.0: permissive, open source', $has( 'Apache-2.0', array( 'permissive', 'open_source' ), array( 'copyleft' ) )
        && $entry( 'Apache-2.0' )['url'] === 'https://www.apache.org/licenses/LICENSE-2.0', $describe( 'Apache-2.0' ) );
$check( 'BSD-3-Clause: permissive', $has( 'BSD-3-Clause', array( 'permissive', 'open_source' ) ) );
$e = $entry( 'CC-BY-SA-4.0' );
$check( 'CC-BY-SA-4.0: content, copyleft, share-alike, commercial allowed',
        $has( 'CC-BY-SA-4.0', array( 'content', 'copyleft' ), array( 'non_free' ) ) && $e['terms']['share_alike'] === true && $e['terms']['commercial'] === true
        && $e['url'] === 'https://creativecommons.org/licenses/by-sa/4.0/', $describe( 'CC-BY-SA-4.0' ) );
$e = $entry( 'CC-BY-4.0' );
$check( 'CC-BY-4.0: content, permissive, attribution', $has( 'CC-BY-4.0', array( 'content', 'permissive' ) ) && $e['terms']['attribution'] === true && $e['terms']['share_alike'] === false );
$e = $entry( 'CC-BY-NC-4.0' );
$check( 'CC-BY-NC-4.0: non_free, type non_commercial, no commercial use',
        $has( 'CC-BY-NC-4.0', array( 'content', 'non_free' ), array( 'free_software', 'permissive' ) ) && $e['type'] === 'non_commercial' && $e['terms']['commercial'] === false, $describe( 'CC-BY-NC-4.0' ) );
$e = $entry( 'CC-BY-ND-4.0' );
$check( 'CC-BY-ND-4.0: non_free, type no_derivatives', $e['type'] === 'no_derivatives' && $e['terms']['derivatives'] === false && in_array( 'non_free', $e['groups'], true ) );
$e = $entry( 'CC-BY-NC-ND-4.0' );
$check( 'CC-BY-NC-ND-4.0: non_free, no commercial use, no derivatives', $e['terms']['commercial'] === false && $e['terms']['derivatives'] === false && in_array( 'non_free', $e['groups'], true ) );
$e = $entry( 'CC0-1.0' );
$check( 'CC0-1.0: public domain, content, no attribution', $has( 'CC0-1.0', array( 'public_domain', 'content' ), array( 'non_free' ) ) && $e['terms']['attribution'] === false );
$e = $entry( 'LicenseRef-AllRightsReserved' );
$check( 'All rights reserved: non_free proprietary, no URL', $e && $e['type'] === 'proprietary' && $e['url'] === '' && in_array( 'non_free', $e['groups'], true ) && $e['terms']['commercial'] === false );
$e = $entry( 'LicenseRef-Custom' );
$check( 'Custom terms: non_free custom, given per object', $e && $e['type'] === 'custom' && !empty( $e['per_object'] ) );
$check( 'SSPL-1.0: not open source, non_free source_available', $has( 'SSPL-1.0', array( 'non_free' ), array( 'open_source' ) ) && $entry( 'SSPL-1.0' )['type'] === 'source_available' );
$check( 'WTFPL: permissive, not public domain', $has( 'WTFPL', array( 'permissive' ), array( 'public_domain' ) ) );
$e = $entry( 'QPL-1.0' );
$check( 'QPL-1.0: weak copyleft, open source, free software, not permissive',
        $has( 'QPL-1.0', array( 'copyleft', 'open_source', 'free_software' ), array( 'permissive', 'non_free' ) ) && $e['copyleft'] === 'weak'
        && $e['terms']['share_alike'] === true && strpos( $e['note'], 'patches' ) !== false, $describe( 'QPL-1.0' ) );
$e = $entry( 'QPL-1.0-INRIA-2004' );
$check( 'QPL-1.0-INRIA-2004: weak copyleft like QPL-1.0, reviewed, but not OSI/FSF listed itself',
        $e && $e['type'] === 'copyleft' && $e['copyleft'] === 'weak' && !isset( $e['reviewed'] ) && $e['groups'] === array( 'copyleft' )
        && $e['osi'] === false && $e['fsf_libre'] === false && $e['terms'] === $entry( 'QPL-1.0' )['terms'], $describe( 'QPL-1.0-INRIA-2004' ) );
$e = $entry( 'Informatica' );
$check( 'Informatica: permissive without conditions, not OSI/FSF, not public domain',
        $e && $e['type'] === 'permissive' && $e['groups'] === array( 'permissive' ) && !isset( $e['reviewed'] )
        && $e['terms'] === array( 'commercial' => true, 'derivatives' => true, 'share_alike' => false, 'attribution' => false ), $describe( 'Informatica' ) );
$unclassified = array();
foreach ( $all as $e )
{
    if ( !$e['groups'] || isset( $e['reviewed'] ) || $e['type'] === 'other' )
    {
        $unclassified[] = $e['id'];
    }
}
$check( 'no entry is unclassified (groups, type, reviewed)', !$unclassified, implode( ', ', $unclassified ) );
$check( 'Unlicense, 0BSD, PDDL-1.0: public domain', $has( 'Unlicense', array( 'public_domain' ) ) && $has( '0BSD', array( 'public_domain', 'permissive' ) ) && $has( 'PDDL-1.0', array( 'public_domain', 'content' ) ) );
$check( 'ODbL-1.0 and ODC-By-1.0: data content, not public domain', $has( 'ODbL-1.0', array( 'content', 'copyleft' ), array( 'public_domain' ) ) && $has( 'ODC-By-1.0', array( 'content' ), array( 'public_domain' ) ) );
$check( 'US government works and expired copyright: public domain pseudo entries',
        $has( 'LicenseRef-USGov-PD', array( 'public_domain' ) ) && $has( 'LicenseRef-PD-Expired', array( 'public_domain' ) ) && $has( 'LicenseRef-NoKnownCopyright', array( 'public_domain' ) ) );

/* 3. Creative Commons: ports, retired tools, the Public Domain Mark */
$e = $entry( 'CC-BY-SA-3.0-DE' );
$check( 'ported CC license with an SPDX id (CC-BY-SA-3.0-DE): SPDX, ported, jurisdiction de',
        $e && $e['spdx'] && !empty( $e['ported'] ) && $e['cc']['jurisdiction'] === 'de' && $e['url'] === 'https://creativecommons.org/licenses/by-sa/3.0/de/' );
$e = $entry( 'LicenseRef-CC-BY-NC-SA-2.5-CH' );
$check( 'ported CC license without an SPDX id (LicenseRef-CC-BY-NC-SA-2.5-CH): non_free, legal code URL',
        $e && !$e['spdx'] && !empty( $e['ported'] ) && $e['type'] === 'non_commercial' && $e['legalcode'] === 'https://creativecommons.org/licenses/by-nc-sa/2.5/ch/legalcode' );
$check( 'CC 3.0 IGO is ported and keeps its SPDX id', ( $e = $entry( 'CC-BY-NC-3.0-IGO' ) ) && $e['spdx'] && $entry( 'LicenseRef-CC-BY-NC-3.0-IGO' ) === null );
$e = $entry( 'LicenseRef-CC-SAMPLING-PLUS-1.0' );
$check( 'retired CC license (Sampling Plus 1.0): deprecated, retired_by_cc, date', $e && $e['deprecated'] && !empty( $e['retired_by_cc'] ) && $e['retired_on'] === '2011-09-12' );
$e = $entry( 'CC-SA-1.0' );
$check( 'retired CC ShareAlike 1.0 (SPDX CC-SA-1.0): retired, copyleft, no attribution', $e && !empty( $e['retired_by_cc'] ) && $e['terms']['attribution'] === false && in_array( 'copyleft', $e['groups'], true ) );
$check( 'retired CC NonCommercial 1.0 ports are retired too', ( $e = $entry( 'LicenseRef-CC-NC-1.0-FI' ) ) && !empty( $e['retired_by_cc'] ) && $e['type'] === 'non_commercial' );
$check( 'CC-GNU GPL, Music Sharing, Founders\' Copyright, PDDC: retired', !empty( $entry( 'LicenseRef-CC-GNU-GPL-2.0' )['retired_by_cc'] )
        && !empty( $entry( 'LicenseRef-CC-MusicSharing' )['retired_by_cc'] ) && !empty( $entry( 'LicenseRef-CC-FoundersCopyright' )['retired_by_cc'] )
        && !empty( $entry( 'CC-PDDC' )['retired_by_cc'] ) );
$check( 'current CC 4.0 licenses are not retired', empty( $entry( 'CC-BY-4.0' )['retired_by_cc'] ) && !$entry( 'CC-BY-4.0' )['deprecated'] );
$e = $entry( 'LicenseRef-PDM-1.0' );
$check( 'Public Domain Mark: LicenseRef-PDM-1.0 is an alias of CC-PDM-1.0, public domain', $e && $e['id'] === 'CC-PDM-1.0' && in_array( 'public_domain', $e['groups'], true )
        && $e['url'] === 'https://creativecommons.org/publicdomain/mark/1.0/' );
$check( 'deprecated SPDX ids are marked with their replacement', $entry( 'GPL-2.0+' )['deprecated'] && $entry( 'GPL-2.0+' )['replaced_by'] === 'GPL-2.0-or-later' );
$check( 'byID() ignores case and returns null for unknown ids', $entry( 'mit' )['id'] === 'MIT' && $entry( 'No-Such-License' ) === null && $entry( '' ) === null );

/* 4. search() */
$hits = xrowMetaDataLicense::search( 'gpl-3.0-or-later' );
$check( 'search: exact id first', $hits && $hits[0]['id'] === 'GPL-3.0-or-later' );
$hits = xrowMetaDataLicense::search( 'attribution-sharealike 2.0', 'content', 500 );
$check( 'search: by name within a group finds the ports', count( $hits ) > 10 && !array_filter( $hits, function ( $h ) { return !in_array( 'content', $h['groups'], true ); } ) );
$check( 'search: limit', count( xrowMetaDataLicense::search( 'cc', null, 5 ) ) === 5 );

/* 5. Allowlist */
$setSettings( array( 'AllowedGroups' => array( 'content', 'public_domain' ) ) );
$check( 'AllowedGroups: CC-BY-4.0 allowed, MIT not', xrowMetaDataLicense::isAllowed( 'CC-BY-4.0' ) && !xrowMetaDataLicense::isAllowed( 'MIT' ) );
$setSettings( array( 'AllowedGroups' => array( 'content' ), 'AllowedLicenses' => array( 'MIT', 'LicenseRef-PDM-1.0' ) ) );
$check( 'AllowedLicenses wins over AllowedGroups, aliases count', xrowMetaDataLicense::isAllowed( 'MIT' ) && xrowMetaDataLicense::isAllowed( 'CC-PDM-1.0' ) && !xrowMetaDataLicense::isAllowed( 'CC-BY-4.0' ) );
$setSettings( array() );
$check( 'empty lists allow every catalogue license, never an unknown id', xrowMetaDataLicense::isAllowed( 'Apache-2.0' ) && !xrowMetaDataLicense::isAllowed( 'Nope-1.0' ) );

/* 6. resolve() precedence with stub nodes */
$meta = function ( $license, $holder = '', $year = '' ) {
    $m = new stdClass();
    $m->license = $license;
    $m->license_holder = $holder;
    $m->license_year = $year;
    $m->license_custom_url = '';
    $m->license_custom_terms = '';
    return $m;
};
$node = function ( $content, array $classDefault, $dataText = '' ) {
    $attr = new xrowMetaDataLicenseTestAttribute( $content, new xrowMetaDataLicenseTestClassAttribute( $classDefault ), $dataText );
    $other = new xrowMetaDataLicenseTestAttribute( null, null, '', 'ezstring' );
    return new xrowMetaDataLicenseTestNode( new xrowMetaDataLicenseTestObject( array( 'title' => $other, 'metadata' => $attr ) ) );
};
$setSettings( array( 'DefaultLicense' => 'GPL-2.0-or-later', 'DefaultHolder' => 'Publisher' ) );
$r = xrowMetaDataLicense::resolve( $node( $meta( 'CC-BY-4.0', 'Jane', '2026' ), array( 'license' => 'MIT', 'allow_object_license' => 1 ) ) );
$check( 'node value first (class allows it)', $r && $r['id'] === 'CC-BY-4.0' && $r['source'] === 'node' && $r['holder'] === 'Jane' && $r['year'] === '2026'
        && $r['attribution'] === '© 2026 Jane' && $r['node_id'] === 4242, json_encode( $r ? array( $r['id'], $r['source'], $r['attribution'] ) : null ) );
$r = xrowMetaDataLicense::resolve( $node( $meta( 'CC-BY-4.0', 'Jane' ), array( 'license' => 'MIT', 'allow_object_license' => 0 ) ) );
$check( 'class switch off: node value ignored, class default used, holder kept', $r && $r['id'] === 'MIT' && $r['source'] === 'class' && $r['holder'] === 'Jane' );
$r = xrowMetaDataLicense::resolve( $node( $meta( '' ), array( 'license' => 'MIT' ) ) );
$check( 'no node value: class default', $r && $r['id'] === 'MIT' && $r['source'] === 'class' && $r['holder'] === 'Publisher' );
$r = xrowMetaDataLicense::resolve( $node( $meta( '' ), array() ) );
$check( 'no node or class value: installation default', $r && $r['id'] === 'GPL-2.0-or-later' && $r['source'] === 'installation' );
$r = xrowMetaDataLicense::resolve( $node( $meta( '' ), array( 'license' => 'MIT', 'allow_object_license' => 0 ) ) );
$check( 'class switch off and no node value: class default', $r && $r['id'] === 'MIT' && $r['source'] === 'class' );
$r = xrowMetaDataLicense::resolve( $node( $meta( 'Not-A-License' ), array( 'license' => 'Also-Not' ) ) );
$check( 'unknown node and class ids fall through to the installation', $r && $r['source'] === 'installation' );
$r = xrowMetaDataLicense::resolve( null );
$check( 'null node: installation default', $r && $r['id'] === 'GPL-2.0-or-later' && $r['source'] === 'installation' && $r['node_id'] === null );
$setSettings( array( 'DefaultLicense' => 'GPL-2.0-or-later', 'AllowedGroups' => array( 'content' ) ) );
$r = xrowMetaDataLicense::resolve( $node( $meta( 'MIT' ), array( 'license' => 'CC-BY-SA-4.0' ) ) );
$check( 'a node license the allowlist refuses is passed over (class default used)', $r && $r['id'] === 'CC-BY-SA-4.0' && $r['source'] === 'class' );
$setSettings( array( 'DefaultLicense' => '' ) );
$check( 'nothing set anywhere: null', xrowMetaDataLicense::resolve( $node( $meta( '' ), array() ) ) === null && xrowMetaDataLicense::resolve( null ) === null );
$setSettings( array( 'Enabled' => 'false', 'DefaultLicense' => 'MIT' ) );
$check( 'Enabled=false: null', xrowMetaDataLicense::resolve( $node( $meta( 'CC0-1.0' ), array() ) ) === null );
$setSettings( array() );
$xml = '<?xml version="1.0" encoding="utf-8"?><MetaData><title>t</title><license>CC0-1.0</license><license_holder>Old</license_holder></MetaData>';
$r = xrowMetaDataLicense::resolve( $node( new stdClass(), array(), $xml ) );
$check( 'content without license fields: read from the stored XML', $r && $r['id'] === 'CC0-1.0' && $r['holder'] === 'Old' && $r['attribution'] === '' );
$custom = $meta( 'LicenseRef-Custom', 'ACME' );
$custom->license_custom_url = 'https://example.com/terms';
$custom->license_custom_terms = 'Use for reviews only.';
$r = xrowMetaDataLicense::resolve( $node( $custom, array() ) );
$check( 'custom license: URL and terms come from the object', $r && $r['url'] === 'https://example.com/terms' && $r['custom_terms'] === 'Use for reviews only.' );
$custom->license_custom_url = 'javascript:alert(1)';
$r = xrowMetaDataLicense::resolve( $node( $custom, array() ) );
$check( 'custom license: a non-http URL is refused', $r && $r['url'] === '' && $r['custom_url'] === '' );
$r = xrowMetaDataLicense::resolve( $node( $meta( 'LicenseRef-PDM-1.0' ), array() ) );
$check( 'an alias resolves to the catalogue id', $r && $r['id'] === 'CC-PDM-1.0' );

/* 7. The shipped installation default */
$shipped = dirname( __DIR__ ) . '/settings/xrowmetadata.ini.append.php';
// The file as shipped, read directly (eZINI would add overrides and its cache)
$shippedDefault = null;
$block = '';
foreach ( preg_split( '/\r?\n/', (string) @file_get_contents( $shipped ) ) as $line )
{
    $line = trim( $line );
    if ( preg_match( '/^\[(.+)\]$/', $line, $m ) )
    {
        $block = $m[1];
    }
    elseif ( $block === 'LicenseSettings' && strpos( $line, 'DefaultLicense=' ) === 0 )
    {
        $shippedDefault = trim( substr( $line, strlen( 'DefaultLicense=' ) ) );
    }
}
$r = xrowMetaDataLicense::resolveValues( array(), array(), array( 'enabled' => true, 'default_license' => $shippedDefault, 'default_holder' => '' ) );
$check( "shipped DefaultLicense ($shippedDefault): no class or node value resolves to GPL-2.0-or-later, source installation",
        $shippedDefault === 'GPL-2.0-or-later' && $r && $r['id'] === 'GPL-2.0-or-later' && $r['source'] === 'installation' );
$setSettings( array( 'DefaultLicense' => (string) $shippedDefault ) );
$r = xrowMetaDataLicense::resolve( $node( $meta( '' ), array() ) );
$check( 'with the shipped default, a node without values gets GPL-2.0-or-later from the installation', $r && $r['id'] === 'GPL-2.0-or-later' && $r['source'] === 'installation' );

/* 8. current(): kept per request, never across requests (Velocity workers) */
$setSettings( array( 'DefaultLicense' => 'MIT' ) );
$_SERVER['REQUEST_TIME_FLOAT'] = '1760000000.1';
$_SERVER['REQUEST_URI'] = '/some/page';
$GLOBALS['eZURIRequestInstance'] = new eZURI( 'content/view/full/987654321' );
$check( 'currentNodeID() reads the system address', xrowMetaDataLicense::currentNodeID() === 987654321 );
$first = xrowMetaDataLicense::current();
$setSettings( array( 'DefaultLicense' => 'Apache-2.0' ) );
$second = xrowMetaDataLicense::current();
$check( 'current(): the same request gets the cached result', $first && $second && $first['id'] === 'MIT' && $second['id'] === 'MIT' );
$_SERVER['REQUEST_TIME_FLOAT'] = '1760000000.2';
$third = xrowMetaDataLicense::current();
$check( 'current(): a new REQUEST_TIME_FLOAT resolves again', $third && $third['id'] === 'Apache-2.0' );
$GLOBALS['eZURIRequestInstance'] = new eZURI( 'user/login' );
$GLOBALS['eZRequestedModuleParams'] = array( 'module_name' => 'content', 'function_name' => 'view', 'parameters' => array( 'NodeID' => 2 ) );
$check( 'module parameters a previous request left are not trusted for another address', xrowMetaDataLicense::currentNodeID() === null );
unset( $GLOBALS['eZRequestedModuleParams'] );

/* Restore */
foreach ( $saved as $key => $value )
{
    if ( $value !== null ) $ini->setVariable( 'LicenseSettings', $key, $value );
}
foreach ( $savedServer as $key => $value )
{
    if ( $value === null ) unset( $_SERVER[$key] ); else $_SERVER[$key] = $value;
}
if ( $savedURI === null ) unset( $GLOBALS['eZURIRequestInstance'] ); else $GLOBALS['eZURIRequestInstance'] = $savedURI;
xrowMetaDataLicense::$catalogueFile = $savedCatalogueFile;
xrowMetaDataLicense::reset();

echo ( $xmlt['fail'] ? 'FAIL' : 'PASS' ) . ": {$xmlt['pass']} passed, {$xmlt['fail']} failed\n";
return $xmlt['fail'] ? 0 : 1;

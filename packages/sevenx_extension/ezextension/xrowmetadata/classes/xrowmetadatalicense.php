<?php

/**
 * The content licenses of xrowmetadata: the catalogue, the installation's
 * allowlist, and which license a page has.
 *
 * The catalogue is share/licenses/licenses.json, made by
 * bin/php/xrowmetadatalicenses.php from the SPDX License List and the
 * Creative Commons legal tools index; it is read from disk, never fetched.
 *
 * A page's license (resolve()) comes from, in this order:
 *   'node'          the license chosen in the object's metadata attribute,
 *                   when the class attribute allows a per-object license
 *   'class'         the class attribute's default license
 *   'installation'  [LicenseSettings] DefaultLicense
 * An object or class license that is not in the catalogue, or not allowed by
 * AllowedGroups / AllowedLicenses, is passed over.
 *
 * What is kept between requests: the catalogue (a file that only changes
 * with a release; reloaded when its mtime changes). What is not: anything
 * about a node or a request. current() keeps its result for one request
 * only, keyed by REQUEST_TIME_FLOAT and the request URI, so a persistent
 * Velocity worker never hands one request's license to the next.
 *
 * @license GNU General Public License v2.0 (or any later version)
 */
class xrowMetaDataLicense
{
    const INI_FILE = 'xrowmetadata.ini';
    const INI_GROUP = 'LicenseSettings';
    const DATATYPE = 'xrowmetadata';

    /** Group of the entries the catalogue does not classify. */
    const OTHER_GROUP = 'other';

    /** The groups of the catalogue, in order. */
    static $groupNames = array( 'open_source', 'free_software', 'copyleft', 'permissive', 'content', 'public_domain', 'non_free' );

    /**
     * The catalogue file; null: share/licenses/licenses.json of this
     * extension. Tests may point it elsewhere (then call reset()).
     */
    static $catalogueFile = null;

    /** @var array|null id => entry */
    private static $entries = null;
    /** @var array lower-case id or alias => id */
    private static $index = array();
    /** @var array the catalogue header (format, sources, groups) */
    private static $header = array();
    /** @var string file|mtime the loaded catalogue belongs to */
    private static $loadedKey = '';
    /** @var string the request the mtime was last checked in */
    private static $checkedRequest = null;

    /** @var array current(): 'request' => key, 'nodes' => nodeID|'' => result */
    private static $current = array( 'request' => null, 'nodes' => array() );

    /**
     * Forgets everything: the catalogue and the current request's license.
     */
    static function reset()
    {
        self::$entries = null;
        self::$index = array();
        self::$header = array();
        self::$loadedKey = '';
        self::$checkedRequest = null;
        self::$current = array( 'request' => null, 'nodes' => array() );
    }

    /**
     * The key of the request being served: REQUEST_TIME_FLOAT and the URI.
     * A persistent worker gets a new one for every request.
     */
    static function requestKey()
    {
        $time = isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ? (string) $_SERVER['REQUEST_TIME_FLOAT'] : '';
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
        return $time . '|' . $uri;
    }

    /* ------------------------------------------------------------------ *
     * Catalogue
     * ------------------------------------------------------------------ */

    /**
     * The path of the catalogue file.
     */
    static function catalogueFile()
    {
        if ( self::$catalogueFile !== null )
        {
            return self::$catalogueFile;
        }
        return dirname( __DIR__ ) . '/share/licenses/licenses.json';
    }

    /**
     * Loads the catalogue once per process; within a new request it checks
     * the file's mtime once, so a deployed catalogue is picked up.
     *
     * @return bool whether a catalogue is loaded
     */
    private static function load()
    {
        $request = self::requestKey();
        if ( self::$entries !== null && self::$checkedRequest === $request )
        {
            return true;
        }
        self::$checkedRequest = $request;
        $file = self::catalogueFile();
        clearstatcache( true, $file );
        $mtime = @filemtime( $file );
        $key = $file . '|' . ( $mtime === false ? '' : $mtime );
        if ( self::$entries !== null && self::$loadedKey === $key )
        {
            return true;
        }
        self::$entries = array();
        self::$index = array();
        self::$header = array();
        self::$loadedKey = $key;
        $data = $mtime === false ? null : json_decode( (string) @file_get_contents( $file ), true );
        if ( !is_array( $data ) || !isset( $data['licenses'] ) || !is_array( $data['licenses'] ) )
        {
            if ( class_exists( 'eZDebug' ) )
            {
                eZDebug::writeError( "The license catalogue $file cannot be read", __METHOD__ );
            }
            return false;
        }
        foreach ( $data['licenses'] as $entry )
        {
            if ( !is_array( $entry ) || !isset( $entry['id'] ) || !is_string( $entry['id'] ) )
            {
                continue;
            }
            self::$entries[$entry['id']] = $entry;
            self::$index[strtolower( $entry['id'] )] = $entry['id'];
        }
        // Aliases after the ids, so an alias never shadows an id
        foreach ( self::$entries as $id => $entry )
        {
            foreach ( isset( $entry['aliases'] ) ? (array) $entry['aliases'] : array() as $alias )
            {
                $alias = strtolower( (string) $alias );
                if ( $alias !== '' && !isset( self::$index[$alias] ) )
                {
                    self::$index[$alias] = $id;
                }
            }
        }
        unset( $data['licenses'] );
        self::$header = $data;
        return true;
    }

    /**
     * Every entry of the catalogue, ordered by id.
     *
     * @return array[]
     */
    static function all()
    {
        self::load();
        return array_values( self::$entries );
    }

    /**
     * The catalogue header: format, sources (SPDX version and date, the CC
     * index commit) and groups.
     */
    static function catalogueInfo()
    {
        self::load();
        return self::$header;
    }

    /**
     * The entry of $id (an id or an alias such as LicenseRef-PDM-1.0; the
     * case does not matter), or null.
     *
     * @return array|null
     */
    static function byID( $id )
    {
        $canonical = self::canonicalID( $id );
        return $canonical === null ? null : self::$entries[$canonical];
    }

    /**
     * The catalogue id of $id or of the alias $id, or null.
     */
    static function canonicalID( $id )
    {
        if ( !is_scalar( $id ) )
        {
            return null;
        }
        $id = trim( (string) $id );
        if ( $id === '' || !self::load() )
        {
            return null;
        }
        if ( isset( self::$entries[$id] ) )
        {
            return $id;
        }
        $lower = strtolower( $id );
        return isset( self::$index[$lower] ) ? self::$index[$lower] : null;
    }

    /**
     * The ids of each group: group => array of ids. Every group of the
     * catalogue is a key (empty when nothing is in it); entries without a
     * group are under 'other'.
     *
     * @return array
     */
    static function groups()
    {
        self::load();
        $groups = array_fill_keys( self::$groupNames, array() );
        $groups[self::OTHER_GROUP] = array();
        foreach ( self::$entries as $id => $entry )
        {
            $list = isset( $entry['groups'] ) ? (array) $entry['groups'] : array();
            if ( !$list )
            {
                $groups[self::OTHER_GROUP][] = $id;
            }
            foreach ( $list as $group )
            {
                $groups[$group][] = $id;
            }
        }
        return $groups;
    }

    /**
     * Entries whose id, alias, name or local name contain $text (no case),
     * best first: the exact id, ids starting with it, names starting with
     * it, then the rest; within each, current licenses before deprecated
     * ones, then by id. $group limits to one group ('other' for the
     * unclassified). $allowedOnly limits to what the installation allows.
     *
     * @return array[]
     */
    static function search( $text, $group = null, $limit = 50, $allowedOnly = false )
    {
        self::load();
        $text = strtolower( trim( (string) $text ) );
        $limit = max( 0, (int) $limit );
        $hits = array();
        foreach ( self::$entries as $id => $entry )
        {
            if ( $group !== null && $group !== '' && !self::inGroup( $entry, $group ) )
            {
                continue;
            }
            $rank = $text === '' ? 3 : self::rank( $entry, $text );
            if ( $rank === null )
            {
                continue;
            }
            if ( $allowedOnly && !self::isAllowed( $id ) )
            {
                continue;
            }
            $hits[] = array( $rank, !empty( $entry['deprecated'] ) ? 1 : 0, strtolower( $id ), $entry );
        }
        usort( $hits, function ( $a, $b ) {
            if ( $a[0] !== $b[0] ) return $a[0] - $b[0];
            if ( $a[1] !== $b[1] ) return $a[1] - $b[1];
            return strcmp( $a[2], $b[2] );
        } );
        $result = array();
        foreach ( $hits as $hit )
        {
            if ( count( $result ) >= $limit )
            {
                break;
            }
            $result[] = $hit[3];
        }
        return $result;
    }

    private static function rank( array $entry, $text )
    {
        $id = strtolower( $entry['id'] );
        $aliases = array_map( 'strtolower', isset( $entry['aliases'] ) ? (array) $entry['aliases'] : array() );
        if ( $id === $text || in_array( $text, $aliases, true ) )
        {
            return 0;
        }
        if ( strpos( $id, $text ) === 0 )
        {
            return 1;
        }
        $name = strtolower( isset( $entry['name'] ) ? (string) $entry['name'] : '' );
        if ( strpos( $name, $text ) === 0 )
        {
            return 2;
        }
        $local = strtolower( isset( $entry['name_local'] ) ? (string) $entry['name_local'] : '' );
        if ( strpos( $id, $text ) !== false || strpos( $name, $text ) !== false
             || ( $local !== '' && strpos( $local, $text ) !== false ) )
        {
            return 3;
        }
        foreach ( $aliases as $alias )
        {
            if ( strpos( $alias, $text ) !== false )
            {
                return 3;
            }
        }
        return null;
    }

    private static function inGroup( array $entry, $group )
    {
        $groups = isset( $entry['groups'] ) ? (array) $entry['groups'] : array();
        return $group === self::OTHER_GROUP ? !$groups : in_array( $group, $groups, true );
    }

    /* ------------------------------------------------------------------ *
     * Settings and allowlist
     * ------------------------------------------------------------------ */

    /**
     * [LicenseSettings] as an array: enabled (bool), default_license,
     * default_holder, allowed_groups, allowed_licenses (arrays without
     * empty values).
     */
    static function settings()
    {
        $ini = eZINI::instance( self::INI_FILE );
        $get = function ( $name, $default ) use ( $ini ) {
            return $ini->hasVariable( self::INI_GROUP, $name ) ? $ini->variable( self::INI_GROUP, $name ) : $default;
        };
        $list = function ( $value ) {
            return array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) $value ) ), 'strlen' ) );
        };
        $enabled = strtolower( trim( (string) $get( 'Enabled', 'true' ) ) );
        return array(
            'enabled' => in_array( $enabled, array( 'true', 'enabled', '1', 'yes' ), true ),
            'default_license' => trim( (string) $get( 'DefaultLicense', '' ) ),
            'default_holder' => trim( (string) $get( 'DefaultHolder', '' ) ),
            'allowed_groups' => $list( $get( 'AllowedGroups', array() ) ),
            'allowed_licenses' => $list( $get( 'AllowedLicenses', array() ) ),
        );
    }

    /**
     * Whether editors may choose $id on this installation: it is in the
     * catalogue and, when AllowedLicenses is set, listed there (by id or
     * alias); otherwise, when AllowedGroups is set, in one of those groups.
     */
    static function isAllowed( $id )
    {
        $canonical = self::canonicalID( $id );
        if ( $canonical === null )
        {
            return false;
        }
        $settings = self::settings();
        if ( $settings['allowed_licenses'] )
        {
            foreach ( $settings['allowed_licenses'] as $allowed )
            {
                if ( self::canonicalID( $allowed ) === $canonical )
                {
                    return true;
                }
            }
            return false;
        }
        if ( $settings['allowed_groups'] )
        {
            $entry = self::$entries[$canonical];
            foreach ( $settings['allowed_groups'] as $group )
            {
                if ( self::inGroup( $entry, $group ) )
                {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    /* ------------------------------------------------------------------ *
     * Resolution
     * ------------------------------------------------------------------ */

    /**
     * The license of $node (an eZContentObjectTreeNode, a node id, an
     * eZContentObject, or null for the installation default):
     *
     *   array( 'id', 'name', 'url', 'groups', 'type',
     *          'source' => 'node'|'class'|'installation',
     *          'holder', 'year', 'attribution',
     *          'copyleft', 'terms', 'spdx', 'deprecated', 'retired_by_cc',
     *          'custom_url', 'custom_terms', 'node_id', 'entry' )
     *
     * or null when no license applies or [LicenseSettings] Enabled is off.
     * 'url' is the license's URL, or for a custom license the address the
     * object gives; '' when there is none (all rights reserved). 'holder'
     * and 'year' are the object's, else DefaultHolder and ''. 'attribution'
     * is the credit the license asks reusers to keep: "© year holder" for a
     * license whose terms require attribution, else ''. 'entry' is the full
     * catalogue entry.
     *
     * @return array|null
     */
    static function resolve( $node )
    {
        $settings = self::settings();
        if ( !$settings['enabled'] )
        {
            return null;
        }
        $values = self::nodeValues( $node );
        return self::resolveValues( $values['object'], $values['class'], $settings, $values['node_id'] );
    }

    /**
     * The precedence of resolve() on plain values:
     *   $object  array( 'license', 'holder', 'year', 'custom_url', 'custom_terms' )
     *   $class   array( 'default_license', 'allow_object_license' (bool) )
     *   $settings settings() (or the same keys)
     *
     * @return array|null
     */
    static function resolveValues( array $object, array $class, array $settings, $nodeID = null )
    {
        if ( isset( $settings['enabled'] ) && !$settings['enabled'] )
        {
            return null;
        }
        $allowObject = !isset( $class['allow_object_license'] ) || (bool) $class['allow_object_license'];
        $candidates = array();
        if ( $allowObject && isset( $object['license'] ) )
        {
            $candidates[] = array( 'node', $object['license'], true );
        }
        if ( isset( $class['default_license'] ) )
        {
            $candidates[] = array( 'class', $class['default_license'], true );
        }
        if ( isset( $settings['default_license'] ) )
        {
            // The installation default is the administrator's choice: not
            // checked against the allowlist
            $candidates[] = array( 'installation', $settings['default_license'], false );
        }
        foreach ( $candidates as $candidate )
        {
            list( $source, $id, $checkAllowed ) = $candidate;
            $entry = self::byID( $id );
            if ( $entry === null )
            {
                if ( is_scalar( $id ) && trim( (string) $id ) !== '' && class_exists( 'eZDebug' ) )
                {
                    eZDebug::writeWarning( "The $source license \"$id\" is not in the license catalogue, passed over", __METHOD__ );
                }
                continue;
            }
            if ( $checkAllowed && !self::isAllowed( $entry['id'] ) )
            {
                continue;
            }
            return self::result( $entry, $source, $object, $settings, $nodeID );
        }
        return null;
    }

    private static function result( array $entry, $source, array $object, array $settings, $nodeID )
    {
        $text = function ( $key ) use ( $object ) {
            return isset( $object[$key] ) && is_scalar( $object[$key] ) ? trim( (string) $object[$key] ) : '';
        };
        $holder = $text( 'holder' );
        if ( $holder === '' && isset( $settings['default_holder'] ) )
        {
            $holder = trim( (string) $settings['default_holder'] );
        }
        $year = $text( 'year' );
        $custom = ( isset( $entry['type'] ) && $entry['type'] === 'custom' ) || !empty( $entry['per_object'] );
        $customURL = $custom ? $text( 'custom_url' ) : '';
        if ( $customURL !== '' && !preg_match( '#^https?://#i', $customURL ) )
        {
            $customURL = '';
        }
        $customTerms = $custom ? $text( 'custom_terms' ) : '';
        $url = isset( $entry['url'] ) ? (string) $entry['url'] : '';
        if ( $customURL !== '' && ( $url === '' || !empty( $entry['per_object'] ) ) )
        {
            $url = $customURL;
        }
        $terms = isset( $entry['terms'] ) && is_array( $entry['terms'] ) ? $entry['terms'] : array();
        $attribution = '';
        if ( !empty( $terms['attribution'] ) && ( $holder !== '' || $year !== '' ) )
        {
            $attribution = trim( '©' . ( $year !== '' ? ' ' . $year : '' ) . ( $holder !== '' ? ' ' . $holder : '' ) );
        }
        return array(
            'id' => $entry['id'],
            'name' => isset( $entry['name'] ) ? (string) $entry['name'] : $entry['id'],
            'url' => $url,
            'groups' => isset( $entry['groups'] ) ? (array) $entry['groups'] : array(),
            'type' => isset( $entry['type'] ) ? (string) $entry['type'] : 'other',
            'source' => $source,
            'holder' => $holder,
            'year' => $year,
            'attribution' => $attribution,
            'copyleft' => isset( $entry['copyleft'] ) ? (string) $entry['copyleft'] : 'none',
            'terms' => $terms,
            'spdx' => !empty( $entry['spdx'] ),
            'deprecated' => !empty( $entry['deprecated'] ),
            'retired_by_cc' => !empty( $entry['retired_by_cc'] ),
            'custom_url' => $customURL,
            'custom_terms' => $customTerms,
            'node_id' => $nodeID === null ? null : (int) $nodeID,
            'entry' => $entry,
        );
    }

    /**
     * The license values of $node's object and class:
     * array( 'object' => ..., 'class' => ..., 'node_id' => int|null ).
     * Works on anything with the methods of the kernel classes (object(),
     * dataMap(), attribute(), content(), contentClassAttribute()).
     */
    static function nodeValues( $node )
    {
        $none = array( 'object' => array(), 'class' => array(), 'node_id' => null );
        if ( $node === null || $node === false || $node === '' )
        {
            return $none;
        }
        if ( is_numeric( $node ) )
        {
            $node = class_exists( 'eZContentObjectTreeNode' ) ? eZContentObjectTreeNode::fetch( (int) $node ) : null;
            if ( !is_object( $node ) )
            {
                return $none;
            }
        }
        if ( !is_object( $node ) )
        {
            return $none;
        }
        $nodeID = null;
        $object = $node;
        if ( method_exists( $node, 'object' ) )
        {
            $nodeID = method_exists( $node, 'attribute' ) ? $node->attribute( 'node_id' ) : null;
            $object = $node->object();
        }
        elseif ( method_exists( $node, 'attribute' ) && method_exists( $node, 'dataMap' ) )
        {
            $nodeID = $node->attribute( 'main_node_id' );
        }
        $result = array( 'object' => array(), 'class' => array(), 'node_id' => is_numeric( $nodeID ) ? (int) $nodeID : null );
        if ( !is_object( $object ) || !method_exists( $object, 'dataMap' ) )
        {
            return $result;
        }
        $attribute = null;
        foreach ( (array) $object->dataMap() as $candidate )
        {
            if ( is_object( $candidate ) && $candidate->attribute( 'data_type_string' ) === self::DATATYPE )
            {
                $attribute = $candidate;
                break;
            }
        }
        if ( $attribute === null )
        {
            return $result;
        }
        $result['object'] = self::objectValues( $attribute );
        $classAttribute = method_exists( $attribute, 'contentClassAttribute' ) ? $attribute->contentClassAttribute() : null;
        $result['class'] = self::classValues( $classAttribute, $attribute );
        return $result;
    }

    /**
     * The license values stored in a metadata attribute: from its content
     * (xrowMetaData) when that has them, else read from the stored XML.
     */
    static function objectValues( $attribute )
    {
        $fields = array( 'license' => 'license', 'holder' => 'license_holder', 'year' => 'license_year',
                         'custom_url' => 'license_custom_url', 'custom_terms' => 'license_custom_terms' );
        $values = array();
        $content = method_exists( $attribute, 'content' ) ? $attribute->content() : null;
        if ( is_object( $content ) && property_exists( $content, 'license' ) )
        {
            foreach ( $fields as $key => $property )
            {
                $values[$key] = isset( $content->$property ) && is_scalar( $content->$property ) ? (string) $content->$property : '';
            }
            return $values;
        }
        $xml = (string) $attribute->attribute( 'data_text' );
        if ( $xml === '' )
        {
            return $values;
        }
        $previous = libxml_use_internal_errors( true );
        try
        {
            $dom = new SimpleXMLElement( $xml );
            foreach ( $fields as $key => $element )
            {
                $values[$key] = htmlspecialchars_decode( (string) $dom->$element, ENT_QUOTES );
            }
        }
        catch ( Exception $e )
        {
            $values = array();
        }
        libxml_clear_errors();
        libxml_use_internal_errors( $previous );
        return $values;
    }

    /**
     * The license settings of the class attribute: default_license and
     * allow_object_license. Through xrowMetaDataType::classLicenseSettings()
     * when the datatype has it; else from the content of the attribute.
     */
    static function classValues( $classAttribute, $attribute = null )
    {
        if ( is_object( $classAttribute ) && class_exists( 'xrowMetaDataType', false )
             && method_exists( 'xrowMetaDataType', 'classLicenseSettings' )
             && $classAttribute instanceof eZContentClassAttribute )
        {
            $settings = xrowMetaDataType::classLicenseSettings( $classAttribute );
            return array( 'default_license' => (string) $settings['default_license'],
                          'allow_object_license' => (bool) $settings['allow_object_license'] );
        }
        if ( is_object( $classAttribute ) && method_exists( $classAttribute, 'attribute' ) )
        {
            // The defaults array of the class attribute (data_text5)
            $stored = (string) $classAttribute->attribute( 'data_text5' );
            $default = $stored !== '' ? @unserialize( $stored, array( 'allowed_classes' => false ) ) : array();
            $default = is_array( $default ) ? $default : array();
            return array(
                'default_license' => isset( $default['license'] ) && is_scalar( $default['license'] ) ? (string) $default['license'] : '',
                'allow_object_license' => !isset( $default['allow_object_license'] ) || (int) $default['allow_object_license'] !== 0,
            );
        }
        $content = is_object( $attribute ) && method_exists( $attribute, 'content' ) ? $attribute->content() : null;
        if ( is_object( $content ) && property_exists( $content, 'license_class' ) )
        {
            return array( 'default_license' => (string) $content->license_class,
                          'allow_object_license' => !property_exists( $content, 'license_allowed' ) || (bool) $content->license_allowed );
        }
        return array();
    }

    /**
     * The license of the page this request shows: resolve() of the node of
     * the request (currentNodeID()), or of null (the installation default)
     * when the request views no node. Kept for the rest of the request.
     *
     * @return array|null
     */
    static function current()
    {
        $request = self::requestKey();
        if ( self::$current['request'] !== $request )
        {
            self::$current = array( 'request' => $request, 'nodes' => array() );
        }
        $nodeID = self::currentNodeID();
        $key = $nodeID === null ? '' : (string) $nodeID;
        if ( !array_key_exists( $key, self::$current['nodes'] ) )
        {
            self::$current['nodes'][$key] = self::resolve( $nodeID );
        }
        return self::$current['nodes'][$key];
    }

    /**
     * The node id the current request views, or null: the system address of
     * the request (content/view/<mode>/<node id>, which a URL alias has been
     * translated to), else the parameters of the module view being run when
     * they are content/view and belong to this request's address.
     */
    static function currentNodeID()
    {
        $uri = null;
        if ( isset( $GLOBALS['eZURIRequestInstance'] ) && $GLOBALS['eZURIRequestInstance'] instanceof eZURI )
        {
            $uri = $GLOBALS['eZURIRequestInstance'];
        }
        elseif ( isset( $GLOBALS['eZRequestedURI'] ) && $GLOBALS['eZRequestedURI'] instanceof eZURI )
        {
            $uri = $GLOBALS['eZRequestedURI'];
        }
        $path = $uri ? trim( (string) $uri->uriString(), '/' ) : '';
        if ( preg_match( '#^content/view/[^/]+/([0-9]+)(/|$)#i', $path, $m ) )
        {
            return (int) $m[1];
        }
        if ( isset( $GLOBALS['eZRequestedModuleParams'] ) && is_array( $GLOBALS['eZRequestedModuleParams'] ) )
        {
            $params = $GLOBALS['eZRequestedModuleParams'];
            $module = isset( $params['module_name'] ) ? (string) $params['module_name'] : '';
            $view = isset( $params['function_name'] ) ? (string) $params['function_name'] : '';
            // A previous request of a persistent worker may have left them:
            // trust them only when they are what this request's address names
            if ( $module === 'content' && $view === 'view' && preg_match( '#^content/view(/|$)#i', $path )
                 && isset( $params['parameters']['NodeID'] ) && ctype_digit( (string) $params['parameters']['NodeID'] ) )
            {
                return (int) $params['parameters']['NodeID'];
            }
        }
        return null;
    }
}

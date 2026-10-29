<?php

/**
 * The template operator xrowmetadata_license: the content license of a node,
 * or of the page being shown, ready for the page head and a visible notice.
 *
 *   {def $license = xrowmetadata_license()}          the page of this request
 *   {def $license = xrowmetadata_license( $node )}   a node, a node id or an object
 *   {def $license = $node|xrowmetadata_license}      the same, piped
 *
 * The result is false when no license applies (none chosen for the node, its
 * class or the installation, or [LicenseSettings] Enabled is off). Otherwise
 * it is the array of xrowMetaDataLicense::resolve() (id, name, url, groups,
 * type, source, holder, year, attribution) with these keys added for output:
 *
 *   kind              license | dedication | mark | reserved (see kind())
 *   url               the license URL when it is http(s), else ''
 *   page_url          the absolute URL of the node, '' for the current request
 *   show              hash( link_rel, meta, json_ld ): what the head prints,
 *                     from ShowLinkRelLicense, ShowInOpenGraph and ShowJsonLd
 *   meta_name         dcterms.license, or dcterms.rights for a public domain
 *                     mark and for a license without a URL
 *   meta_content      the URL, else the name
 *   json_ld_value     the JSON value of schema.org "license" (a URL string,
 *                     or a CreativeWork with a name when there is no URL)
 *   json_ld_property  '"license": <value>,' to merge into an existing
 *                     CreativeWork block in a template
 *   json_ld_node      a WebPage node with url and license, for an @graph
 *   json_ld_script    the WebPage node as a complete <script> element
 *
 * Every JSON string is encoded with the HEX flags, so it can be printed raw
 * inside a <script> element without closing it.
 *
 * Nothing is kept between requests here: a persistent worker gets the
 * license as it is now.
 */
class xrowMetaDataLicenseOperator
{
    const INI_FILE = 'xrowmetadata.ini';
    const INI_GROUP = 'LicenseSettings';

    function operatorList()
    {
        return array( 'xrowmetadata_license' );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array(
            'xrowmetadata_license' => array(
                'node' => array(
                    'type' => 'mixed',
                    'required' => false,
                    'default' => null
                )
            )
        );
    }

    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters )
    {
        $subject = isset( $namedParameters['node'] ) ? $namedParameters['node'] : null;
        if ( $subject === null && $operatorValue !== null && $operatorValue !== '' )
        {
            $subject = $operatorValue;
        }
        $operatorValue = self::forOutput( $subject );
    }

    /**
     * The license of $subject (a node, a node id or an object; null for the
     * page of this request) with the output keys described above, or false.
     *
     * @return array|false
     */
    static function forOutput( $subject = null )
    {
        if ( !class_exists( 'xrowMetaDataLicense' ) || !self::enabled( 'Enabled' ) )
        {
            return false;
        }
        $node = self::toNode( $subject );
        if ( $subject !== null && $subject !== false && $subject !== 0 && $subject !== '0' && !$node )
        {
            // Asked for a node that does not exist: nothing, rather than the
            // license of whatever page happens to be shown.
            return false;
        }
        $license = $node ? xrowMetaDataLicense::resolve( $node ) : xrowMetaDataLicense::current();
        if ( !is_array( $license ) || !isset( $license['id'] ) || (string) $license['id'] === '' )
        {
            return false;
        }
        return self::decorate( $license, $node );
    }

    /**
     * Adds the output keys to a resolved license array. Public so that a
     * theme's own PHP (a JSON-LD builder) can use the same values.
     */
    static function decorate( array $license, $node = null )
    {
        foreach ( array( 'id', 'name', 'url', 'type', 'source', 'holder', 'year', 'attribution' ) as $key )
        {
            $license[$key] = isset( $license[$key] ) && is_scalar( $license[$key] ) ? trim( (string) $license[$key] ) : '';
        }
        $license['groups'] = isset( $license['groups'] ) ? array_values( (array) $license['groups'] ) : array();
        if ( $license['name'] === '' )
        {
            $license['name'] = $license['id'];
        }
        $license['url'] = self::safeURL( $license['url'] );
        $license['kind'] = self::kind( $license );
        $license['page_url'] = $node instanceof eZContentObjectTreeNode ? self::nodeURL( $node ) : '';

        $hasURL = $license['url'] !== '';
        $license['show'] = array(
            // rel="license": "the main content of the current document is
            // covered by the copyright license described by the referenced
            // document" (HTML). A public domain mark is not a license.
            'link_rel' => self::enabled( 'ShowLinkRelLicense' ) && $hasURL && $license['kind'] !== 'mark',
            'meta' => self::enabled( 'ShowInOpenGraph' ),
            'json_ld' => self::enabled( 'ShowJsonLd' )
        );
        $license['meta_name'] = ( $license['kind'] === 'mark' || !$hasURL ) ? 'dcterms.rights' : 'dcterms.license';
        $license['meta_content'] = $hasURL ? $license['url'] : $license['name'];

        $value = $hasURL ? $license['url'] : array( '@type' => 'CreativeWork', 'name' => $license['name'] );
        $license['json_ld_value'] = self::json( $value );
        $license['json_ld_property'] = '"license": ' . $license['json_ld_value'] . ',';
        $webPage = array( '@type' => 'WebPage' );
        if ( $license['page_url'] !== '' )
        {
            $webPage['url'] = $license['page_url'];
        }
        $webPage['license'] = $value;
        $license['json_ld_node'] = self::json( $webPage );
        $license['json_ld_script'] = '<script type="application/ld+json">'
            . self::json( array( '@context' => 'https://schema.org' ) + $webPage )
            . '</script>';
        return $license;
    }

    /**
     * What kind of statement the entry is, which decides the markup and the
     * wording of the notice:
     *  - mark:       a statement that the work is free of known copyright
     *                (Public Domain Mark, US government work, expired
     *                copyright, no known copyright). Not a license: no
     *                rel="license", dcterms.rights instead of dcterms.license,
     *                no copyright sign in the notice.
     *  - dedication: a waiver into the public domain (CC0, Unlicense, PDDL,
     *                0BSD, the retired CC public domain dedication). It is a
     *                legal tool, so rel="license" applies; no copyright sign.
     *  - reserved:   proprietary / all rights reserved.
     *  - license:    everything else, software licenses included.
     * A catalogue entry may state it as 'kind' (or 'type' mark/dedication).
     */
    static function kind( array $license )
    {
        foreach ( array( 'kind', 'type' ) as $key )
        {
            if ( isset( $license[$key] ) && in_array( $license[$key], array( 'mark', 'dedication', 'reserved', 'license' ), true ) )
            {
                return $license[$key];
            }
        }
        $id = isset( $license['id'] ) ? (string) $license['id'] : '';
        $url = isset( $license['url'] ) ? (string) $license['url'] : '';
        $groups = isset( $license['groups'] ) ? (array) $license['groups'] : array();
        if ( preg_match( '/^LicenseRef-(PDM|USGov|PD-?Expired|PD-?No|No-?Known|NKC)/i', $id ) || stripos( $url, 'creativecommons.org/publicdomain/mark/' ) !== false )
        {
            return 'mark';
        }
        if ( in_array( 'public_domain', $groups, true ) )
        {
            return 'dedication';
        }
        $type = isset( $license['type'] ) ? (string) $license['type'] : '';
        if ( $type === 'proprietary' )
        {
            return 'reserved';
        }
        return 'license';
    }

    /**
     * $url when it is an absolute http(s) URL without spaces or control
     * characters, else ''. Nothing else is ever printed as a link.
     */
    static function safeURL( $url )
    {
        $url = trim( (string) $url );
        if ( $url === '' || preg_match( '/[\x00-\x20\x7f"<>\\\\]/', $url ) )
        {
            return '';
        }
        if ( !preg_match( '#^https?://#i', $url ) || filter_var( $url, FILTER_VALIDATE_URL ) === false )
        {
            return '';
        }
        return $url;
    }

    /**
     * Whether a switch of [LicenseSettings] is on; a missing one is on.
     */
    static function enabled( $name )
    {
        $ini = eZINI::instance( self::INI_FILE );
        if ( !$ini->hasVariable( self::INI_GROUP, $name ) )
        {
            return true;
        }
        $value = strtolower( trim( (string) $ini->variable( self::INI_GROUP, $name ) ) );
        return in_array( $value, array( 'enabled', 'true', '1', 'yes', 'on' ), true );
    }

    /**
     * @return eZContentObjectTreeNode|null
     */
    static function toNode( $subject )
    {
        if ( $subject instanceof eZContentObjectTreeNode )
        {
            return $subject;
        }
        if ( $subject instanceof eZContentObject )
        {
            $node = $subject->attribute( 'main_node' );
            return $node instanceof eZContentObjectTreeNode ? $node : null;
        }
        if ( is_int( $subject ) || ( is_string( $subject ) && ctype_digit( $subject ) && strlen( $subject ) <= 10 ) )
        {
            $nodeID = (int) $subject;
            if ( $nodeID > 0 )
            {
                $node = eZContentObjectTreeNode::fetch( $nodeID );
                return $node instanceof eZContentObjectTreeNode ? $node : null;
            }
        }
        return null;
    }

    static function nodeURL( eZContentObjectTreeNode $node )
    {
        $url = (string) $node->attribute( 'url_alias' );
        eZURI::transformURI( $url, false, 'full' );
        return self::safeURL( $url );
    }

    static function json( $value )
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $json = json_encode( $value, $flags );
        return $json === false ? 'null' : $json;
    }
}

<?php

/**
 * The license of a page as HTTP response headers:
 *
 *   Link: <https://www.gnu.org/licenses/old-licenses/gpl-2.0.html>; rel="license"   (RFC 8288)
 *   X-Content-License: GPL-2.0-or-later
 *
 * Decided per request from xrowmetadata.ini [LicenseHeaderSettings], for the
 * node the request views (xrowMetaDataLicense::resolve()). Attached through
 * this extension's site.ini [Event] Listeners, which ezpEvent attaches once
 * per request (it detaches its previous set first), so a persistent worker
 * (Velocity) never runs a listener twice:
 *
 *   request/preinput    reset()         forgets the previous request
 *   content/view        noteNode()      the node id and the addresses of the view
 *   response/preoutput  preOutput()     resolves the license and sends the headers
 *   httpcache/store/headers  cacheHeaders()  the same headers into a page the
 *                       role-aware HTTP cache keeps, so a cache hit (served
 *                       without the kernel, by the early exit or Velocity's
 *                       parent) carries them too; only fired by a kernel that
 *                       has that filter
 *
 * content/view fires on a view cache hit as well, before the node is loaded,
 * so the node id costs nothing; the license costs one node fetch per page.
 * Nothing is kept between requests.
 */
class xrowMetaDataLicenseHeader
{
    const INI_FILE = 'xrowmetadata.ini';
    const INI_GROUP = 'LicenseHeaderSettings';

    /** @var array|null the state of this request (see state()) */
    private static $state = null;

    /** The request this state belongs to: REQUEST_TIME_FLOAT, set per request by PHP-FPM and Velocity alike. */
    private static function requestKey()
    {
        return isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ? (string)$_SERVER['REQUEST_TIME_FLOAT'] : '';
    }

    /** The state of the current request, a fresh one when it belongs to another. */
    private static function &state()
    {
        $key = self::requestKey();
        if ( self::$state === null || self::$state['request'] !== $key )
        {
            self::$state = array(
                'request' => $key,
                'node_id' => null,      // the node content/view was asked for
                'paths' => array(),     // the addresses the request is matched against
                'headers' => null,      // the computed headers, array name => value (null = not yet)
                'sent' => false,
            );
        }
        return self::$state;
    }

    /** request/preinput: a new request starts with nothing of the last one. */
    public static function reset()
    {
        self::$state = null;
    }

    /**
     * content/view filter: notes the node and the addresses it was reached by
     * (the typed one after the siteaccess, an alias, and the system one,
     * content/view/full/<id>). Returns the node id unchanged.
     */
    public static function noteNode( $nodeID, $ini = null )
    {
        if ( !self::enabled() )
            return $nodeID;
        $state =& self::state();
        // A content/view inside the page (a rerun, an error view) keeps the first one: it is what was asked for.
        if ( $state['node_id'] === null && (int)$nodeID > 0 )
        {
            $state['node_id'] = (int)$nodeID;
            $state['paths'] = self::requestPaths();
        }
        return $nodeID;
    }

    /**
     * response/preoutput: sends the headers of this request's license, when
     * there is one. Returns the page unchanged.
     */
    public static function preOutput( $templateResult )
    {
        try
        {
            $headers = self::headers();
            $state =& self::state();
            if ( $headers && !$state['sent'] && !headers_sent() )
            {
                foreach ( $headers as $name => $value )
                {
                    // A Link header can hold several links; others (preload) are kept.
                    header( $name . ': ' . $value, strcasecmp( $name, 'Link' ) !== 0 );
                }
                $state['sent'] = true;
            }
        }
        catch ( Exception $e )
        {
            // Never a reason for a page not to be shown; eZExecution::cleanExit() is not called in here.
            eZDebug::writeError( $e->getMessage(), __METHOD__ );
        }
        return $templateResult;
    }

    /**
     * httpcache/store/headers filter: adds the headers to those the role-aware
     * HTTP cache stores with the page.
     */
    public static function cacheHeaders( $headers )
    {
        if ( !is_array( $headers ) )
            return $headers;
        try
        {
            foreach ( (array)self::headers() as $name => $value )
            {
                if ( strcasecmp( $name, 'Link' ) === 0 && isset( $headers['Link'] ) && $headers['Link'] !== '' )
                    $headers['Link'] .= ', ' . $value;
                else
                    $headers[$name] = $value;
            }
        }
        catch ( Exception $e )
        {
            eZDebug::writeError( $e->getMessage(), __METHOD__ );
        }
        return $headers;
    }

    /**
     * The headers for the current request, name => value; empty when none is
     * to be sent. Computed once per request.
     *
     * @return array
     */
    public static function headers()
    {
        $state =& self::state();
        if ( $state['headers'] !== null )
            return $state['headers'];
        $state['headers'] = array();
        if ( !self::enabled() )
            return $state['headers'];

        $nodeID = $state['node_id'];
        if ( $nodeID !== null && !self::viewWasRendered( $nodeID ) )
            $nodeID = null;
        $onlyContent = self::setting( 'OnlyContentViews', 'enabled' ) === 'enabled';
        if ( $nodeID === null && $onlyContent )
            return $state['headers'];

        $paths = $state['paths'] ?: self::requestPaths();
        // Excluded is excluded by every address this request has, the raw one
        // the server set for it included: a persistent worker may still hold
        // the eZURI of an earlier request.
        if ( !self::pathAllowed( $paths ) || !self::pathAllowed( self::rawRequestPaths() ) )
            return $state['headers'];

        $license = self::resolve( $nodeID );
        if ( !is_array( $license ) || empty( $license['id'] ) )
            return $state['headers'];

        $state['headers'] = self::headersFor( $license );
        return $state['headers'];
    }

    /**
     * The header lines for a resolved license, from HeaderLink and HeaderName.
     * A value that cannot be one header line is left out.
     *
     * @param array $license as xrowMetaDataLicense::resolve() returns it
     * @return array name => value
     */
    public static function headersFor( array $license )
    {
        $headers = array();
        $url = isset( $license['url'] ) ? trim( (string)$license['url'] ) : '';
        // A mark (Public Domain Mark, no known copyright ...) is no license: like
        // the page head, no rel="license" for it; X-Content-License names it.
        $isMark = class_exists( 'xrowMetaDataLicenseOperator' ) && xrowMetaDataLicenseOperator::kind( $license ) === 'mark';
        if ( self::setting( 'HeaderLink', 'enabled' ) === 'enabled' && $url !== '' && !$isMark
             && preg_match( '#^https?://[^\s<>"\x00-\x1F\x7F]+$#i', $url ) )
        {
            $headers['Link'] = '<' . $url . '>; rel="license"';
        }
        $name = trim( (string)self::setting( 'HeaderName', 'X-Content-License' ) );
        $id = trim( (string)$license['id'] );
        // A header name is a token; the SPDX id (or LicenseRef-...) is one too.
        if ( $name !== '' && preg_match( '/^[A-Za-z0-9!#$%&\'*+.^_`|~-]+$/', $name )
             && preg_match( '/^[A-Za-z0-9.+:_-]+$/', $id ) )
        {
            $headers[$name] = $id;
        }
        return $headers;
    }

    /**
     * Whether $paths pass IncludePaths and ExcludePaths: globs (fnmatch) on
     * the address after the siteaccess, with a leading slash. A request
     * matches a list when any of its addresses (typed, alias, system) does.
     * Empty IncludePaths means every address.
     *
     * @param string[] $paths
     */
    public static function pathAllowed( array $paths )
    {
        $include = self::patterns( 'IncludePaths' );
        $exclude = self::patterns( 'ExcludePaths' );
        if ( $include && !self::matchesAny( $paths, $include ) )
            return false;
        if ( $exclude && self::matchesAny( $paths, $exclude ) )
            return false;
        return true;
    }

    private static function matchesAny( array $paths, array $patterns )
    {
        foreach ( $paths as $path )
        {
            foreach ( $patterns as $pattern )
            {
                // "/news/*" covers /news itself as well
                if ( fnmatch( $pattern, $path, FNM_CASEFOLD )
                     || ( substr( $pattern, -2 ) === '/*' && strcasecmp( rtrim( $path, '/' ), substr( $pattern, 0, -2 ) ) === 0 ) )
                    return true;
            }
        }
        return false;
    }

    /** A list setting as non-empty patterns, each with a leading slash. */
    private static function patterns( $name )
    {
        $list = array();
        foreach ( (array)self::setting( $name, array() ) as $pattern )
        {
            $pattern = trim( (string)$pattern );
            if ( $pattern === '' )
                continue;
            $list[] = $pattern[0] === '/' ? $pattern : '/' . $pattern;
        }
        return $list;
    }

    /**
     * The addresses of the current request after the siteaccess: as typed
     * (an alias, user parameters included and without), and as the system
     * address it was translated to. Each with a leading slash, no query.
     *
     * @return string[]
     */
    /**
     * The address the server gave this request (REQUEST_URI, set anew for
     * every request by PHP-FPM and Velocity alike), without query, as typed
     * and without its first segment (a siteaccess in the URI). Only used to
     * exclude: it never makes a request get the headers.
     *
     * @return string[]
     */
    public static function rawRequestPaths()
    {
        $request = isset( $_SERVER['REQUEST_URI'] ) ? (string)$_SERVER['REQUEST_URI'] : '';
        $request = '/' . ltrim( preg_replace( '/[?#].*$/', '', $request ), '/' );
        $paths = array( $request );
        if ( preg_match( '#^/[^/]+(/.+)$#', $request, $m ) )
            $paths[] = $m[1];
        return $paths;
    }

    public static function requestPaths()
    {
        $paths = array();
        if ( isset( $GLOBALS['eZURIRequestInstance'] ) && $GLOBALS['eZURIRequestInstance'] instanceof eZURI )
        {
            $uri = $GLOBALS['eZURIRequestInstance'];
            $paths[] = '/' . ltrim( (string)$uri->originalURIString(), '/' );
            $paths[] = '/' . ltrim( (string)$uri->uriString(), '/' );
        }
        else
        {
            $request = (string)eZSys::requestURI();
            $request = preg_replace( '/[?#].*$/', '', $request );
            $paths[] = '/' . ltrim( $request, '/' );
        }
        return array_values( array_unique( $paths ) );
    }

    /**
     * Whether the page about to be sent is the view of $nodeID: the module
     * result the page layout was given names it. An error page (not found,
     * access denied) shown instead of the view has no node and gets no header.
     */
    private static function viewWasRendered( $nodeID )
    {
        $tpl = eZTemplate::factory();
        if ( !$tpl->hasVariable( 'module_result' ) )
            return false;
        $result = $tpl->variable( 'module_result' );
        if ( !is_array( $result ) || !isset( $result['node_id'] ) || (int)$result['node_id'] !== (int)$nodeID )
            return false;
        if ( isset( $result['ui_context'] ) && in_array( $result['ui_context'], array( 'edit', 'ajax', 'browse', 'authentication' ), true ) )
            return false;
        return true;
    }

    /**
     * The license of the node (installation default for null), through the
     * registry. One node fetch; nothing else is loaded.
     *
     * @return array|null
     */
    private static function resolve( $nodeID )
    {
        if ( !class_exists( 'xrowMetaDataLicense' ) || !method_exists( 'xrowMetaDataLicense', 'resolve' ) )
            return null;
        $node = null;
        if ( $nodeID !== null )
        {
            $node = eZContentObjectTreeNode::fetch( (int)$nodeID );
            if ( !$node instanceof eZContentObjectTreeNode )
                return null;
        }
        return xrowMetaDataLicense::resolve( $node );
    }

    /** Whether licenses and their headers are switched on. */
    public static function enabled()
    {
        $ini = eZINI::instance( self::INI_FILE );
        if ( $ini->hasVariable( 'LicenseSettings', 'Enabled' )
             && !in_array( strtolower( (string)$ini->variable( 'LicenseSettings', 'Enabled' ) ), array( 'true', 'enabled' ), true ) )
            return false;
        return self::setting( 'Enabled', 'enabled' ) === 'enabled';
    }

    /** A setting of [LicenseHeaderSettings], or $default when unset. */
    private static function setting( $name, $default = null )
    {
        $ini = eZINI::instance( self::INI_FILE );
        if ( !$ini->hasVariable( self::INI_GROUP, $name ) )
            return $default;
        return $ini->variable( self::INI_GROUP, $name );
    }
}

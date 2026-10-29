<?php

/**
 * The Open Graph image of a metadata attribute: which object it is, whether
 * that object can still be shown, and the picture of it that og:image and
 * twitter:image print.
 *
 * An object may have its own image (an object relation chosen in the edit
 * form); otherwise the class attribute's default image is used. An image that
 * was deleted, moved to the trash or never published is passed over, so the
 * class default takes its place and nothing fails.
 *
 * Nothing here is kept between requests: a persistent worker serves the image
 * as it is now, not as it was when the worker started.
 */
class xrowMetaDataOpenGraphImage
{
    const INI_FILE = 'xrowmetadata.ini';
    const INI_GROUP = 'OpenGraph';

    /**
     * A setting of [OpenGraph] in xrowmetadata.ini, or $default when unset.
     */
    static function setting( $name, $default = null )
    {
        $ini = eZINI::instance( self::INI_FILE );
        if ( !$ini->hasVariable( self::INI_GROUP, $name ) )
        {
            return $default;
        }
        return $ini->variable( self::INI_GROUP, $name );
    }

    /**
     * The class identifiers an editor may choose as an object's Open Graph image.
     *
     * @return array
     */
    static function imageClasses()
    {
        $classes = self::setting( 'ImageClasses', array( 'image' ) );
        return array_values( array_filter( array_map( 'trim', (array) $classes ), 'strlen' ) );
    }

    /**
     * $value as a positive object id, or 0.
     */
    static function objectID( $value )
    {
        if ( is_int( $value ) )
        {
            return $value > 0 ? $value : 0;
        }
        $value = trim( (string) $value );
        if ( $value === '' || !ctype_digit( $value ) || strlen( $value ) > 10 )
        {
            return 0;
        }
        return (int) $value;
    }

    /**
     * The object with the id $objectID when it can be shown as an image: it
     * exists, it is published and it has a node (an object in the trash has
     * none). Otherwise false.
     *
     * @return eZContentObject|false
     */
    static function usableObject( $objectID )
    {
        $objectID = self::objectID( $objectID );
        if ( $objectID === 0 )
        {
            return false;
        }
        $object = eZContentObject::fetch( $objectID );
        if ( !$object instanceof eZContentObject )
        {
            return false;
        }
        if ( (int) $object->attribute( 'status' ) !== eZContentObject::STATUS_PUBLISHED )
        {
            return false;
        }
        if ( !$object->attribute( 'main_node_id' ) )
        {
            return false;
        }
        return $object;
    }

    /**
     * Whether an object of this class may be chosen as an Open Graph image in
     * the edit form. With an empty ImageClasses list every class may.
     */
    static function isImageClass( eZContentObject $object )
    {
        $classes = self::imageClasses();
        return !$classes || in_array( $object->attribute( 'class_identifier' ), $classes, true );
    }

    /**
     * The attribute of $object that holds its picture: the first of
     * ImageAttributes with content, else the first ezimage attribute with
     * content. False when it has none.
     *
     * @return eZContentObjectAttribute|false
     */
    static function imageAttribute( eZContentObject $object )
    {
        $dataMap = $object->dataMap();
        if ( !is_array( $dataMap ) )
        {
            return false;
        }
        $identifiers = (array) self::setting( 'ImageAttributes', array( 'site_opengraph_image', 'image', 'site_logo', 'file' ) );
        foreach ( $identifiers as $identifier )
        {
            if ( isset( $dataMap[$identifier] ) && self::isPictureAttribute( $dataMap[$identifier] ) )
            {
                return $dataMap[$identifier];
            }
        }
        foreach ( $dataMap as $attribute )
        {
            if ( $attribute->attribute( 'data_type_string' ) === 'ezimage' && self::isPictureAttribute( $attribute ) )
            {
                return $attribute;
            }
        }
        return false;
    }

    /**
     * An ezimage attribute with an image, or an ezbinaryfile attribute
     * holding an image file.
     */
    static function isPictureAttribute( eZContentObjectAttribute $attribute )
    {
        if ( !$attribute->hasContent() )
        {
            return false;
        }
        switch ( $attribute->attribute( 'data_type_string' ) )
        {
            case 'ezimage':
                return true;
            case 'ezbinaryfile':
                $file = $attribute->content();
                return is_object( $file ) && strpos( (string) $file->attribute( 'mime_type' ), 'image/' ) === 0;
        }
        return false;
    }

    /**
     * The picture of the object $objectID for og:image, or false when the
     * object cannot be shown or has no picture:
     * array( object_id, name, url (absolute), path (from the web root), width,
     * height, type, alt, thumbnail_url, thumbnail_width, thumbnail_height ).
     *
     * @return array|false
     */
    static function info( $objectID )
    {
        $object = self::usableObject( $objectID );
        if ( !$object )
        {
            return false;
        }
        $attribute = self::imageAttribute( $object );
        if ( !$attribute )
        {
            return false;
        }

        $name = (string) $object->attribute( 'name' );
        $info = array( 'object_id' => (int) $object->attribute( 'id' ),
                       'name' => $name,
                       'url' => '',
                       'path' => '',
                       'width' => '',
                       'height' => '',
                       'type' => '',
                       'alt' => '',
                       'thumbnail_url' => '',
                       'thumbnail_width' => '',
                       'thumbnail_height' => '' );

        if ( $attribute->attribute( 'data_type_string' ) === 'ezimage' )
        {
            $handler = $attribute->content();
            $alias = self::imageAlias( $handler, (string) self::setting( 'ImageAlias', 'opengraph' ) );
            if ( !$alias )
            {
                return false;
            }
            $info['path'] = $alias['url'];
            $info['width'] = isset( $alias['width'] ) ? (string) $alias['width'] : '';
            $info['height'] = isset( $alias['height'] ) ? (string) $alias['height'] : '';
            $info['type'] = isset( $alias['mime_type'] ) ? (string) $alias['mime_type'] : '';
            $info['alt'] = isset( $alias['alternative_text'] ) ? trim( (string) $alias['alternative_text'] ) : '';

            $thumbnail = self::imageAlias( $handler, (string) self::setting( 'ThumbnailAlias', 'small' ) );
            if ( $thumbnail )
            {
                $info['thumbnail_url'] = self::localURL( $thumbnail['url'] );
                $info['thumbnail_width'] = isset( $thumbnail['width'] ) ? (string) $thumbnail['width'] : '';
                $info['thumbnail_height'] = isset( $thumbnail['height'] ) ? (string) $thumbnail['height'] : '';
            }
        }
        else
        {
            $file = $attribute->content();
            $info['path'] = (string) $file->attribute( 'filepath' );
            $info['type'] = (string) $file->attribute( 'mime_type' );
            $info['thumbnail_url'] = self::localURL( $info['path'] );
        }

        if ( $info['path'] === '' )
        {
            return false;
        }
        if ( $info['alt'] === '' )
        {
            $info['alt'] = $name;
        }
        $info['url'] = self::absoluteURL( $info['path'] );
        return $info;
    }

    /**
     * The alias $aliasName of an image, falling back to original when the
     * alias is unknown or cannot be made. False when there is no image.
     *
     * @return array|false
     */
    static function imageAlias( $handler, $aliasName )
    {
        if ( !is_object( $handler ) || !method_exists( $handler, 'imageAlias' ) )
        {
            return false;
        }
        foreach ( array_unique( array( $aliasName !== '' ? $aliasName : 'original', 'original' ) ) as $name )
        {
            try
            {
                $alias = $handler->imageAlias( $name );
            }
            catch ( Exception $e )
            {
                eZDebug::writeWarning( "Image alias '$name' could not be made: " . $e->getMessage(), __METHOD__ );
                $alias = false;
            }
            if ( is_array( $alias ) && !empty( $alias['is_valid'] ) && !empty( $alias['url'] ) )
            {
                return $alias;
            }
        }
        return false;
    }

    /**
     * $path (a file below the web root) as a URL path of this installation.
     */
    static function localURL( $path )
    {
        $href = '/' . ltrim( (string) $path, '/' );
        eZURI::transformURI( $href, true, 'relative', false );
        return $href;
    }

    /**
     * $path (a file below the web root) as an absolute URL: the host of the
     * request, else SiteURL of [OpenGraph], else https:// and [SiteSettings]
     * SiteURL.
     */
    static function absoluteURL( $path )
    {
        $href = self::localURL( $path );
        if ( preg_match( '#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $href ) )
        {
            // A cluster handler that serves files from its own server
            return $href;
        }
        $server = '';
        if ( eZSys::hostname() !== '' && !eZSys::isShellExecution() )
        {
            $server = eZSys::serverURL();
        }
        if ( $server === '' )
        {
            $server = trim( (string) self::setting( 'SiteURL', '' ) );
        }
        if ( $server === '' )
        {
            $siteURL = trim( (string) eZINI::instance()->variable( 'SiteSettings', 'SiteURL' ) );
            $server = preg_match( '#^https?://#', $siteURL ) ? $siteURL : 'https://' . $siteURL;
        }
        // Only scheme, host and port: a path in SiteURL is the siteaccess
        // (or a folder that $href carries already), never where files are
        $parts = parse_url( $server );
        if ( is_array( $parts ) && isset( $parts['host'] ) )
        {
            $server = ( isset( $parts['scheme'] ) ? $parts['scheme'] : 'https' ) . '://' . $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
        }
        return rtrim( $server, '/' ) . $href;
    }
}

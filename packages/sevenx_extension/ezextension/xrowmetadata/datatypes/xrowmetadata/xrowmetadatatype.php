<?php

class xrowMetaDataType extends eZDataType
{
    const DATA_TYPE_STRING = 'xrowmetadata';

    /*!
     Initializes with a keyword id and a description.
    */
    function __construct()
    {
        parent::__construct( self::DATA_TYPE_STRING, ezpI18n::tr( 'kernel/classes/datatypes', 'Metadata', 'Datatype name' ), array(
            'serialize_supported' => true
        ) );
    }

    /*!
     Sets the default value.
    */
    function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
    {
        if ( $currentVersion != false )
        {
            $originalContentObjectAttributeID = $originalContentObjectAttribute->attribute( 'id' );
            $contentObjectAttributeID = $contentObjectAttribute->attribute( 'id' );

            // if translating or copying an object
            if ( $originalContentObjectAttributeID != $contentObjectAttributeID )
            {
                $metadata = $originalContentObjectAttribute->content();
                if ( $metadata instanceof xrowMetadata )
                {
                    //@TODO do something to store the stuff
                }
            }
        }
    }

    /*!
     Validates the input and returns true if the input was
     valid for this datatype.
    */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        if ( $http->hasPostVariable( $base . '_xrowmetadata_data_array_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $data = $http->postVariable( $base . '_xrowmetadata_data_array_' . $contentObjectAttribute->attribute( 'id' ) );
            $classAttribute = $contentObjectAttribute->contentClassAttribute();
            if ( ! $classAttribute->attribute( 'is_information_collector' ) and $contentObjectAttribute->validateIsRequired() )
            {
                if ( $data == "" )
                {
                    $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes', 'Input required.' ) );
                    return eZInputValidator::STATE_INVALID;
                }
                if ( empty( $data['title'] ) )
                {
                    $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes', 'Title required.' ) );
                    return eZInputValidator::STATE_INVALID;
                }
            }
            if ( is_countable( $data['description'] ) > 160 )
            {
                    $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes', 'Description should be shorter as 155 characters.' ) );
                    return eZInputValidator::STATE_INVALID;
            }
            if ( !empty( $data['canonical_url']) && substr($data['canonical_url'], 0, 8) != "https://" )
            {
                    $contentObjectAttribute->setValidationError( ezpI18n::tr( 'kernel/classes/datatypes', 'Canonical url must use https protocol.' ) );
                    return eZInputValidator::STATE_INVALID;
            }
            // The license: only what the form sends is checked, and nothing
            // when the class sets the license (the input is not taken then)
            $licenseAllowed = $classAttribute ? self::allowsObjectLicense( $classAttribute ) : true;
            if ( $licenseAllowed && is_array( $data ) && array_intersect_key( $data, array_flip( self::$licenseFields ) ) )
            {
                $current = self::fetchMetaData( $contentObjectAttribute );
                $check = array();
                foreach ( self::$licenseFields as $key )
                {
                    $check[$key] = isset( $data[$key] ) && is_scalar( $data[$key] ) ? $data[$key] : $current->$key;
                }
                $error = xrowMetaDataLicensePicker::validate( $check, $current->license );
                if ( $error !== '' )
                {
                    $contentObjectAttribute->setValidationError( $error );
                    return eZInputValidator::STATE_INVALID;
                }
            }
        }
        return eZInputValidator::STATE_ACCEPTED;
    }

    /*!
     Fetches the http post var keyword input and stores it in the data instance.
    */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        if ( $http->hasPostVariable( $base . '_xrowmetadata_data_array_' . $contentObjectAttribute->attribute( 'id' ) ) )
        {
            $data = $http->postVariable( $base . '_xrowmetadata_data_array_' . $contentObjectAttribute->attribute( 'id' ) );
            $data['keywords'] = explode( ',', $data['keywords'] );
            $new = array();
            foreach( $data['keywords'] as $keyword )
            {
                if ( trim( $keyword ) )
                {
                    $new[] = trim( $keyword );
                }
            }
            $data['keywords'] = $new;

            // The Open Graph image is chosen with "Choose image" and "Remove"
            // (custom actions), never typed in: the form keeps what is stored
            $current = self::fetchMetaData( $contentObjectAttribute );
            $data['og_image'] = $current->storedOgImage();
            foreach ( array( 'title', 'description', 'priority', 'change', 'sitemap_use', 'canonical_url' ) as $key )
            {
                if ( !isset( $data[$key] ) || !is_scalar( $data[$key] ) )
                {
                    $data[$key] = $key === 'sitemap_use' ? '0' : '';
                }
            }
            // An Open Graph field the form does not send keeps its stored value:
            // the form no longer offers og_image_width and og_image_height (the
            // size printed is the image alias's), and an override template may
            // leave out alt and type
            foreach ( array( 'og_image_width', 'og_image_height', 'og_image_alt', 'og_image_type' ) as $key )
            {
                if ( !isset( $data[$key] ) || !is_scalar( $data[$key] ) )
                {
                    $data[$key] = (string) $current->$key;
                }
            }

            // The license fields: a field the form does not send keeps its
            // stored value, and so does every field while the class sets
            // the license ("Allow a per-object license" cleared)
            foreach ( self::$licenseFields as $key )
            {
                if ( !$current->license_allowed || !isset( $data[$key] ) || !is_scalar( $data[$key] ) )
                {
                    $data[$key] = (string) $current->$key;
                }
            }

            $meta = self::fillMetaData( $data );
            $meta->og_image_source = $current->og_image_source;
            $meta->og_image_allowed = $current->og_image_allowed;
            $meta->og_image = $current->og_image;
            $meta->license_allowed = $current->license_allowed;
            $meta->license_class = $current->license_class;
            $contentObjectAttribute->setContent( $meta );
            return true;
        }
        return false;
    }
    function onPublish( $contentObjectAttribute, $contentObject, $publishedNodes )
    {
        $trans = $contentObjectAttribute->fetchAttributeTranslations();
        $xml = $contentObjectAttribute->attribute( "data_text" );
        $dom2 = new DOMDocument( '1.0', 'utf-8' );
        $dom2->loadXML( $xml );
        $priority = $dom2->getElementsByTagName( "priority" )->item( 0 );
        $change = $dom2->getElementsByTagName( "change" )->item( 0 );
        $sitemap_usage = $dom2->getElementsByTagName( "sitemap_use" )->item( 0 );
        foreach ( $trans as $translation )
        {
            if ( $contentObjectAttribute->LanguageCode == $translation->LanguageCode )
            {
                continue;
            }
            $old = $translation->attribute( "data_text" );
            $dom = new DOMDocument( '1.0', 'utf-8' );
            $dom->loadXML( $old );

            $dom->documentElement->replaceChild( $dom->importNode( $priority, true ), $dom->getElementsByTagName( "priority" )->item( 0 ) );
            $dom->documentElement->replaceChild( $dom->importNode( $change, true ), $dom->getElementsByTagName( "change" )->item( 0 ) );
            $dom->documentElement->replaceChild( $dom->importNode( $sitemap_usage, true ), $dom->getElementsByTagName( "sitemap_use" )->item( 0 ) );

            $translation->setAttribute( "data_text", $dom->saveXML() );
            eZPersistentObject::storeObject( $translation );
        }
    }
    /*!
     Does nothing since it uses the data_text field in the content object attribute.
     See fetchObjectAttributeHTTPInput for the actual storing.
    */
    function storeObjectAttribute( $attribute )
    {
        if( $attribute->ID === null )
        {
            eZPersistentObject::storeObject( $attribute );
        }

        $meta = $attribute->content();
        if ( !$meta instanceof xrowMetaData )
        {
            $meta = new xrowMetaData();
        }
        // Only the object's own image is stored, never the class default
        $attribute->setAttribute( 'data_int', (int) $meta->storedOgImage() );
        $xmlString = self::saveXML( $meta );
        $attribute->setAttribute( 'data_text', $xmlString );

        // save keywords
        $keyword = new eZKeyword();
        $keyword->setKeywordArray( is_array( $meta->keywords ) ? $meta->keywords : array() );
        $keyword->store( $attribute );

        self::storeImageRelations( $attribute );
    }

    /**
     * Makes the attribute-level object relations of this attribute in this
     * version match the Open Graph images of its translations: the image
     * shows in the object's relations, and deleting the image tells this
     * attribute (fixRelatedObjectItem()).
     */
    static function storeImageRelations( $attribute )
    {
        $object = $attribute->object();
        if ( !$object instanceof eZContentObject )
        {
            return;
        }
        $version = (int) $attribute->attribute( 'version' );
        $classAttributeID = (int) $attribute->attribute( 'contentclassattribute_id' );

        $ids = array();
        // When the class sets the image, a stored own image is kept in the
        // attribute but is no relation: it is not used
        $classAttribute = $attribute->contentClassAttribute();
        $allowed = $classAttribute ? self::allowsObjectOgImage( $classAttribute ) : true;
        $own = (int) $attribute->attribute( 'data_int' );
        if ( $allowed && $own > 0 )
        {
            $ids[$own] = $own;
        }
        if ( $allowed && $attribute->attribute( 'id' ) )
        {
            foreach ( $attribute->fetchAttributeTranslations() as $translation )
            {
                if ( $translation->attribute( 'language_code' ) === $attribute->attribute( 'language_code' ) )
                {
                    continue;
                }
                $id = (int) $translation->attribute( 'data_int' );
                if ( $id > 0 )
                {
                    $ids[$id] = $id;
                }
            }
        }

        $object->removeContentObjectRelation( false, $version, $classAttributeID, eZContentObject::RELATION_ATTRIBUTE );
        foreach ( $ids as $id )
        {
            if ( eZContentObject::fetch( $id ) instanceof eZContentObject )
            {
                $object->addContentObjectRelation( $id, $version, $classAttributeID, eZContentObject::RELATION_ATTRIBUTE );
            }
        }
    }

    /**
     * Sets the object's own Open Graph image ('' removes it) and stores the
     * attribute. The content is replaced too, so the input fetched before
     * a custom action does not write the old image back.
     * When the class sets the image (the per-object image is not allowed)
     * nothing changes and false is returned.
     */
    function updateObjectAttributeOgImage( $attribute, $ogImage )
    {
        $classAttribute = $attribute->contentClassAttribute();
        if ( $classAttribute && !self::allowsObjectOgImage( $classAttribute ) )
        {
            eZDebug::writeWarning( 'The class sets the Open Graph image of this attribute, an own image is not taken', __METHOD__ );
            return false;
        }
        $meta = $attribute->content();
        if ( !$meta instanceof xrowMetaData )
        {
            $meta = self::fetchMetaData( $attribute );
        }
        $id = xrowMetaDataOpenGraphImage::objectID( $ogImage );
        $meta->og_image_own = $id > 0 ? (string) $id : '';
        $meta->og_image = $meta->og_image_own;
        $meta->og_image_source = $id > 0 ? 'object' : '';
        $attribute->setContent( $meta );
        $attribute->store();
        return true;
    }

    /**
     * The object $objectID was deleted ('remove') or moved to the trash
     * ('trash'). A deleted image is taken out of the attribute; a trashed one
     * stays stored, so restoring it brings it back, and is not shown meanwhile.
     */
    function fixRelatedObjectItem( $contentObjectAttribute, $objectID, $mode )
    {
        if ( $mode === 'remove' )
        {
            $this->removeRelatedObjectItem( $contentObjectAttribute, $objectID );
        }
    }

    function removeRelatedObjectItem( $contentObjectAttribute, $objectID )
    {
        $meta = self::fetchMetaData( $contentObjectAttribute );
        if ( (int) $meta->storedOgImage() !== (int) $objectID )
        {
            return false;
        }
        $meta->og_image_own = '';
        $contentObjectAttribute->setAttribute( 'data_int', 0 );
        $contentObjectAttribute->setAttribute( 'data_text', self::saveXML( $meta ) );
        eZPersistentObject::storeObject( $contentObjectAttribute );
        $object = $contentObjectAttribute->object();
        if ( $object instanceof eZContentObject )
        {
            $object->removeContentObjectRelation( (int) $objectID, (int) $contentObjectAttribute->attribute( 'version' ),
                                                  (int) $contentObjectAttribute->attribute( 'contentclassattribute_id' ), eZContentObject::RELATION_ATTRIBUTE );
        }
        return true;
    }

    function customObjectAttributeHTTPAction( $http, $action, $contentObjectAttribute, $parameters )
    {
        switch ( $action )
        {
            case "set_object_relation" :
            {
                if ( $http->hasPostVariable( 'BrowseActionName' ) and
                     $http->postVariable( 'BrowseActionName' ) == ( 'AddRelatedObject_' . $contentObjectAttribute->attribute( 'id' ) ) and
                     $http->hasPostVariable( "SelectedObjectIDArray" ) )
                {
                    if ( !$http->hasPostVariable( 'BrowseCancelButton' ) )
                    {
                        $selectedObjectIDArray = $http->postVariable( "SelectedObjectIDArray" );
                        $objectID = is_array( $selectedObjectIDArray ) ? reset( $selectedObjectIDArray ) : $selectedObjectIDArray;
                        // The browse result is form input as well: only an
                        // existing image the editor may read is accepted
                        $object = eZContentObject::fetch( xrowMetaDataOpenGraphImage::objectID( $objectID ) );
                        if ( !$object instanceof eZContentObject || !$object->canRead() || !xrowMetaDataOpenGraphImage::isImageClass( $object ) )
                        {
                            eZDebug::writeError( 'The selected object is no image the editor may read, the Open Graph image is left as it was', __METHOD__ );
                            break;
                        }
                        $this->updateObjectAttributeOgImage( $contentObjectAttribute, (int) $object->attribute( 'id' ) );
                    }
                }
            } break;

            case "browse_object" :
            {
                $classAttribute = $contentObjectAttribute->contentClassAttribute();
                if ( $classAttribute && !self::allowsObjectOgImage( $classAttribute ) )
                {
                    eZDebug::writeWarning( 'The class sets the Open Graph image of this attribute, no image is chosen per object', __METHOD__ );
                    break;
                }
                $module = $parameters['module'];
                $redirectionURI = $parameters['current-redirection-uri'];
                $browseParameters = array( 'action_name' => 'AddRelatedObject_' . $contentObjectAttribute->attribute( 'id' ),
                                           'type' =>  'AddRelatedObjectToDataType',
                                           'selection' => 'single',
                                           'browse_custom_action' => array( 'name' => 'CustomActionButton[' . $contentObjectAttribute->attribute( 'id' ) . '_set_object_relation]',
                                                                            'value' => $contentObjectAttribute->attribute( 'id' ) ),
                                           'persistent_data' => array( 'HasObjectInput' => 0 ),
                                           'from_page' => $redirectionURI );
                $imageClasses = xrowMetaDataOpenGraphImage::imageClasses();
                if ( $imageClasses )
                {
                    $browseParameters['class_array'] = $imageClasses;
                }
                $startNode = trim( (string) xrowMetaDataOpenGraphImage::setting( 'BrowseStartNode', '' ) );
                if ( $startNode !== '' )
                {
                    $browseParameters['start_node'] = $startNode;
                }
                eZContentBrowse::browse( $browseParameters, $module );
            } break;

            case "remove_object" :
            {
                $this->updateObjectAttributeOgImage( $contentObjectAttribute, '' );
            } break;

            default :
            {
                eZDebug::writeError( "Unknown custom HTTP action: " . $action, "xrowMetaDataType" );
            } break;
        }
    }

    function customClassAttributeHTTPAction( $http, $action, $classAttribute )
    {
        switch ( $action )
        {
            case "set_object_relation" :
            {
                if ( $http->hasPostVariable( 'BrowseActionName' ) and
                     $http->postVariable( 'BrowseActionName' ) == ( 'AddRelatedObjectToClassAttr' . $classAttribute->attribute( 'id' ) ) and
                     $http->hasPostVariable( "SelectedObjectIDArray" ) )
                {
                    if ( !$http->hasPostVariable( 'BrowseCancelButton' ) )
                    {
                        $selectedObjectIDArray = $http->postVariable( "SelectedObjectIDArray" );
                        $objectID = (int) $selectedObjectIDArray[0];
                        $default = self::classAttributeDefault( $classAttribute );
                        $default['og_image'] = $objectID;
                        self::setClassAttributeDefault( $classAttribute, $default );
                        $classAttribute->store();
                    }
                }
            } break;

            case "browse_object" :
            {
                $module = $classAttribute->currentModule();
                $browseParameters = array( 'action_name' => 'AddRelatedObjectToClassAttr' . $classAttribute->attribute( 'id' ),
                                           'type' =>  'AddRelatedObjectToDataType',
                                           'browse_custom_action' => array( 'name' => 'CustomActionButton[' . $classAttribute->attribute( 'id' ) . '_set_object_relation]',
                                                                            'value' => $classAttribute->attribute( 'id' ) ),
                                           'persistent_data' => array( 'ContentClassHasInput' => false ),
                                           'from_page' => $module->currentRedirectionURI() );
                eZContentBrowse::browse( $browseParameters, $module );
            } break;

            case "remove_object" :
            {
                $default = self::classAttributeDefault( $classAttribute );
                unset( $default['og_image'] );
                self::setClassAttributeDefault( $classAttribute, $default );
                $classAttribute->store();
            } break;

            default :
            {
                eZDebug::writeError( "Unknown custom HTTP action: " . $action, "xrowMetaDataType" );
            } break;
        }
    }

    /*!
     Delete stored object attribute
    */
    function deleteStoredObjectAttribute( $contentObjectAttribute, $version = null )
    {
        if ( $version != null ) // Do not delete if discarding draft
        {
            return;
        }

        $contentObjectAttributeID = $contentObjectAttribute->attribute( "id" );

        $db = eZDB::instance();

        /* First we retrieve all the keyword ID related to this object attribute */
        $res = $db->arrayQuery( "SELECT keyword_id
                                 FROM ezkeyword_attribute_link
                                 WHERE objectattribute_id='$contentObjectAttributeID'" );

        //if ( !is_countable ( $res ) )
        if ( is_array( $res ) && count( $res ) == 0 )
        {
            /* If there are no keywords at all, we abort the function as there
             * is nothing more to do */
            return;
        }
        $keywordIDs = array();
        foreach ( $res as $record )
            $keywordIDs[] = $record['keyword_id'];
        $keywordIDString = implode( ', ', $keywordIDs );

        /* Then we see which ones only have a count of 1 */
        $res = $db->arrayQuery( "SELECT keyword_id
                                 FROM ezkeyword, ezkeyword_attribute_link
                                 WHERE ezkeyword.id = ezkeyword_attribute_link.keyword_id
                                     AND ezkeyword.id IN ($keywordIDString)
                                 Group By keyword_id
                                 HAVING count(*) = 1" );

        $unusedKeywordIDs = array();
        foreach ( $res as $record )
            $unusedKeywordIDs[] = $record['keyword_id'];
        $unusedKeywordIDString = implode( ', ', $unusedKeywordIDs );

        /* Then we delete those unused keywords */
        if ( $unusedKeywordIDString )
            $db->query( "DELETE FROM ezkeyword WHERE id IN ($unusedKeywordIDString)" );

        /* And as last we remove the link between the keyword and the object
         * attribute to be removed */
        $db->query( "DELETE FROM ezkeyword_attribute_link
                     WHERE objectattribute_id='$contentObjectAttributeID'" );
    }

    /*!
     \reimp
    */
    function validateClassAttributeHTTPInput( $http, $base, $attribute )
    {
        return eZInputValidator::STATE_ACCEPTED;
    }

    /*!
     \reimp
    */
    function fixupClassAttributeHTTPInput( $http, $base, $attribute )
    {
    }

    /*!
     \reimp
    */
    function fetchClassAttributeHTTPInput( $http, $base, $attribute )
    {
        $id = $attribute->attribute( 'id' );
        $default = self::classAttributeDefault( $attribute );
        $postName = 'xrowmetadata_og_image_' . $id;
        if ( $http->hasPostVariable( $postName ) )
        {
            $value = xrowMetaDataOpenGraphImage::objectID( $http->postVariable( $postName ) );
            if ( $value > 0 )
            {
                $default['og_image'] = $value;
            }
            else
            {
                unset( $default['og_image'] );
            }
        }
        // The checkbox sends nothing when cleared: the hidden field says the
        // form had it
        if ( $http->hasPostVariable( 'xrowmetadata_object_og_image_shown_' . $id ) )
        {
            $attribute->setAttribute( self::CLASS_SETS_OG_IMAGE_FIELD, $http->hasPostVariable( 'xrowmetadata_object_og_image_' . $id ) ? 0 : 1 );
        }
        // The default license of the class: an id of the catalogue, '' none
        $postName = 'xrowmetadata_default_license_' . $id;
        if ( $http->hasPostVariable( $postName ) )
        {
            $license = $http->postVariable( $postName );
            $license = is_scalar( $license ) ? trim( (string) $license ) : '';
            if ( $license === '' )
            {
                unset( $default['license'] );
            }
            elseif ( xrowMetaDataLicensePicker::info( $license ) )
            {
                $default['license'] = $license;
            }
            else
            {
                eZDebug::writeWarning( 'The license "' . $license . '" is not in the catalogue, the default license of the class is left as it was', __METHOD__ );
            }
        }
        // "Allow a per-object license": the hidden field says the form had it
        if ( $http->hasPostVariable( 'xrowmetadata_object_license_shown_' . $id ) )
        {
            $default['allow_object_license'] = $http->hasPostVariable( 'xrowmetadata_object_license_' . $id ) ? 1 : 0;
        }
        self::setClassAttributeDefault( $attribute, $default );
        return true;
    }

    /**
     * The license fields of the object, stored as elements of the same name.
     */
    static $licenseFields = array( 'license', 'license_holder', 'license_year', 'license_custom_url', 'license_custom_terms' );

    /**
     * The license settings of the class attribute, kept in the defaults
     * array (data_text5) with the default Open Graph image:
     * 'default_license' (catalogue id, '' for none) and
     * 'allow_object_license' (true unless "Allow a per-object license" was
     * cleared; every class saved before 1.4.9 allows it).
     *
     * @return array
     */
    static function classLicenseSettings( $classAttribute )
    {
        $default = self::classAttributeDefault( $classAttribute );
        return array(
            'default_license' => isset( $default['license'] ) && is_scalar( $default['license'] ) ? (string) $default['license'] : '',
            'allow_object_license' => !isset( $default['allow_object_license'] ) || (int) $default['allow_object_license'] !== 0,
        );
    }

    /**
     * Whether an object may choose its own license.
     */
    static function allowsObjectLicense( $classAttribute )
    {
        $settings = self::classLicenseSettings( $classAttribute );
        return $settings['allow_object_license'];
    }

    /**
     * The class attribute field that says the class sets the Open Graph
     * image: 1 means an object may not choose its own ("Allow a per-object
     * Open Graph image" cleared). 0 or NULL, as in every class saved before
     * 1.4.8, means it may. Templates read it as $class_attribute.data_int3.
     */
    const CLASS_SETS_OG_IMAGE_FIELD = 'data_int3';

    /**
     * The defaults of the class attribute (data_text5): 'og_image', the
     * default image object id, also in data_int4 (older data has it there
     * only).
     *
     * @return array
     */
    static function classAttributeDefault( $classAttribute )
    {
        $stored = (string) $classAttribute->attribute( 'data_text5' );
        $default = $stored !== '' ? @unserialize( $stored, array( 'allowed_classes' => false ) ) : array();
        if ( !is_array( $default ) )
        {
            $default = array();
        }
        if ( !isset( $default['og_image'] ) )
        {
            $dataInt = (int) $classAttribute->attribute( 'data_int4' );
            if ( $dataInt > 0 )
            {
                $default['og_image'] = $dataInt;
            }
        }
        return $default;
    }

    /**
     * Writes $default to the class attribute: data_text5 and the default
     * image in data_int4. Not stored.
     */
    static function setClassAttributeDefault( $classAttribute, array $default )
    {
        $image = isset( $default['og_image'] ) ? xrowMetaDataOpenGraphImage::objectID( $default['og_image'] ) : 0;
        if ( $image > 0 )
        {
            $default['og_image'] = $image;
        }
        else
        {
            unset( $default['og_image'] );
        }
        $classAttribute->setAttribute( 'data_int4', $image );
        $classAttribute->setAttribute( 'data_text5', serialize( $default ) );
    }

    /**
     * Whether an object may choose its own Open Graph image ("Allow a
     * per-object Open Graph image" of the class attribute, on by default).
     */
    static function allowsObjectOgImage( $classAttribute )
    {
        return (int) $classAttribute->attribute( self::CLASS_SETS_OG_IMAGE_FIELD ) !== 1;
    }

    function classAttributeContent( $classAttribute )
    {
        $objectID = (int) $classAttribute->attribute( 'data_int4' );
        if ( $objectID > 0 )
        {
            $object = eZContentObject::fetch( $objectID );
            if ( $object instanceof eZContentObject )
            {
                return $object;
            }
        }
        return false;
    }

    static function fetchMetaData( $attribute )
    {
       try
       {
          $xml = new SimpleXMLElement( $attribute->attribute( 'data_text' ) );

          $keywords = htmlspecialchars_decode( (string) $xml->keywords, ENT_QUOTES );
          $keywords = !empty( $keywords ) ? explode( ",", $keywords ) : array();

          // The object's own image: <og_image>, else data_int (older data
          // has only one of them, the oldest neither)
          $own = xrowMetaDataOpenGraphImage::objectID( (string) $xml->og_image );
          if ( $own === 0 )
          {
              $own = xrowMetaDataOpenGraphImage::objectID( (string) $attribute->attribute( 'data_int' ) );
          }
          $og_image = $own > 0 ? (string) $own : '';

          $meta = new xrowMetaData( htmlspecialchars_decode( (string)$xml->title, ENT_QUOTES ),
                                    $keywords,
                                    htmlspecialchars_decode( (string)$xml->description, ENT_QUOTES ),
                                    htmlspecialchars_decode( (string)$xml->priority, ENT_QUOTES ),
                                    htmlspecialchars_decode( (string)$xml->change, ENT_QUOTES ),
                                    htmlspecialchars_decode( (string)$xml->sitemap_use , ENT_QUOTES ),
                                    htmlspecialchars_decode( (string)$xml->canonical_url , ENT_QUOTES ),
                                    $og_image,
                                    (string) $xml->og_image_width,
                                    (string) $xml->og_image_height,
                                    htmlspecialchars_decode( (string) $xml->og_image_alt, ENT_QUOTES ),
                                    (string) $xml->og_image_type );
          // The license (1.4.9): an element older data does not have is ''
          $license = array();
          foreach ( self::$licenseFields as $key )
          {
              $license[$key] = htmlspecialchars_decode( (string) $xml->$key, ENT_QUOTES );
          }
          $meta->setLicense( $license );
       }
       catch ( Exception $e )
       {
           $meta = new xrowMetaData();
       }
       self::resolveOgImage( $meta, $attribute );
       self::resolveLicense( $meta, $attribute );
       return $meta;
    }

    /**
     * Sets license_allowed and license_class of $meta from the class
     * attribute. The stored license fields stay as they are.
     */
    static function resolveLicense( xrowMetaData $meta, $attribute )
    {
        $classAttribute = $attribute->contentClassAttribute();
        if ( !$classAttribute )
        {
            $meta->license_allowed = true;
            $meta->license_class = '';
            return;
        }
        $settings = self::classLicenseSettings( $classAttribute );
        $meta->license_allowed = $settings['allow_object_license'];
        $meta->license_class = $settings['default_license'];
    }

    /**
     * Sets og_image of $meta to the image the page shows: the object's own
     * image when it can be shown, else the class default when that can be
     * shown, else ''. og_image_own keeps what is stored either way.
     */
    static function resolveOgImage( xrowMetaData $meta, $attribute )
    {
        $meta->og_image = '';
        $meta->og_image_source = '';
        $classAttribute = $attribute->contentClassAttribute();
        // When the class sets the image, a stored own image is not used
        $meta->og_image_allowed = $classAttribute ? self::allowsObjectOgImage( $classAttribute ) : true;
        $own = $meta->storedOgImage();
        if ( $meta->og_image_allowed && $own !== '' && xrowMetaDataOpenGraphImage::usableObject( $own ) )
        {
            $meta->og_image = $own;
            $meta->og_image_source = 'object';
            return;
        }
        if ( !$classAttribute )
        {
            return;
        }
        $classDefault = self::classAttributeDefault( $classAttribute );
        if ( isset( $classDefault['og_image'] ) && xrowMetaDataOpenGraphImage::usableObject( $classDefault['og_image'] ) )
        {
            $meta->og_image = (string) (int) $classDefault['og_image'];
            $meta->og_image_source = 'class';
        }
    }
    /*
     * @return xrowMetaData
     */
    static function fillMetaData( $array )
    {
        $meta = new xrowMetaData( $array['title'], $array['keywords'], $array['description'], $array['priority'], $array['change'], $array['sitemap_use'], $array['canonical_url'], $array['og_image'], $array['og_image_width'], $array['og_image_height'], $array['og_image_alt'], $array['og_image_type'] );
        $meta->setLicense( $array );
        return $meta;
    }
    /*!
     Returns the content.
    */
    function objectAttributeContent( $attribute )
    {
        return self::fetchMetaData( $attribute );
    }

    /*!
     Returns the meta data used for storing search indeces.
    */
    function metaData( $attribute )
    {
        $meta = self::fetchMetaData( $attribute );
        return $meta->title.' '.implode(' ', $meta->keywords).' '.$meta->description;
    }

    /*!
     \reuturn the collect information action if enabled
    */
    function contentActionList( $classAttribute )
    {
        return array();
    }

    /*!
     Returns the content of the keyword for use as a title
    */
    function title( $attribute, $name = null )
    {
        $meta = self::fetchMetaData( $attribute );
        return $meta->title;
    }

    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        $meta = self::fetchMetaData( $contentObjectAttribute );
        if ( $meta instanceof xrowMetaData ) {
            return true;
        }
        else
        {
            return false;
        }
    }

    /*!
     \reimp
    */
    function isIndexable()
    {
        return true;
    }

    /*!
     \return string representation of an contentobjectattribute data for simplified export

    */
    function toString( $contentObjectAttribute )
    {
        return $contentObjectAttribute->attribute( 'data_text' );
    }

    function fromString( $contentObjectAttribute, $string )
    {
        if ( $string != '' )
        {
            $contentObjectAttribute->setAttribute( 'data_text', $string );
            $meta = self::fetchMetaData( $contentObjectAttribute );
            $contentObjectAttribute->setContent( $meta );
        }
        return true;
    }

    static function saveXML( $meta )
    {
        $xml = new DOMDocument( "1.0", "UTF-8" );
        $xmldom = $xml->createElement( "MetaData" );
        $node = $xml->createElement( "title", htmlspecialchars( $meta->title, ENT_QUOTES, 'UTF-8' ) );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "keywords", htmlspecialchars( implode(',', $meta->keywords) , ENT_QUOTES, 'UTF-8' ) );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "description", htmlspecialchars( $meta->description, ENT_QUOTES, 'UTF-8' ) );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "canonical_url", htmlspecialchars( $meta->canonical_url, ENT_QUOTES, 'UTF-8' ) );
        $xmldom->appendChild( $node );
        if (!empty( $meta->priority ) )
        {
            $node = $xml->createElement( "priority", htmlspecialchars( $meta->priority, ENT_QUOTES, 'UTF-8' ) );
        }
        else
        {
            $node = $xml->createElement( "priority" );
        }
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "change", htmlspecialchars( $meta->change, ENT_QUOTES, 'UTF-8' ) );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "sitemap_use", htmlspecialchars( $meta->sitemap_use, ENT_QUOTES, 'UTF-8' ) );
        $xmldom->appendChild( $node );

        // Only the object's own image is saved, never the class default
        $ogImage = ( $meta instanceof xrowMetaData ) ? $meta->storedOgImage() : (string) $meta->og_image;
        $node = $xml->createElement( "og_image", htmlspecialchars( $ogImage, ENT_QUOTES, 'UTF-8' ) );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "og_image_width", htmlspecialchars( $meta->og_image_width, ENT_QUOTES, 'UTF-8' ) );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "og_image_height", htmlspecialchars( $meta->og_image_height, ENT_QUOTES, 'UTF-8' ) );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "og_image_alt", htmlspecialchars( $meta->og_image_alt, ENT_QUOTES, 'UTF-8' ) );
        $xmldom->appendChild( $node );
        $node = $xml->createElement( "og_image_type", htmlspecialchars( $meta->og_image_type, ENT_QUOTES, 'UTF-8' ) );
        $xmldom->appendChild( $node );

        // The license (1.4.9), escaped like the other fields: createElement()
        // reads entity references in its value, so a bare & would be cut off
        foreach ( self::$licenseFields as $key )
        {
            $value = isset( $meta->$key ) && is_scalar( $meta->$key ) ? (string) $meta->$key : '';
            $node = $xml->createElement( $key, htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ) );
            $xmldom->appendChild( $node );
        }

        $xml->appendChild( $xmldom );

        return $xml->saveXML();
    }

    /*!
     \reimp
     \param package
     \param content attribute

     \return a DOM representation of the content object attribute
    */
    function serializeContentObjectAttribute( $package, $objectAttribute )
    {
        $xmlString = self::saveXML( $objectAttribute->content() );
        $DOMNode = $this->createContentObjectAttributeDOMNode( $objectAttribute );

        if ( $xmlString != '' )
        {
            $doc = new DOMDocument( '1.0', 'utf-8' );
            $success = $doc->loadXML( $xmlString );
            $importedRootNode = $DOMNode->ownerDocument->importNode( $doc->documentElement, true );
            $DOMNode->appendChild( $importedRootNode );
         }
        return $DOMNode;
    }

    function unserializeContentObjectAttribute( $package, $objectAttribute, $attributeNode )
    {
        foreach ( $attributeNode->childNodes as $childNode )
        {
            if ( $childNode->nodeType == XML_ELEMENT_NODE )
            {
                $xmlString = $childNode->ownerDocument->saveXML( $childNode );
                $objectAttribute->setAttribute( 'data_text', $xmlString );
                break;
            }
        }
    }
}

eZDataType::register( xrowMetaDataType::DATA_TYPE_STRING, 'xrowMetaDataType' );

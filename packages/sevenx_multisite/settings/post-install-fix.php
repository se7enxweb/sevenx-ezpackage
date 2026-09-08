<?php
// Post-install helpers for the sevenx_multisite kickstart installer.
// Keep all non-interactive DB normalization here so the installer and build_reference
// can share the same logic.

if ( !function_exists( 'sevenxFixPackageNodesAndExplayouts' ) )
{
    function sevenxFixPackageNodesAndExplayouts()
    {
        $db = eZDB::instance();

        $packageDir = eZSys::rootDir() . '/var/storage/packages/7x/sevenx_multisite_democontent/ezcontentobject';
        $files = glob( $packageDir . '/object-media-o-*.xml' );

        if ( !is_array( $files ) )
        {
            eZDebug::writeError( 'No package object files found', __FUNCTION__ );
            return false;
        }

        $nodeAssignments = array();
        $packageNodeMap = array();
        $packageObjectMap = array();

        foreach ( $files as $file )
        {
            $dom = new DOMDocument();
            if ( !@$dom->load( $file ) )
                continue;

            $objectNode = $dom->getElementsByTagNameNS( 'http://ez.no/ezobject', 'object' )->item( 0 );
            if ( !$objectNode )
                continue;

            $packageObjectID = (int)$objectNode->getAttribute( 'ezremote:id' );
            $objectRemoteID = $objectNode->getAttribute( 'remote_id' );

            $object = $objectRemoteID ? eZContentObject::fetchByRemoteID( $objectRemoteID ) : false;
            if ( !$object )
            {
                $object = eZContentObject::fetch( $packageObjectID );
            }
            if ( !$object )
                continue;

            $objectID = (int)$object->attribute( 'id' );
            $version = (int)$object->attribute( 'current_version' );
            if ( $version < 1 )
                continue;

            $packageObjectMap[$packageObjectID] = $objectID;

            $naListNode = $dom->getElementsByTagNameNS( 'http://ez.no/object/', 'node-assignment-list' )->item( 0 );
            if ( !$naListNode )
                continue;

            $naList = $naListNode->getElementsByTagName( 'node-assignment' );
            if ( $naList->length === 0 )
                $naList = $naListNode->getElementsByTagNameNS( 'http://ez.no/object/', 'node-assignment' );
            foreach ( $naList as $na )
            {
                $packageNodeId = (int)$na->getAttribute( 'node-id' );
                $nodeRemoteID = $na->getAttribute( 'remote-id' );
                $parentRemoteID = $na->getAttribute( 'parent-node-remote-id' );

                $nodeAssignments[$packageNodeId] = array(
                    'object_id' => $objectID,
                    'version' => $version,
                    'package_node_id' => $packageNodeId,
                    'node_remote_id' => $nodeRemoteID,
                    'parent_remote_id' => $parentRemoteID,
                    'sort_field' => eZContentObjectTreeNode::sortFieldID( $na->getAttribute( 'sort-field' ) ),
                    'sort_order' => (int)$na->getAttribute( 'sort-order' ),
                    'priority' => (int)$na->getAttribute( 'priority' ),
                    'is_main' => (int)$na->getAttribute( 'is-main-node' ),
                    'name' => $na->getAttribute( 'name' ),
                );
            }
        }

        eZDebug::writeNotice( 'Parsed ' . count( $nodeAssignments ) . ' node assignments from package XML', __FUNCTION__ );

        $existingNodes = $db->arrayQuery( 'SELECT remote_id, node_id FROM ezcontentobject_tree' );
        $remoteIdToNodeId = array();
        foreach ( $existingNodes as $row )
            $remoteIdToNodeId[$row['remote_id']] = (int)$row['node_id'];

        $resolveParentNodeId = function( $parentRemoteID, $remoteIdToNodeId )
        {
            if ( $parentRemoteID === '' )
                return 2;

            if ( isset( $remoteIdToNodeId[$parentRemoteID] ) )
                return $remoteIdToNodeId[$parentRemoteID];

            $parentNode = eZContentObjectTreeNode::fetchByRemoteID( $parentRemoteID );
            if ( $parentNode )
                return (int)$parentNode->attribute( 'node_id' );

            return false;
        };

        $createdCount = 0;
        $pass = 0;
        $maxPasses = 50;

        do
        {
            $progress = false;
            $pass++;

            foreach ( $nodeAssignments as $packageNodeId => $a )
            {
                if ( isset( $packageNodeMap[$packageNodeId] ) )
                    continue;

                if ( isset( $remoteIdToNodeId[$a['node_remote_id']] ) )
                {
                    $packageNodeMap[$packageNodeId] = $remoteIdToNodeId[$a['node_remote_id']];
                    continue;
                }

                $parentNodeID = $resolveParentNodeId( $a['parent_remote_id'], $remoteIdToNodeId );
                if ( $parentNodeID === false )
                    continue;

                $existingNode = eZContentObjectTreeNode::findNode( $parentNodeID, $a['object_id'], true );
                if ( $existingNode )
                {
                    $actualNodeId = (int)$existingNode->attribute( 'node_id' );
                    $remoteIdToNodeId[$a['node_remote_id']] = $actualNodeId;
                    $packageNodeMap[$packageNodeId] = $actualNodeId;
                    continue;
                }

                $nodeAssignment = eZNodeAssignment::create( array(
                    'contentobject_id' => $a['object_id'],
                    'contentobject_version' => $a['version'],
                    'parent_node' => $parentNodeID,
                    'is_main' => $a['is_main'],
                    'sort_field' => $a['sort_field'],
                    'sort_order' => $a['sort_order'],
                    'priority' => $a['priority'],
                    'parent_remote_id' => $a['node_remote_id'],
                ) );
                $nodeAssignment->store();

                $actualNodeId = eZContentOperationCollection::publishNode( $parentNodeID, $a['object_id'], $a['version'], false );
                if ( $actualNodeId )
                {
                    $actualNodeId = (int)$actualNodeId;
                    $remoteIdToNodeId[$a['node_remote_id']] = $actualNodeId;
                    $packageNodeMap[$packageNodeId] = $actualNodeId;
                    $createdCount++;
                    $progress = true;
                }
            }
        } while ( $progress && $pass < $maxPasses );

        eZDebug::writeNotice( "Created $createdCount missing tree nodes in $pass pass(es)", __FUNCTION__ );

        // Remap explayouts references.
        $remapValue = function( $value, $map )
        {
            if ( is_array( $value ) )
            {
                foreach ( $value as $key => $v )
                {
                    $value[$key] = $remapValue( $v, $map );
                }
                return $value;
            }
            if ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) )
            {
                $intValue = (int)$value;
                if ( $intValue > 0 && isset( $map[$intValue] ) )
                    return $map[$intValue];
            }
            return $value;
        };

        $targetTypes = array( 'content_node', 'ibexa_subtree', 'subtree', 'node' );
        $rows = $db->arrayQuery( 'SELECT id, target_type, target_value FROM explayouts_rule_target' );
        foreach ( $rows as $row )
        {
            if ( !in_array( $row['target_type'], $targetTypes ) )
                continue;

            $newValue = $remapValue( $row['target_value'], $packageNodeMap );
            if ( (string)$newValue !== (string)$row['target_value'] )
            {
                $db->query( 'UPDATE explayouts_rule_target SET target_value = \'' . $db->escapeString( (string)$newValue ) . '\' WHERE id = ' . (int)$row['id'] );
            }
        }

        $rows = $db->arrayQuery( 'SELECT id, value_id, value_type FROM explayouts_collection_item' );
        foreach ( $rows as $row )
        {
            $map = false;
            if ( $row['value_type'] === 'ez_location' )
                $map = $packageNodeMap;
            elseif ( $row['value_type'] === 'ez_content' )
                $map = $packageObjectMap;

            if ( $map === false )
                continue;

            $newValue = $remapValue( $row['value_id'], $map );
            if ( (int)$newValue !== (int)$row['value_id'] )
            {
                $db->query( 'UPDATE explayouts_collection_item SET value_id = ' . (int)$newValue . ' WHERE id = ' . (int)$row['id'] );
            }
        }

        $rows = $db->arrayQuery( 'SELECT id, parameters FROM explayouts_collection_query' );
        foreach ( $rows as $row )
        {
            $parameters = json_decode( $row['parameters'], true );
            if ( !is_array( $parameters ) )
                continue;

            if ( isset( $parameters['parent_location_id'] ) && $parameters['parent_location_id'] !== null )
                $parameters['parent_location_id'] = $remapValue( $parameters['parent_location_id'], $packageNodeMap );

            if ( isset( $parameters['topic_content_id'] ) && $parameters['topic_content_id'] !== null )
                $parameters['topic_content_id'] = $remapValue( $parameters['topic_content_id'], $packageObjectMap );

            $newParameters = json_encode( $parameters );
            if ( $newParameters !== $row['parameters'] )
            {
                $db->query( 'UPDATE explayouts_collection_query SET parameters = \'' . $db->escapeString( $newParameters ) . '\' WHERE id = ' . (int)$row['id'] );
            }
        }

        $rows = $db->arrayQuery( 'SELECT id, name, value FROM explayouts_block_parameter' );
        foreach ( $rows as $row )
        {
            if ( $row['name'] === 'content' && is_numeric( $row['value'] ) )
            {
                $newValue = $remapValue( $row['value'], $packageObjectMap );
                if ( (string)$newValue !== (string)$row['value'] )
                {
                    $db->query( 'UPDATE explayouts_block_parameter SET value = \'' . $db->escapeString( (string)$newValue ) . '\' WHERE id = ' . (int)$row['id'] );
                }
            }
        }

        eZDebug::writeNotice( 'Done remapping explayouts references', __FUNCTION__ );

        // Remap eztags_attribute_link object IDs from package to installed.
        $rows = $db->arrayQuery( 'SELECT id, object_id FROM eztags_attribute_link' );
        foreach ( $rows as $row )
        {
            $newValue = $remapValue( $row['object_id'], $packageObjectMap );
            if ( (int)$newValue !== (int)$row['object_id'] )
            {
                $db->query( 'UPDATE eztags_attribute_link SET object_id = ' . (int)$newValue . ' WHERE id = ' . (int)$row['id'] );
            }
        }

        sevenxFixMenuINIFiles( $packageNodeMap );

        // Reparse package eztags attributes and store them. The package installer
        // may not have linked tags for content classes with a subtree limit, or
        // may have linked them to the wrong object due to package/actual ID drift.
        sevenxFixEzTagsFromPackage( $packageDir, $packageObjectMap );

        // Remap any numeric <embed object_id="..."/> or <embed-node node_id="..."/> 
        // references in ezxmltext fields from package IDs to installed IDs.
        sevenxFixEmbeddedObjectIDs( $packageObjectMap, $packageNodeMap );

        return true;
    }
}

if ( !function_exists( 'sevenxSetSiteInfoMenuNodeIDs' ) )
{
    // Stubbed out: menu node IDs now live in the theme's static menu.ini files
    // (extension/sevenx_themes_media/settings/siteaccess/<sa>/menu.ini).
    function sevenxSetSiteInfoMenuNodeIDs()
    {
        return true;
    }
}

if ( !function_exists( 'sevenxFixMenuINIFiles' ) )
{
    function sevenxFixMenuINIFiles( &$packageNodeMap )
    {
        // Fit & Healthy (default site) menu configuration.
        $fitMainMenuPackageIds = array( 79, 117, 131, 150, 151 );
        $fitFooterMenuPackageIds = array( 150, 262, 172, 179, 77 );

        $fitMainMenuNexusMap = array(
            79 => 167,
            117 => 168,
            131 => 190,
            150 => 195,
            151 => 198,
        );
        $fitFooterMenuNexusMap = array(
            150 => 195,
            // 262 is the Workout topic, matching the reference's node 210. This
            // was 266, the "Ad 728x90" htmlbox, so the footer listed an advert.
            262 => 210,
            172 => 224,
            179 => 357,
            77 => 506,
        );

        // Bold Agency menu configuration.
        $boldMainMenuPackageIds = array( 63, 64, 65, 75 );
        $boldFooterMenuPackageIds = array( 63, 64, 65, 75, 179, 61 );

        $boldMainMenuNexusMap = array(
            63 => 387,
            64 => 392,
            65 => 393,
            75 => 405,
        );
        $boldFooterMenuNexusMap = array(
            63 => 387,
            64 => 392,
            65 => 393,
            75 => 405,
            179 => 357,
            61 => 508,
        );

        $siteaccesses = array(
            'site' => 'fit',
            'eng' => 'fit',
            'sevenx_site_user' => 'fit',
            'bold' => 'bold',
            'bold_ger' => 'bold',
        );
        $baseDir = eZSys::rootDir() . '/extension/sevenx_themes_media/settings/siteaccess/';

        foreach ( $siteaccesses as $sa => $siteType )
        {
            if ( $siteType === 'bold' )
            {
                $mainMenuPackageIds = $boldMainMenuPackageIds;
                $footerMenuPackageIds = $boldFooterMenuPackageIds;
                $mainMenuNexusMap = $boldMainMenuNexusMap;
                $footerMenuNexusMap = $boldFooterMenuNexusMap;
            }
            else
            {
                $mainMenuPackageIds = $fitMainMenuPackageIds;
                $footerMenuPackageIds = $fitFooterMenuPackageIds;
                $mainMenuNexusMap = $fitMainMenuNexusMap;
                $footerMenuNexusMap = $fitFooterMenuNexusMap;
            }

            $mainMenuIds = array();
            $mainMenuNexusIds = array();
            foreach ( $mainMenuPackageIds as $packageNodeId )
            {
                $actualId = isset( $packageNodeMap[$packageNodeId] ) ? (int)$packageNodeMap[$packageNodeId] : $packageNodeId;
                $mainMenuIds[] = $actualId;
                $mainMenuNexusIds[] = isset( $mainMenuNexusMap[$packageNodeId] ) ? (int)$mainMenuNexusMap[$packageNodeId] : $actualId;
            }

            $footerMenuIds = array();
            $footerMenuNexusIds = array();
            foreach ( $footerMenuPackageIds as $packageNodeId )
            {
                $actualId = isset( $packageNodeMap[$packageNodeId] ) ? (int)$packageNodeMap[$packageNodeId] : $packageNodeId;
                $footerMenuIds[] = $actualId;
                $footerMenuNexusIds[] = isset( $footerMenuNexusMap[$packageNodeId] ) ? (int)$footerMenuNexusMap[$packageNodeId] : $actualId;
            }

            $path = $baseDir . $sa . '/menu.ini.append.php';
            // The Bold siteaccesses have their own site info object, and it is
            // the one carrying their menu relations. Writing the Fit & Healthy
            // remote id for every siteaccess pointed Bold at the wrong object.
            $siteInfoRemoteID = ( $siteType === 'bold' )
                ? 'media-o-site-info-bold'
                : 'media-o-site-info';
            $lines = array(
                '<?php /* #?ini charset="utf-8"?',
                '',
                '[SiteInfo]',
                'RemoteID=' . $siteInfoRemoteID,
            );
            foreach ( $mainMenuIds as $id )
                $lines[] = "MainMenuID[]=$id";
            foreach ( $mainMenuNexusIds as $id )
                $lines[] = "NexusMainMenuID[]=$id";
            foreach ( $footerMenuIds as $id )
                $lines[] = "FooterMenuID[]=$id";
            foreach ( $footerMenuNexusIds as $id )
                $lines[] = "NexusFooterMenuID[]=$id";
            $lines[] = '*/ ?>';

            $content = implode( "\n", $lines ) . "\n";
            @mkdir( dirname( $path ), 0755, true );
            file_put_contents( $path, $content );
        }

        eZDebug::writeNotice( 'Updated menu.ini files for siteaccesses: ' . implode( ', ', $siteaccesses ), __FUNCTION__ );
        return true;
    }
}

if ( !function_exists( 'sevenxCleanStaleUrlTextAttributes' ) )
{
    function sevenxCleanStaleUrlTextAttributes()
    {
        $db = eZDB::instance();

        $rows = $db->arrayQuery( '
            SELECT o.id, o.name, a.id AS attr_id, a.version, a.data_text AS url_text
            FROM ezcontentobject o
            JOIN ezcontentobject_attribute a ON a.contentobject_id = o.id
            JOIN ezcontentclass_attribute ca ON a.contentclassattribute_id = ca.id AND ca.identifier = "url_text"
            WHERE a.version = o.current_version AND a.data_text IS NOT NULL AND a.data_text != ""'
        );

        $cleaned = 0;
        foreach ( $rows as $row )
        {
            $name = eZURLAliasML::convertToAlias( $row['name'], 'node_' . $row['id'] );
            $url = strtolower( $row['url_text'] );
            $nameLower = strtolower( $name );

            if ( $url !== $nameLower && strpos( $nameLower, $url ) === false && strpos( $url, $nameLower ) === false )
            {
                $db->query( 'UPDATE ezcontentobject_attribute SET data_text = "" WHERE id = ' . (int)$row['attr_id'] . ' AND version = ' . (int)$row['version'] );
                $cleaned++;
            }
        }

        eZDebug::writeNotice( "Cleaned $cleaned stale url_text attributes", __FUNCTION__ );
        return true;
    }
}

if ( !function_exists( 'sevenxRegenerateURLAliases' ) )
{
    function sevenxRegenerateURLAliases()
    {
        $db = eZDB::instance();

        $db->query( 'TRUNCATE TABLE ezurlalias_ml' );
        $db->query( 'TRUNCATE TABLE ezurlalias' );
        $db->query( 'TRUNCATE TABLE ezurlalias_ml_incr' );

        $rows = $db->arrayQuery( 'SELECT node_id FROM ezcontentobject_tree ORDER BY depth ASC, node_id ASC' );
        $count = 0;
        foreach ( $rows as $row )
        {
            $node = eZContentObjectTreeNode::fetch( (int)$row['node_id'] );
            if ( !$node )
                continue;
            $node->updateSubTreePath();
            $count++;
        }

        eZDebug::writeNotice( "Regenerated URL aliases for $count nodes", __FUNCTION__ );
        return true;
    }
}

if ( !function_exists( 'sevenxFixEmbeddedObjectIDs' ) )
{
    function sevenxFixEmbeddedObjectIDs( $packageObjectMap, $packageNodeMap )
    {
        $db = eZDB::instance();

        $rows = $db->arrayQuery( '
            SELECT a.id, a.version, a.data_text
            FROM ezcontentobject_attribute a
            JOIN ezcontentclass_attribute ca ON a.contentclassattribute_id = ca.id
            WHERE ca.data_type_string = "ezxmltext"
              AND a.data_text LIKE "%<embed%"' );

        $fixed = 0;
        foreach ( $rows as $row )
        {
            $data = $row['data_text'];
            $newData = $data;

            if ( preg_match_all( '/<embed[^>]+object_id="(\d+)"/', $data, $m ) )
            {
                foreach ( $m[1] as $packageId )
                {
                    if ( isset( $packageObjectMap[(int)$packageId] ) )
                    {
                        $newData = preg_replace( '/(<embed[^>]*)object_id="' . (int)$packageId . '"/', '$1object_id="' . (int)$packageObjectMap[(int)$packageId] . '"', $newData, 1 );
                    }
                }
            }

            if ( preg_match_all( '/<embed-node[^>]+node_id="(\d+)"/', $data, $m ) )
            {
                foreach ( $m[1] as $packageNodeId )
                {
                    if ( isset( $packageNodeMap[(int)$packageNodeId] ) )
                    {
                        $newData = preg_replace( '/(<embed-node[^>]*)node_id="' . (int)$packageNodeId . '"/', '$1node_id="' . (int)$packageNodeMap[(int)$packageNodeId] . '"', $newData, 1 );
                    }
                }
            }

            if ( $newData !== $data )
            {
                $db->query( 'UPDATE ezcontentobject_attribute SET data_text = \'' . $db->escapeString( $newData ) . '\' WHERE id = ' . (int)$row['id'] . ' AND version = ' . (int)$row['version'] );
                $fixed++;
            }
        }

        eZDebug::writeNotice( "Remapped embedded object IDs in $fixed ezxmltext attributes", __FUNCTION__ );
    }
}

if ( !function_exists( 'sevenxFixEzTagsFromPackage' ) )
{
    function sevenxFixEzTagsFromPackage( $packageDir, $packageObjectMap )
    {
        $adminUser = eZUser::instance( 14 );
        if ( $adminUser )
            eZUser::setCurrentlyLoggedInUser( $adminUser, 14 );

        $db = eZDB::instance();

        $files = glob( $packageDir . '/object-media-o-*.xml' );
        $fixed = 0;

        foreach ( $files as $file )
        {
            $dom = new DOMDocument();
            if ( !@$dom->load( $file ) )
                continue;

            $objectNode = $dom->getElementsByTagNameNS( 'http://ez.no/ezobject', 'object' )->item( 0 );
            if ( !$objectNode )
                continue;

            $packageObjectID = (int)$objectNode->getAttribute( 'ezremote:id' );
            $objectRemoteID = $objectNode->getAttribute( 'remote_id' );

            $objectID = isset( $packageObjectMap[$packageObjectID] ) ? $packageObjectMap[$packageObjectID] : false;
            if ( !$objectID && $objectRemoteID )
            {
                $object = eZContentObject::fetchByRemoteID( $objectRemoteID );
                if ( $object )
                    $objectID = (int)$object->attribute( 'id' );
            }
            if ( !$objectID )
                continue;

            $contentObject = eZContentObject::fetch( $objectID );
            if ( !$contentObject )
                continue;

            $version = (int)$contentObject->attribute( 'current_version' );
            if ( $version < 1 )
                continue;

            $attributes = $dom->getElementsByTagNameNS( 'http://ez.no/object/', 'attribute' );
            foreach ( $attributes as $attrNode )
            {
                if ( $attrNode->getAttribute( 'type' ) !== 'eztags' )
                    continue;

                $idString = '';
                $keywordString = '';
                $parentString = '';
                $localeString = '';

                foreach ( $attrNode->childNodes as $child )
                {
                    if ( $child->nodeType !== XML_ELEMENT_NODE )
                        continue;
                    $nodeName = $child->localName;
                    if ( $nodeName === 'id-string' )
                        $idString = $child->textContent;
                    else if ( $nodeName === 'keyword-string' )
                        $keywordString = $child->textContent;
                    else if ( $nodeName === 'parent-string' )
                        $parentString = $child->textContent;
                    else if ( $nodeName === 'locale-string' )
                        $localeString = $child->textContent;
                }

                if ( $keywordString === '' )
                    continue;

                $identifier = $attrNode->getAttribute( 'identifier' );
                if ( !$identifier )
                    $identifier = $attrNode->getAttributeNS( 'http://ez.no/ezobject', 'identifier' );

                $dataMap = $contentObject->fetchDataMap( $version );
                if ( !isset( $dataMap[$identifier] ) )
                    continue;

                $objectAttribute = $dataMap[$identifier];
                $eZTags = eZTags::createFromStrings( $objectAttribute, $idString, $keywordString, $parentString, $localeString );
                $objectAttribute->setContent( $eZTags );
                $eZTags->store( $objectAttribute );
                $fixed++;
            }
        }

        // Ensure the Workout topic rule has high enough priority to beat the media subtree fallback.
        $db->query( 'UPDATE explayouts_rule SET priority = 175 WHERE id = 50 AND priority < 175' );

        eZDebug::writeNotice( "Fixed $fixed eztags attributes from package XML", __FUNCTION__ );
    }
}

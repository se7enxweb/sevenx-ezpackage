<?php
// Post-install helpers for the sevenx_multisite kickstart installer.
// Keep all non-interactive DB normalization here so the installer and build_reference
// can share the same logic.
//
// The same two files ship in sevenx_multisite and sevenx_multisite_clean. The
// only difference between the two installations is the content package the
// site package requires (sevenx_multisite_democontent or
// sevenx_multisite_democontent_clean), and sevenxDemocontentPackageName() is
// the one place that tells them apart. Everything else reads the content that
// package actually installs, so an installation without demo content gets the
// same settings, siteaccesses, layouts and fixes, minus the references to
// content it does not have.

if ( !function_exists( 'sevenxSitePackageName' ) )
{
    /** The site package these settings files belong to: the directory above settings/. */
    function sevenxSitePackageName()
    {
        return basename( dirname( __DIR__ ) );
    }
}

if ( !function_exists( 'sevenxDemocontentPackageName' ) )
{
    /**
     * The content package the site package installs: the package it requires
     * whose name starts with sevenx_multisite_democontent. Read from the site
     * package's own package.xml, so a renamed or repointed package needs no
     * change here; the name convention is only the fallback for a caller that
     * includes this file from somewhere else.
     */
    function sevenxDemocontentPackageName()
    {
        $sitePackageName = sevenxSitePackageName();
        $sitePackage = eZPackage::fetch( $sitePackageName, false, false, false );
        if ( $sitePackage instanceof eZPackage )
        {
            $dependencies = $sitePackage->attribute( 'dependencies' );
            $requires = isset( $dependencies['requires'] ) ? (array)$dependencies['requires'] : array();
            foreach ( $requires as $require )
            {
                if ( isset( $require['name'] ) && strpos( $require['name'], 'sevenx_multisite_democontent' ) === 0 )
                    return $require['name'];
            }
        }
        return substr( $sitePackageName, -6 ) === '_clean'
             ? 'sevenx_multisite_democontent_clean'
             : 'sevenx_multisite_democontent';
    }
}

if ( !function_exists( 'sevenxDemocontentObjectDir' ) )
{
    /** The ezcontentobject directory of the content package, wherever its repository is. */
    function sevenxDemocontentObjectDir()
    {
        $name = sevenxDemocontentPackageName();
        $package = eZPackage::fetch( $name, false, false, false );
        if ( $package instanceof eZPackage )
            return $package->path() . '/ezcontentobject';
        return eZSys::rootDir() . '/var/storage/packages/7x/' . $name . '/ezcontentobject';
    }
}

if ( !function_exists( 'sevenxFixPackageNodesAndExplayouts' ) )
{
    function sevenxFixPackageNodesAndExplayouts()
    {
        $db = eZDB::instance();

        $packageDir = sevenxDemocontentObjectDir();
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
        //
        // The seed (sevenx_themes_media's share/db_data.dba) is written against
        // the package node and object ids of the full demo content. Each value
        // is one of three things:
        //   mapped  - a package id the content package installed: rewritten to
        //             this installation's id, as before;
        //   base    - a node or object that existed before the content package
        //             was installed (node 1, the content root): kept;
        //   missing - a package id of content this package does not ship. The
        //             clean package leaves the demo content out, so its layouts
        //             still name it. Kept as it was, such a value points at
        //             whatever this installation happened to give that id -
        //             rule targets matched the wrong pages, collections listed
        //             unrelated objects - so the reference is removed instead:
        //             rule target and collection item rows are deleted, a
        //             query's parent/topic is set to 0 (the query answers
        //             nothing), and a rule left without targets is disabled.
        // With the full demo content nothing is missing, so this changes nothing
        // there; the counts are logged either way.
        $remapValue = function( $value, $map ) use ( &$remapValue )
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

        // Highest node / object id before the content package was installed,
        // recorded by the installer's preInstall(). Without it (this file used
        // on its own) only values that name nothing at all count as missing.
        $baseMaxNodeID = isset( $GLOBALS['sevenxBaseMaxNodeID'] ) ? (int)$GLOBALS['sevenxBaseMaxNodeID'] : PHP_INT_MAX;
        $baseMaxObjectID = isset( $GLOBALS['sevenxBaseMaxObjectID'] ) ? (int)$GLOBALS['sevenxBaseMaxObjectID'] : PHP_INT_MAX;
        $isBase = function( $value, $kind ) use ( $db, $baseMaxNodeID, $baseMaxObjectID )
        {
            $id = (int)$value;
            if ( $id <= 0 )
                return false;
            if ( $kind === 'node' )
                return $id <= $baseMaxNodeID
                    && (bool)$db->arrayQuery( "SELECT node_id FROM ezcontentobject_tree WHERE node_id = $id" );
            return $id <= $baseMaxObjectID
                && (bool)$db->arrayQuery( "SELECT id FROM ezcontentobject WHERE id = $id" );
        };
        // 'mapped' / 'base' / 'missing', or 'none' for an empty or non-numeric value
        $classify = function( $value, $map, $kind ) use ( $isBase )
        {
            if ( !( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) || (int)$value <= 0 )
                return 'none';
            if ( isset( $map[(int)$value] ) )
                return 'mapped';
            return $isBase( $value, $kind ) ? 'base' : 'missing';
        };
        $removed = array( 'rule targets' => 0, 'rules disabled' => 0, 'collection items' => 0,
                          'query parents' => 0, 'query topics' => 0, 'component contents' => 0,
                          'tag links' => 0 );

        $targetTypes = array( 'content_node', 'ibexa_subtree', 'subtree', 'node' );
        $rulesLosingTargets = array();
        $rows = $db->arrayQuery( 'SELECT id, rule_id, target_type, target_value FROM explayouts_rule_target' );
        foreach ( $rows as $row )
        {
            if ( !in_array( $row['target_type'], $targetTypes ) )
                continue;

            if ( $classify( $row['target_value'], $packageNodeMap, 'node' ) === 'missing' )
            {
                $db->query( 'DELETE FROM explayouts_rule_target WHERE id = ' . (int)$row['id'] );
                $rulesLosingTargets[(int)$row['rule_id']] = true;
                $removed['rule targets']++;
                continue;
            }

            $newValue = $remapValue( $row['target_value'], $packageNodeMap );
            if ( (string)$newValue !== (string)$row['target_value'] )
            {
                $db->query( 'UPDATE explayouts_rule_target SET target_value = \'' . $db->escapeString( (string)$newValue ) . '\' WHERE id = ' . (int)$row['id'] );
            }
        }
        // A rule whose every target named missing content matches nothing it
        // was written for; left enabled with no target it would match anything.
        foreach ( array_keys( $rulesLosingTargets ) as $ruleID )
        {
            $left = $db->arrayQuery( 'SELECT COUNT(*) AS c FROM explayouts_rule_target WHERE rule_id = ' . (int)$ruleID );
            if ( $left && (int)$left[0]['c'] === 0 )
            {
                $db->query( 'UPDATE explayouts_rule SET enabled = 0 WHERE id = ' . (int)$ruleID );
                $removed['rules disabled']++;
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

            if ( $classify( $row['value_id'], $map, $row['value_type'] === 'ez_location' ? 'node' : 'object' ) === 'missing' )
            {
                $db->query( 'DELETE FROM explayouts_collection_item WHERE id = ' . (int)$row['id'] );
                $removed['collection items']++;
                continue;
            }

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
            {
                if ( $classify( $parameters['parent_location_id'], $packageNodeMap, 'node' ) === 'missing' )
                {
                    $parameters['parent_location_id'] = 0;
                    $removed['query parents']++;
                }
                else
                    $parameters['parent_location_id'] = $remapValue( $parameters['parent_location_id'], $packageNodeMap );
            }

            if ( isset( $parameters['topic_content_id'] ) && $parameters['topic_content_id'] !== null )
            {
                if ( $classify( $parameters['topic_content_id'], $packageObjectMap, 'object' ) === 'missing' )
                {
                    $parameters['topic_content_id'] = 0;
                    $removed['query topics']++;
                }
                else
                    $parameters['topic_content_id'] = $remapValue( $parameters['topic_content_id'], $packageObjectMap );
            }

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
                // A component's content is a reference-site content id, which
                // the theme's component_content() resolves by the remote id the
                // package gives that object (media-o-<id + 776>, media-o-<id>).
                // When the package ships no such object that lookup falls back
                // to fetching the bare number - which logs "Object not found"
                // on every page view, or finds an unrelated object - so the
                // parameter is emptied and the component renders nothing.
                $id = (int)$row['value'];
                if ( !isset( $packageObjectMap[$id] ) && $id > 0
                     && !eZContentObject::fetchByRemoteID( 'media-o-' . ( $id + 776 ), false )
                     && !eZContentObject::fetchByRemoteID( 'media-o-' . $id, false )
                     && !$isBase( $id, 'object' ) )
                {
                    $db->query( 'UPDATE explayouts_block_parameter SET value = \'\' WHERE id = ' . (int)$row['id'] );
                    $removed['component contents']++;
                    continue;
                }

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
            if ( $classify( $row['object_id'], $packageObjectMap, 'object' ) === 'missing' )
            {
                $db->query( 'DELETE FROM eztags_attribute_link WHERE id = ' . (int)$row['id'] );
                $removed['tag links']++;
                continue;
            }

            $newValue = $remapValue( $row['object_id'], $packageObjectMap );
            if ( (int)$newValue !== (int)$row['object_id'] )
            {
                $db->query( 'UPDATE eztags_attribute_link SET object_id = ' . (int)$newValue . ' WHERE id = ' . (int)$row['id'] );
            }
        }

        $summary = array();
        foreach ( $removed as $what => $n )
            $summary[] = "$n $what";
        eZDebug::writeNotice( 'References to content ' . sevenxDemocontentPackageName() .
                              ' does not ship, removed: ' . implode( ', ', $summary ), __FUNCTION__ );

        sevenxFixMenuINIFiles( $packageNodeMap );

        // Reparse package eztags attributes and store them. The package installer
        // may not have linked tags for content classes with a subtree limit, or
        // may have linked them to the wrong object due to package/actual ID drift.
        sevenxFixEzTagsFromPackage( $packageDir, $packageObjectMap );

        // Remap any numeric <embed object_id="..."/> or <embed-node node_id="..."/> 
        // references in ezxmltext fields from package IDs to installed IDs.
        sevenxFixEmbeddedObjectIDs( $packageObjectMap, $packageNodeMap );

        // Everything above rewrote settings and content ids AFTER the package
        // installer had already populated caches. sevenxFixMenuINIFiles() writes
        // a fresh menu.ini per siteaccess with this install's node ids, and the
        // remaps touch content and tags. Nothing else clears eZ's caches at the
        // end of an install - the installer clears only the explayouts resolver
        // cache - so the first page views were served from caches built before
        // these fixes and showed the previous install's node ids. The footer
        // menu is where this surfaced: its second entry pointed at whatever
        // content happened to hold the old node id.
        if ( class_exists( 'eZCache' ) )
        {
            eZCache::clearAll();
            eZDebug::writeNotice( 'Cleared all caches after the post-install fixes', __FUNCTION__ );
        }

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

        // The legal pages each site links from its cookie banner and its lead
        // form. They are per site, not per installation: Fit & Healthy has its
        // own pair and Bold has its own, and the templates used to write out
        // the path of the German Bold pages for every site - which answered 404
        // from both Bold siteaccesses, because the prefix those remove was in
        // the path already. Package node ids, mapped below like the menus.
        $fitPrivacyPolicyPackageId  = 77;
        $fitCookiePolicyPackageId   = 78;
        $boldPrivacyPolicyPackageId = 61;
        $boldCookiePolicyPackageId  = 62;

        $siteaccesses = array(
            'site' => 'fit',
            'eng' => 'fit',
            'sevenx_site_user' => 'fit',
            'bold' => 'bold',
            'bold_ger' => 'bold',
        );
        // Install-local menu ids belong in the project's own siteaccess settings,
        // which override an extension's. Writing them into
        // extension/sevenx_themes_media meant every install rewrote a composer
        // managed, git tracked file with node ids valid only for that database -
        // and eZINI drops the comments in it on the way through. The theme keeps
        // shipping its defaults; this just layers the current install's ids on top.
        $baseDir = eZSys::rootDir() . '/settings/siteaccess/';

        foreach ( $siteaccesses as $sa => $siteType )
        {
            if ( $siteType === 'bold' )
            {
                $mainMenuPackageIds = $boldMainMenuPackageIds;
                $footerMenuPackageIds = $boldFooterMenuPackageIds;
                $mainMenuNexusMap = $boldMainMenuNexusMap;
                $footerMenuNexusMap = $boldFooterMenuNexusMap;
                $privacyPolicyPackageId = $boldPrivacyPolicyPackageId;
                $cookiePolicyPackageId = $boldCookiePolicyPackageId;
            }
            else
            {
                $mainMenuPackageIds = $fitMainMenuPackageIds;
                $footerMenuPackageIds = $fitFooterMenuPackageIds;
                $mainMenuNexusMap = $fitMainMenuNexusMap;
                $footerMenuNexusMap = $fitFooterMenuNexusMap;
                $privacyPolicyPackageId = $fitPrivacyPolicyPackageId;
                $cookiePolicyPackageId = $fitCookiePolicyPackageId;
            }

            $privacyPolicyId = isset( $packageNodeMap[$privacyPolicyPackageId] )
                             ? (int)$packageNodeMap[$privacyPolicyPackageId] : 0;
            $cookiePolicyId = isset( $packageNodeMap[$cookiePolicyPackageId] )
                            ? (int)$packageNodeMap[$cookiePolicyPackageId] : 0;

            // A menu entry whose page the content package did not install is
            // left out. It used to be written as the bare package node id,
            // which names whatever this installation gave that id - on an
            // installation without the demo content, pages that do not exist
            // or unrelated objects. The two lists stay in step: the templates
            // pair them by position.
            $mainMenuIds = array();
            $mainMenuNexusIds = array();
            foreach ( $mainMenuPackageIds as $packageNodeId )
            {
                if ( !isset( $packageNodeMap[$packageNodeId] ) )
                    continue;
                $actualId = (int)$packageNodeMap[$packageNodeId];
                $mainMenuIds[] = $actualId;
                $mainMenuNexusIds[] = isset( $mainMenuNexusMap[$packageNodeId] ) ? (int)$mainMenuNexusMap[$packageNodeId] : $actualId;
            }

            $footerMenuIds = array();
            $footerMenuNexusIds = array();
            foreach ( $footerMenuPackageIds as $packageNodeId )
            {
                if ( !isset( $packageNodeMap[$packageNodeId] ) )
                    continue;
                $actualId = (int)$packageNodeMap[$packageNodeId];
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
            // Each list is reset before its values. eZ merges INI arrays across
            // settings layers, so without the bare Key[] line these ids are
            // appended to the ones sevenx_themes_media ships rather than
            // replacing them - the menus then render both sets at once, the
            // theme's correct entries mixed in with whatever content happens to
            // hold this install's node ids.
            $lines[] = 'MainMenuID[]';
            foreach ( $mainMenuIds as $id )
                $lines[] = "MainMenuID[]=$id";
            $lines[] = 'NexusMainMenuID[]';
            foreach ( $mainMenuNexusIds as $id )
                $lines[] = "NexusMainMenuID[]=$id";
            $lines[] = 'FooterMenuID[]';
            foreach ( $footerMenuIds as $id )
                $lines[] = "FooterMenuID[]=$id";
            $lines[] = 'NexusFooterMenuID[]';
            foreach ( $footerMenuNexusIds as $id )
                $lines[] = "NexusFooterMenuID[]=$id";

            // Left out when the package did not install the page, so that the
            // templates fall back to plain text rather than linking node 0.
            if ( $privacyPolicyId > 0 )
                $lines[] = "PrivacyPolicyID=$privacyPolicyId";
            if ( $cookiePolicyId > 0 )
                $lines[] = "CookiePolicyID=$cookiePolicyId";

            $lines[] = '*/ ?>';

            $content = implode( "\n", $lines ) . "\n";
            @mkdir( dirname( $path ), 0755, true );
            file_put_contents( $path, $content );
        }

        eZDebug::writeNotice( 'Updated menu.ini files for siteaccesses: ' . implode( ', ', $siteaccesses ), __FUNCTION__ );
        return true;
    }
}

if ( !function_exists( 'sevenxClearLinksToContentNotInstalled' ) )
{
    /**
     * Switch off block links whose page the content package did not install.
     *
     * Title blocks in the seeded layouts link their heading to a section by
     * path - /fitness, /recipes - relative to the site's PathPrefix. On an
     * installation without the demo content those sections do not exist and
     * every such heading linked to a 404. A path is kept when it resolves as it
     * is or below one of the site prefixes given; otherwise the link is emptied
     * and the block's use_link switched off, so the heading renders as text.
     * JSON-shaped and node links need nothing here: expLayoutsLinkParameter
     * already renders an unresolvable one as text.
     *
     * Runs after the prefixed URL aliases exist. With the full demo content
     * every path resolves and nothing changes.
     *
     * @param array $prefixes url alias paths of the site home nodes (fit-healthy, bold-agency)
     */
    function sevenxClearLinksToContentNotInstalled( array $prefixes )
    {
        $db = eZDB::instance();
        $rows = $db->arrayQuery( "SELECT id, block_id, value FROM explayouts_block_parameter WHERE name = 'link'" );
        $cleared = 0;
        foreach ( $rows as $row )
        {
            $value = trim( (string)$row['value'] );
            if ( $value === '' || $value[0] !== '/' )
                continue;
            $path = trim( $value, '/' );
            if ( $path === '' )
                continue;

            $found = false;
            foreach ( array_merge( array( '' ), $prefixes ) as $prefix )
            {
                $prefix = trim( (string)$prefix, '/' );
                if ( eZURLAliasML::fetchNodeIDByPath( ( $prefix !== '' ? $prefix . '/' : '' ) . $path ) )
                {
                    $found = true;
                    break;
                }
            }
            if ( $found )
                continue;

            $db->query( "UPDATE explayouts_block_parameter SET value = '' WHERE id = " . (int)$row['id'] );
            $db->query( "UPDATE explayouts_block_parameter SET value = '0' WHERE name = 'use_link' AND block_id = " . (int)$row['block_id'] );
            $cleared++;
        }

        eZDebug::writeNotice( "Switched off $cleared block link(s) to pages this installation does not have", __FUNCTION__ );
        return true;
    }
}

if ( !function_exists( 'sevenxCleanStaleUrlTextAttributes' ) )
{
    function sevenxCleanStaleUrlTextAttributes()
    {
        $db = eZDB::instance();

        $rows = $db->arrayQuery( "
            SELECT o.id, o.name, a.id AS attr_id, a.version, a.data_text AS url_text
            FROM ezcontentobject o
            JOIN ezcontentobject_attribute a ON a.contentobject_id = o.id
            JOIN ezcontentclass_attribute ca ON a.contentclassattribute_id = ca.id AND ca.identifier = 'url_text'
            WHERE a.version = o.current_version AND a.data_text IS NOT NULL"
        );

        $cleaned = 0;
        foreach ( (array) $rows as $row )
        {
            // <> '' in SQL matches nothing where '' is NULL (Oracle): the empty
            // string is skipped here
            if ( (string)$row['url_text'] === '' )
                continue;
            $name = eZURLAliasML::convertToAlias( $row['name'], 'node_' . $row['id'] );
            $url = strtolower( $row['url_text'] );
            $nameLower = strtolower( $name );

            if ( $url !== $nameLower && strpos( $nameLower, $url ) === false && strpos( $url, $nameLower ) === false )
            {
                $db->query( "UPDATE ezcontentobject_attribute SET data_text = '' WHERE id = " . (int)$row['attr_id'] . ' AND version = ' . (int)$row['version'] );
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

        // The alias tables are deliberately not emptied first.
        //
        // updateSubTreePath() recreates a node's alias underneath its parent's
        // element, so it needs that element to exist. Emptying the tables
        // removes the root elements the content tree hangs from, and the walk
        // below then leaves most of the tree without an alias at all: after an
        // install only the media library, whose aliases are created when its
        // objects are published, still had any. Every page on the two sites
        // rendered a system url.
        //
        // Measured on an installed database: with the truncate, 85 alias rows
        // and no alias for either site subtree. Without it, the same walk takes
        // 85 rows to 282 and covers 98/98 media, 138/139 main site and 16/16
        // Bold. bin/php/updateniceurls.php --update-nodes, which does not empty
        // the tables either, produces the same result.
        // Select whole rows, not just ids: eZContentObjectTreeNode::fetch runs the
        // node through the prioritised-language filter, and by this point in an
        // install that filter is stale in-process - fetch returns null for nodes
        // that are plainly there, and the walk skipped them. On a single language
        // install that was 177 of 267 nodes, including the two site home nodes,
        // so their whole subtrees ended up with no alias at all and every page
        // below them answered 404.
        //
        // A node built straight from its row does the same job here:
        // updateSubTreePath only needs the row's own columns.
        // Node 1, the root, is its own parent and has no alias
        $rows = $db->arrayQuery( 'SELECT * FROM ezcontentobject_tree WHERE node_id <> 1 ORDER BY depth ASC, node_id ASC' );
        $count = 0;
        $changed = 0;
        $rebuilt = 0;
        foreach ( $rows as $row )
        {
            $node = eZContentObjectTreeNode::fetch( (int)$row['node_id'] );
            if ( !$node )
            {
                $node = new eZContentObjectTreeNode( $row );
                ++$rebuilt;
            }
            if ( $node->updateSubTreePath() )
                $changed++;
            $count++;
        }

        eZDebug::writeNotice( "Regenerated URL aliases for $count nodes, $changed changed", __FUNCTION__ );
        return true;
    }
}

if ( !function_exists( 'sevenxFixEmbeddedObjectIDs' ) )
{
    function sevenxFixEmbeddedObjectIDs( $packageObjectMap, $packageNodeMap )
    {
        $db = eZDB::instance();

        $rows = $db->arrayQuery( "
            SELECT a.id, a.version, a.data_text
            FROM ezcontentobject_attribute a
            JOIN ezcontentclass_attribute ca ON a.contentclassattribute_id = ca.id
            WHERE ca.data_type_string = 'ezxmltext'
              AND a.data_text LIKE '%<embed%'" );

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

/**
 * Re-key class and class attribute name and description lists stored under a
 * number instead of a language code.
 *
 * eZSerializedObjectNameList keys each name by a locale. Created while no
 * content language exists - the setup wizard, before the site's languages are
 * registered - the locale was false, serialize() turned that key into 0 and
 * always-available into false: a:2:{i:0;s:12:"Publish date";...}. No language
 * lookup finds key 0, so the admin shows the name blank (class/view/1: the
 * folder's tags and publish_date), and the descriptions of the base classes
 * were left the same way.
 *
 * Each numeric entry moves to the class's own language (its initial language,
 * else $fallbackLocale) unless that language already has a non-empty value;
 * always-available then names an entry that exists. Every version is repaired
 * so a class edited later starts from the repaired lists. Idempotent.
 *
 * @return array('changed' => rows written, 'left' => rows still without a language)
 */
function sevenxRepairClassNameLists( $fallbackLocale )
{
    $db = eZDB::instance();
    $locales = array();
    foreach ( $db->arrayQuery( 'SELECT id, locale FROM ezcontent_language' ) as $l )
        $locales[(int)$l['id']] = $l['locale'];
    $classLocale = array();
    foreach ( $db->arrayQuery( 'SELECT id, version, initial_language_id FROM ezcontentclass' ) as $c )
    {
        $lid = (int)$c['initial_language_id'] & ~1;
        $classLocale[(int)$c['id']] = isset( $locales[$lid] ) ? $locales[$lid] : $fallbackLocale;
    }

    $repair = function ( $raw, $locale )
    {
        $a = @unserialize( (string)$raw );
        if ( !is_array( $a ) )
            return null;
        $out = $a;
        foreach ( $a as $k => $v )
        {
            if ( !is_int( $k ) )
                continue;
            unset( $out[$k] );
            if ( !isset( $out[$locale] ) || ( trim( (string)$out[$locale] ) === '' && trim( (string)$v ) !== '' ) )
                $out[$locale] = $v;
        }
        $langs = array_values( array_filter( array_keys( $out ), function ( $k ) { return $k !== 'always-available'; } ) );
        if ( !$langs )
        {
            $out[$locale] = '';
            $langs = array( $locale );
        }
        $aa = isset( $out['always-available'] ) ? $out['always-available'] : false;
        if ( !$aa || !isset( $out[$aa] ) )
        {
            unset( $out['always-available'] );
            $out['always-available'] = isset( $out[$locale] ) ? $locale : $langs[0];
        }
        return $out === $a ? null : serialize( $out );
    };

    $changed = 0;
    $left = array();
    $tables = array(
        'ezcontentclass' => array( 'key' => 'id', 'class' => 'id' ),
        'ezcontentclass_attribute' => array( 'key' => 'id', 'class' => 'contentclass_id' ),
    );
    foreach ( $tables as $table => $t )
    {
        $rows = $db->arrayQuery( "SELECT {$t['key']} AS k, version, {$t['class']} AS class_id, serialized_name_list, serialized_description_list FROM $table" );
        foreach ( $rows as $r )
        {
            $locale = isset( $classLocale[(int)$r['class_id']] ) ? $classLocale[(int)$r['class_id']] : $fallbackLocale;
            $set = array();
            foreach ( array( 'serialized_name_list', 'serialized_description_list' ) as $col )
            {
                $new = $repair( $r[$col], $locale );
                if ( $new !== null )
                    $set[] = "$col = '" . $db->escapeString( $new ) . "'";
            }
            if ( $set )
            {
                $db->query( "UPDATE $table SET " . implode( ', ', $set ) . ' WHERE ' . $t['key'] . ' = ' . (int)$r['k'] . ' AND version = ' . (int)$r['version'] );
                $changed++;
            }
            $check = @unserialize( (string)$r['serialized_name_list'] );
            if ( !is_array( $check ) )
                $left[] = "$table:" . (int)$r['k'] . '/' . (int)$r['version'];
        }
    }
    eZDebug::writeNotice( "Repaired $changed class/attribute name lists", __FUNCTION__ );
    return array( 'changed' => $changed, 'left' => $left );
}

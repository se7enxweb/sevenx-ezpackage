<?php
//
// Copyright (C) 1998 - 2026 Exponential Foundation and 7x. All rights reserved.
//
// This file is part of Exponential. It may be distributed and/or modified
// under the terms of the GNU General Public License v2.0 (or any later
// version) as published by the Free Software Foundation and appearing in the
// file LICENSE included in the packaging of this file.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The GNU General Public License is available at
// https://www.gnu.org/licenses/.
//

// Post-install helpers for the website installer (sevenxezwebininstaller.php)
// of sevenx_site and sevenx_site_clean. The two packages carry this file byte
// for byte the same.
//
// Only the generic repairs of the multisite installer's post-install-fix.php
// are here: the URL aliases of every node, and the class and attribute name
// lists stored without a language. Its steps for the multisite demo content
// (node and layout repair, menus, tags, embedded objects) do not apply to the
// website. Each function is declared only when it is not there yet, so this
// file and the multisite one can be loaded in the same process.

if ( !function_exists( 'sevenxRegenerateURLAliases' ) )
{
    /**
     * Rebuild the URL alias of every node below the root.
     *
     * The alias tables are deliberately not emptied first: updateSubTreePath()
     * recreates a node's alias underneath its parent's element, so it needs
     * that element to exist. Emptying the tables removes the root elements the
     * content tree hangs from, and the walk then leaves most of the tree
     * without an alias. bin/php/updateniceurls.php --update-nodes, which does
     * not empty the tables either, produces the same result.
     *
     * Whole rows are selected, not just ids: eZContentObjectTreeNode::fetch
     * runs the node through the prioritised-language filter, which is stale
     * in-process during an install, so fetch returns null for nodes that are
     * plainly there. A node built straight from its row does the same job:
     * updateSubTreePath only needs the row's own columns.
     */
    function sevenxRegenerateURLAliases()
    {
        $db = eZDB::instance();
        // Node 1, the root, is its own parent and has no alias
        $rows = $db->arrayQuery( 'SELECT * FROM ezcontentobject_tree WHERE node_id <> 1 ORDER BY depth ASC, node_id ASC' );
        $count = 0;
        $changed = 0;
        foreach ( $rows as $row )
        {
            $node = eZContentObjectTreeNode::fetch( (int)$row['node_id'] );
            if ( !$node )
                $node = new eZContentObjectTreeNode( $row );
            if ( $node->updateSubTreePath() )
                $changed++;
            $count++;
        }

        eZDebug::writeNotice( "Regenerated URL aliases for $count nodes, $changed changed", __FUNCTION__ );
        return true;
    }
}

if ( !function_exists( 'sevenxRepairClassNameLists' ) )
{
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
}

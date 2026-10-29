<?php

/**
 * What the edit forms need to choose a license: the catalogue
 * (xrowMetaDataLicense) as option groups for the picker, the inherited
 * license (class default, else installation default), the terms summary,
 * and the validation of a chosen id.
 *
 * Each license is listed once, in its first group of this order: public
 * domain, non-free (one group per type), content (Creative Commons), copyleft,
 * permissive, open source, free software. Its other groups are given as well
 * (option data-groups), so the group filter of the picker shows every license
 * of a group, wherever it is listed.
 */
class xrowMetaDataLicensePicker
{
    const INI_FILE = 'xrowmetadata.ini';
    const INI_GROUP = 'LicenseSettings';
    const CUSTOM_ID = 'LicenseRef-Custom';
    const CONTEXT = 'design/standard/class/datatype';

    /**
     * The order of the option groups; 'non_free' stands for one group per type.
     */
    protected static $groupOrder = array( 'public_domain', 'non_free', 'content', 'copyleft', 'permissive', 'open_source', 'free_software' );

    /**
     * The group codes of the picker's group filter (option data-g).
     */
    static $groupCodes = array( 'open_source' => 'os', 'free_software' => 'fs', 'copyleft' => 'cl', 'permissive' => 'pm',
                                'content' => 'ct', 'public_domain' => 'pd', 'non_free' => 'nf' );

    protected static $typeOrder =array( 'proprietary', 'non_commercial', 'no_derivatives', 'source_available', 'custom' );

    /**
     * The option groups per locale (a persistent worker serves several).
     */
    protected static $choicesCache = array();

    /**
     * Forgets the option groups (after a change of the catalogue or of the
     * allowlist in the same process).
     */
    static function reset()
    {
        self::$choicesCache = array();
    }

    /**
     * Whether licenses can be chosen: [LicenseSettings] Enabled and the
     * catalogue class is there.
     */
    static function enabled()
    {
        if ( !class_exists( 'xrowMetaDataLicense' ) )
        {
            return false;
        }
        $ini = eZINI::instance( self::INI_FILE );
        if ( $ini->hasVariable( self::INI_GROUP, 'Enabled' ) )
        {
            return in_array( strtolower( (string) $ini->variable( self::INI_GROUP, 'Enabled' ) ), array( 'true', 'enabled', '1', 'yes' ), true );
        }
        return true;
    }

    /**
     * The installation default license id ([LicenseSettings] DefaultLicense), '' for none.
     */
    static function installationDefault()
    {
        $ini = eZINI::instance( self::INI_FILE );
        return $ini->hasVariable( self::INI_GROUP, 'DefaultLicense' ) ? trim( (string) $ini->variable( self::INI_GROUP, 'DefaultLicense' ) ) : '';
    }

    /**
     * The catalogue entry of $id with what the forms print, or false.
     *
     * @return array|false
     */
    static function info( $id )
    {
        $id = is_scalar( $id ) ? trim( (string) $id ) : '';
        if ( $id === '' || !class_exists( 'xrowMetaDataLicense' ) )
        {
            return false;
        }
        $entry = xrowMetaDataLicense::byID( $id );
        if ( !is_array( $entry ) )
        {
            return false;
        }
        return self::decorate( $entry );
    }

    /**
     * The license that applies when the object chooses none: the class
     * default $classDefault when it is in the catalogue (source 'class'),
     * else the installation default (source 'installation'), else false.
     *
     * @return array|false
     */
    static function inherited( $classDefault = '' )
    {
        $info = self::info( $classDefault );
        if ( $info )
        {
            $info['source'] = 'class';
            return $info;
        }
        $info = self::info( self::installationDefault() );
        if ( $info )
        {
            $info['source'] = 'installation';
            return $info;
        }
        return false;
    }

    /**
     * Whether $id needs the address or the text of its terms.
     */
    static function isCustom( $id )
    {
        if ( $id === self::CUSTOM_ID )
        {
            return true;
        }
        $info = self::info( $id );
        return $info ? $info['custom'] : false;
    }

    /**
     * Checks a chosen license. Returns '' when it may be stored, else the
     * validation message.
     *
     * A license id equal to $stored (what the attribute has already) is not
     * checked against the catalogue and the allowlist: a catalogue refresh or
     * a narrower allowlist must not keep an editor from saving other changes.
     *
     * @param array $data license, license_custom_url, license_custom_terms, license_year
     * @param string|null $stored the license id stored before this input
     * @return string
     */
    static function validate( array $data, $stored = null )
    {
        $id = isset( $data['license'] ) && is_scalar( $data['license'] ) ? trim( (string) $data['license'] ) : '';
        $year = isset( $data['license_year'] ) && is_scalar( $data['license_year'] ) ? trim( (string) $data['license_year'] ) : '';
        $url = isset( $data['license_custom_url'] ) && is_scalar( $data['license_custom_url'] ) ? trim( (string) $data['license_custom_url'] ) : '';
        $terms = isset( $data['license_custom_terms'] ) && is_scalar( $data['license_custom_terms'] ) ? trim( (string) $data['license_custom_terms'] ) : '';

        if ( $year !== '' && !preg_match( '/^[0-9]{4}(\s*[-\x{2013}]\s*[0-9]{4})?$/u', $year ) )
        {
            return ezpI18n::tr( 'kernel/classes/datatypes', 'The license year must be a year (2026) or a range of years (2019-2026).' );
        }
        if ( $url !== '' && ( !preg_match( '#^https?://#i', $url ) || filter_var( $url, FILTER_VALIDATE_URL ) === false ) )
        {
            return ezpI18n::tr( 'kernel/classes/datatypes', 'The address of the license terms must be a full address starting with https:// or http://.' );
        }
        if ( $id === '' )
        {
            return '';
        }
        if ( $stored !== null && $id === trim( (string) $stored ) )
        {
            return ( self::isCustom( $id ) && $url === '' && $terms === '' )
                ? ezpI18n::tr( 'kernel/classes/datatypes', 'A custom license needs the address of its terms or the text of its terms.' )
                : '';
        }
        if ( !class_exists( 'xrowMetaDataLicense' ) || !is_array( xrowMetaDataLicense::byID( $id ) ) )
        {
            return ezpI18n::tr( 'kernel/classes/datatypes', 'The license "%id" is not in the license catalogue. Choose a license in the list (type a part of its name or SPDX id into the search field to find it), or choose "Use inherited".', null, array( '%id' => $id ) );
        }
        if ( !xrowMetaDataLicense::isAllowed( $id ) )
        {
            return ezpI18n::tr( 'kernel/classes/datatypes', 'The license "%id" is not allowed on this installation. Choose a license in the list, or choose "Use inherited".', null, array( '%id' => $id ) );
        }
        if ( self::isCustom( $id ) && $url === '' && $terms === '' )
        {
            return ezpI18n::tr( 'kernel/classes/datatypes', 'A custom license needs the address of its terms or the text of its terms.' );
        }
        return '';
    }

    /**
     * The option groups of the picker: array of
     * array( 'key', 'label', 'options' => array( option, ... ) ), an option
     * being decorate()'s array. Only licenses the installation allows,
     * plus $selected when it is in the catalogue.
     *
     * @return array
     */
    static function choices( $selected = '' )
    {
        if ( !class_exists( 'xrowMetaDataLicense' ) )
        {
            return array();
        }
        $locale = eZLocale::currentLocaleCode();
        if ( !isset( self::$choicesCache[$locale] ) )
        {
            $groups = array();
            foreach ( self::groupKeys() as $key => $label )
            {
                $groups[$key] = array( 'key' => $key, 'label' => $label, 'options' => array() );
            }
            $all = xrowMetaDataLicense::all();
            foreach ( is_array( $all ) ? $all : array() as $entry )
            {
                if ( !is_array( $entry ) || !isset( $entry['id'] ) || !xrowMetaDataLicense::isAllowed( $entry['id'] ) )
                {
                    continue;
                }
                $option = self::decorate( $entry );
                $groups[$option['list_group']]['options'][] = $option;
            }
            foreach ( $groups as $key => $group )
            {
                if ( !$group['options'] )
                {
                    unset( $groups[$key] );
                    continue;
                }
                usort( $groups[$key]['options'], array( __CLASS__, 'compareOptions' ) );
            }
            self::$choicesCache[$locale] = $groups;
        }
        $groups = self::$choicesCache[$locale];

        // The chosen license is always offered, even when not allowed any more
        $selected = is_scalar( $selected ) ? trim( (string) $selected ) : '';
        if ( $selected !== '' )
        {
            $found = false;
            foreach ( $groups as $group )
            {
                foreach ( $group['options'] as $option )
                {
                    if ( $option['id'] === $selected )
                    {
                        $found = true;
                        break 2;
                    }
                }
            }
            $info = $found ? false : self::info( $selected );
            if ( $info )
            {
                $key = $info['list_group'];
                if ( !isset( $groups[$key] ) )
                {
                    $labels = self::groupKeys();
                    $groups[$key] = array( 'key' => $key, 'label' => $labels[$key], 'options' => array() );
                }
                array_unshift( $groups[$key]['options'], $info );
            }
        }
        return array_values( $groups );
    }

    static function compareOptions( $a, $b )
    {
        return strnatcasecmp( $a['name'], $b['name'] );
    }

    /**
     * The option groups in their order: group key => label.
     *
     * @return array
     */
    static function groupKeys()
    {
        $groupLabels = self::groupLabels();
        $typeLabels = self::typeLabels();
        $keys = array();
        foreach ( self::$groupOrder as $group )
        {
            if ( $group === 'non_free' )
            {
                foreach ( self::$typeOrder as $type )
                {
                    $keys['non_free.' . $type] = $typeLabels[$type];
                }
                $keys['non_free.other'] = $typeLabels['other'];
                continue;
            }
            $keys[$group] = $groupLabels[$group];
        }
        $keys['other'] = $groupLabels['other'];
        return $keys;
    }

    /**
     * The groups of the catalogue with their names (the group filter).
     *
     * @return array
     */
    static function groupLabels()
    {
        return array(
            'open_source' => ezpI18n::tr( self::CONTEXT, 'Open source' ),
            'free_software' => ezpI18n::tr( self::CONTEXT, 'Free software' ),
            'copyleft' => ezpI18n::tr( self::CONTEXT, 'Copyleft' ),
            'permissive' => ezpI18n::tr( self::CONTEXT, 'Permissive' ),
            'content' => ezpI18n::tr( self::CONTEXT, 'Content (Creative Commons)' ),
            'public_domain' => ezpI18n::tr( self::CONTEXT, 'Public domain' ),
            'non_free' => ezpI18n::tr( self::CONTEXT, 'Non-free' ),
            'other' => ezpI18n::tr( self::CONTEXT, 'Other licenses' ),
        );
    }

    /**
     * The types of non-free licenses with their group names.
     *
     * @return array
     */
    static function typeLabels()
    {
        return array(
            'proprietary' => ezpI18n::tr( self::CONTEXT, 'Non-free: proprietary, all rights reserved' ),
            'non_commercial' => ezpI18n::tr( self::CONTEXT, 'Non-free: non-commercial' ),
            'no_derivatives' => ezpI18n::tr( self::CONTEXT, 'Non-free: no derivatives' ),
            'source_available' => ezpI18n::tr( self::CONTEXT, 'Non-free: source available' ),
            'custom' => ezpI18n::tr( self::CONTEXT, 'Non-free: custom terms' ),
            'other' => ezpI18n::tr( self::CONTEXT, 'Non-free: other' ),
        );
    }

    /**
     * The four terms the summary shows, key => label.
     *
     * @return array
     */
    static function termLabels()
    {
        return array(
            'commercial' => ezpI18n::tr( self::CONTEXT, 'Commercial use' ),
            'derivatives' => ezpI18n::tr( self::CONTEXT, 'Derivatives' ),
            'share_alike' => ezpI18n::tr( self::CONTEXT, 'Share-alike' ),
            'attribution' => ezpI18n::tr( self::CONTEXT, 'Attribution' ),
        );
    }

    /**
     * $entry of the catalogue with: 'label' (name and id), 'retired',
     * 'custom', 'list_group' (the option group it is listed in),
     * 'group_list' (its groups, space separated), 'terms_code' (four
     * characters: commercial, derivatives, share-alike, attribution; 1 yes,
     * 0 no, - unknown) and 'terms_list' (key, label, value: yes|no|unknown).
     *
     * @return array
     */
    static function decorate( array $entry )
    {
        $id = (string) $entry['id'];
        $name = isset( $entry['name'] ) && $entry['name'] !== '' ? (string) $entry['name'] : $id;
        $groups = isset( $entry['groups'] ) && is_array( $entry['groups'] ) ? array_values( array_map( 'strval', $entry['groups'] ) ) : array();
        $type = isset( $entry['type'] ) ? (string) $entry['type'] : '';
        $retired = !empty( $entry['deprecated'] ) || !empty( $entry['retired_by_cc'] );

        $listGroup = 'other';
        foreach ( self::$groupOrder as $group )
        {
            if ( in_array( $group, $groups, true ) )
            {
                if ( $group === 'non_free' )
                {
                    $listGroup = 'non_free.' . ( in_array( $type, self::$typeOrder, true ) ? $type : 'other' );
                }
                else
                {
                    $listGroup = $group;
                }
                break;
            }
        }

        $terms = isset( $entry['terms'] ) && is_array( $entry['terms'] ) ? $entry['terms'] : array();
        $code = '';
        $list = array();
        foreach ( self::termLabels() as $key => $label )
        {
            $value = array_key_exists( $key, $terms ) && $terms[$key] !== null ? ( $terms[$key] ? 'yes' : 'no' ) : 'unknown';
            $code .= $value === 'yes' ? '1' : ( $value === 'no' ? '0' : '-' );
            $list[] = array( 'key' => $key, 'label' => $label, 'value' => $value );
        }

        $entry['id'] = $id;
        $entry['name'] = $name;
        $entry['url'] = isset( $entry['url'] ) ? (string) $entry['url'] : '';
        $entry['groups'] = $groups;
        $entry['type'] = $type;
        $entry['label'] = $name === $id ? $id : $name . ' (' . $id . ')';
        $entry['retired'] = $retired;
        // Terms given per object (LicenseRef-Custom, or an entry marked per_object)
        $entry['custom'] = ( $id === self::CUSTOM_ID || !empty( $entry['per_object'] ) );
        $entry['list_group'] = $listGroup;
        $entry['group_list'] = implode( ' ', $groups );
        // Short forms for the option attributes of the picker (some 1300
        // options): the group codes, and the address with a common prefix
        // written as s: (SPDX page of the id) or c: (Creative Commons licenses)
        $codes = array();
        foreach ( $groups as $group )
        {
            $codes[] = isset( self::$groupCodes[$group] ) ? self::$groupCodes[$group] : $group;
        }
        $entry['group_codes'] = implode( ' ', $codes );
        $url = $entry['url'];
        if ( $url === 'https://spdx.org/licenses/' . $id . '.html' )
        {
            $url = 's:';
        }
        elseif ( strpos( $url, 'https://creativecommons.org/licenses/' ) === 0 )
        {
            $url = 'c:' . substr( $url, strlen( 'https://creativecommons.org/licenses/' ) );
        }
        $entry['url_short'] = $url;
        $entry['terms_code'] = $code;
        $entry['terms_list'] = $list;
        return $entry;
    }
}

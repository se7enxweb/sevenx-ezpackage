<?php
//
// Created on: <7-Aug-2026 13:00:00 gb>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: Exponential
// SOFTWARE RELEASE: 6.0.x
// COPYRIGHT NOTICE: Copyright (C) 1998 - 2026 7x
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

require_once dirname( __FILE__ ) . '/post-install-fix.php';

class sevenxMultiSiteInstaller extends eZSiteInstaller
{
    const MAJOR_VERSION = 1.0;
    const MINOR_VERSION = 0;

    function __construct( $parameters = false )
    {
        parent::__construct( $parameters );
    }

    function &instance( $params )
    {
        $impl = & $GLOBALS["sevenxMultiSiteInstallerGlobalInstance"];
        if ( get_class( $impl ) != "sevenxmultisiteinstaller" )
            $impl = new sevenxMultiSiteInstaller( $params );
        return $impl;
    }

    function resetGlobals()
    {
        unset( $GLOBALS["sevenxMultiSiteInstallerGlobalInstance"] );
    }

    function initSettings( $parameters )
    {
        $siteINI = eZINI::instance();
        $classIdentifier = 'template_look';
        //get the class
        $class = eZContentClass::fetchByIdentifier( $classIdentifier, true, eZContentClass::VERSION_STATUS_TEMPORARY );
        if ( ! $class )
        {
            $class = eZContentClass::fetchByIdentifier( $classIdentifier, true, eZContentClass::VERSION_STATUS_DEFINED );
            if ( ! $class )
            {
                eZDebug::writeError( "Warning, DEFINED version for class identifier $classIdentifier does not exist." );
                return;
            }
        }
        $classId = $class->attribute( 'id' );
        $this->Settings['template_look_class_id'] = $classId;
        $objects = eZContentObject::fetchSameClassList( $classId );
        if ( ! count( $objects ) )
        {
            eZDebug::writeError( "Object of class $classIdentifier does not exist." );
            return;
        }
        $templateLookObject = $objects[0];
        $this->Settings['template_look_object'] = $templateLookObject;
        $this->Settings['template_look_object_id'] = $templateLookObject->attribute( 'id' );
        if ( ! is_array( $parameters ) )
            return;
        $this->addSetting( 'admin_account_id', eZSiteInstaller::getParam( $parameters, 'object_remote_map/1bb4fe25487f05527efa8bfd394cecc7', '' ) );
        $this->addSetting( 'guest_accounts_id', eZSiteInstaller::getParam( $parameters, 'object_remote_map/5f7f0bdb3381d6a461d8c29ff53d908f', '' ) );
        $this->addSetting( 'anonymous_accounts_id', eZSiteInstaller::getParam( $parameters, 'object_remote_map/15b256dbea2ae72418ff5facc999e8f9', '' ) );
        $this->addSetting( 'package_object', eZSiteInstaller::getParam( $parameters, 'package_object', false ) );
        $this->addSetting( 'design_list', eZSiteInstaller::getParam( $parameters, 'design_list', array() ) );
        $this->addSetting( 'database_settings', eZSiteInstaller::getParam( $parameters, 'database_settings', array() ) );
        $this->addSetting( 'main_site_design', 'media' );
        // Order matters: later extensions win design and template overrides, so
        // the two themes stay at the end. Within the explayouts family the
        // providers (core, api, standard, the query types) are listed before
        // the UI that consumes them.
        $this->addSetting( 'extension_list', array( 
            'xrowmetadata',
            // Exponential UI before ezjscore: its settings (ezjsc::jquery as jQuery 4,
            // the admin's BackendJavaScriptList and BackendCSSFileList) win over
            // ezjscore's, as an extension listed earlier does.
            'expui',
            'ezjscore',
            // Remote services (ezjscore/call/exp<domain>::<service>) for remote admin apps
            // and JavaScript frontends: doc/bc/6.0/backend_ezjscore_services.md. Only the
            // services; its portal designs get no siteaccess in a default install.
            'expservices',
            'ezoe',
            // Cross site request forgery protection for every posted form. It
            // has been active on the installation all along and was never in
            // this list, so a reinstall switched it off.
            'ezformtoken',
            'ezwt',
            'ezstarrating',
            'ezgmaplocation',
            'ezautosave',
            'ezodf',
            'ezie',
            'ezprestapi',
            'ezpaypal',
            'owsimpleoperator',
            'swark',
            'bcgooglesitemaps',
            'bcwebsitestatistics',
            'bccie',
            'xrowextract',
            'enhancedezbinaryfile',
            'enhancedselection2',
            // The ezbirthday datatype (a birthday field for user and person classes).
            'birthday',
            'ezwebin',
            'ezmultiupload',
            // Content syndication between installations (feeds, import and export
            // filters, the ezsyndicate datatype); its tables come from extensionSchemas().
            'syndication',
            'ezupdate',
            'git_manager',
            'eztags',
            'ngclasslist',
            'explayouts',
            'explayouts_core',
            'explayouts_api',
            'explayouts_standard',
            'explayouts_site_api',
            'explayouts_relation_list_query',
            'explayouts_tags_query',
            'explayouts_content_browser_core',
            'explayouts_content_browser',
            'explayouts_content_browser_ui',
            'explayouts_ui',
            'explayouts_ui_api',
            'expquery_translator',
            'expsite_core',
            'expsite_api',
            'expsite_app',
            'exp_enhanced_link',
            'expchangeclass',
            'cjw_newsletter',
            'recaptcha',
            // An hCaptcha field for forms, next to recaptcha; it needs its keys set.
            'hcaptcha',
            // Two-factor (TOTP, e-mail) and social login; the login handler and policies: twoFactorAuthenticationAvailable()
            'sevenx_authentication_2fa',
            'powercontent',
            'sevenx_dse',
            'sevenx_themes_media',
            'sevenx_themes_simple'
            // exp_adminui is deliberately not here: it is an access extension, switched on by the adminui
            // siteaccess alone (ActiveAccessExtensions[], see adminUISiteaccessName()), so every other
            // siteaccess keeps its own design.
        ) );
        $this->addSetting( 'version', $this->solutionVersion() );
        $this->addSetting( 'locales', eZSiteInstaller::getParam( $parameters, 'all_language_codes', array() ) );
        $this->addSetting( 'primary_language', eZSiteInstaller::getParam( $parameters, 'all_language_codes/0', '' ) );
        // The bundled content is in eng-US and stays in it whatever language
        // is chosen: a site in another language falls back to it. The setup
        // passes it as fallback_language_codes; a setup that does not is
        // answered the same way here.
        $fallbackLocales = eZSiteInstaller::getParam( $parameters, 'fallback_language_codes', null );
        if ( !is_array( $fallbackLocales ) )
            $fallbackLocales = in_array( 'eng-US', (array)$this->setting( 'locales' ) ) ? array() : array( 'eng-US' );
        $this->addSetting( 'fallback_locales', array_values( array_diff( $fallbackLocales, (array)$this->setting( 'locales' ) ) ) );
        // usual user siteaccess like 'site'
        $userSiteaccess = eZSiteInstaller::getParam( $parameters, 'user_siteaccess', 'site' );
        if ( $userSiteaccess == 'sevenx_site_user' || $userSiteaccess == '' )
            $userSiteaccess = 'site';
        $this->addSetting( 'user_siteaccess', $userSiteaccess );
        // usual admin siteaccess like 'admin'
        $adminSiteaccess = eZSiteInstaller::getParam( $parameters, 'admin_siteaccess', 'admin' );
        if ( $adminSiteaccess == 'sevenx_site_admin' || $adminSiteaccess == '' )
            $adminSiteaccess = 'admin';
        $this->addSetting( 'admin_siteaccess', $adminSiteaccess );
        // the editor siteaccess: the admin for content editing only, which the
        // setup creates from the admin one (eZStepCreateSites::createEditorSiteAccess())
        $this->addSetting( 'editor_siteaccess', 'editor' );
        // the Admin UI siteaccess (exp_adminui), '' when the installation has none: adminUISiteaccessName()
        $this->addSetting( 'adminui_siteaccess', $this->adminUISiteaccessName() );
        // Site title from the setup. When nobody typed one, the web wizard
        // offers the site package's summary ("MultiSite Default Installation")
        // and the kickstarter falls back to it, and that became the SiteName
        // of every Fit & Healthy siteaccess - the browser title of each page
        // and the logo's alt text. It describes the package, not the site, so
        // the site's own name is used instead.
        $siteTitle = trim( (string)eZSiteInstaller::getParam( $parameters, 'site_type/title', '' ) );
        $packageSummary = trim( (string)eZSiteInstaller::getParam( $parameters, 'site_type/name', '' ) );
        if ( $siteTitle === '' || $siteTitle === $packageSummary || $siteTitle === 'MultiSite Default Installation' )
            $siteTitle = $userSiteaccess === 'bold' ? 'Bold Agency' : 'Fit & Healthy';
        $this->addSetting( 'site_title', $siteTitle );
        // extra siteaccess based on languages info, like 'eng', 'rus', ...
        $primaryLanguage = $this->setting( 'primary_language' );
        $userSiteaccess = $this->setting( 'user_siteaccess' );
        $languageBasedList = array();
        $languageSiteaccessMap = array();
        // Names a translation siteaccess must not take: the siteaccesses the
        // installation has anyway.
        $taken = array( $userSiteaccess, $this->setting( 'admin_siteaccess' ), $this->setting( 'editor_siteaccess' ), 'site', 'admin', 'editor', 'adminui', 'bold', 'bold_ger' );
        $translationLocales = array_values( array_diff( (array)$this->setting( 'locales' ), array( $primaryLanguage ) ) );
        foreach ( $this->setting( 'locales' ) as $locale )
        {
            if ( $locale != $primaryLanguage )
            {
                // Unique among the translations: eng-US and eng-GB were both
                // 'eng', and the second siteaccess overwrote the first. The
                // first keeps the short name, later ones add their country
                // (eng_gb).
                $languageName = $this->languageNameFromLocale( $locale, $translationLocales );
                // Bold Agency translations are prefixed to avoid colliding
                // with the Fit & Healthy language siteaccesses.
                if ( $userSiteaccess === 'bold' )
                    $languageName = 'bold_' . $languageName;
                // ... and not one of the siteaccesses the installation has anyway
                if ( in_array( $languageName, $taken, true ) )
                    $languageName .= '_' . strtolower( substr( $locale, strpos( $locale, '-' ) + 1 ) );
                $suffix = 2;
                $base = $languageName;
                while ( in_array( $languageName, $taken, true ) )
                    $languageName = $base . $suffix++;
                $taken[] = $languageName;
                $languageBasedList[] = $languageName;
                $languageSiteaccessMap[$locale] = $languageName;
            }
        }
        $this->addSetting( 'language_based_siteaccess_list', $languageBasedList );
        $this->addSetting( 'language_siteaccess_map', $languageSiteaccessMap );
        $this->addSetting( 'user_siteaccess_list', array_merge( array( 
            $this->setting( 'user_siteaccess' ) 
        ), $languageBasedList ) );
        // AvailableSiteAccessList, RelatedSiteAccessList and SiteList: adminui among them when there is one
        $this->addSetting( 'all_siteaccess_list', array_values( array_filter( array_merge( $this->setting( 'user_siteaccess_list' ), array(
            $this->setting( 'admin_siteaccess' ),
            $this->setting( 'editor_siteaccess' ),
            $this->setting( 'adminui_siteaccess' )
        ) ), 'strlen' ) ) );
        $this->addSetting( 'access_type', eZSiteInstaller::getParam( $parameters, 'site_type/access_type', '' ) );
        $this->addSetting( 'access_type_value', eZSiteInstaller::getParam( $parameters, 'site_type/access_type_value', '' ) );
        $this->addSetting( 'admin_access_type_value', eZSiteInstaller::getParam( $parameters, 'site_type/admin_access_type_value', '' ) );
        $editorAccessValue = trim( (string)eZSiteInstaller::getParam( $parameters, 'site_type/editor_access_type_value', '' ) );
        if ( $editorAccessValue === '' && class_exists( 'eZStepSiteAccess' ) )
            $editorAccessValue = (string)eZStepSiteAccess::defaultEditorAccessValue( $this->setting( 'access_type' ) );
        $this->addSetting( 'editor_access_type_value', $editorAccessValue );
        // The host the site's addresses are built on. Nothing passes 'host',
        // and without it they were built on the host of the request running
        // the installation: "localhost" for the kickstarter, which runs on the
        // command line, so every SiteURL came out as localhost/<siteaccess>.
        // The wizard's own site URL (site_details URL in kickstart.ini, the
        // browser's address in the web wizard) says where the site is.
        $installHost = (string)eZSiteInstaller::getParam( $parameters, 'host', '' );
        if ( $installHost === '' )
            $installHost = (string)eZSiteInstaller::getParam( $parameters, 'site_type/url', '' );
        $this->addSetting( 'host', $installHost );
        $siteaccessUrls = array( 
            'admin' => $this->createSiteaccessUrls( array( 
                'siteaccess_list' => array( 
                    $this->setting( 'admin_siteaccess' ) 
                ), 
                'access_type' => $this->setting( 'access_type' ), 
                'access_type_value' => $this->setting( 'admin_access_type_value' ), 
                'host' => $this->setting( 'host' ),
                'host_prepend_siteaccess' => false,
                // The site runs with ForceVirtualHost=true (commonSiteINISettings):
                // no index.php, which the web wizard's requests all carried
                'index_file' => ''
            ) ),
            'editor' => $this->createSiteaccessUrls( array( 
                'siteaccess_list' => array( 
                    $this->setting( 'editor_siteaccess' ) 
                ), 
                'access_type' => $this->setting( 'access_type' ), 
                'access_type_value' => $this->setting( 'editor_access_type_value' ), 
                'host' => $this->setting( 'host' ),
                'host_prepend_siteaccess' => false,
                'index_file' => ''
            ) ),
            'user' => $this->createSiteaccessUrls( array( 
                'siteaccess_list' => array( 
                    $this->setting( 'user_siteaccess' ) 
                ), 
                'access_type' => $this->setting( 'access_type' ), 
                'access_type_value' => $this->setting( 'access_type_value' ), 
                'host' => $this->setting( 'host' ),
                'host_prepend_siteaccess' => false,
                // The site runs with ForceVirtualHost=true (commonSiteINISettings):
                // no index.php, which the web wizard's requests all carried
                'index_file' => ''
            ) ),
            'translation' => $this->createSiteaccessUrls( array( 
                'siteaccess_list' => $this->setting( 'language_based_siteaccess_list' ), 
                'access_type' => $this->setting( 'access_type' ),
                // Only a port counts up. For host access the value is the
                // site's host name, which the translations are put in front
                // of (ger.example.com); adding one to it made SiteURL "ger.128"
                // out of 127.0.0.1 and "ger.1" out of any name.
                'access_type_value' => $this->setting( 'access_type' ) === 'port'
                                       ? (int)$this->setting( 'access_type_value' ) + 1
                                       : $this->setting( 'access_type_value' ),
                'host' => $this->setting( 'host' ),
                'exclude_port_list' => array( 
                    $this->setting( 'admin_access_type_value' ), 
                    $this->setting( 'editor_access_type_value' ), 
                    $this->setting( 'access_type_value' )
                ),
                'index_file' => ''
            ) )
        );
        $this->addSetting( 'siteaccess_urls', $siteaccessUrls );
        // $this->addSetting( 'var_dir', eZSiteInstaller::getParam( $parameters, 'var_dir', 'var/' . $this->setting( 'user_siteaccess' ) ) );
        $this->addSetting( 'var_dir', eZSiteInstaller::getParam( $parameters, 'var_dir', 'var/site' ) );
    }

    function initSteps()
    {
        $postInstallSteps = array( 
            array( 
                '_function' => 'dbBegin', 
                '_params' => array() 
            ), 
            array( 
                '_function' => 'setVersion', 
                '_params' => array() 
            ), 

            array(
                '_function' => 'postInstallFixContentObjectNameLanguages',
                '_params' => array()
            ),

            // Both are repairs of data the package install leaves incomplete,
            // and both only need the content objects, which exist by now. They
            // run first because executeSteps aborts the chain on the first step
            // that reports an error, and several later steps are fragile.
            array(
                '_function' => 'postInstallResolveRelationListIds',
                '_params' => array()
            ),
            array(
                '_function' => 'postInstallRekeyStarRatings',
                '_params' => array()
            ),
            // cjw_newsletter is in extension_list; its content classes are not part of the
            // site packages, so they are imported here into the "Newsletter" class group
            // from the packages the extension ships. Idempotent and independent of the rest.
            array(
                '_function' => 'postInstallImportNewsletterClasses',
                '_params' => array()
            ),

            array( 
                '_function' => 'postInstallAdminSiteaccessINIUpdate', 
                '_params' => array() 
            ), 
            array( 
                '_function' => 'postInstallUserSiteaccessINIUpdate', 
                '_params' => array() 
            ), 
            array( 
                '_function' => 'createTranslationSiteAccesses', 
                '_params' => array() 
            ), 
            array( 
                '_function' => 'createSecondarySiteaccesses', 
                '_params' => array() 
            ), 
            array( 
                '_function' => 'updateTemplateLookClassAttributes', 
                '_params' => array() 
            ), 
            array( 
                '_function' => 'updateTemplateLookObjectAttributes', 
                '_params' => array() 
            ), 
            array( 
                '_function' => 'swapNodes', 
                '_params' => array( 
                    'src_node' => array( 
                        'name' => "eZ Publish" 
                    ), 
                    'dst_node' => array( 
                        'name' => "Home" 
                    ) 
                ) 
            ), 
            array( 
                '_function' => 'removeContentObject', 
                '_params' => array( 
                    'name' => 'eZ Publish' 
                ) 
            ), 
            array( 
                '_function' => 'removeClassAttribute', 
                '_params' => array( 
                    'class_id' => $this->setting( 'template_look_class_id' ), 
                    'attribute_identifier' => 'id' 
                ) 
            ), 
            array( 
                '_function' => 'createContentSection', 
                '_params' => array( 
                    'name' => 'Restricted', 
                    'navigation_part_identifier' => 'ezcontentnavigationpart' 
                ) 
            ), 
            array( 
                '_function' => 'addPoliciesForRole', 
                '_params' => array( 
                    'role_name' => 'Anonymous', 
                    'policies' => array( 
                        array( 
                            'module' => 'shop', 
                            'function' => 'buy', 
                            'limitation' => array( 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array(
                               'Class' => array( 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'comment' 
                                        )
                                    ),
                                    array(
                                        '_function' => 'classIDbyIdentifier',
                                        '_params' => array(
                                            'identifier' => 'review'
                                        )
                                    )
                                ), 
                               'Section' => array( 
                                    '_function' => 'sectionIDbyName', 
                                    '_params' => array( 
                                        'section_name' => 'Standard' 
                                    ) 
                               ), 
                               'Language' => array( 
                                          $this->setting( 'primary_language' ) ? $this->setting( 'primary_language' ) : 'eng-US' 
                               ) 
                            ) 
                        ), 
                        array(
                            'module' => 'ezjscore',
                            'function' => 'call',
                            'limitation' => array(
                                'FunctionList' => array(
                                    'ezstarrating_rate', 'ezstarrating_user_has_rated', 'loadMore'
                                    )
                                )
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'read', 
                            'limitation' => array( 
                                'Class' => array( 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'image' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'banner' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'flash' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'real_video' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'windows_media' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'quicktime' 
                                        ) 
                                    ),
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'video' 
                                        ) 
                                    ) 
                                ), 
                                'Section' => array( 
                                    '_function' => 'sectionIDbyName', 
                                    '_params' => array( 
                                        'section_name' => 'Media' 
                                    ) 
                                ) 
                            ) 
                        ) 
                    ) 
                ) 
            ), 
            array( 
                '_function' => 'removePoliciesForRole', 
                '_params' => array( 
                    'role_name' => 'Editor', 
                    'policies' => array( 
                        array( 
                            'module' => 'content', 
                            'function' => '*' 
                        ) 
                    ) 
                ) 
            ), 
            array( 
                '_function' => 'addPoliciesForRole', 
                '_params' => array( 
                    'role_name' => 'Editor', 
                    'policies' => array( 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array( 
                                'Class' => array( 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'folder' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'link' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'file' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'product' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'feedback_form' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'frontpage' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'article' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'article_mainpage' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'article_subpage' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'blog' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'poll' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'multicalendar' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'documentation_page' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'infobox' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'flash' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'quicktime' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'windows_media' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'real_video' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'gallery' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'forum' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'forums' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'event_calendar' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'banner' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'image' 
                                        ) 
                                    ) 
                                ), 
                                'ParentClass' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'folder' 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array( 
                                'Class' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'blog_post' 
                                    ) 
                                ), 
                                'ParentClass' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'blog' 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array( 
                                'Class' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'forum_topic' 
                                    ) 
                                ), 
                                'ParentClass' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'forum' 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array( 
                                'Class' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'event' 
                                    ) 
                                ), 
                                'ParentClass' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'event_calendar' 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array( 
                                'Class' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'image' 
                                    ) 
                                ), 
                                'ParentClass' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'gallery' 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array( 
                                'Class' => array( 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'folder' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'link' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'feedback_form' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'frontpage' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'documentation_page' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'gallery' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'event_calendar' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'multicalendar' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'forums' 
                                        ) 
                                    ) 
                                ), 
                                'ParentClass' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'frontpage' 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'websitetoolbar', 
                            'function' => 'use', 
                            'limitation' => array( 
                                'Class' => array( 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'folder' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'link' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'article' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'article_mainpage' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'article_subpage' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'blog' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'blog_post' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'product' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'feedback_form' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'frontpage' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'documentation_page' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'multicalendar' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'poll' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'file' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'flash' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'image' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'quicktime' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'windows_media' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'real_video' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'gallery' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'forum' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'event' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'event_calendar' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'forums' 
                                        ) 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'edit' 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'read', 
                            'limitation' => array( 
                                'Section' => array( 
                                    array( 
                                        '_function' => 'sectionIDbyName', 
                                        '_params' => array( 
                                            'section_name' => 'Standard' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'sectionIDbyName', 
                                        '_params' => array( 
                                            'section_name' => 'Restricted' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'sectionIDbyName', 
                                        '_params' => array( 
                                            'section_name' => 'Media' 
                                        ) 
                                    ) 
                                ) 
                            ) 
                        ) 
                    ) 
                ) 
            ), 
            array( 
                '_function' => 'addPoliciesForRole', 
                '_params' => array( 
                    'role_name' => 'Editor', 
                    'policies' => array( 
                        array( 
                            'module' => 'notification', 
                            'function' => 'use' 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'manage_locations' 
                        ), 
                        array( 
                            'module' => 'ezodf', 
                            'function' => '*' 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'diff' 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'versionread' 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'versionremove' 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'remove' 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'translate' 
                        ), 
                        array( 
                            'module' => 'rss', 
                            'function' => 'feed' 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'bookmark' 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'pendinglist' 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'dashboard' 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'view_embed' 
                        )
                    ) 
                ) 
            ), 
            array( 
                '_function' => 'createContentObject', 
                '_params' => array( 
                    'class_identifier' => 'user_group', 
                    'location' => 'users', 
                    'attributes' => array( 
                        'name' => 'Partners', 
                        'description' => '' 
                    ) 
                ) 
            ), 
            array( 
                '_function' => 'setSection', 
                '_params' => array( 
                    // created above under Users
                    'location' => 'users/partners',
                    'section_name' => 'Restricted'
                )
            ),
            array(
                '_function' => 'setSection',
                '_params' => array(
                    // the top-level Archives folder of the base data: its content is not available by default,
                    // only through roles that allow the Restricted section
                    'location' => 'x_archives',
                    'section_name' => 'Restricted'
                )
            ),
            array( 
                '_function' => 'addPoliciesForRole', 
                '_params' => array( 
                    'role_name' => 'Partner', 
                    'policies' => array( 
                        array( 
                            'module' => 'content', 
                            'function' => 'read', 
                            'limitation' => array( 
                                'Section' => array( 
                                    '_function' => 'sectionIDbyName', 
                                    '_params' => array( 
                                        'section_name' => 'Restricted' 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array( 
                                'Class' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'forum_topic' 
                                    ) 
                                ), 
                                'Section' => array( 
                                    '_function' => 'sectionIDbyName', 
                                    '_params' => array( 
                                        'section_name' => 'Restricted' 
                                    ) 
                                ), 
                                'ParentClass' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'forum' 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array( 
                                'Class' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'forum_reply' 
                                    ) 
                                ), 
                                'Section' => array( 
                                    '_function' => 'sectionIDbyName', 
                                    '_params' => array( 
                                        'section_name' => 'Restricted' 
                                    ) 
                                ), 
                                'ParentClass' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'forum_topic' 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array( 
                                'Class' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'comment' 
                                    ) 
                                ), 
                                'Section' => array( 
                                    '_function' => 'sectionIDbyName', 
                                    '_params' => array( 
                                        'section_name' => 'Restricted' 
                                    ) 
                                ), 
                                'ParentClass' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'article' 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'edit', 
                            'limitation' => array( 
                                'Class' => array( 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'comment' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'forum_topic' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'forum_reply' 
                                        ) 
                                    ) 
                                ), 
                                'Section' => array( 
                                    array( 
                                        '_function' => 'sectionIDbyName', 
                                        '_params' => array( 
                                            'section_name' => 'Restricted' 
                                        ) 
                                    ) 
                                ), 
                                'Owner' => 1 
                            ) 
                        ),  // self
                        array( 
                            'module' => 'user', 
                            'function' => 'selfedit' 
                        ), 
                        array( 
                            'module' => 'notification', 
                            'function' => 'use' 
                        ) 
                    ) 
                ) 
            ), 
            array( 
                '_function' => 'renameContentObject', 
                '_params' => array( 
                    'contentobject_id' => '11',  // 11 is id of "Guest accounts"
                    'name' => 'Members' 
                ) 
            ), 
            array( 
                '_function' => 'addPoliciesForRole', 
                '_params' => array( 
                    'role_name' => 'Member', 
                    'policies' => array( 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array( 
                                'Class' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'forum_topic' 
                                    ) 
                                ), 
                                'Section' => array( 
                                    '_function' => 'sectionIDbyName', 
                                    '_params' => array( 
                                        'section_name' => 'Standard' 
                                    ) 
                                ), 
                                'ParentClass' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'forum' 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array( 
                                'Class' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'forum_reply' 
                                    ) 
                                ), 
                                'Section' => array( 
                                    '_function' => 'sectionIDbyName', 
                                    '_params' => array( 
                                        'section_name' => 'Standard' 
                                    ) 
                                ), 
                                'ParentClass' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'forum_topic' 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'create', 
                            'limitation' => array( 
                                'Class' => array( 
                                    '_function' => 'classIDbyIdentifier', 
                                    '_params' => array( 
                                        'identifier' => 'comment' 
                                    ) 
                                ), 
                                'Section' => array( 
                                    '_function' => 'sectionIDbyName', 
                                    '_params' => array( 
                                        'section_name' => 'Standard' 
                                    ) 
                                ), 
                                'ParentClass' => array( 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'article' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'blog_post' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'article_mainpage' 
                                        ) 
                                    ) 
                                ) 
                            ) 
                        ), 
                        array( 
                            'module' => 'content', 
                            'function' => 'edit', 
                            'limitation' => array( 
                                'Class' => array( 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'comment' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'forum_topic' 
                                        ) 
                                    ), 
                                    array( 
                                        '_function' => 'classIDbyIdentifier', 
                                        '_params' => array( 
                                            'identifier' => 'forum_reply' 
                                        ) 
                                    ) 
                                ), 
                                'Section' => array( 
                                    array( 
                                        '_function' => 'sectionIDbyName', 
                                        '_params' => array( 
                                            'section_name' => 'Standard' 
                                        ) 
                                    ) 
                                ), 
                                'Owner' => 1 
                            ) 
                        ),  // self
                        array( 
                            'module' => 'user', 
                            'function' => 'selfedit' 
                        ), 
                        array( 
                            'module' => 'notification', 
                            'function' => 'use' 
                        ), 
                        array( 
                            'module' => 'user', 
                            'function' => 'password' 
                        ), 
                        array( 
                            'module' => 'ezjscore', 
                            'function' => 'call' 
                        )
                    ) 
                ) 
            ), 
            array( 
                '_function' => 'assignUserToRole', 
                '_params' => array( 
                    'location' => 'users/members', 
                    'role_name' => 'Member' 
                ) 
            ), 
            array( 
                '_function' => 'assignUserToRole', 
                '_params' => array( 
                    'location' => 'users/partners', 
                    'role_name' => 'Partner' 
                ) 
            ), 
            array( 
                '_function' => 'assignUserToRole', 
                '_params' => array( 
                    'location' => 'users/partners', 
                    'role_name' => 'Member' 
                ) 
            ), 
            array( 
                '_function' => 'assignUserToRole', 
                '_params' => array( 
                    'location' => 'users/partners', 
                    'role_name' => 'Anonymous' 
                ) 
            ), 
            array( 
                '_function' => 'assignUserToRole', 
                '_params' => array( 
                    'location' => 'users/editors', 
                    'role_name' => 'Member' 
                ) 
            ), 
            array( 
                '_function' => 'updateClassAttributes', 
                '_params' => array( 
                    'class' => array( 
                        'identifier' => 'folder' 
                    ), 
                    'attributes' => array( 
                        array( 
                            'identifier' => 'short_description', 
                            'new_name' => 'Summary' 
                        ), 
                        array( 
                            'identifier' => 'show_children', 
                            'new_name' => 'Display sub items' 
                        ) 
                    ) 
                ) 
            ), 
            array( 
                '_function' => 'setRSSExport', 
                '_params' => array( 
                    'creator' => '14', 
                    'access_url' => 'my_feed', 
                    'main_node_only' => '1', 
                    'number_of_objects' => '10', 
                    'rss_version' => '2.0', 
                    'status' => '1', 
                    'title' => 'My RSS Feed', 
                    'rss_export_itmes' => array( 
                        0 => array( 
                            'class_id' => '16', 
                            'description' => 'intro', 
                            'source_node_id' => '139', 
                            'status' => '1', 
                            'title' => 'title' 
                        ) 
                    ) 
                ) 
            ), 
            array( 
                '_function' => 'dbCommit', 
                '_params' => array() 
            ),
            // The URL work depends on the home node being resolved by the
            // steps above, so it stays here - moving it earlier left the site
            // siteaccess with an empty PathPrefix and every page below the
            // site root unreachable.
            array(
                '_function' => 'postInstallFixPackageNodesAndExplayouts',
                '_params' => array()
            ),
            array(
                '_function' => 'postInstallImportAdminLayouts',
                '_params' => array()
            ),
            array(
                '_function' => 'postInstallCleanUrlText',
                '_params' => array()
            ),
            array(
                '_function' => 'postInstallRegenerateURLAliases',
                '_params' => array()
            ),
            array(
                '_function' => 'postInstallCreateSitePrefixAliases',
                '_params' => array()
            ),
            // Needs the prefixed aliases the step above stores.
            array(
                '_function' => 'postInstallClearLinksToContentNotInstalled',
                '_params' => array()
            ),
            array(
                '_function' => 'postInstallPlaceOrphanedPackageObjects',
                '_params' => array()
            ),
            array(
                '_function' => 'postInstallSetSiteHomeAndPrefix',
                '_params' => array()
            ),
            array(
                '_function' => 'postInstallCloseInformationPagesOnPublicSiteaccesses',
                '_params' => array()
            ),
            array(
                '_function' => 'postInstallResyncContentClassNames',
                '_params' => array()
            ),
            array(
                '_function' => 'postInstallRepairClassNameLists',
                '_params' => array()
            ),
            // After every step that writes the admin siteaccess's settings: adminui is made from them.
            array(
                '_function' => 'postInstallCreateAdminUISiteaccess',
                '_params' => array()
            ),
            // After the last step that writes a siteaccess's site.ini.
            array(
                '_function' => 'postInstallEnableSearchStatsOnEverySiteaccess',
                '_params' => array()
            ),

            // Cosmetic, and deliberately last. executeSteps aborts the whole
            // chain on the first step that reports an error, and the template
            // look object is not always resolvable at this point - when it is
            // not, these two steps used to take the role policies, the node
            // repair and the two steps above down with them. A missing logo is
            // worth far less than any of that.
            array( 
                '_function' => 'updateObjectAttributeFromString', 
                '_params' => array( 
                    'object_id' => $this->setting( 'template_look_object_id' ), 
                    'class_attribute_identifier' => 'image', 
                    'string' => array( 
                        '_function' => 'packageFileItemPath', 
                        '_params' => array( 
                            'collection' => 'default', 
                            'file_item' => array( 
                                'type' => 'image', 
                                'name' => 'logo.png' 
                            ) 
                        ) 
                    ) 
                ) 
            ),
            array( 
                '_function' => 'updateObjectAttributeFromString', 
                '_params' => array( 
                    'object_id' => $this->setting( 'template_look_object_id' ), 
                    'class_attribute_identifier' => 'sitestyle', 
                    'string' => 'ezwebin_design_blue' 
                ) 
            )
        );
        $this->Steps['post_install'] = $postInstallSteps;
    }

    /*!
     Re-impl.
    */
    function handleError()
    {
        $errCode = $this->lastErrorCode();
        if ( $errCode === eZSiteInstaller::ERR_ABORT )
            $this->dbCommit( array() );
        return $errCode;
    }

    /*!
     Override swapNodes so a missing legacy 'eZ Publish' source node does not
     abort the entire post-install sequence. The package no longer contains an
     object by that name; swapping is only needed when it exists.
    */
    function swapNodes( $params )
    {
        $srcNodeID = $this->nodeIdByName( $params['src_node'] );
        $dstNodeID = $this->nodeIdByName( $params['dst_node'] );

        if ( !$srcNodeID || !$dstNodeID )
        {
            eZDebug::writeNotice( 'Skipping swapNodes: missing src=' . var_export( $srcNodeID, true ) . ' dst=' . var_export( $dstNodeID, true ), __METHOD__ );
            return true;
        }

        $result = parent::swapNodes( $params );
        if ( $this->lastErrorCode() !== eZSiteInstaller::ERR_OK )
        {
            eZDebug::writeWarning( 'swapNodes reported an error (errCode=' . $this->lastErrorCode() . '), continuing post-install', __METHOD__ );
            $this->setLastErrorCode( eZSiteInstaller::ERR_OK );
        }
        return $result;
    }

    /*!
     Override setSection so a missing target subtree does not abort the entire
     post-install sequence. The 'Restricted' section is created anyway; only
     assign it when the requested location actually exists in this package.
    */
    function setSection( $params )
    {
        $rootNode = $this->nodeByUrl( $params );
        if ( !is_object( $rootNode ) )
        {
            eZDebug::writeNotice( 'Skipping setSection: location ' . $params['location'] . ' not found', __METHOD__ );
            $this->setLastErrorCode( eZSiteInstaller::ERR_OK );
            return true;
        }

        $result = parent::setSection( $params );
        if ( $this->lastErrorCode() !== eZSiteInstaller::ERR_OK )
        {
            eZDebug::writeWarning( 'setSection reported an error (errCode=' . $this->lastErrorCode() . '), continuing post-install', __METHOD__ );
            $this->setLastErrorCode( eZSiteInstaller::ERR_OK );
        }
        return $result;
    }

    /*!
     Override parent updateINIFiles so arrays are fully reset when requested.
    */
    function updateINIFiles( $params )
    {
        foreach( $params['groups'] as $settingsData )
        {
            $iniFilename = $settingsData['name'] . '.append.php';
            $ini = eZINI::instance( $iniFilename, $params['settings_dir'] );
            if ( isset( $settingsData['discard_old_values'] ) && $settingsData['discard_old_values'] )
                $ini->reset();

            $ini->setReadOnlySettingsCheck( false );
            $ini->setVariables( $settingsData['settings'] );
            $ini->save( false, false, false, false, true, true );
        }
    }

    /*!
     Install from command-line.
    */
    function install()
    {
        $settings = array();
        $settings[] = array( 
            'settings_dir' => 'settings/siteaccess/' . $this->setting( 'user_siteaccess' ), 
            'groups' => $this->siteINISettings() 
        );
        $settings[] = array( 
            'settings_dir' => 'settings/siteaccess/' . $this->setting( 'admin_siteaccess' ), 
            'groups' => $this->adminINISettings() 
        );
        $settings[] = array( 
            'settings_dir' => 'settings/override', 
            'groups' => $this->commonINISettings() 
        );
        foreach ($settings as $settingsGroup)
            $this->updateINIFiles( $settingsGroup );
        $this->updateRoles( array( 
            'roles' => $this->siteRoles() 
        ) );
        $this->updatePreferences( array( 
            'prefs' => $this->sitePreferences() 
        ) );
        $this->postInstall();
    }

    /**
     * SiteLanguageList for a siteaccess: $first (default: the primary
     * language), the other chosen languages, then the fallback languages the
     * bundled content is in when they were not chosen.
     */
    function siteLanguageList( $first = false )
    {
        $primaryLanguage = $this->setting( 'primary_language' );
        $locales = (array)$this->setting( 'locales' );
        if ( !$locales )
            $locales = array( $primaryLanguage ? $primaryLanguage : 'eng-US' );
        if ( !$first )
            $first = $locales[0];
        $list = array( $first );
        foreach ( array_merge( $locales, (array)$this->setting( 'fallback_locales' ) ) as $locale )
        {
            if ( !in_array( $locale, $list ) )
                $list[] = $locale;
        }
        return $list;
    }

    /**
     * The locale class and attribute names are created in: the install's
     * primary language, else the configured content locale, else the
     * kernel's fallback locale (site.ini [RegionalSettings]
     * ContentObjectFallbackLocale, eng-US; never eng-GB, which left names in a
     * language the site did not have).
     */
    function primaryLanguageLocale()
    {
        $locale = $this->setting( 'primary_language' );
        if ( !$locale )
        {
            if ( method_exists( 'eZSerializedObjectNameList', 'configuredLanguageLocale' ) )
                return eZSerializedObjectNameList::configuredLanguageLocale();
            $ini = eZINI::instance();
            $locale = $ini->hasVariable( 'RegionalSettings', 'ContentObjectLocale' )
                ? trim( (string)$ini->variable( 'RegionalSettings', 'ContentObjectLocale' ) ) : '';
            if ( $locale === '' )
                $locale = 'eng-US';
        }
        return $locale;
    }

    /**
     * Class and attribute names and descriptions keyed by a number instead of
     * a language code (see sevenxRepairClassNameLists()) are re-keyed, so the
     * admin shows them. Runs after the class names are resynced.
     */
    function postInstallRepairClassNameLists( $params = false )
    {
        $result = sevenxRepairClassNameLists( $this->primaryLanguageLocale() );
        if ( $result['left'] )
            eZDebug::writeError( 'Class/attribute name lists still without a language: ' . implode( ', ', $result['left'] ), __METHOD__ );
        return true;
    }

    /**
     * The name of the Admin UI siteaccess this installation gets, or '' for none.
     *
     * The siteaccess is the exp_adminui extension's adminui design on top of admin4l, made from the admin siteaccess
     * by the kernel's eZStepCreateSites::createAdminUISiteAccess(), which the setup calls for every installation too:
     * one place says what its settings are. It is made only when the extension is in the installation, and only by a
     * kernel that knows how; otherwise the reason is logged and the installation has no adminui. It is reached by URI
     * only (/adminui): siteaccess_urls has no entry for it, so it gets no host, port or HostMatchMapItems line.
     */
    function adminUISiteaccessName( $extensionRoot = 'extension' )
    {
        $reason = '';
        if ( !class_exists( 'eZStepCreateSites' ) || !method_exists( 'eZStepCreateSites', 'createAdminUISiteAccess' ) )
            $reason = 'this kernel cannot make it (no eZStepCreateSites::createAdminUISiteAccess)';
        else if ( !eZStepCreateSites::adminUIAvailable( $extensionRoot ) )
            $reason = 'the exp_adminui extension is not in ' . $extensionRoot;
        if ( $reason === '' )
            return eZStepCreateSites::ADMINUI_SITEACCESS;
        eZDebug::writeNotice( "No adminui siteaccess: $reason", __METHOD__ );
        eZLog::write( "sevenxMultiSiteInstaller: no adminui siteaccess, $reason", 'setup.log' );
        return '';
    }

    /**
     * settings/siteaccess/adminui from the admin siteaccess as the steps before have written it: the same database,
     * languages, VarDir, login and access rules, override, menu, toolbar and content structure menu settings, with
     * the adminui design chain and ActiveAccessExtensions[]=exp_adminui. Never stops the install.
     */
    function postInstallCreateAdminUISiteaccess( $params = false )
    {
        $name = (string)$this->setting( 'adminui_siteaccess' );
        if ( $name === '' )
            return true;
        $siteaccessRoot = is_array( $params ) && isset( $params['siteaccess_root'] ) ? $params['siteaccess_root'] : 'settings/siteaccess';
        $extensionRoot = is_array( $params ) && isset( $params['extension_root'] ) ? $params['extension_root'] : 'extension';
        $made = eZStepCreateSites::createAdminUISiteAccess( $this->setting( 'admin_siteaccess' ), $siteaccessRoot, $extensionRoot );
        if ( $made === false )
            eZDebug::writeError( "The $name siteaccess could not be made from settings/siteaccess/" . $this->setting( 'admin_siteaccess' ), __METHOD__ );
        else
            eZDebug::writeNotice( "The $name siteaccess was made from settings/siteaccess/" . $this->setting( 'admin_siteaccess' ), __METHOD__ );
        return true;
    }

    /*!
      pre-install stuff.
    */
    /**
     * Make the settings of every extension the site will run with part of
     * this run, before the packages are installed.
     *
     * The site's ActiveExtensions are only written to settings/override at the
     * end of the install. On a new installation there is no earlier override,
     * so the classes and objects were imported with only the kernel's
     * extensions active: the datatypes of enhancedselection2 and ngclasslist
     * were "not found" and every eztags attribute read an eztags.ini without
     * its SearchSettings. A reinstall over an existing site hid this, because
     * the previous site's settings were still in place.
     *
     * Only the override directories are added: site.ini itself is not
     * reloaded, since the setup keeps its database settings in memory. The
     * INI files already read are read again on their next use.
     */
    function activateSiteExtensionSettings( $siteINI )
    {
        // In the order the site will load them: the first match wins when a
        // datatype is looked up, and sevenx_themes_media carries a stand-in
        // eztags datatype that must not shadow the real one.
        $extensions = array_values( array_unique( (array)$this->setting( 'extension_list' ) ) );
        if ( $siteINI->variable( 'ExtensionSettings', 'ExtensionOrdering' ) === 'enabled' )
            $extensions = eZExtension::extensionOrdering( $extensions );
        $added = false;
        foreach ( $extensions as $extension )
        {
            $dir = eZExtension::baseDirectory() . '/' . $extension . '/settings';
            if ( is_dir( $dir ) && $siteINI->prependOverrideDir( $dir, true, 'extension:' . $extension, 'extension' ) )
                $added = true;
        }
        if ( !$added )
            return;
        foreach ( array( 'content.ini', 'eztags.ini', 'template.ini', 'image.ini', 'ezoe.ini', 'ezxml.ini' ) as $file )
            eZINI::resetInstance( $file );
    }

    function preInstall()
    {
        $db = eZDB::instance();
        // What the database holds before the content package is installed: the
        // base data. The post-install fix (post-install-fix.php) tells a
        // reference to base data (node 1, the content root) from a package id
        // of content the package does not ship by it.
        // ORDER BY with arrayQuery()'s limit rather than MAX(): every driver
        // applies the limit in its own dialect (LIMIT, FETCH NEXT, a cursor limit)
        $maxNode = $db->arrayQuery( 'SELECT node_id FROM ezcontentobject_tree ORDER BY node_id DESC', array( 'limit' => 1 ) );
        $maxObject = $db->arrayQuery( 'SELECT id FROM ezcontentobject ORDER BY id DESC', array( 'limit' => 1 ) );
        $GLOBALS['sevenxBaseMaxNodeID'] = $maxNode ? (int)$maxNode[0]['node_id'] : 0;
        $GLOBALS['sevenxBaseMaxObjectID'] = $maxObject ? (int)$maxObject[0]['id'] : 0;
        eZDebug::writeNotice( 'Base data before the content package: nodes up to ' . $GLOBALS['sevenxBaseMaxNodeID'] .
                              ', objects up to ' . $GLOBALS['sevenxBaseMaxObjectID'] .
                              '; content package ' . sevenxDemocontentPackageName(), __METHOD__ );
        $db->begin();
        // extend 'folder' class
        $this->addClassAttributes( array( 
            'class' => array( 
                'identifier' => 'folder' 
            ), 
            'attributes' => array( 
                array( 
                    'identifier' => 'tags', 
                    'name' => 'Tags', 
                    'data_type_string' => 'ezkeyword',
                    // Named in the site's language: this runs before any
                    // content language exists, and without one the name was
                    // stored under key 0 and shown blank in the admin.
                    'language' => $this->primaryLanguageLocale()
                ), 
                array( 
                    'identifier' => 'publish_date', 
                    'name' => 'Publish date', 
                    'data_type_string' => 'ezdatetime', 
                    'default_value' => 0,
                    'language' => $this->primaryLanguageLocale()
                ) 
            ) 
        ) );
        $db->commit();
        // hack for images/binaryfiles
        // need to set siteaccess to have correct placement(VarDir) for files in SetupWizard
        $ini = eZINI::instance();
        // $this->setting( 'var_dir' ) );
        $ini->setVariable( 'FileSettings', 'VarDir', 'var/site' );
        $this->activateSiteExtensionSettings( $ini );
        $contentINI = eZINI::instance( 'content.ini' );
        $datatypeRepositories = $contentINI->variable( 'DataTypeSettings', 'ExtensionDirectories' );
        $datatypeRepositories[] = 'ezstarrating';
        $datatypeRepositories[] = 'ezgmaplocation';
        $datatypeRepositories[] = 'exp_enhanced_link';
        $datatypeRepositories[] = 'eztags';
        $contentINI->setVariables( array( 
            'DataTypeSettings' => array( 
                'ExtensionDirectories' => $datatypeRepositories 
            ) 
        ) );
        $availableDatatype = $contentINI->variable( 'DataTypeSettings', 'AvailableDataTypes' );
        $availableDatatype[] = 'ezsrrating';
        $availableDatatype[] = 'ezgmaplocation';
        $availableDatatype[] = 'ngenhancedlink';
        $availableDatatype[] = 'eztags';
        $contentINI->setVariables( array( 
            'DataTypeSettings' => array( 
                'AvailableDataTypes' => $availableDatatype 
            ) 
        ) );
        // sevenx_authentication_2fa: the two-step sign-in field on the user class (addTwoFactorUserField())
        if ( $this->twoFactorAuthenticationAvailable() )
            $this->addTwoFactorUserField();
        // Every table the extension schemas declare starts empty: a reinstall's
        // "remove" drops only the tables named ez*, so the others kept the
        // previous site's rows and each schema insert failed "already exists".
        $this->dropExtensionTables();
        foreach ( $this->extensionSchemas() as $e )
            $this->insertDBFile( $e[0], $e[1], $e[2] );

        // Fix eztags language metadata from the legacy DBA export, which has
        // mismatched language_id / main_language_id / language_mask values.
        $db = eZDB::instance();
        $engGBId = (int) eZContentLanguage::idByLocale( 'eng-GB' );
        if ( $engGBId > 0 )
        {
            $publishedStatus = 1;
            $db->query( "UPDATE eztags_keyword SET language_id = $engGBId WHERE locale = 'eng-GB' AND language_id != $engGBId" );
            // A subquery, not UPDATE ... INNER JOIN, which only MySQL accepts
            $db->query( "UPDATE eztags SET main_language_id = $engGBId, language_mask = $engGBId WHERE id IN ( SELECT keyword_id FROM eztags_keyword WHERE locale = 'eng-GB' AND status = $publishedStatus )" );
        }
    }

    /*!
     Collapse duplicate content languages onto their lowest id.

     share/db_data.dba seeds ezcontent_language with eng-US as id 2, named
     "English (United States)". ezstep_create_sites then walks the configured
     languages and calls eZContentLanguage::addLanguage for each one it cannot
     find - and it does not find that seeded row, so it adds eng-US a second
     time as id 4, named "English (American)" after share/locale/eng-US.ini.

     The install then builds all of its content against the later id, leaving
     the site running on a duplicate: the same locale twice, and two bits in
     every language mask meaning the same thing.

     This repoints every language column onto the lowest id per locale and
     deletes the duplicates. Masks are bit fields, so the duplicate's bit is
     cleared and the survivor's set.
    */
    function postInstallMergeDuplicateLanguages( $params = false )
    {
        $db = eZDB::instance();

        $rows = $db->arrayQuery( 'SELECT id, locale FROM ezcontent_language ORDER BY id ASC' );
        $byLocale = array();
        foreach ( $rows as $row )
            $byLocale[$row['locale']][] = (int) $row['id'];

        // Every language column here is either a single-bit id or a bit
        // field, and some carry the always-available bit folded in - a name row
        // for language 4 is stored as 5, not 4. So one bit transform covers the
        // lot: clear the duplicate's bit, set the survivor's. Matching on
        // equality instead would silently skip every always-available row.
        $columns = array(
            'ezcontentclass'              => array( 'initial_language_id', 'language_mask' ),
            'ezcontentclass_name'         => array( 'language_id' ),
            'ezcontentobject'             => array( 'initial_language_id', 'language_mask' ),
            'ezcontentobject_attribute'   => array( 'language_id' ),
            'ezcontentobject_name'        => array( 'language_id' ),
            'ezcontentobject_version'     => array( 'initial_language_id', 'language_mask' ),
            'ezcobj_state'                => array( 'language_mask' ),
            'ezcobj_state_group'          => array( 'language_mask' ),
            'ezcobj_state_group_language' => array( 'language_id' ),
            'ezcobj_state_language'       => array( 'language_id' ),
            'eztags'                      => array( 'main_language_id', 'language_mask' ),
            'eztags_keyword'              => array( 'language_id' ),
            'ezurlalias_ml'               => array( 'lang_mask' ),
        );

        $tables = $db->eZTableList();
        $merged = 0;

        foreach ( $byLocale as $locale => $ids )
        {
            if ( count( $ids ) < 2 )
                continue;

            $keep = array_shift( $ids );
            foreach ( $ids as $drop )
            {
                foreach ( $columns as $table => $columnList )
                {
                    if ( !isset( $tables[$table] ) ) continue;
                    foreach ( $columnList as $column )
                    {
                        // eZDB's bitAnd()/bitOr() write the operators in each
                        // database's dialect (BITAND on Oracle); the complement
                        // mask ~$drop is worked out here, not in SQL
                        $db->query( "UPDATE $table SET $column = " .
                                    $db->bitOr( $db->bitAnd( $column, ~(int)$drop ), (int)$keep ) .
                                    " WHERE " . $db->bitAnd( $column, (int)$drop ) . " <> 0" );
                    }
                }
                $db->query( "DELETE FROM ezcontent_language WHERE id = $drop" );
                eZDebug::writeNotice( "Merged duplicate language $locale: id $drop into id $keep",
                                      __METHOD__ );
                ++$merged;
            }
        }

        if ( $merged )
        {
            // The running process still holds the pre-merge language list, and
            // a prioritised entry pointing at a language id that no longer
            // exists makes every alias and name lookup miss - which showed up
            // as 'the node users doesn't exist' several steps later. Re-apply
            // the same locales so the list is rebuilt against the surviving
            // ids; locales are unchanged by the merge, only ids are.
            $locales = array();
            foreach ( eZContentLanguage::prioritizedLanguages() as $language )
                $locales[] = $language->attribute( 'locale' );

            eZContentLanguage::expireCache();
            eZContentObject::clearCache();

            if ( $locales )
                eZContentLanguage::setPrioritizedLanguages( $locales );
            eZContentLanguage::expireCache();
        }


        return true;
    }

    /*!
     Make each object name row's language_id agree with its own translation.

     ezcontentobject_name carries both content_translation, a locale string,
     and language_id, that locale's id with the always-available bit folded in.
     The install leaves the fourteen objects seeded by share/db_data.dba with
     rows that say eng-US but carry the id of a different language, because the
     seeded ids are written before the language table is built and are never
     reconciled afterwards.

     That inconsistency is invisible while a second language happens to occupy
     the stale id and sits in the prioritised list - the mask covers it either
     way. With eng-US alone the mask stops covering it, and every lookup that
     joins this table starts missing: eZContentObjectTreeNode::fetch joins it,
     so fetchByURLPath returns nothing, so nodeByUrl reports an error, so
     executeSteps aborts the rest of the install.

     The always-available bit is preserved; only the language bits are put
     right.
    */
    function postInstallFixContentObjectNameLanguages( $params = false )
    {
        $db = eZDB::instance();
        $tables = $db->eZTableList();
        if ( !isset( $tables['ezcontentobject_name'] ) )
            return true;

        $languages = $db->arrayQuery( 'SELECT id, locale FROM ezcontent_language' );
        $fixed = 0;

        foreach ( $languages as $language )
        {
            $id = (int) $language['id'];
            $locale = $db->escapeString( $language['locale'] );

            // (language_id & 1) keeps the always-available bit, | id sets the
            // right language. Rows already correct are left untouched. The bit
            // operators go through eZDB (BITAND on Oracle), and the complement
            // mask ~1 is worked out here, not in SQL.
            $rows = $db->arrayQuery(
                "SELECT COUNT(*) AS c FROM ezcontentobject_name" .
                " WHERE content_translation = '$locale'" .
                "   AND " . $db->bitAnd( 'language_id', ~1 ) . " <> $id" );
            $count = $rows ? (int) $rows[0]['c'] : 0;
            if ( !$count )
                continue;

            $db->query(
                "UPDATE ezcontentobject_name SET language_id = " . $db->bitOr( $db->bitAnd( 'language_id', 1 ), $id ) .
                " WHERE content_translation = '$locale'" .
                "   AND " . $db->bitAnd( 'language_id', ~1 ) . " <> $id" );
            $fixed += $count;
            eZDebug::writeNotice( "Repointed $count $locale name rows onto language id $id",
                                  __METHOD__ );
        }

        // ezcontentobject_attribute has the same pair of columns - language_code,
        // a locale, and language_id, that locale's id - and the same
        // disagreement. It has to be put right before the masks below, which are
        // derived from it.
        foreach ( $languages as $language )
        {
            $id = (int) $language['id'];
            $locale = $db->escapeString( $language['locale'] );
            $db->query(
                "UPDATE ezcontentobject_attribute SET language_id = " . $db->bitOr( $db->bitAnd( 'language_id', 1 ), $id ) .
                " WHERE language_code = '$locale'" .
                "   AND " . $db->bitAnd( 'language_id', ~1 ) . " <> $id" );
        }

        // The same disagreement exists one level up: ezcontentobject.language_mask
        // and ezcontentobject_version.language_mask are left holding bits for
        // languages the object was never translated into. eZContentObjectTreeNode
        // ::fetch filters on the object mask as well as on the name row, so an
        // object whose mask says ger-DE is invisible under an eng-US only
        // prioritised list even when its name row is right.
        //
        // The attributes are the authoritative record of which languages an
        // object actually has, so the masks are rebuilt from them. Their
        // language_id already carries the always-available bit, so OR-ing them
        // reproduces it.
        //
        // One path for every database: the masks are folded here from the
        // attributes' language ids and written back where they differ. BIT_OR
        // does not exist in every engine (Oracle before 21c, MongoDB), and an
        // UPDATE from a derived table is written differently in each (UPDATE ...
        // JOIN in MySQL, UPDATE ... FROM in PostgreSQL, MERGE in Oracle), so a
        // plain SELECT and single-row UPDATEs are the form every driver takes.
        $masks = array();
        foreach ( (array) $db->arrayQuery( 'SELECT contentobject_id, version, language_id FROM ezcontentobject_attribute' ) as $row )
        {
            $objectID = (int) $row['contentobject_id'];
            $version  = (int) $row['version'];
            $masks[$objectID][$version] = ( isset( $masks[$objectID][$version] ) ? $masks[$objectID][$version] : 0 ) | (int) $row['language_id'];
        }

        foreach ( (array) $db->arrayQuery( 'SELECT contentobject_id, version, language_mask FROM ezcontentobject_version' ) as $row )
        {
            $objectID = (int) $row['contentobject_id'];
            $version  = (int) $row['version'];
            if ( empty( $masks[$objectID][$version] ) || $masks[$objectID][$version] == (int) $row['language_mask'] )
                continue;
            $db->query( 'UPDATE ezcontentobject_version SET language_mask = ' . $masks[$objectID][$version] .
                        " WHERE contentobject_id = $objectID AND version = $version" );
        }

        // The object's own mask follows its current version.
        foreach ( (array) $db->arrayQuery( 'SELECT id, current_version, language_mask FROM ezcontentobject' ) as $row )
        {
            $objectID = (int) $row['id'];
            $current  = (int) $row['current_version'];
            if ( empty( $masks[$objectID][$current] ) || $masks[$objectID][$current] == (int) $row['language_mask'] )
                continue;
            $db->query( 'UPDATE ezcontentobject SET language_mask = ' . $masks[$objectID][$current] .
                        " WHERE id = $objectID" );
        }

        eZContentObject::clearCache();

        return true;
    }

    /*!
     Point each siteaccess at its own home node, and hide that node with PathPrefix.

     Nothing else in the install does this. The code that was written for it
     lives in the private fixPackageNodesAndExplayouts() below, which has no
     callers - postInstallFixPackageNodesAndExplayouts calls the standalone
     sevenxFixPackageNodesAndExplayouts() in post-install-fix.php instead, and
     that function has no PathPrefix logic. So a clean install has always left
     PathPrefix empty, and every page below a site root answered 404. The
     settings files only ever held a value because they were carried between
     installs by hand.

     Home nodes are found by remote id, which survives a rebuild; node ids do
     not. Each siteaccess INI is loaded explicitly and saved, because
     eZINI::instance() would only change the running instance.
    */
    /**
     * The Exponential information pages (ezinfo: versions, extensions, license)
     * belong to the administration. Every siteaccess of the installation that
     * is not an administration siteaccess (the admin siteaccess, or one with
     * an admin design) gets [SiteAccessRules] that switch the ezinfo module
     * off, so a public site answers 404 there: the user site, Bold Agency and
     * every translation siteaccess.
     */
    function postInstallCloseInformationPagesOnPublicSiteaccesses( $params = false )
    {
        // The siteaccesses as they are on disk at this point: the override's
        // RelatedSiteAccessList is only written after the post-install.
        $siteaccesses = array();
        foreach ( glob( 'settings/siteaccess/*/site.ini.append.php' ) ?: array() as $file )
            $siteaccesses[] = basename( dirname( $file ) );
        $adminSiteaccess = $this->setting( 'admin_siteaccess' );
        $closed = array();
        foreach ( $siteaccesses as $siteaccess )
        {
            $path = 'settings/siteaccess/' . $siteaccess;
            if ( $siteaccess === '' || $siteaccess === $adminSiteaccess || !file_exists( $path . '/site.ini.append.php' ) )
                continue;

            $ini = eZINI::instance( 'site.ini.append.php', $path, null, false, null, true );
            $design = $ini->hasVariable( 'DesignSettings', 'SiteDesign' ) ? (string)$ini->variable( 'DesignSettings', 'SiteDesign' ) : '';
            if ( in_array( $design, array( 'admin', 'admin2', 'admin3', 'admin4', 'admin4l', 'editor', 'adminui' ), true ) )
                continue;

            $ini->setVariable( 'SiteAccessRules', 'Rules', array( 'access;enable', 'moduleall', 'access;disable', 'module;ezinfo' ) );
            $ini->save( false, false, false, false, true, true );
            $closed[] = $siteaccess;
        }
        eZDebug::writeNotice( 'The ezinfo module is switched off on: ' . ( $closed ? implode( ', ', $closed ) : 'no siteaccess' ), __FUNCTION__ );
        return true;
    }

    function postInstallSetSiteHomeAndPrefix( $params = false )
    {
        $homes = array(
            $this->setting( 'user_siteaccess' ) => 'media-n-939',   // Fit & Healthy
            'bold'                              => 'media-n-940',   // Bold Agency
            'bold_ger'                          => 'media-n-940',
        );

        $db = eZDB::instance();

        // The home node's name, per siteaccess. PathPrefix is convertToAlias of
        // that name, so it needs no database at all - which matters, because the
        // home nodes do not exist yet at any point this installer runs. Looking
        // them up here returned nothing every time and wrote an empty prefix,
        // leaving every page below a site root on a 404. The names are fixed
        // properties of the shipped demo content, the same way
        // secondarySiteaccessHomeRemoteID() hardcodes the remote ids.
        $homeNames = array(
            $this->setting( 'user_siteaccess' ) => 'Fit & Healthy',
            'bold'                              => 'Bold Agency',
            'bold_ger'                          => 'Bold Agency',
        );

        // The translation siteaccesses serve the user siteaccess's site in
        // another language: same home node, same prefix, same menus.
        $userSiteaccess = $this->setting( 'user_siteaccess' );
        foreach ( (array)$this->setting( 'language_based_siteaccess_list' ) as $languageSiteaccess )
        {
            if ( isset( $homeNames[$languageSiteaccess] ) || !isset( $homeNames[$userSiteaccess] ) )
                continue;
            $homes[$languageSiteaccess] = isset( $homes[$userSiteaccess] ) ? $homes[$userSiteaccess] : '';
            $homeNames[$languageSiteaccess] = $homeNames[$userSiteaccess];
            $this->copySiteaccessMenus( $userSiteaccess, $languageSiteaccess );
        }

        foreach ( $homeNames as $siteaccess => $homeName )
        {
            if ( !$siteaccess )
                continue;

            $dir = 'settings/siteaccess/' . $siteaccess;
            if ( !file_exists( $dir . '/site.ini.append.php' ) )
                continue;

            $alias = eZURLAliasML::convertToAlias( $homeName, '' );
            if ( $alias === '' )
                continue;

            // Written through a direct-access eZINI rather than updateINIFiles():
            // that helper takes a shared cached eZINI::instance() for the file,
            // and earlier steps in this install have already taken their own
            // instance of the same path, so the two disagree about what is on
            // disk. Every other INI writer in this installer uses direct access.
            $ini = eZINI::instance( 'site.ini.append.php', $dir, null, false, null, true );
            $ini->setReadOnlySettingsCheck( false );
            // The sites are served from a virtual host with clean urls, so eZ must
            // generate them without index.php. Left at its default of false it
            // emits /index.php/... and the treemenu entry point then misreads
            // its own parameters: index_treemenu.php skips exactly two url
            // elements to reach the view arguments, so the extra index.php
            // shifts them by one and NodeID arrives as the string 'treemenu',
            // which casts to 0. The content tree answered 404 for every node.
            $ini->setVariable( 'SiteAccessSettings', 'ForceVirtualHost', 'true' );
            $ini->setVariable( 'SiteAccessSettings', 'PathPrefix', $alias );

            // IndexPage needs a node id, so it is only set when the node is
            // actually resolvable - it is not required for urls to work.
            $escaped = $db->escapeString( isset( $homes[$siteaccess] ) ? $homes[$siteaccess] : '' );
            if ( $escaped !== '' )
            {
                $rows = $db->arrayQuery( "SELECT node_id, depth FROM ezcontentobject_tree" .
                                         " WHERE remote_id = '$escaped' ORDER BY node_id ASC", array( 'limit' => 1 ) );
                if ( $rows )
                {
                    // The leading slash matters: eZ routes IndexPage as a full
                    // module path, and 'content/view/full/133' does not resolve.
                    $homeURL = '/content/view/full/' . (int) $rows[0]['node_id'];
                    $ini->setVariable( 'SiteSettings', 'IndexPage', $homeURL );
                    $ini->setVariable( 'SiteSettings', 'DefaultPage', $homeURL );
                    $ini->setVariable( 'SiteSettings', 'RootNodeDepth', (int) $rows[0]['depth'] );
                }
                else
                {
                    eZDebug::writeWarning( "Home node $escaped for siteaccess $siteaccess does not resolve;" .
                                           " IndexPage left as it stands and / will answer the content root",
                                           __METHOD__ );
                }
            }

            $ini->save( false, false, false, false, true, true );

            eZDebug::writeNotice( "$siteaccess PathPrefix set to $alias", __METHOD__ );
        }

        // The empty-path alias has to point at the user siteaccess home node;
        // updateSubTreePath does not maintain it.
        $userHome = eZContentObjectTreeNode::fetchByRemoteID( 'media-n-939' );
        if ( $userHome )
        {
            $db = eZDB::instance();
            $id = (int) $userHome->attribute( 'node_id' );
            // ( text = '' OR text IS NULL ): the empty path is NULL where '' is NULL (Oracle)
            $db->query( "UPDATE ezurlalias_ml SET action = 'eznode:$id' WHERE ( text = '' OR text IS NULL ) AND parent = 0" );
        }

        return true;
    }

    /**
     * Give a translation siteaccess the menus of the siteaccess it translates.
     *
     * The header and footer menus ([SiteInfo] in menu.ini) are written for the
     * user siteaccess from this installation's node ids by the package node
     * fix, after the translation siteaccesses were copied from it - so they had
     * none, and the menu templates read settings that did not exist.
     */
    function copySiteaccessMenus( $srcSiteaccess, $dstSiteaccess )
    {
        $src = 'settings/siteaccess/' . $srcSiteaccess . '/menu.ini.append.php';
        $dstDir = 'settings/siteaccess/' . $dstSiteaccess;
        if ( !file_exists( $src ) || !is_dir( $dstDir ) )
        {
            eZDebug::writeNotice( "No menu settings copied from $srcSiteaccess to $dstSiteaccess", __METHOD__ );
            return false;
        }
        if ( !copy( $src, $dstDir . '/menu.ini.append.php' ) )
        {
            eZDebug::writeWarning( "Could not copy $src to $dstDir", __METHOD__ );
            return false;
        }
        return true;
    }

    /**
     * Rebuild ezcontentclass.serialized_name_list from ezcontentclass_name.
     *
     * eZContentClass builds the name it shows from the denormalised
     * serialized_name_list column, not from the ezcontentclass_name rows. A
     * fresh install ends up with the two disagreeing for the classes the base
     * data file seeds - folder, article, user, image, link, file, comment,
     * user_group, common_ini_settings, template_look - where the column holds
     * an empty eng-US value while the name rows hold the real name. Every
     * interface that prints a class name then shows a blank: the class list,
     * the create-here menus, the changeclass destination list.
     *
     * The name rows are the surviving copy, so the column is rebuilt from them.
     * A class with no name in either place is reported and left alone rather
     * than guessed at.
     */
    function postInstallResyncContentClassNames( $params = false )
    {
        $db = eZDB::instance();
        $defined = eZContentClass::VERSION_STATUS_DEFINED;

        $rows = $db->arrayQuery(
            'SELECT id, identifier, serialized_name_list FROM ezcontentclass' .
            ' WHERE version = ' . $defined . ' ORDER BY id' );

        $rebuilt = 0;
        foreach ( $rows as $row )
        {
            $classID = (int) $row['id'];
            $list = @unserialize( $row['serialized_name_list'] );
            if ( !is_array( $list ) )
                $list = array();

            $hasName = false;
            foreach ( $list as $key => $value )
            {
                if ( $key === 'always-available' )
                    continue;
                if ( trim( (string) $value ) !== '' )
                    $hasName = true;
            }

            if ( $hasName )
                continue;

            $nameRows = $db->arrayQuery(
                "SELECT language_locale, name FROM ezcontentclass_name" .
                " WHERE contentclass_id = $classID AND contentclass_version = $defined" );

            $names = array();
            foreach ( $nameRows as $nameRow )
            {
                $locale = trim( (string) $nameRow['language_locale'] );
                $name = trim( (string) $nameRow['name'] );
                if ( $locale !== '' && $name !== '' )
                    $names[$locale] = $name;
            }

            if ( !$names )
            {
                eZDebug::writeWarning( "Content class '{$row['identifier']}' has no name in" .
                                       " ezcontentclass_name either, leaving it alone",
                                       __METHOD__ );
                continue;
            }

            // Keep the existing always-available pointer when it still names a
            // language that has a value; otherwise point it at one that does.
            $alwaysAvailable = isset( $list['always-available'] ) ? $list['always-available'] : false;
            if ( !$alwaysAvailable || !isset( $names[$alwaysAvailable] ) )
            {
                $locales = array_keys( $names );
                $alwaysAvailable = $locales[0];
            }
            $names['always-available'] = $alwaysAvailable;

            $db->query( 'UPDATE ezcontentclass SET serialized_name_list = "' .
                        $db->escapeString( serialize( $names ) ) . '"' .
                        " WHERE id = $classID AND version = $defined" );
            ++$rebuilt;
        }

        if ( $rebuilt > 0 )
            eZDebug::writeNotice( "Rebuilt the name list of $rebuilt content class(es)", __METHOD__ );

        return true;
    }

    /*!
     Give a location to package objects that installed without one.

     eZContentObjectPackageHandler resolves a node assignment's
     parent-node-remote-id through eZContentObjectTreeNode::fetch, which applies
     the prioritised-language filter. When that filter is stale the parent does
     not resolve, the assignment is dropped, and the object is published with no
     node at all - in ezcontentobject, absent from ezcontentobject_tree, and 404
     on its url.

     Each object file in the package records the parent it belongs under, so the
     placement is recovered from there and the parent resolved with plain SQL.
     Two passes, because a child's parent may itself be placed by the first.

     The node's own remote id is restored from the package as well: addLocation
     mints a fresh one, and children reference their parent by it, so a new
     remote id would strand the subtree.
    */
    function postInstallPlaceOrphanedPackageObjects( $params = false )
    {
        // the content package this site package requires (post-install-fix.php)
        $dir = sevenxDemocontentObjectDir();
        if ( !is_dir( $dir ) )
            return true;

        $db = eZDB::instance();
        $files = array();
        foreach ( scandir( $dir ) as $file )
            if ( preg_match( '/^object-.+\.xml$/', $file ) ) $files[] = $file;

        $placed = 0;
        for ( $pass = 0; $pass < 2; ++$pass )
        {
            foreach ( $files as $file )
            {
                $dom = new DOMDocument( '1.0', 'utf-8' );
                if ( !@$dom->load( $dir . '/' . $file ) ) continue;

                $remoteID = $dom->documentElement->getAttribute( 'remote_id' );
                if ( $remoteID === '' ) continue;

                $object = eZContentObject::fetchByRemoteID( $remoteID );
                if ( !$object ) continue;
                $objectID = (int) $object->attribute( 'id' );

                $rows = $db->arrayQuery( "SELECT COUNT(*) AS c FROM ezcontentobject_tree" .
                                         " WHERE contentobject_id = $objectID" );
                if ( $rows && (int) $rows[0]['c'] > 0 ) continue;

                $parentRemote = '';
                $nodeRemote = '';
                foreach ( $dom->getElementsByTagName( 'node-assignment' ) as $assignment )
                {
                    if ( $assignment->getAttribute( 'is-main-node' ) !== '1' ) continue;
                    $parentRemote = $assignment->getAttribute( 'parent-node-remote-id' );
                    $nodeRemote = $assignment->getAttribute( 'remote-id' );
                }
                if ( $parentRemote === '' ) continue;

                $escaped = $db->escapeString( $parentRemote );
                $parentRows = $db->arrayQuery( "SELECT node_id FROM ezcontentobject_tree" .
                                               " WHERE remote_id = '$escaped'" .
                                               " ORDER BY node_id ASC", array( 'limit' => 1 ) );
                if ( !$parentRows ) continue;

                // The row is written directly rather than through
                // eZContentObject::addLocation. That goes via
                // eZContentObjectTreeNode::addChildTo, which fetches the parent
                // with the prioritised-language filter and then dereferences the
                // result without checking it - during an install the fetch
                // returns null and the whole run dies on a fatal.
                $parentID = (int) $parentRows[0]['node_id'];
                $parent = $db->arrayQuery( "SELECT path_string, depth, sort_field, sort_order," .
                                           " is_hidden, is_invisible FROM ezcontentobject_tree" .
                                           " WHERE node_id = $parentID" );
                if ( !$parent ) continue;
                $parent = $parent[0];

                $version = (int) $object->attribute( 'current_version' );
                $remote = $db->escapeString( $nodeRemote !== '' ? $nodeRemote : md5( $remoteID . $parentID ) );
                $depth = (int) $parent['depth'] + 1;
                $sortField = (int) $parent['sort_field'];
                $sortOrder = (int) $parent['sort_order'];
                $hidden = (int) $parent['is_hidden'];
                $invisible = (int) $parent['is_invisible'];

                $db->query(
                    "INSERT INTO ezcontentobject_tree" .
                    " ( parent_node_id, contentobject_id, contentobject_version, depth," .
                    "   path_string, path_identification_string, remote_id, sort_field," .
                    "   sort_order, priority, is_hidden, is_invisible, modified_subnode," .
                    "   contentobject_is_published, main_node_id )" .
                    " VALUES ( $parentID, $objectID, $version, $depth," .
                    "   '', '', '$remote', $sortField, $sortOrder, 0, $hidden, $invisible," .
                    "   " . time() . ", 1, 0 )" );

                $newID = (int) $db->lastSerialID( 'ezcontentobject_tree', 'node_id' );
                if ( !$newID ) continue;

                $path = $db->escapeString( $parent['path_string'] . $newID . '/' );
                $db->query( "UPDATE ezcontentobject_tree SET path_string = '$path'," .
                            " main_node_id = $newID WHERE node_id = $newID" );

                // Built from its own row for the same reason: fetch cannot see it.
                $rowsNew = $db->arrayQuery( "SELECT * FROM ezcontentobject_tree WHERE node_id = $newID" );
                if ( $rowsNew )
                {
                    $newNode = new eZContentObjectTreeNode( $rowsNew[0] );
                    $newNode->updateSubTreePath();
                }

                ++$placed;
                eZDebug::writeNotice( 'Placed ' . $object->attribute( 'name' ) .
                                      ' under node ' . $parentRows[0]['node_id'], __METHOD__ );
            }
        }

        return true;
    }

    /*!
     Resolve object relation list references left unresolved by the package install.

     eZContentObjectPackageHandler stores an ezobjectrelationlist attribute as
     the package serialises it:

       <relation-item priority="1" contentobject-remote-id="media-o-1013"/>

     and does not convert that into the runtime form, which also carries
     contentobject-id, contentobject-version, contentclass-id and the rest. The
     remote id is the durable reference - object ids change on every rebuild -
     but nothing reads it at runtime, so every such relation comes out of an
     install pointing at nothing. On this site that is 81 relation items across
     36 objects, the product gallery among them.

     Each affected attribute is rebuilt through the datatype's own fromString(),
     which takes object ids and fills in every runtime field. Relations whose
     target is not installed are dropped rather than left dangling.
    */
    function postInstallResolveRelationListIds( $params = false )
    {
        $db = eZDB::instance();
        $rows = $db->arrayQuery(
            "SELECT id, version, data_text FROM ezcontentobject_attribute" .
            " WHERE data_type_string = 'ezobjectrelationlist'" .
            "   AND data_text LIKE '%contentobject-remote-id%'" );
        if ( !$rows )
            return true;

        $datatype = eZDataType::create( 'ezobjectrelationlist' );
        if ( !$datatype )
            return true;

        $repaired = 0;
        $dropped = 0;
        foreach ( $rows as $row )
        {
            $dom = new DOMDocument( '1.0', 'utf-8' );
            if ( !@$dom->loadXML( $row['data_text'] ) )
                continue;

            $objectIDs = array();
            foreach ( $dom->getElementsByTagName( 'relation-item' ) as $item )
            {
                // Already resolved: leave the whole attribute alone.
                if ( $item->getAttribute( 'contentobject-id' ) !== '' )
                {
                    $objectIDs = array();
                    break;
                }
                $remoteID = $item->getAttribute( 'contentobject-remote-id' );
                if ( $remoteID === '' )
                    continue;
                $target = eZContentObject::fetchByRemoteID( $remoteID );
                if ( $target )
                    $objectIDs[] = (int) $target->attribute( 'id' );
                else
                    ++$dropped;
            }
            if ( !$objectIDs )
                continue;

            $attribute = eZContentObjectAttribute::fetch( $row['id'], $row['version'] );
            if ( !$attribute )
                continue;

            $datatype->fromString( $attribute, implode( '-', $objectIDs ) );
            $attribute->store();
            ++$repaired;
        }

        eZDebug::writeNotice( "Resolved relation lists on $repaired attributes"
                            . ( $dropped ? ", dropped $dropped unresolvable relations" : '' ),
                              __METHOD__ );
        return true;
    }

    /*!
     Re-key the seeded star ratings onto the objects they belong to.

     ezstarrating stores a rating outside the content object, in its own table,
     keyed on contentobject_id + contentobject_attribute_id. The object's own
     rating attribute serialises empty, so a rating cannot travel in the
     content package; sevenx_themes_media's db_data.dba seeds the row instead.

     Both key columns are ids eZ assigns while installing, so the seeded values
     are whatever they were on the machine the data was exported from. This
     walks each seeded row, finds the object that the row was exported for by
     remote id, and rewrites the row against that object's real ids - so the
     rating follows the product rather than whichever object happens to land on
     the exported id.

     Rows whose object is not installed are dropped: a rating pointing at an
     unrelated object is worse than no rating.
    */
    /**
     * Imports the cjw_newsletter content classes (root, system, list, list_virtual, edition,
     * article) into the content class group "Newsletter". The class definitions are the
     * .ezpkg packages in extension/cjw_newsletter/packages, read by
     * CjwNewsletterClassInstaller, which reports a class that exists as "already present".
     * A site without the extension is left alone, and a failure here never stops the install.
     */
    function postInstallImportNewsletterClasses( $params = false )
    {
        if ( !in_array( 'cjw_newsletter', (array)$this->setting( 'extension_list' ) ) )
            return true;

        if ( !class_exists( 'CjwNewsletterClassInstaller', false ) )
        {
            $file = eZSys::rootDir() . '/extension/cjw_newsletter/classes/cjwnewsletterclassinstaller.php';
            if ( !file_exists( $file ) )
                return true;
            require_once $file;
        }

        $report = CjwNewsletterClassInstaller::install( 'Newsletter' );
        foreach ( $report as $identifier => $state )
            eZDebug::writeNotice( "cjw_newsletter class $identifier: $state", __METHOD__ );

        // The newsletter tree (root, system, one list) and the setting that points at its root.
        $treeReport = array();
        $rootNodeID = CjwNewsletterClassInstaller::installTree( null, $treeReport );
        foreach ( $treeReport as $part => $state )
            eZDebug::writeNotice( "cjw_newsletter tree $part: $state", __METHOD__ );
        if ( $rootNodeID )
        {
            $written = CjwNewsletterClassInstaller::writeRootFolderSetting( $rootNodeID );
            eZDebug::writeNotice( "cjw_newsletter RootFolderNodeId=$rootNodeID: $written", __METHOD__ );
        }
        return true;
    }

    function postInstallRekeyStarRatings( $params = false )
    {
        $db = eZDB::instance();
        $tables = $db->eZTableList();
        if ( !isset( $tables['ezstarrating'] ) )
            return true;

        // Seeded rating rows, and the remote id of the object each belongs to.
        $seeded = array(
            // Test Product, shipped by sevenx_multisite_democontent.
            'af35e1174dab340acaddcff36cea57d3' => array( 'attribute' => 'rating',
                                                         'exported_object_id' => 319,
                                                         'rating_average' => 4.3 ),
        );

        foreach ( $seeded as $remoteID => $info )
        {
            $exportedID = (int) $info['exported_object_id'];
            $rows = $db->arrayQuery( "SELECT * FROM ezstarrating WHERE contentobject_id = $exportedID" );
            if ( !$rows )
                continue;

            $object = eZContentObject::fetchByRemoteID( $remoteID );
            if ( !$object )
                continue;

            $objectID = (int) $object->attribute( 'id' );

            // Resolve the attribute id straight from the tables rather than
            // through data_map: at post-install time the object's data map
            // comes back empty, and an empty map must never be read as "this
            // object has no rating attribute" - acting on that once deleted
            // the row this step exists to preserve.
            $identifier = $db->escapeString( $info['attribute'] );
            $attrRows = $db->arrayQuery(
                "SELECT a.id FROM ezcontentobject_attribute a" .
                " INNER JOIN ezcontentclass_attribute ca ON ca.id = a.contentclassattribute_id" .
                " INNER JOIN ezcontentobject o ON o.id = a.contentobject_id" .
                " WHERE a.contentobject_id = $objectID" .
                "   AND a.version = o.current_version" .
                "   AND ca.identifier = '$identifier'", array( 'limit' => 1 ) );
            if ( !$attrRows )
                continue;

            $attributeID = (int) $attrRows[0]['id'];
            if ( $objectID === $exportedID
                 && (int) $rows[0]['contentobject_attribute_id'] === $attributeID )
                continue; // already correct

            // The average is restored here as well as re-keyed. A .dba cannot
            // carry a fractional one: eZDBSchemaInterface::generateDataValueTextSQL
            // casts every float field with (int), so 4.3 is seeded as 4.
            $average = isset( $info['rating_average'] )
                     ? (float) $info['rating_average']
                     : (float) $rows[0]['rating_average'];

            $db->query( "DELETE FROM ezstarrating WHERE contentobject_id = $objectID" );
            $db->query( "UPDATE ezstarrating SET contentobject_id = $objectID," .
                        " contentobject_attribute_id = $attributeID," .
                        " rating_average = $average" .
                        " WHERE contentobject_id = $exportedID" );
        }

        return true;
    }

    function postInstallFixPackageNodesAndExplayouts( $params = false )
    {
        $result = sevenxFixPackageNodesAndExplayouts();
        if ( $result && class_exists( 'expLayoutsResolver' ) )
            expLayoutsResolver::clearCache();
        return $result;
    }

    /**
     * The default admin layouts (admin_3col, admin_2col, admin_full) with their zones, blocks
     * and rules, from explayouts' share/fragments/admin_layouts_db_data.dba. Their ids start at
     * 100001, far above the layouts of the site, so nothing collides; the loader leaves the
     * tables' sequences after the highest id (eZDbSchema corrects them on PostgreSQL).
     * Idempotent: nothing is loaded when an admin layout already exists.
     */
    function postInstallImportAdminLayouts( $params = false )
    {
        $db = eZDB::instance();
        $fragment = $this->extensionBasePath( 'explayouts', 'explayouts' ) . '/share/fragments/admin_layouts_db_data.dba';
        if ( !file_exists( $fragment ) )
        {
            eZDebug::writeNotice( 'No admin layouts fragment at ' . $fragment . ', skipped', __FUNCTION__ );
            return true;
        }
        $rows = $db->arrayQuery( "SELECT COUNT(*) AS c FROM explayouts_layout WHERE layout_type IN ( 'admin_3col', 'admin_2col', 'admin_full' )" );
        if ( $rows === false || ( isset( $rows[0]['c'] ) && (int)$rows[0]['c'] > 0 ) )
        {
            eZDebug::writeNotice( 'Admin layouts exist already (or the explayouts tables do not), nothing imported', __FUNCTION__ );
            return true;
        }

        $dataArray = eZDbSchema::read( $fragment, true );
        $schemaFile = $this->extensionBasePath( 'explayouts', 'explayouts' ) . '/share/db_schema.dba';
        $schemaArray = file_exists( $schemaFile ) ? eZDbSchema::read( $schemaFile, true ) : false;
        if ( !is_array( $dataArray ) || !isset( $dataArray['data'] ) || !is_array( $schemaArray ) || !isset( $schemaArray['schema'] ) )
        {
            eZDebug::writeError( 'The admin layouts fragment or the explayouts schema cannot be read', __FUNCTION__ );
            return false;
        }
        // Only the tables the fragment carries.
        $schema = array( '_info' => isset( $schemaArray['schema']['_info'] ) ? $schemaArray['schema']['_info'] : array() );
        foreach ( array_keys( $dataArray['data'] ) as $table )
        {
            if ( !isset( $schemaArray['schema'][$table] ) )
            {
                eZDebug::writeError( 'The table ' . $table . ' of the admin layouts fragment is not in the explayouts schema', __FUNCTION__ );
                return false;
            }
            $schema[$table] = $schemaArray['schema'][$table];
        }
        $dbSchema = eZDbSchema::instance( array( 'type' => strtolower( $db->databaseName() ),
                                                 'instance' => $db,
                                                 'schema' => $schema,
                                                 'data' => $dataArray['data'] ) );
        if ( !$dbSchema || !$dbSchema->insertSchema( array( 'schema' => false, 'data' => true ) ) )
        {
            eZDebug::writeError( 'The admin layouts could not be imported: ' . $db->errorMessage(), __FUNCTION__ );
            return false;
        }
        if ( class_exists( 'expLayoutsResolver' ) )
            expLayoutsResolver::clearCache();
        eZDebug::writeNotice( 'The default admin layouts and rules are imported', __FUNCTION__ );
        return true;
    }

    function postInstallCleanUrlText( $params = false )
    {
        return sevenxCleanStaleUrlTextAttributes();
    }

    function postInstallRegenerateURLAliases( $params = false )
    {
        return sevenxRegenerateURLAliases();
    }

    /**
     * Create the URL aliases the siteaccess PathPrefix settings depend on.
     *
     * Each site is served from a home node below the content root, and its
     * siteaccess hides that node with PathPrefix - so /bold/services is looked
     * up as bold-agency/services, from the alias root. eZ generates aliases
     * with the prefix left out, which is the point of the setting, so nothing
     * in a normal alias regeneration ever creates the prefixed path: the home
     * node's children end up either at the alias root or under a system
     * node_2 element, and every prefixed request answers 404. The layout
     * resolver is hit too, because it keys on the resolved path - the Bold home
     * page falls through to the catch-all rule and renders without its layout.
     *
     * storePath() creates any missing parents and re-parents an existing
     * entry, so storing each node's prefixed path builds the tree the settings
     * expect without removing anything the regeneration produced.
     *
     * Runs after postInstallRegenerateURLAliases, which has to have populated
     * the per-language texts first.
     */
    function postInstallCreateSitePrefixAliases( $params = false )
    {
        // home nodes of the sites this installation serves, by remote id
        $homeRemoteIDs = array( 'media-n-939', 'media-n-940' );

        $stored = 0;
        foreach ( $homeRemoteIDs as $remoteID )
        {
            $homeNode = eZContentObjectTreeNode::fetchByRemoteID( $remoteID );
            if ( !$homeNode )
            {
                eZDebug::writeWarning( "Could not resolve home node $remoteID, no prefixed aliases created for it", __FUNCTION__ );
                continue;
            }

            $stored += $this->storeSitePrefixAliases( $homeNode );
        }

        eZDebug::writeNotice( "Stored $stored prefixed URL alias path(s)", __FUNCTION__ );
        return true;
    }

    /**
     * Switch off the block links that name a page the content package did not
     * install (see sevenxClearLinksToContentNotInstalled() in
     * post-install-fix.php). Relative paths are tried below each site's home.
     */
    function postInstallClearLinksToContentNotInstalled( $params = false )
    {
        $prefixes = array();
        foreach ( array( 'media-n-939', 'media-n-940' ) as $remoteID )
        {
            $homeNode = eZContentObjectTreeNode::fetchByRemoteID( $remoteID );
            if ( !$homeNode )
                continue;
            $alias = $this->nodeAliasText( (int)$homeNode->attribute( 'node_id' ), $this->primaryLanguageLocale() );
            if ( $alias !== '' )
                $prefixes[] = $alias;
            $pathAlias = (string)$homeNode->attribute( 'url_alias' );
            if ( $pathAlias !== '' && !in_array( $pathAlias, $prefixes ) )
                $prefixes[] = $pathAlias;
        }
        return sevenxClearLinksToContentNotInstalled( $prefixes );
    }

    /**
     * Store the prefixed alias path of every node in a site's subtree, for each
     * language that site's home node is translated into.
     */
    function storeSitePrefixAliases( eZContentObjectTreeNode $homeNode )
    {
        $db = eZDB::instance();
        $homeNodeID = (int)$homeNode->attribute( 'node_id' );
        $homeObject = $homeNode->object();
        $locales = $homeObject ? (array)$homeObject->availableLanguages() : array();
        if ( !$locales )
        {
            // Both of these come back empty when the language context is not
            // established yet, which is what happens during a kickstart of a
            // MongoDB installation. Calling attribute() on the false that
            // topPriorityLanguage() then returns killed the whole install at
            // the last step, after all the content was already in.
            $topLanguage = eZContentLanguage::topPriorityLanguage();
            if ( $topLanguage )
            {
                $locales = array( $topLanguage->attribute( 'locale' ) );
            }
            else
            {
                // Whatever is chosen has to be a language this database
                // actually has: an alias is stored against its id, and a
                // locale that does not resolve fails later inside
                // eZURLAliasML with the same unguarded attribute() call.
                $candidates = array();
                $ini = eZINI::instance();
                if ( $ini->hasVariable( 'RegionalSettings', 'ContentObjectLocale' ) )
                    $candidates[] = (string)$ini->variable( 'RegionalSettings', 'ContentObjectLocale' );
                if ( $ini->hasVariable( 'RegionalSettings', 'SiteLanguageList' ) )
                    $candidates = array_merge( $candidates, (array)$ini->variable( 'RegionalSettings', 'SiteLanguageList' ) );

                foreach ( $candidates as $candidate )
                {
                    if ( $candidate !== '' && eZContentLanguage::fetchByLocale( $candidate ) )
                    {
                        $locales = array( $candidate );
                        break;
                    }
                }

                if ( !$locales )
                {
                    // Last resort: the first language the database holds.
                    $installed = eZContentLanguage::fetchList();
                    if ( is_array( $installed ) && $installed )
                    {
                        $first = reset( $installed );
                        $locales = array( $first->attribute( 'locale' ) );
                    }
                }

                if ( !$locales )
                {
                    eZDebug::writeWarning(
                        'No language could be resolved for node ' . $homeNode->attribute( 'node_id' )
                        . '; no prefixed aliases stored for its subtree', __METHOD__ );
                    return 0;
                }
            }
        }

        $subtree = $db->arrayQuery( "
            SELECT node_id FROM ezcontentobject_tree
            WHERE path_string LIKE '" . $db->escapeString( $homeNode->attribute( 'path_string' ) ) . "%'
            ORDER BY depth ASC, node_id ASC" );

        $stored = 0;
        foreach ( $locales as $locale )
        {
            $prefix = $this->nodeAliasText( $homeNodeID, $locale );
            if ( $prefix === '' )
                continue;

            foreach ( $subtree as $row )
            {
                $nodeID = (int)$row['node_id'];

                // Never touch the home node's own element. It is the parent the
                // whole subtree hangs from, and storePath treats a changed path
                // for an existing action as a move: moving it orphans every
                // child, and the cleanup pass then removes them. The prefix
                // element is created implicitly as the parent of the first child
                // path stored below, which is all the PathPrefix lookup needs.
                if ( $nodeID === $homeNodeID )
                    continue;

                {
                    $segments = array();
                    $walk = eZContentObjectTreeNode::fetch( $nodeID );
                    $incomplete = false;
                    while ( $walk )
                    {
                        $walkID = (int)$walk->attribute( 'node_id' );
                        if ( $walkID === $homeNodeID )
                            break;
                        $text = $this->nodeAliasText( $walkID, $locale );
                        if ( $text === '' )
                        {
                            $incomplete = true;
                            break;
                        }
                        array_unshift( $segments, $text );
                        $walk = $walk->attribute( 'parent' );
                    }
                    if ( $incomplete )
                        continue;
                    array_unshift( $segments, $prefix );
                    $path = implode( '/', $segments );
                }

                // cleanupElements is off: it removes elements it considers
                // unreferenced, and with a whole subtree being restored in one
                // pass it discarded aliases that were about to be re-parented.
                $result = eZURLAliasML::storePath( $path, 'eznode:' . $nodeID, $locale, false, false, false, false );
                if ( isset( $result['status'] ) && $result['status'] )
                    $stored++;
            }
        }

        return $stored;
    }

    /**
     * The alias text a node already carries in the given language, falling back
     * to its name converted to an alias.
     *
     * Reusing the generated row keeps the per-language wording the content
     * defines - services against leistungen - instead of deriving both from one
     * translation.
     */
    function nodeAliasText( $nodeID, $locale )
    {
        $db = eZDB::instance();
        $language = eZContentLanguage::fetchByLocale( $locale );
        if ( $language )
        {
            // text != '' matches nothing where '' is NULL (Oracle): NULL is
            // excluded in SQL, the empty string here
            $rows = $db->arrayQuery( "
                SELECT text FROM ezurlalias_ml
                WHERE action = 'eznode:" . (int)$nodeID . "'
                  AND text IS NOT NULL
                  AND " . $db->bitAnd( 'lang_mask', (int)$language->attribute( 'id' ) ) . " > 0
                ORDER BY id" );
            foreach ( (array) $rows as $row )
                if ( (string)$row['text'] !== '' )
                    return $row['text'];
        }

        $node = eZContentObjectTreeNode::fetch( (int)$nodeID );
        if ( !$node )
            return '';

        $object = $node->object();
        $name = $object ? $object->name( false, $locale ) : false;
        if ( $name === false || $name === null || $name === '' )
            $name = $node->attribute( 'name' );
        if ( $name === null || $name === '' )
            return '';

        return eZURLAliasML::convertToAlias( $name, 'node_' . (int)$nodeID );
    }

    private function fixPackageNodesAndExplayouts()
    {
        $adminUser = eZUser::instance( 14 );
        if ( $adminUser )
        {
            eZUser::setCurrentlyLoggedInUser( $adminUser, 14 );
        }
        else
        {
            eZDebug::writeWarning( 'The administrator (user 14) was not found: the nodes below are published without a logged-in user', __METHOD__ );
        }

        // eng-US is the only content language this site supports. eng-GB was
        // never a translation, only an artefact of kickstart.ini listing it as
        // a second language, and it left every class definition carrying an
        // empty eng-GB name and description.
        eZContentLanguage::setPrioritizedLanguages( array( 'eng-US' ) );

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

            $object = eZContentObject::fetch( $packageObjectID );
            if ( !$object && $objectRemoteID )
            {
                $object = eZContentObject::fetchByRemoteID( $objectRemoteID );
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

                // Flush in-memory object caches before each publish so a stale
                // object from a previous DB connection is not reused.
                eZContentClass::expireCache();
                unset( $GLOBALS['eZContentObjectContentObjectCache'] );


                $actualNodeId = eZContentOperationCollection::publishNode( $parentNodeID, $a['object_id'], $a['version'], false );
                if ( $actualNodeId )
                {
                    $actualNodeId = (int)$actualNodeId;
                    $remoteIdToNodeId[$a['node_remote_id']] = $actualNodeId;
                    $packageNodeMap[$packageNodeId] = $actualNodeId;
                    $createdCount++;
                    $progress = true;
                }
                else
                {
                    eZDebug::writeError( "publishNode failed for object {$a['object_id']} under parent $parentNodeID", __FUNCTION__ );
                }
            }
        } while ( $progress && $pass < $maxPasses );

        eZDebug::writeNotice( "Created $createdCount missing tree nodes in $pass pass(es)", __FUNCTION__ );

        // Remap explayouts references.
        // Bound to itself: it calls itself for the elements of an array
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

        eZDebug::writeNotice( 'Done remapping explayouts references', __FUNCTION__ );

        // Align the siteaccess IndexPage/DefaultPage with the real home node so
        // URL alias regeneration and the explayouts resolver see the same root.
        $homeNode = eZContentObjectTreeNode::fetchByRemoteID( 'media-n-939' );
        if ( $homeNode )
        {
            $homeNodeId = (int)$homeNode->attribute( 'node_id' );
            $homeURL = 'content/view/full/' . $homeNodeId;
            $homeAlias = eZURLAliasML::convertToAlias( $homeNode->attribute( 'name' ), 'node_' . $homeNodeId );

            // Write these into the user siteaccess's own site.ini.append.php and
            // save it, the way the Bold siteaccesses below are handled.
            // eZINI::instance() alone only sets them on the running instance, so
            // the file kept whatever PathPrefix it already had - and on a clean
            // install that is nothing, which leaves every page below the site
            // root unreachable.
            $userSiteaccess = $this->setting( 'user_siteaccess' );
            $userSettingsDir = 'settings/siteaccess/' . $userSiteaccess;
            $ini = eZINI::instance( 'site.ini.append.php', $userSettingsDir, null, false, null, true );
            if ( $ini && file_exists( $userSettingsDir . '/site.ini.append.php' ) )
            {
                $ini->setVariable( 'SiteSettings', 'IndexPage', $homeURL );
                $ini->setVariable( 'SiteSettings', 'DefaultPage', $homeURL );
                $ini->setVariable( 'SiteAccessSettings', 'PathPrefix', $homeAlias );
                $ini->save( false, false, false, false, true, true );
            }

            // Keep the running instance in step for the remaining steps.
            $runtimeINI = eZINI::instance();
            $runtimeINI->setVariable( 'SiteSettings', 'IndexPage', $homeURL );
            $runtimeINI->setVariable( 'SiteSettings', 'DefaultPage', $homeURL );
            $runtimeINI->setVariable( 'SiteAccessSettings', 'PathPrefix', $homeAlias );

            // Make sure the empty-root alias points to the home node so eZURLAliasML
            // resolves it for the empty path (it is not updated by updateSubTreePath).
            $db->query( "UPDATE ezurlalias_ml SET action='eznode:" . $homeNodeId . "' WHERE ( text = '' OR text IS NULL ) AND parent = 0" );

            eZDebug::writeNotice( "Set home page to $homeURL and prefix to $homeAlias", __FUNCTION__ );
        }
        else
        {
            eZDebug::writeWarning( 'Could not find home node by remote ID media-n-939', __FUNCTION__ );
        }

        // Align Bold Agency (and its German translation) with the real Bold home node.
        $boldHomeNode = eZContentObjectTreeNode::fetchByRemoteID( 'media-n-940' );
        if ( $boldHomeNode )
        {
            $boldHomeNodeId = (int)$boldHomeNode->attribute( 'node_id' );
            $boldHomeURL = 'content/view/full/' . $boldHomeNodeId;
            $boldHomeAlias = eZURLAliasML::convertToAlias( $boldHomeNode->attribute( 'name' ), 'node_' . $boldHomeNodeId );

            foreach ( array( 'bold', 'bold_ger' ) as $boldSiteaccess )
            {
                $boldINI = eZINI::instance( 'site.ini.append.php', 'settings/siteaccess/' . $boldSiteaccess, null, false, null, true );
                if ( !$boldINI || !file_exists( 'settings/siteaccess/' . $boldSiteaccess . '/site.ini.append.php' ) )
                    continue;

                $boldINI->setVariable( 'SiteSettings', 'IndexPage', $boldHomeURL );
                $boldINI->setVariable( 'SiteSettings', 'DefaultPage', $boldHomeURL );
                $boldINI->setVariable( 'SiteAccessSettings', 'PathPrefix', $boldHomeAlias );
                $boldINI->save( false, false, false, false, true, true );

                eZDebug::writeNotice( "Set $boldSiteaccess home page to $boldHomeURL and prefix to $boldHomeAlias", __FUNCTION__ );
            }
        }

        $this->installMenuNodeIDs();

        return true;
    
    }

    /**
     * Writes the header and footer menus (menu.ini [SiteInfo] MainMenuID[],
     * FooterMenuID[], PrivacyPolicyID, CookiePolicyID) into each siteaccess's
     * own menu.ini.append.php, resolved from the nodes' remote ids.
     *
     * Node ids depend on the order the package creates nodes in, so a list of
     * ids written into the theme's settings points at whatever node happens to
     * get that id (images and partner logos in a clean install). Remote ids
     * come from the package and are the same in every installation.
     */
    private function installMenuNodeIDs()
    {
        $fitHealthy = array(
            'MainMenuID' => array( 'media-n-721', 'media-n-722', 'media-n-744', 'media-n-749', 'media-n-752' ),
            'FooterMenuID' => array( 'media-n-749', 'media-n-9501', 'media-n-778', 'media-n-911', 'media-n-1060' ),
            'PrivacyPolicyID' => 'media-n-1060',
            'CookiePolicyID' => 'media-n-1061',
        );
        $boldAgency = array(
            'MainMenuID' => array( 'media-n-941', 'media-n-946', 'media-n-947', 'media-n-959' ),
            'FooterMenuID' => array( 'media-n-941', 'media-n-946', 'media-n-947', 'media-n-959', 'media-n-911', 'media-n-1062' ),
            'PrivacyPolicyID' => 'media-n-1062',
            'CookiePolicyID' => 'media-n-1063',
        );
        $menus = array(
            $this->setting( 'user_siteaccess' ) => $fitHealthy,
            'bold' => $boldAgency,
            'bold_ger' => $boldAgency,
        );
        // Language siteaccesses (eng, ...) translate the user siteaccess;
        // bold_* ones translate Bold Agency.
        foreach ( (array)$this->setting( 'language_based_siteaccess_list' ) as $languageSiteaccess )
        {
            if ( !isset( $menus[$languageSiteaccess] ) )
                $menus[$languageSiteaccess] = strpos( $languageSiteaccess, 'bold_' ) === 0 ? $boldAgency : $fitHealthy;
        }

        $nodeID = function( $remoteID )
        {
            $node = eZContentObjectTreeNode::fetchByRemoteID( $remoteID );
            return $node ? (int)$node->attribute( 'node_id' ) : false;
        };

        foreach ( $menus as $siteaccess => $menu )
        {
            $settingsDir = 'settings/siteaccess/' . $siteaccess;
            if ( !$siteaccess || !is_dir( $settingsDir ) )
                continue;

            $ini = eZINI::instance( 'menu.ini.append.php', $settingsDir, null, false, null, true );
            $missing = array();
            foreach ( $menu as $name => $remoteIDs )
            {
                if ( is_array( $remoteIDs ) )
                {
                    $ids = array();
                    foreach ( $remoteIDs as $remoteID )
                    {
                        $id = $nodeID( $remoteID );
                        if ( $id )
                            $ids[] = $id;
                        else
                            $missing[] = $remoteID;
                    }
                    $ini->setVariable( 'SiteInfo', $name, $ids );
                }
                else
                {
                    $id = $nodeID( $remoteIDs );
                    if ( $id )
                        $ini->setVariable( 'SiteInfo', $name, $id );
                    else
                        $missing[] = $remoteIDs;
                }
            }
            $ini->save( false, false, false, false, true, true );

            if ( $missing )
                eZDebug::writeWarning( "Menu nodes not found for $siteaccess: " . implode( ', ', array_unique( $missing ) ), __FUNCTION__ );
            eZDebug::writeNotice( "Set $siteaccess header and footer menus from remote ids", __FUNCTION__ );
        }
    }

    private function cleanStaleUrlTextAttributes()
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

    private function regenerateURLAliases()
    {
        $db = eZDB::instance();

        $db->query( 'TRUNCATE TABLE ezurlalias_ml' );
        $db->query( 'TRUNCATE TABLE ezurlalias' );
        $db->query( 'TRUNCATE TABLE ezurlalias_ml_incr' );

        // Node 1, the root, is its own parent and has no alias
        $rows = $db->arrayQuery( 'SELECT node_id FROM ezcontentobject_tree WHERE node_id <> 1 ORDER BY depth ASC, node_id ASC' );
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

        // Publish all explayouts layouts (and their zones/blocks). The DBA
        // exports from nexus store drafts with status=1; the eZ4 renderer
        // expects status=2 for published blocks.
        $layouts = expLayoutsLayout::fetchList();
        $publishedCount = 0;
        foreach ( $layouts as $layout )
        {
            $layout->publish();
            $publishedCount++;
        }
        eZDebug::writeNotice( "Published $publishedCount explayouts layouts", __FUNCTION__ );

        return true;
    
    }

    /**
     * The extension schemas this installer inserts, in order: package, extension,
     * whether its db_data.dba is loaded too.
     *
     * sevenx_themes_media comes first: its schema and data carry the layouts,
     * tags and ratings of the demo content, so the tables explayouts, eztags and
     * ezstarrating also declare exist by the time those are inserted and are
     * left as sevenx_themes_media seeded them.
     */
    function extensionSchemas()
    {
        $schemas = $this->declaredExtensionSchemas();
        // Every other extension of the install list that declares tables (a
        // share/db_schema.dba, else sql/<engine>/schema.sql) gets them as well:
        // a list kept by hand left xrowextract and expservices without theirs, and the
        // upgrade check then asked to create them.
        $listed = array();
        foreach ( $schemas as $e )
            $listed[$e[1]] = true;
        foreach ( array_values( array_unique( (array)$this->setting( 'extension_list' ) ) ) as $extension )
        {
            if ( isset( $listed[$extension] ) )
                continue;
            $base = $this->extensionBasePath( $extension, $extension );
            if ( file_exists( $base . '/share/db_schema.dba' ) || glob( $base . '/sql/*/schema.sql' ) )
                $schemas[] = array( $extension, $extension, false );
        }
        return $schemas;
    }

    /** The extension schemas named by hand: package, extension, whether db_data.dba is loaded too. */
    function declaredExtensionSchemas()
    {
        return array(
            array( 'sevenx_themes_media', 'sevenx_themes_media', true ),
            // Schema only: ezstarrating ships DDL and no default rows; the demo
            // rating data lives in sevenx_themes_media's db_data.dba.
            array( 'ezstarrating_extension', 'ezstarrating', false ),
            array( 'ezgmaplocation_extension', 'ezgmaplocation', false ),
            array( 'eztags', 'eztags', false ),
            array( 'explayouts', 'explayouts', true ),
            // enhancedselection2 owns sckenhancedselection, which twenty class
            // attributes across ng_category, ng_video, ng_component_features,
            // ng_component_quote and ng_menu_item depend on.
            array( 'enhancedselection2', 'enhancedselection2', false ),
            // cjw_newsletter is in extension_list and owns eleven cjwnl_* tables.
            array( 'cjw_newsletter', 'cjw_newsletter', false ),
            // syndication is in extension_list and owns ten ezsyndication_* tables
            // and ezx_ezpnet_soap_log (share/db_schema.dba from syndication 1.3.1 on).
            array( 'syndication', 'syndication', false ),
        );
    }

    /** Where an extension's files are: <package>/ezextension/<name>, else extension/<name>. */
    function extensionBasePath( $packageName, $extensionName )
    {
        $extensionPackage = eZPackage::fetch( $packageName, false, false, false );
        if ( $extensionPackage instanceof eZPackage )
            return $extensionPackage->path() . '/ezextension/' . $extensionName;
        return eZSys::rootDir() . '/' . eZExtension::baseDirectory() . '/' . $extensionName;
    }

    /** Drop every table any extension schema of extensionSchemas() declares. */
    function dropExtensionTables()
    {
        $db = eZDB::instance();
        $tables = array();
        foreach ( $this->extensionSchemas() as $e )
        {
            $file = $this->extensionBasePath( $e[0], $e[1] ) . '/share/db_schema.dba';
            $schema = file_exists( $file ) ? eZDbSchema::read( $file, true ) : false;
            if ( is_array( $schema ) && isset( $schema['schema'] ) )
                foreach ( array_keys( $schema['schema'] ) as $t )
                    if ( $t !== '_info' )
                        $tables[$t] = true;
        }
        $mysql = in_array( strtolower( $db->databaseName() ), array( 'mysql', 'mysqli' ) );
        if ( $mysql )
            $db->query( 'SET FOREIGN_KEY_CHECKS=0' );
        // DROP TABLE IF EXISTS is what MySQL, PostgreSQL, SQLite and MongoDB take,
        // Oracle only from 23ai on. There is no single neutral form: the other one,
        // dropping what relationList() names, misses tables on SQLite, whose
        // relationList() names ez* tables only. So Oracle drops the tables its
        // relationList() (every table of the schema) names, through the driver.
        $existing = $db->databaseName() === 'oracle' ? array_flip( (array) $db->relationList() ) : null;
        foreach ( array_keys( $tables ) as $t )
        {
            if ( $existing === null )
                $db->query( 'DROP TABLE IF EXISTS ' . $t );
            else if ( isset( $existing[$t] ) )
                $db->removeRelation( $t, eZDBInterface::RELATION_TABLE );
        }
        if ( $mysql )
            $db->query( 'SET FOREIGN_KEY_CHECKS=1' );
        eZDebug::writeNotice( count( $tables ) . ' extension tables reset before the extension schemas are inserted', __METHOD__ );
    }

    function insertDBFile( $packageName, $extensionName, $loadContent = false )
    {
        $db = eZDB::instance();
        $basePath = $this->extensionBasePath( $packageName, $extensionName );

        if ( !file_exists( $basePath ) )
        {
            eZDebug::writeError( 'Extension path not found for ' . $extensionName . ': ' . $basePath, __METHOD__ );
            return;
        }

        $dbaFile = $basePath . '/share/db_schema.dba';
        if ( file_exists( $dbaFile ) )
        {
            $schemaArray = eZDbSchema::read( $dbaFile, true );
            if ( is_array( $schemaArray ) && isset( $schemaArray['schema'] ) )
            {
                if ( $loadContent )
                {
                    $dbaDataFile = $basePath . '/share/db_data.dba';
                    if ( file_exists( $dbaDataFile ) )
                    {
                        $dataArray = eZDbSchema::read( $dbaDataFile, true );
                        if ( is_array( $dataArray ) && isset( $dataArray['data'] ) )
                        {
                            $schemaArray = array_merge( $schemaArray, $dataArray );
                        }
                    }
                }

                // Tables that exist now were created by an earlier schema of this
                // run (dropExtensionTables() emptied the rest): left as they are,
                // with their data, instead of failing "already exists".
                $existing = array();
                $reader = eZDbSchema::instance( $db );
                $live = $reader ? $reader->schema( array( 'format' => 'local' ) ) : array();
                foreach ( array_keys( $schemaArray['schema'] ) as $t )
                    if ( $t !== '_info' && isset( $live[$t] ) )
                    {
                        $existing[] = $t;
                        unset( $schemaArray['schema'][$t], $schemaArray['data'][$t] );
                    }
                if ( $existing )
                    eZDebug::writeNotice( "$extensionName: " . count( $existing ) . ' tables already created by an earlier schema, left as they are: ' . implode( ', ', $existing ), __METHOD__ );

                $schemaArray['type']     = strtolower( $db->databaseName() );
                $schemaArray['instance'] = $db;
                $dbSchema = eZDbSchema::instance( $schemaArray );

                if ( $dbSchema )
                {
                    $res = $dbSchema->insertSchema( array( 'schema' => true, 'data' => $loadContent ) );
                    if ( !$res )
                    {
                        eZDebug::writeError( 'Can\'t initialize ' . $extensionName . ' database from db_schema.dba: ' . $db->errorMessage(), __METHOD__ );
                    }
                    $this->createMissingSchemaTables( $db, $dbSchema, $schemaArray['schema'], $extensionName );
                    return;
                }

                // No eZDbSchema handler for this database (e.g. Oracle); fall through to per-engine SQL files.
            }
        }

        // Fallback to engine-specific SQL files.
        $engine = strtolower( $db->databaseName() );
        $sqlMap = array(
            'sqlite'     => array( 'sqlite.sql', 'schema.sql' ),
            'mysql'      => array( 'mysql.sql', 'schema.sql' ),
            'mysqli'     => array( 'mysql.sql', 'schema.sql' ),
            'postgresql' => array( 'postgresql.sql', 'schema.sql' ),
            'pgsql'      => array( 'postgresql.sql', 'schema.sql' ),
            'oracle'     => array( 'schema.sql' ),
            'mongo'      => array( 'schema.json' ),
        );

        // The directory an engine keeps its files in is not always the name the
        // driver reports. expMongoDB reports 'mongo' while extensions ship
        // sql/mongodb.
        $sqlDirMap = array( 'mongo' => 'mongodb' );

        if ( !isset( $sqlMap[$engine] ) )
        {
            eZDebug::writeError( 'No SQL fallback for database type: ' . $engine, __METHOD__ );
            return;
        }

        $sqlDir = isset( $sqlDirMap[$engine] ) ? $sqlDirMap[$engine] : $engine;
        $sqlPath = $basePath . '/sql/' . $sqlDir;
        $res = false;
        foreach ( $sqlMap[$engine] as $sqlFile )
        {
            $res = $db->insertFile( $sqlPath, $sqlFile, false );
            if ( $res )
            {
                break;
            }
        }

        if ( !$res )
        {
            eZDebug::writeError( 'Can\'t initialize ' . $extensionName . ' database schema.', __METHOD__ );
        }

        if ( $res && $loadContent )
        {
            // Engine-specific data files take precedence; fall back to common democontent.sql.
            $dataFiles = array( 'data.sql', 'democontent.sql' );
            $res = false;
            foreach ( $dataFiles as $dataFile )
            {
                $dataPath = $basePath . '/sql/' . $sqlDir;
                $res = $db->insertFile( $dataPath, $dataFile, false );
                if ( $res )
                {
                    break;
                }
                $dataPath = $basePath . '/sql/common';
                $res = $db->insertFile( $dataPath, $dataFile, false );
                if ( $res )
                {
                    break;
                }
            }

            if ( !$res )
            {
                eZDebug::writeError( 'Can\'t initialize ' . $extensionName . ' demo data.', __METHOD__ );
            }
        }
    }

    /**
     * Every table an extension's schema declares, checked after insertSchema().
     * insertSchema() stops at the first statement that fails, so one bad index
     * left every later table of the file uncreated while the install carried
     * on. A missing table is created on its own from the same definition, and
     * whatever still fails is reported with the database's message.
     */
    function createMissingSchemaTables( $db, $dbSchema, $schema, $extensionName )
    {
        // eZTableList() lists only tables named ez*; the schema reader lists all
        $reader = eZDbSchema::instance( $db );
        $live = $reader ? $reader->schema( array( 'format' => 'local' ) ) : array();
        unset( $live['_info'] );
        $existing = array_flip( array_map( 'strtolower', array_keys( is_array( $live ) ? $live : array() ) ) );
        $failed = array();
        foreach ( $schema as $table => $tableDef )
        {
            if ( $table === '_info' || !is_array( $tableDef ) || isset( $existing[strtolower( $table )] ) )
                continue;
            $ok = true;
            foreach ( $dbSchema->generateTableSQLList( $table, $tableDef, array(), false ) as $sql )
            {
                if ( !$db->query( $sql ) )
                {
                    $ok = false;
                    break;
                }
            }
            if ( $ok )
                eZDebug::writeWarning( "Created table $table of $extensionName, which the schema insert had left out", __METHOD__ );
            else
                $failed[] = $table . ' (' . $db->errorMessage() . ')';
        }
        if ( $failed )
            eZDebug::writeError( "Tables of $extensionName that could not be created: " . implode( ', ', $failed ), __METHOD__ );
        return !$failed;
    }

    function updateTemplateLookClassAttributes( $params = false )
    {
        $newAttributesInfo = array( 
            array( 
                "data_type_string" => "ezurl", 
                "name" => "Site map URL", 
                "identifier" => "site_map_url" 
            ), 
            array( 
                "data_type_string" => "ezurl", 
                "name" => "Tag Cloud URL", 
                "identifier" => "tag_cloud_url" 
            ), 
            array( 
                "data_type_string" => "ezstring", 
                "name" => "Login (label)", 
                "identifier" => "login_label" 
            ), 
            array( 
                "data_type_string" => "ezstring", 
                "name" => "Logout (label)", 
                "identifier" => "logout_label" 
            ), 
            array( 
                "data_type_string" => "ezstring", 
                "name" => "My profile (label)", 
                "identifier" => "my_profile_label" 
            ), 
            array( 
                "data_type_string" => "ezstring", 
                "name" => "Register new user (label)", 
                "identifier" => "register_user_label" 
            ), 
            array( 
                "data_type_string" => "ezstring", 
                "name" => "RSS feed", 
                "identifier" => "rss_feed" 
            ), 
            array( 
                "data_type_string" => "ezstring", 
                "name" => "Shopping basket (label)", 
                "identifier" => "shopping_basket_label" 
            ), 
            array( 
                "data_type_string" => "ezstring", 
                "name" => "Site settings (label)", 
                "identifier" => "site_settings_label" 
            ), 
            array( 
                "data_type_string" => "eztext", 
                "name" => "Footer text", 
                "identifier" => "footer_text" 
            ), 
            array( 
                "data_type_string" => "ezboolean", 
                "name" => "Hide \"Powered by\"", 
                "identifier" => "hide_powered_by" 
            ), 
            array( 
                "data_type_string" => "eztext", 
                "name" => "Footer Javascript", 
                "identifier" => "footer_script" 
            ) 
        );
        $this->addClassAttributes( array( 
            'class' => array( 
                'id' => $this->setting( 'template_look_class_id' ) 
            ), 
            'attributes' => $newAttributesInfo 
        ) );
    }

    function updateTemplateLookObjectAttributes( $params = false )
    {
        //create data array
        $templateLookData = array( 
            "site_map_url" => array( 
                "DataText" => "Site map", 
                "Content" => "/content/view/sitemap/2" 
            ), 
            "tag_cloud_url" => array( 
                "DataText" => "Tag cloud", 
                "Content" => "/content/view/tagcloud/2" 
            ), 
            "login_label" => array( 
                "DataText" => "Login" 
            ), 
            "logout_label" => array( 
                "DataText" => "Logout" 
            ), 
            "my_profile_label" => array( 
                "DataText" => "My profile" 
            ), 
            "register_user_label" => array( 
                "DataText" => "Register" 
            ), 
            "rss_feed" => array( 
                "DataText" => "/rss/feed/my_feed" 
            ), 
            "shopping_basket_label" => array( 
                "DataText" => "Shopping basket" 
            ), 
            "site_settings_label" => array( 
                "DataText" => "Site settings" 
            ), 
            "footer_text" => array( 
                "DataText" => "Copyright &#169; " . date( 'Y' ) . " <a href=\"https://se7enx.com\" title=\"7x\">7x</a> (except where otherwise noted). All rights reserved." 
            ), 
            "hide_powered_by" => array( 
                "DataInt" => 0 
            ), 
            "footer_script" => array( 
                "DataText" => "" 
            ) 
        );
        $this->updateContentObjectAttributes( array( 
            'object_id' => $this->setting( 'template_look_object_id' ), 
            'attributes_data' => $templateLookData 
        ) );
    }

    function solutionVersion()
    {
        $version = self::MAJOR_VERSION . '.' . self::MINOR_VERSION;
        return $version;
    }

    /**
     * The remote id of the main site's home node, as shipped by the demo
     * content package. Fixed, the same way secondarySiteaccessHomeRemoteID()
     * hardcodes the remote ids of the other siteaccess homes.
     */
    function mainSiteaccessHomeRemoteID()
    {
        return 'media-n-939';   // Fit & Healthy
    }

    function homeNodeID()
    {
        $homeNodeID = $this->setting( 'home_node_id' );
        if ( $homeNodeID )
            return $homeNodeID;

        // Resolve by remote id with plain SQL before anything else. The class
        // scan below reads $object->attribute( 'name' ) and 'main_node', and
        // both go through the prioritised-language filter: ezcontentobject_name
        // rows carry language ids that are still stale while the install is
        // running, and eZContentObjectTreeNode::fetch() returns null for the
        // same reason. Every one of those lookups failed, homeNodeID() fell
        // through to 2, and postInstallUserSiteaccessINIUpdate() wrote
        // IndexPage=/content/view/full/2 - so the site answered its own content
        // root ("Websites") at / instead of the home page. Remote ids are not
        // language-scoped, so this path cannot fail that way.
        $db = eZDB::instance();
        if ( $db )
        {
            $escaped = $db->escapeString( $this->mainSiteaccessHomeRemoteID() );
            $rows = $db->arrayQuery( "SELECT node_id FROM ezcontentobject_tree" .
                                     " WHERE remote_id = '$escaped' ORDER BY node_id ASC", array( 'limit' => 1 ) );
            if ( $rows )
                return (int) $rows[0]['node_id'];
        }

        $homeNodeID = 2; // default content root
        $classIdentifiers = array( 'frontpage', 'ng_frontpage' );
        foreach ( $classIdentifiers as $identifier )
        {
            $class = eZContentClass::fetchByIdentifier( $identifier, true, eZContentClass::VERSION_STATUS_DEFINED );
            if ( !$class )
                continue;

            $objects = eZContentObject::fetchSameClassList( $class->attribute( 'id' ), true, false, false, array( 'name' => 'asc' ) );
            $fallbackNodeID = 0;
            foreach ( $objects as $object )
            {
                $objectName = $object->attribute( 'name' );
                $mainNode = $object->attribute( 'main_node' );
                if ( !$mainNode )
                    continue;

                if ( strcasecmp( $objectName, 'Fit & Healthy' ) == 0 )
                {
                    return $mainNode->attribute( 'node_id' );
                }

                if ( $fallbackNodeID == 0 )
                    $fallbackNodeID = $mainNode->attribute( 'node_id' );
            }

            // No preferred match found; use the first object of this class as a fallback
            if ( $homeNodeID == 2 && $fallbackNodeID != 0 )
            {
                $homeNodeID = $fallbackNodeID;
            }
        }
        return $homeNodeID;
    }

    function homeNodeDepth()
    {
        $homeNodeID = $this->homeNodeID();
        $node = eZContentObjectTreeNode::fetch( $homeNodeID );
        if ( $node )
        {
            $path = trim( $node->attribute( 'path_string' ), '/' );
            if ( $path != '' )
            {
                $depth = count( explode( '/', $path ) ) - 1;
                return $depth > 0 ? $depth : 1;
            }
        }
        return 1;
    }

    function rootNodeID()
    {
        $homeNodeID = $this->homeNodeID();
        if ( $homeNodeID == 2 )
            return 2;
        $node = eZContentObjectTreeNode::fetch( $homeNodeID );
        if ( $node )
        {
            $parentNode = $node->attribute( 'parent' );
            if ( $parentNode )
                return $parentNode->attribute( 'node_id' );
        }
        return 2;
    }

    function homeNodeURL( $nodeID = false )
    {
        if ( $nodeID === false )
            $nodeID = $this->homeNodeID();
        return '/content/view/full/' . $nodeID;
    }

    function homeNodePath()
    {
        $homeNodeID = $this->homeNodeID();
        if ( $homeNodeID == 2 )
            return '';

        $homeNode = eZContentObjectTreeNode::fetch( $homeNodeID );
        if ( !$homeNode )
            return '';

        $parentNode = $homeNode->attribute( 'parent' );
        if ( $parentNode && $parentNode->attribute( 'node_id' ) != 2 )
            return $parentNode->attribute( 'url_alias' );

        return $homeNode->attribute( 'url_alias' );
    }

    /**
     * The language switcher map for the administration interface: the user
     * siteaccess plus its translation siteaccesses.
     *
     * Only the admin siteaccess gets one. TranslationSA is what drives the
     * header language dropdown (see setupTranslationSAList(), which returns
     * nothing when the setting is absent), and the public sites do not offer
     * that control - the main site is single-language, and the Bold sites get
     * their own two-entry map from the theme extension.
     *
     * It must not go in settings/override either: TranslationSA is a
     * whole-array setting, so a copy there is loaded last and its reset wipes
     * the entries a theme extension ships for its own siteaccesses.
     */
    function mainTranslationSAMap()
    {
        $siteaccessTypes = $this->setting( 'siteaccess_urls' );
        $userSiteaccess = $this->setting( 'user_siteaccess' );

        $map = array();
        $map[$userSiteaccess] = $this->translationSALabel( $userSiteaccess, $this->setting( 'primary_language' ) );

        if ( isset( $siteaccessTypes['translation'] ) )
        {
            foreach ( $siteaccessTypes['translation'] as $name => $urlInfo )
            {
                if ( !isset( $map[$name] ) )
                    $map[$name] = $this->translationSALabel( $name );
            }
        }

        return $map;
    }

    function translationSALabel( $siteaccess, $locale = false )
    {
        $labelMap = array(
            'site' => 'English',
            'sevenx_site_user' => 'English',
            'eng' => 'English',
            'ger' => 'Deutsch',
        );

        $boldLabelMap = array(
            'bold' => 'English',
            'bold_ger' => 'Deutsch',
        );

        if ( isset( $boldLabelMap[$siteaccess] ) )
            return $boldLabelMap[$siteaccess];

        // The user siteaccess and each translation siteaccess are labelled with
        // the name of the language they serve, in that language (share/locale
        // LanguageName: "Deutsch (Deutschland)", "English (United Kingdom)").
        // The fixed map said English for a German site, and names it did not
        // know came out as "Fre" or "Pol".
        if ( !$locale )
        {
            $mapLocale = array_search( $siteaccess, (array)$this->setting( 'language_siteaccess_map' ), true );
            if ( $mapLocale !== false )
                $locale = $mapLocale;
        }
        if ( $locale )
        {
            $localeObject = eZLocale::instance( $locale );
            $label = $localeObject ? trim( (string)$localeObject->attribute( 'language_name' ) ) : '';
            if ( $label !== '' )
                return $label;
        }

        if ( strpos( $siteaccess, 'bold_' ) === 0 )
        {
            $suffix = substr( $siteaccess, 5 );
            if ( $suffix === 'ger' )
                return 'Deutsch';
            if ( $suffix === 'eng' )
                return 'English';
            if ( $locale )
                return ucfirst( $this->languageNameFromLocale( $locale ) );
            return ucfirst( $suffix );
        }

        if ( isset( $labelMap[$siteaccess] ) )
            return $labelMap[$siteaccess];

        if ( $locale )
            return ucfirst( $this->languageNameFromLocale( $locale ) );

        return ucfirst( $siteaccess );
    }

    function solutionName()
    {
        return 'simple';
    }

    function solutionExtensionName()
    {
        return 'sevenx_multisite_default_installer';
    }

    /**
     * Search statistics on every siteaccess this installer writes: site.ini [SearchSettings] LogSearchStats=enabled in
     * settings/siteaccess/<name>/site.ini.append.php (Exponential's settings/site.ini has it disabled). One step, after
     * every siteaccess's site.ini is written, over the installer's own list of siteaccesses, so one added to that list
     * later gets it too. A siteaccess whose directory is not there yet is left out and logged: the editor siteaccess
     * (and adminui, when the setup makes it) is a copy of the admin one made after the post-install, and takes the
     * setting with the copy. The file is saved round trip, everything else in it as it was. Never stops the install.
     */
    function postInstallEnableSearchStatsOnEverySiteaccess( $params = false )
    {
        $root = is_array( $params ) && isset( $params['siteaccess_root'] ) ? rtrim( $params['siteaccess_root'], '/' ) : 'settings/siteaccess';
        $done = array();
        $left = array();
        foreach ( $this->searchStatsSiteaccessList() as $siteaccess )
        {
            $dir = $root . '/' . $siteaccess;
            if ( !file_exists( $dir . '/site.ini.append.php' ) )
            {
                $left[] = $siteaccess;
                continue;
            }
            $ini = new eZINI( 'site.ini.append.php', $dir, null, false, null, true, true );
            $ini->setReadOnlySettingsCheck( false );
            $ini->setVariable( 'SearchSettings', 'LogSearchStats', 'enabled' );
            if ( $ini->save( false, false, false, false, true, false ) )
                $done[] = $siteaccess;
            else
                eZDebug::writeError( "LogSearchStats=enabled could not be saved in $dir/site.ini.append.php", __METHOD__ );
        }
        eZDebug::writeNotice( 'LogSearchStats=enabled on: ' . ( $done ? implode( ', ', $done ) : 'no siteaccess' )
                              . ( $left ? '; not there yet: ' . implode( ', ', $left ) : '' ), __METHOD__ );
        return true;
    }

    /**
     * The siteaccesses this installer writes settings for: the served list where the installer has one, otherwise
     * every siteaccess it names (main, admin, and one per language).
     */
    function searchStatsSiteaccessList()
    {
        if ( method_exists( $this, 'servedSiteaccessList' ) )
            $list = $this->servedSiteaccessList();
        else
            $list = $this->hasSetting( 'all_siteaccess_list' ) ? (array)$this->setting( 'all_siteaccess_list' ) : array();
        if ( $this->hasSetting( 'language_based_siteaccess_list' ) )
            $list = array_merge( $list, (array)$this->setting( 'language_based_siteaccess_list' ) );
        // the installers without that list make one siteaccess per language (createTranslationSiteAccesses())
        else if ( $this->hasSetting( 'locales' ) )
            foreach ( (array)$this->setting( 'locales' ) as $locale )
                $list[] = $this->languageNameFromLocale( $locale );
        return array_values( array_unique( array_filter( array_map( 'strval', $list ), 'strlen' ) ) );
    }

    /**
     * Whether the installation has the sevenx_authentication_2fa extension (two-factor and social login). Its login
     * handler and role policies are written only then: a login handler that is not there breaks every login.
     */
    function twoFactorAuthenticationAvailable()
    {
        return is_dir( 'extension/sevenx_authentication_2fa' );
    }

    /**
     * sevenx_authentication_2fa: adds the field "Two-step sign-in" (two_factor, datatype sevenxauthentication2fa)
     * to the user class, with an empty attribute for each existing user object. The datatype is registered for
     * this process first; without it, or when the class already has the field, nothing is changed.
     */
    function addTwoFactorUserField()
    {
        $contentINI = eZINI::instance( 'content.ini' );
        $repositories = $contentINI->variable( 'DataTypeSettings', 'ExtensionDirectories' );
        if ( !in_array( 'sevenx_authentication_2fa', $repositories, true ) )
            $repositories[] = 'sevenx_authentication_2fa';
        $available = $contentINI->variable( 'DataTypeSettings', 'AvailableDataTypes' );
        if ( !in_array( 'sevenxauthentication2fa', $available, true ) )
            $available[] = 'sevenxauthentication2fa';
        $contentINI->setVariables( array( 'DataTypeSettings' => array( 'ExtensionDirectories' => $repositories,
                                                                       'AvailableDataTypes' => $available ) ) );
        if ( !eZDataType::create( 'sevenxauthentication2fa' ) )
        {
            eZDebug::writeError( 'The sevenxauthentication2fa datatype could not be loaded; the user class gets no two-step sign-in field', __METHOD__ );
            return false;
        }
        $class = eZContentClass::fetchByIdentifier( 'user' );
        if ( !$class )
            return false;
        foreach ( $class->fetchAttributes() as $attribute )
        {
            if ( $attribute->attribute( 'data_type_string' ) === 'sevenxauthentication2fa' )
                return true;
        }
        $language = method_exists( $this, 'primaryLanguageLocale' ) ? $this->primaryLanguageLocale() : $this->setting( 'primary_language' );
        $db = eZDB::instance();
        $db->begin();
        $this->addClassAttributes( array(
            'class' => array( 'identifier' => 'user' ),
            'attributes' => array(
                array( 'identifier' => 'two_factor', 'name' => 'Two-step sign-in', 'data_type_string' => 'sevenxauthentication2fa',
                       'can_translate' => 0, 'is_required' => 0, 'is_searchable' => 0, 'language' => $language ? $language : false )
            )
        ) );
        $db->commit();
        return true;
    }

    /**
     * settings/override/sevenxauthentication2fa.ini.append.php: two-step sign-in is the users' choice
     * (Enforce2FA=disabled; DefaultMethod applies once it is enforced), and the authenticator secrets are stored
     * encrypted with a key made for this installation alone.
     */
    function twoFactorINISettings()
    {
        return array(
            'name' => 'sevenxauthentication2fa.ini',
            'settings' => array(
                'General' => array( 'Enforce2FA' => 'disabled', 'DefaultMethod' => 'totp' ),
                'TOTPSettings' => array( 'SecretKey' => base64_encode( random_bytes( 48 ) ) )
            )
        );
    }

    function createTranslationSiteAccesses()
    {
        $primaryLanguage = $this->setting( 'primary_language' );
        $userSiteaccess = $this->setting( 'user_siteaccess' );
        $siteTitle = $this->setting( 'site_title' );
        $homeNodeURL = $this->homeNodeURL();
        $homeNodeDepth = $this->homeNodeDepth();

        foreach ( $this->setting( 'locales' ) as $locale )
        {
            // The primary language is served by the main user siteaccess.
            if ( $locale == $primaryLanguage )
                continue;

            // Prepare 'SiteLanguageList' with the current locale first, then all
            // other available locales as fallbacks.
            $languageList = $this->siteLanguageList( $locale );

            // The unique name initSettings() gave this locale
            $languageSiteaccessMap = (array)$this->setting( 'language_siteaccess_map' );
            $languageName = isset( $languageSiteaccessMap[$locale] ) ? $languageSiteaccessMap[$locale] : $this->languageNameFromLocale( $locale );

            $siteaccessTypes = $this->setting( 'siteaccess_urls' );

            // A translation serves the same pages as the user siteaccess, so it
            // takes the same extension siteaccess settings - the theme's
            // template overrides, menus and PathPrefix for 'site' - below its
            // own. Without them the German one had no menu settings and died
            // on its front page.
            $siteAccessSettings = array( 'ExtensionSettingsSiteAccess' => $userSiteaccess );

            // PathPrefix: Bold Agency's is its home node path. The Fit & Healthy
            // one is written by postInstallSetSiteHomeAndPrefix(), as for the
            // user siteaccess; the empty prefix written here masked it, and
            // every page below the site root answered 404.
            $homeNodePath = $this->homeNodePath();
            if ( $userSiteaccess === 'bold' && $homeNodePath !== '' )
                $siteAccessSettings['PathPrefix'] = $homeNodePath;

            // Create siteaccess
            $this->createSiteAccess( array(
                'src' => array(
                    'siteaccess' => $userSiteaccess
                ),
                'dst' => array(
                    'siteaccess' => $languageName,
                    'settings' => array(
                        'site.ini' => array(
                            'RegionalSettings' => array(
                                'Locale' => $locale,
                                'ContentObjectLocale' => $locale,
                                'TextTranslation' => $locale != 'eng-GB' ? 'enabled' : 'disabled',
                                'SiteLanguageList' => $languageList
                            ),
                            'SiteSettings' => array(
                                'SiteName' => $siteTitle,
                                'SiteURL' => $siteaccessTypes['translation'][$languageName]['url'],
                                'IndexPage' => $homeNodeURL,
                                'DefaultPage' => $homeNodeURL,
                                'RootNodeDepth' => $homeNodeDepth,
                                'MetaDataArray' => array(
                                    'author' => '7x',
                                    'copyright' => '7x',
                                    'description' => 'An Exponential multisite installation',
                                    'keywords' => 'exponential, multisite, siteaccess'
                                )
                            ),
                            'SiteAccessSettings' => $siteAccessSettings
                        )
                    )
                )
            ) );
        }
    }

    ///////////////////////////////////////////////////////////////////////////
    // Setup roles
    ///////////////////////////////////////////////////////////////////////////
    function siteRoles( $params = false )
    {
        $guestAccountsID = $this->setting( 'guest_accounts_id' );
        $anonAccountsID = $this->setting( 'anonymous_accounts_id' );
        $roles = array();
        // Add possibility to read rss by default for anonymous/guests
        $roles[] = array( 
            'name' => 'Anonymous', 
            'policies' => array( 
                array( 
                    'module' => 'rss', 
                    'function' => 'feed' 
                ) 
            ), 
            'assignments' => array( 
                array( 
                    'user_id' => $guestAccountsID 
                ), 
                array( 
                    'user_id' => $anonAccountsID 
                ) 
            ) 
        );
        include_once ('lib/ezutils/classes/ezsys.php');
        // Make sure anonymous can only login to user side
        // Anonymous may log in on every siteaccess a visitor can reach, not just
        // the main one. Without bold and bold_ger here the Bold site refuses
        // anonymous access even once the siteaccesses are registered.
        $loginSiteAccessCRCs = array();
        foreach ( $this->servedSiteaccessList() as $servedSiteaccess )
        {
            // the administration interfaces: the admin, and adminui, which is the admin in another design
            if ( $servedSiteaccess === $this->setting( 'admin_siteaccess' ) || $servedSiteaccess === $this->setting( 'adminui_siteaccess' ) )
                continue;
            $loginSiteAccessCRCs[] = eZSys::ezcrc32( $servedSiteaccess );
        }
        $roles[] = array( 
            'name' => 'Anonymous', 
            'policies' => array( 
                array( 
                    'module' => 'user', 
                    'function' => 'login', 
                    'limitation' => array( 
                        'SiteAccess' => $loginSiteAccessCRCs
                    ) 
                ) 
            ) 
        );

        // Media section images are embedded in article bodies; public roles
        // need content/read for section 3 or the embed handler refuses to render.
        $mediaReadPolicy = array(
            'module' => 'content',
            'function' => 'read',
            'limitation' => array( 'Section' => array( 3 ) )
        );
        $roles[] = array(
            'name' => 'Anonymous',
            'policies' => array( $mediaReadPolicy )
        );
        $roles[] = array(
            'name' => 'Member',
            'policies' => array( $mediaReadPolicy )
        );

        // Article and recipe pages link every tag to its own tag page
        // (/tags/view/<Topics>/<Keyword>, see content/parts/tags.tpl), so the
        // public roles need the eztags module's view function or those links
        // return 401.
        $tagsViewPolicy = array(
            'module' => 'tags',
            'function' => 'view'
        );
        $roles[] = array(
            'name' => 'Anonymous',
            'policies' => array( $tagsViewPolicy )
        );
        $roles[] = array(
            'name' => 'Member',
            'policies' => array( $tagsViewPolicy )
        );

        // The lead form is opened from a button that fetches
        // info-collection/view-modal and posts back to info-collection/submit
        // (see the js-form-modal-trigger data-url in the Bold templates). Both
        // views declare the module's single 'read' function, so without this
        // policy the button answers 401 and no form ever appears.
        $infoCollectionPolicy = array(
            'module' => 'info-collection',
            'function' => 'read'
        );
        $roles[] = array(
            'name' => 'Anonymous',
            'policies' => array( $infoCollectionPolicy )
        );
        $roles[] = array(
            'name' => 'Member',
            'policies' => array( $infoCollectionPolicy )
        );

        // sevenx_authentication_2fa (its INSTALL.md, role policies): every role that signs in lets its users manage
        // their own second step (Administrator has */* already). The steps before signing in need no policy: the
        // extension lists them in [RoleSettings] PolicyOmitList.
        if ( $this->twoFactorAuthenticationAvailable() )
        {
            foreach ( array( 'Member', 'Editor', 'Partner' ) as $twoFactorRole )
            {
                $roles[] = array(
                    'name' => $twoFactorRole,
                    'policies' => array(
                        array( 'module' => 'user2fa', 'function' => 'setup' ),
                        array( 'module' => 'user2fa', 'function' => 'verify' )
                    )
                );
            }
        }
        return $roles;
    }

    ///////////////////////////////////////////////////////////////////////////
    // Setup preferences
    ///////////////////////////////////////////////////////////////////////////
    function sitePreferences()
    {
        $adminAccountID = $this->setting( 'admin_account_id' );
        $preferences = array();
        // Make sure admin starts with:
        // - The 'preview' window set as open by default
        // - The 'content structure' tool is open by default
        // - The 'bookmarks' tool is open by default
        // - The 'roles' and 'policies' windows are open by default
        // - The child list limit is 25 by default
        $preferences[] = array( 
            'user_id' => $adminAccountID, 
            'preferences' => array( 
                array( 
                    'name' => 'admin_navigation_content', 
                    'value' => '1' 
                ), 
                array( 
                    'name' => 'admin_navigation_roles', 
                    'value' => '1' 
                ), 
                array( 
                    'name' => 'admin_navigation_policies', 
                    'value' => '1' 
                ), 
                array( 
                    'name' => 'admin_list_limit', 
                    'value' => '2' 
                ), 
                array( 
                    'name' => 'admin_treemenu', 
                    'value' => '1' 
                ), 
                array( 
                    'name' => 'admin_bookmark_menu', 
                    'value' => '1' 
                ) 
            ) 
        );
        return $preferences;
    }

    ///////////////////////////////////////////////////////////////////////////
    // Post-install siteaccess INI updates
    ///////////////////////////////////////////////////////////////////////////
    function postInstallAdminSiteaccessINIUpdate( $params = false )
    {
        $adminSiteaccess = $this->setting( 'admin_siteaccess' );
        $siteINI = eZINI::instance( 'site.ini.append.php', 'settings/siteaccess/' . $adminSiteaccess, null, false, null, true );
        // admin4l first, then admin4 and the three older designs, in this order, and all are needed.
        //
        // admin4l is the default admin design: it builds the page from the explayouts admin
        // layouts (the default layouts and rules are imported by postInstallImportAdminLayouts)
        // and draws every page part with the admin4 templates, so admin4 comes first in the list
        // after it and supplies whatever admin4l does not override.
        //
        // admin4 is complete in itself (every template, stylesheet,
        // image and script the admin needs), with the light and dark modes of the 2026 look.
        // The names after admin4l stay for the extensions: a design name is looked up in
        // every extension too, and extensions ship their admin screens in admin4, admin3, admin2
        // and admin folders. An installation whose kernel has no design/admin4l yet still works:
        // the kernel skips a design folder that is not there, and admin4 takes over.
        //
        // admin3 is the previous skin: its own pagelayout and eighteen overrides. admin2
        // is where extensions put their administration interfaces. admin is the
        // complete base interface, all three hundred odd templates including the
        // contentstructuremenu ones the content tree is built from.
        //
        // This used to set SiteDesign to the siteaccess NAME, which is 'admin',
        // and push admin3 into the fallback list. A design named after the
        // siteaccess only resolves by coincidence, and with admin first the
        // admin3 pagelayout never won - the interface rendered against the old
        // base design instead.
        //
        // admin2 must stay in the list (admin4 and admin3 too, for the same reason). A design name is a namespace across
        // every design root, not one directory: the project's own design/admin2
        // is empty, but eztags ships 36 templates there, cjw_newsletter 15 and
        // enhancedezbinaryfile 1. Dropping the name orphaned all 52, and since
        // eZ renders an unresolvable template as an empty string rather than an
        // error, the affected pages - /tags/dashboard among them - returned a
        // bare shell with no indication of what was wrong.
        $siteINI->setVariable( 'DesignSettings', 'SiteDesign', 'admin4l' );
        $siteINI->setVariable( 'DesignSettings', 'AdditionalSiteDesignList', array( 'admin4', 'admin3', 'admin2', 'admin' ) );
        $siteINI->setVariable( 'SiteAccessSettings', 'RelatedSiteAccessList', $this->servedSiteaccessList() );
        // Clean urls here too: the administration interface is where the
        // treemenu is used, and it is the entry point that misreads its
        // parameters when index.php is left in the generated url.
        $siteINI->setVariable( 'SiteAccessSettings', 'ForceVirtualHost', 'true' );
        $siteINI->setVariable( 'FileSettings', 'VarDir', 'var/site' );
        $siteINI->setVariable( 'SiteSettings', 'SiteName', 'Admin' );
        $siteINI->save();
        unset( $siteINI );

        $contentINI = eZINI::instance( 'content.ini.append.php', 'settings/siteaccess/' . $adminSiteaccess, null, false, null, true );
        $contentINI->setVariable( 'NodeSettings', 'RootNode', $this->rootNodeID() );
        $contentINI->save( false, false, false, false, true, true );
    }

    function postInstallUserSiteaccessINIUpdate( $params = false )
    {
        $userSiteaccess = $this->setting( 'user_siteaccess' );
        $siteaccessUrl = $this->setting( 'siteaccess_urls' );
        $siteINI = eZINI::instance( "site.ini.append.php", "settings/siteaccess/" . $userSiteaccess, null, false, null, true );
        $siteINI->setVariable( "SiteSettings", "SiteDescription", "An Exponential multisite installation" );
        $siteINI->setVariable( "FileSettings", "VarDir", "var/site" );
        $siteINI->setVariable( "DesignSettings", "SiteDesign", $this->setting( 'main_site_design' ) );
        $siteINI->setVariable( "SiteAccessSettings", "RelatedSiteAccessList", $this->servedSiteaccessList() );
        $siteINI->setVariable( 'DesignSettings', 'AdditionalSiteDesignList', array(
            'ezwebin', 'standard', 'base'
        ) );
        $siteINI->setVariable( 'SiteSettings', 'SiteName', $this->setting( 'site_title' ) );
        $siteINI->setVariable( 'SiteSettings', 'IndexPage', $this->homeNodeURL() );
        $siteINI->setVariable( 'SiteSettings', 'DefaultPage', $this->homeNodeURL() );
        $siteINI->setVariable( 'SiteSettings', 'RootNodeDepth', $this->homeNodeDepth() );
        $siteINI->setVariable( 'SiteSettings', 'MetaDataArray', array(
            'author' => '7x',
            'copyright' => '7x',
            'description' => 'An Exponential multisite installation',
            'keywords' => 'exponential, multisite, siteaccess'
        ) );
        if ( isset( $siteaccessUrl['user'][$userSiteaccess]['url'] ) )
            $siteINI->setVariable( 'SiteSettings', 'SiteURL', $siteaccessUrl['user'][$userSiteaccess]['url'] );

        $homeNodePath = $this->homeNodePath();
        if ( $homeNodePath !== '' )
            $siteINI->setVariable( 'SiteAccessSettings', 'PathPrefix', $homeNodePath );

        // The shipped settings/site.ini sets RequireUserLogin=true, and the
        // setup wizard only overrides it for the admin siteaccess. Without this
        // the public site answers every request with the login form.
        // siteSiteINISettings() already does this, but that runs only on the
        // command-line install() path, not through the wizard.
        $siteINI->setVariable( 'SiteAccessSettings', 'RequireUserLogin', 'false' );
        $siteINI->setVariable( 'SiteAccessSettings', 'ShowHiddenNodes', 'false' );

        $siteINI->save( false, false, false, false, true, true );
        unset( $siteINI );

        $contentINI = eZINI::instance( 'content.ini.append.php', 'settings/siteaccess/' . $userSiteaccess, null, false, null, true );
        $contentINI->setVariable( 'NodeSettings', 'RootNode', $this->homeNodeID() );
        $contentINI->save( false, false, false, false, true, true );
    }

    ///////////////////////////////////////////////////////////////////////////
    // Admin siteaccess INI settings
    ///////////////////////////////////////////////////////////////////////////
    function adminINISettings()
    {
        $settings = array();
        $settings[] = $this->adminToolbarINISettings();
        $settings[] = $this->adminContentStructureMenuINISettings();
        $settings[] = $this->adminOverrideINISettings();
        $settings[] = $this->adminSiteINISettings();
        $settings[] = $this->adminContentINISettings();
        $settings[] = $this->adminIconINISettings();
        $settings[] = $this->adminViewCacheINISettings();
        $settings[] = $this->adminODFINISettings();
        $settings[] = $this->adminOEINISettings();
        $settings[] = $this->adminMenuINISettings();
        return $settings;
    }

    /*!
     The order of the admin header tabs (menu.ini [TopAdminMenu] Tabs[]). The kernel lists its own tabs and every
     extension appends its tab after them, so the whole list is written for the admin siteaccess to place the
     extension tabs between the kernel's: Store, then Layouts, Setup, Tags, Design, Audit, Git, Export, CIE and Newsletter.
     A tab of an extension that is not active has no [Topmenu_<tab>] block and is left out of the header.
    */
    function adminMenuINISettings()
    {
        return array(
            'name' => 'menu.ini',
            'reset_arrays' => true,
            'settings' => array(
                'TopAdminMenu' => array(
                    'Tabs' => array(
                        'dashboard',
                        'content',
                        'media',
                        'users',
                        'shop',
                        'explayouts_ui_dashboard',
                        'setup',
                        'eztags',
                        'design',
                        'audit',
                        'gitmanager',
                        'xrowextract',
                        'bccie_overview',
                        'newsletter'
                    )
                )
            )
        );
    }

    function adminContentStructureMenuINISettings()
    {
        $contentStructureMenu = array(
            'name' => 'contentstructuremenu.ini',
            'reset_arrays' => true,
            'settings' => array(
                'TreeMenu' => array(
                    'ShowClasses' => array(
                        'article',
                        'documentation_page',
                        'event_calendar',
                        'file',
                        'folder',
                        'forums',
                        'frontpage',
                        'gallery',
                        'image',
                        'link',
                        'ng_accordion_item',
                        'ng_article',
                        'ng_audio',
                        'ng_banner',
                        'ng_blog_post',
                        'ng_category',
                        'ng_component_about',
                        'ng_component_features',
                        'ng_component_hero',
                        'ng_component_lead',
                        'ng_component_logos',
                        'ng_component_quote',
                        'ng_contact_form',
                        'ng_container',
                        'ng_frontpage',
                        'ng_gallery',
                        'ng_htmlbox',
                        'ng_job_position',
                        'ng_landing_page',
                        'ng_news',
                        'ng_person',
                        'ng_recipe',
                        'ng_topic',
                        'ng_video',
                        'product',
                        'user_group'
                    )
                )
            )
        );
        return $contentStructureMenu;
    }

    function adminToolbarINISettings()
    {
        $toolbar = array( 
            'name' => 'toolbar.ini', 
            'reset_arrays' => true, 
            'settings' => array( 
                'Toolbar' => array( 
                    'AvailableToolBarArray' => array( 
                        0 => 'setup', 
                        1 => 'admin_right', 
                        2 => 'admin_developer' 
                    ) 
                ), 
                'Tool' => array( 
                    'AvailableToolArray' => array( 
                        0 => 'setup_link', 
                        1 => 'admin_current_user', 
                        2 => 'admin_bookmarks', 
                        3 => 'admin_clear_cache', 
                        4 => 'admin_quick_settings' 
                    ) 
                ), 
                'Toolbar_setup' => array( 
                    'Tool' => array( 
                        0 => 'setup_link', 
                        1 => 'setup_link', 
                        2 => 'setup_link', 
                        3 => 'setup_link', 
                        4 => 'setup_link' 
                    ) 
                ), 
                // Clear cache and Bookmarks first in the right sidebar; the
                // editor siteaccess, a copy of these, keeps Bookmarks first
                // (kernel setup step createEditorSiteAccess)
                'Toolbar_admin_right' => array(
                    'Tool' => array(
                        0 => 'admin_clear_cache',
                        1 => 'admin_bookmarks',
                        2 => 'admin_current_user',
                        3 => 'admin_preferences'
                    )
                ),
                'Toolbar_admin_developer' => array(
                    'Tool' => array(
                        0 => 'admin_quick_settings'
                    )
                ),
                'Tool_setup_link' => array( 
                    'title' => '', 
                    'link_icon' => '', 
                    'url' => '' 
                ), 
                'Tool_setup_link_description' => array( 
                    'title' => 'Title', 
                    'link_icon' => 'Icon', 
                    'url' => 'URL' 
                ), 
                'Tool_setup_setup_link_1' => array( 
                    'title' => 'Classes', 
                    'link_icon' => 'classes.png', 
                    'url' => '/class/grouplist' 
                ), 
                'Tool_setup_setup_link_2' => array( 
                    'title' => 'Cache', 
                    'link_icon' => 'cache.png', 
                    'url' => '/setup/cache' 
                ), 
                'Tool_setup_setup_link_3' => array( 
                    'title' => 'URL translator', 
                    'link_icon' => 'url_translator.png', 
                    'url' => '/content/urltranslator' 
                ), 
                'Tool_setup_setup_link_4' => array( 
                    'title' => 'Settings', 
                    'link_icon' => 'common_ini_settings.png', 
                    'url' => '/content/edit/52' 
                ), 
                'Tool_setup_setup_link_5' => array( 
                    'title' => 'Look and feel', 
                    'link_icon' => 'look_and_feel.png', 
                    'url' => '/content/edit/54' 
                ) 
            ) 
        );
        return $toolbar;
    }

    function adminOverrideINISettings()
    {
        return array( 
            'name' => 'override.ini', 
            'discard_old_values' => true, 
            'settings' => array( 
                'article' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/article.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'article' 
                    ) 
                ), 
                'comment' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/comment.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'comment' 
                    ) 
                ), 
                'company' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/company.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'company' 
                    ) 
                ), 
                'feedback_form' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/feedback_form.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'feedback_form' 
                    ) 
                ), 
                'file' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/file.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'file' 
                    ) 
                ), 
                'flash' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/flash.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'flash' 
                    ) 
                ), 
                'folder' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/folder.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'folder' 
                    ) 
                ), 
                'forum' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/forum.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum' 
                    ) 
                ), 
                'forum_topic' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/forum_topic.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum_topic' 
                    ) 
                ), 
                'forum_reply' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/forum_reply.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum_reply' 
                    ) 
                ), 
                'gallery' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/gallery.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'gallery' 
                    ) 
                ), 
                'image' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'link' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/link.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'link' 
                    ) 
                ), 
                'person' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/person.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'person' 
                    ) 
                ), 
                'poll' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/poll.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'poll' 
                    ) 
                ), 
                'product' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/product.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'product' 
                    ) 
                ), 
                'review' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/review.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'review' 
                    ) 
                ), 
                'quicktime' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/quicktime.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'quicktime' 
                    ) 
                ), 
                'real_video' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/real_video.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'real_video' 
                    ) 
                ), 
                'weblog' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/weblog.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'weblog' 
                    ) 
                ), 
                'windows_media' => array( 
                    'Source' => 'node/view/admin_preview.tpl', 
                    'MatchFile' => 'admin_preview/windows_media.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'windows_media' 
                    ) 
                ), 
                'thumbnail_image' => array( 
                    'Source' => 'node/view/thumbnail.tpl', 
                    'MatchFile' => 'thumbnail/image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'embed_image' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed_image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'embed-inline_image' => array( 
                    'Source' => 'content/view/embed-inline.tpl', 
                    'MatchFile' => 'embed-inline_image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'embed_node_image' => array( 
                    'Source' => 'node/view/embed.tpl', 
                    'MatchFile' => 'embed_image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'embed-inline_node_image' => array( 
                    'Source' => 'node/view/embed-inline.tpl', 
                    'MatchFile' => 'embed-inline_image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'thumbnail_image_browse' => array( 
                    'Source' => 'node/view/browse_thumbnail.tpl', 
                    'MatchFile' => 'thumbnail/image_browse.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'thumbnail_banner' => array( 
                    'Source' => 'node/view/thumbnail.tpl', 
                    'MatchFile' => 'thumbnail/image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'banner' 
                    ) 
                ), 
                'thumbnail_banner_browse' => array( 
                    'Source' => 'node/view/browse_thumbnail.tpl', 
                    'MatchFile' => 'thumbnail/image_browse.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'banner' 
                    ) 
                ) 
            ) 
        );
    }

    function adminSiteINISettings()
    {
        $settings = array();
        $settings['SiteAccessSettings'] = array( 
            'RequireUserLogin' => 'true' 
        );
        $settings['SiteSettings'] = array( 
            'LoginPage' => 'custom' 
        );
        // The admin's own address. The setup wizard writes every siteaccess's
        // SiteURL from the host of the request it runs in, and the kickstarter
        // runs on the command line, with no host: the admin was left with
        // SiteURL=localhost, which Setup > System information then showed as
        // the site. The public siteaccess is given its address in
        // siteINISettings(); this is the same for the admin.
        $siteaccessUrl = $this->setting( 'siteaccess_urls' );
        $adminSiteaccess = $this->setting( 'admin_siteaccess' );
        if ( isset( $siteaccessUrl['admin'][$adminSiteaccess]['url'] ) && $siteaccessUrl['admin'][$adminSiteaccess]['url'] !== '' )
            $settings['SiteSettings']['SiteURL'] = $siteaccessUrl['admin'][$adminSiteaccess]['url'];
        // Make sure viewcaching works in admin with the new admin interface
        $settings['ContentSettings'] = array( 
            'CachedViewPreferences' => array( 
                'full' => 'admin_navigation_content=1;admin_children_viewmode=list;admin_list_limit=1' 
            ) 
        );
        $settings['SiteAccessSettings'] = array_merge( $settings['SiteAccessSettings'], array( 
            'ShowHiddenNodes' => 'true' 
        ) );
        $primaryLanguage = $this->setting( 'primary_language' );
        $settings['RegionalSettings'] = array( 
            'Locale' => $primaryLanguage ? $primaryLanguage : 'eng-US',
            'ContentObjectLocale' => $primaryLanguage ? $primaryLanguage : 'eng-US',
            'SiteLanguageList' => $this->siteLanguageList(), 
            'TranslationSA' => $this->mainTranslationSAMap() 
        );
        return array( 
            'name' => 'site.ini', 
            'settings' => $settings 
        );
    }

    function adminContentINISettings()
    {
        $designList = $this->setting( 'design_list' );
        $image = array( 
            'name' => 'content.ini', 
            'reset_arrays' => true, 
            'settings' => array( 
                'VersionView' => array( 
                    'AvailableSiteDesignList' => $designList 
                ) 
            ) 
        );
        return $image;
    }

    function adminIconINISettings()
    {
        $image = array( 
            'name' => 'icon.ini', 
            'reset_arrays' => true, 
            'settings' => array( 
                'IconSettings' => array( 
                    'Theme' => 'crystal-admin', 
                    'Size' => 'normal' 
                ) 
            ) 
        );
        return $image;
    }

    function adminViewCacheINISettings()
    {
        return array( 
            'name' => 'viewcache.ini', 
            'settings' => array( 
                'ViewCacheSettings' => array( 
                    'SmartCacheClear' => 'enabled' 
                ) 
            ) 
        );
    }

    function adminODFINISettings()
    {
        // admin siteaccess uses the same ODF-settings
        return $this->siteODFINISettings();
    }

    function adminOEINISettings()
    {
        return array( 
            'name' => 'ezoe.ini', 
            'settings' => array( 
                'EditorSettings' => array( 
                    'SkinVariant' => 'silver' 
                ) 
            ) 
        );
    }

    ///////////////////////////////////////////////////////////////////////////
    // Common INI settings
    ///////////////////////////////////////////////////////////////////////////
    function commonINISettings()
    {
        $settings = array();
        $settings[] = $this->commonSiteINISettings();
        $settings[] = $this->commonContentINISettings();
        $settings[] = $this->commonMenuINISettings();
        $settings[] = $this->commonViewCacheINISettings();
        $settings[] = $this->commonStaticCacheINISettings();
        $settings[] = $this->commonHttpCacheINISettings();
        $settings[] = $this->commonOverrideINISettings();
        $settings[] = $this->commonForumINISettings();
        $settings[] = $this->commonOEAttributesINISettings();
        $settings[] = $this->commonXMLINISettings();
        // sevenx_authentication_2fa: opt-in, with a secret key of this installation (twoFactorINISettings())
        if ( $this->twoFactorAuthenticationAvailable() )
            $settings[] = $this->twoFactorINISettings();
        return $settings;
    }

    /**
     * Siteaccesses this solution serves besides the main one and its language
     * variants.
     *
     * Bold Agency is a second site inside the same installation. The installer
     * already tunes settings/siteaccess/bold and bold_ger (IndexPage,
     * DefaultPage, PathPrefix) but never registered them, so
     * AvailableSiteAccessList omitted both and /bold_ger/ answered 404 from the
     * main siteaccess. Only names whose siteaccess directory is actually present
     * are returned, so an install without them is unaffected.
     */
    function secondarySiteaccessList()
    {
        $list = array();
        foreach ( array( 'bold', 'bold_ger' ) as $siteaccess )
        {
            if ( $siteaccess === $this->setting( 'user_siteaccess' ) )
                continue;
            if ( in_array( $siteaccess, (array)$this->setting( 'all_siteaccess_list' ), true ) )
                continue;
            if ( $this->secondarySiteaccessIsAvailable( $siteaccess ) )
                $list[] = $siteaccess;
        }
        return $list;
    }

    /**
     * Whether this installation provides the given secondary siteaccess.
     *
     * settings/siteaccess/<name> cannot be the only signal: on a first install
     * it does not exist yet, and the override is generated during CreateSites,
     * before createSecondarySiteaccesses() writes it in post-install. A theme
     * extension always ships siteaccess settings for the sites it provides, so
     * that is the durable signal.
     */
    function secondarySiteaccessIsAvailable( $siteaccess )
    {
        if ( file_exists( 'settings/siteaccess/' . $siteaccess . '/site.ini.append.php' ) )
            return true;

        $shipped = glob( 'extension/*/settings/siteaccess/' . $siteaccess . '/site.ini.append.php' );
        return is_array( $shipped ) && count( $shipped ) > 0;
    }

    /**
     * Remote id of the home node for a secondary siteaccess.
     *
     * Node ids differ between installations, so the home node is addressed by
     * the remote id the content package assigns it.
     */
    function secondarySiteaccessHomeRemoteID( $siteaccess )
    {
        $map = array(
            'bold'     => 'media-n-940',
            'bold_ger' => 'media-n-940'
        );
        return isset( $map[$siteaccess] ) ? $map[$siteaccess] : false;
    }

    /**
     * The SiteURL of a secondary siteaccess (bold, bold_ger), without a scheme.
     *
     * A secondary siteaccess has no host or port of its own: it is reached by
     * path on the address of the user siteaccess (MatchOrder starts with uri),
     * so its SiteURL is that address followed by /<siteaccess>. It was the user
     * siteaccess's own SiteURL, so every canonical and og:url link of the Bold
     * site pointed at the main site, where the path is not found.
     *
     * - host access: the user siteaccess's host, example.com/bold
     * - port access: its host and port, example.com:8080/bold
     * - URI access:  the user siteaccess's address ends in its own path
     *   (example.com/site), which is taken off first: example.com/bold
     * A siteaccess that does have an address of its own in siteaccess_urls (a
     * host, a port or a path) keeps that one, and an address that already ends
     * in /<siteaccess> is not given it twice.
     */
    function secondarySiteaccessURL( $siteaccess )
    {
        $siteaccessUrl = (array)$this->setting( 'siteaccess_urls' );
        foreach ( $siteaccessUrl as $list )
        {
            if ( isset( $list[$siteaccess]['url'] ) && (string)$list[$siteaccess]['url'] !== '' )
                return (string)$list[$siteaccess]['url'];
        }

        $userSiteaccess = $this->setting( 'user_siteaccess' );
        $base = isset( $siteaccessUrl['user'][$userSiteaccess]['url'] )
            ? (string)$siteaccessUrl['user'][$userSiteaccess]['url']
            : (string)$this->setting( 'host' );
        $base = rtrim( preg_replace( '#^[a-zA-Z][a-zA-Z0-9+.-]*://#', '', trim( $base ) ), '/' );
        if ( $base === '' )
            return '';

        if ( in_array( $this->setting( 'access_type' ), array( 'url', 'uri' ), true ) && $userSiteaccess !== '' )
        {
            $own = '/' . $userSiteaccess;
            if ( substr( $base, -strlen( $own ) ) === $own )
                $base = substr( $base, 0, -strlen( $own ) );
        }

        $suffix = '/' . $siteaccess;
        if ( substr( $base, -strlen( $suffix ) ) === $suffix )
            return $base;
        return $base . $suffix;
    }

    /**
     * Write settings/siteaccess/<name>/site.ini.append.php for each secondary
     * siteaccess this installation provides.
     *
     * These deliberately carry NO [DatabaseSettings]. The database belongs to
     * the installation and is written once into
     * settings/override/site.ini.append.php, so every siteaccess inherits it.
     * Per-siteaccess database settings previously pinned Bold Agency to a
     * database the kickstarter never creates, which left the siteaccess
     * resolving and then failing to connect on a fresh machine.
     *
     * SiteName, RootNode and TranslationList come from the theme extension's
     * own siteaccess settings, so they are not repeated here.
     *
     * IndexPage, DefaultPage and PathPrefix are written here, from the home
     * node resolved by remote id. They cannot be left to the theme extension:
     * settings/siteaccess outranks extension/<ext>/settings/siteaccess, and
     * the extension can only ship node ids from whichever installation it was
     * exported on. Without them the siteaccess falls back to the shipped
     * default of node 2 and an empty PathPrefix, which serves the content root
     * instead of the site and turns every prefixed path into an undefined
     * module.
     */
    function createSecondarySiteaccesses()
    {
        $secondary = $this->secondarySiteaccessList();
        if ( empty( $secondary ) )
        {
            eZDebug::writeNotice( 'No secondary siteaccesses provided by this installation', __FUNCTION__ );
            return true;
        }

        $related = $this->servedSiteaccessList();
        $siteaccessUrl = $this->setting( 'siteaccess_urls' );
        $adminSiteaccess = $this->setting( 'admin_siteaccess' );
        $loginFormURL = isset( $siteaccessUrl['admin'][$adminSiteaccess]['url'] )
            ? 'http://' . $siteaccessUrl['admin'][$adminSiteaccess]['url'] . '/user/login'
            : '';

        foreach ( $secondary as $siteaccess )
        {
            // Its own address, the user siteaccess's followed by /<siteaccess>
            $siteURL = $this->secondarySiteaccessURL( $siteaccess );
            $dir = 'settings/siteaccess/' . $siteaccess;
            if ( !file_exists( $dir ) )
                eZDir::mkdir( $dir, false, true );

            $homeURL = '';
            $pathPrefix = '';
            $homeRemoteID = $this->secondarySiteaccessHomeRemoteID( $siteaccess );
            if ( $homeRemoteID )
            {
                $homeNode = eZContentObjectTreeNode::fetchByRemoteID( $homeRemoteID );
                if ( $homeNode )
                {
                    $homeNodeID = (int)$homeNode->attribute( 'node_id' );
                    $homeURL = $this->homeNodeURL( $homeNodeID );
                    // convertToAlias derives the prefix from the node name and
                    // is therefore stable here. url_alias cannot be read yet:
                    // postInstallRegenerateURLAliases runs much later.
                    $pathPrefix = eZURLAliasML::convertToAlias( $homeNode->attribute( 'name' ), 'node_' . $homeNodeID );
                }
                else
                {
                    eZDebug::writeWarning( "Could not resolve home node $homeRemoteID for siteaccess $siteaccess, leaving IndexPage and PathPrefix unset", __FUNCTION__ );
                }
            }

            $lines = array(
                '<?php /* #?ini charset="utf-8"?',
                '',
                '# Written by the multisite installer. No [DatabaseSettings] here on',
                '# purpose: the database is declared once in settings/override and',
                '# inherited, so this siteaccess follows the installation it lives in.',
                '',
                '[InformationCollectionSettings]',
                'EmailReceiver=',
                '',
                '[Session]',
                'SessionNamePerSiteAccess=disabled',
                '',
                '[UserSettings]',
                'RegistrationEmail=',
                '',
                '[SiteAccessSettings]',
                'RequireUserLogin=false',
                'ShowHiddenNodes=false',
                'RelatedSiteAccessList[]',
            );
            foreach ( $related as $r )
                $lines[] = 'RelatedSiteAccessList[]=' . $r;
            if ( $pathPrefix !== '' )
                $lines[] = 'PathPrefix=' . $pathPrefix;
            $lines[] = '';
            $lines[] = '[DesignSettings]';
            $lines[] = 'SiteDesign=' . $this->setting( 'main_site_design' );
            $lines[] = 'AdditionalSiteDesignList[]';
            foreach ( array( 'simple', 'ezwebin', 'standard', 'base' ) as $design )
                $lines[] = 'AdditionalSiteDesignList[]=' . $design;
            $lines[] = '';
            $lines[] = '[SiteSettings]';
            if ( $homeURL !== '' )
            {
                $lines[] = 'IndexPage=' . $homeURL;
                $lines[] = 'DefaultPage=' . $homeURL;
            }
            if ( $siteURL !== '' )
                $lines[] = 'SiteURL=' . $siteURL;
            $lines[] = 'LoginPage=embedded';
            if ( $loginFormURL !== '' )
                $lines[] = 'AdditionalLoginFormActionURL=' . $loginFormURL;
            $lines[] = '';
            $lines[] = '*/ ?>';

            $path = $dir . '/site.ini.append.php';
            eZFile::create( basename( $path ), dirname( $path ), implode( "\n", $lines ) . "\n" );
            eZDebug::writeNotice( "Wrote $path without DatabaseSettings", __FUNCTION__ );
        }

        return true;
    }

    /**
     * Every siteaccess a visitor may reach, main plus language plus secondary.
     */
    function servedSiteaccessList()
    {
        return array_values( array_unique( array_merge(
            (array)$this->setting( 'all_siteaccess_list' ),
            $this->secondarySiteaccessList()
        ) ) );
    }

    function commonSiteINISettings()
    {
        $settings = array();
        // MatchOrder must include uri: the secondary siteaccesses are reached by
        // path (/bold_ger/), and with the eZ default of host-only the path is
        // never consulted - the request matches the main host and the path is
        // then treated as content, giving a 404 rendered in the main site's
        // design. uri is tried first and falls through to host, which is what
        // the main site relies on.
        $settings['SiteAccessSettings'] = array( 
            'AvailableSiteAccessList' => $this->servedSiteaccessList(),
            'RelatedSiteAccessList' => $this->servedSiteaccessList(),
            // With port access the siteaccesses are told apart by port
            // ([PortAccessSettings]): without port here every port served the
            // main siteaccess.
            'MatchOrder' => $this->setting( 'access_type' ) === 'port' ? 'uri;port' : 'uri;host',
            'PathPrefixExclude' => array( 'Media', 'Users' ),
            // This belongs here and not only in the siteaccess files, which is
            // where the rest of this installer sets it. eZSys::init() decides
            // whether generated urls carry index.php, and it runs from
            // ezpKernelWeb and ezpKernelTreeMenu before any siteaccess has been
            // matched, so it reads settings/site.ini and settings/override and
            // nothing else. Set only per siteaccess it is read too late: eZSys
            // has already decided, and every url built by the ezurl operator
            // comes out as /index.php/... - forty one of the two hundred and
            // eleven links on an ordinary administration page.
            //
            // Those addresses still answer - eZSys::init() strips the index
            // directory back off an incoming request when the setting is off,
            // so nothing breaks. What they are is wrong: every address the
            // installation hands out disagrees with the one the editor sees in
            // the bar, the rewrite rules exist precisely so that they need not,
            // and two spellings of one address is two of everything downstream
            // that keys on an address.
            'ForceVirtualHost' => 'true'
        );
        $settings['SiteSettings'] = array( 
            'SiteList' => $this->servedSiteaccessList(), 
            // 'DefaultAccess' => $this->languageNameFromLocale( $this->setting( 'primary_language' ) ),
            'DefaultAccess' => $this->setting( 'user_siteaccess' ), 
            'RootNodeDepth' => 1 
        );
        $settings['ExtensionSettings'] = array( 
            'ActiveExtensions' => $this->setting( 'extension_list' ) 
        );
        $db = eZDB::instance();

        // Every other field here comes from the driver the site is actually
        // running on; the implementation was hardcoded, so a MongoDB install
        // finished by writing ezmysqli next to MongoDB's credentials and port
        // 27017. The site then hung trying to speak MySQL to mongod.
        // The global site.ini says what the stock settings say (ezmysqli), not
        // what was chosen: an SQLite install wrote ezmysqli here, and every
        // siteaccess that inherits the database from settings/override (bold,
        // the translations) failed with "Access denied for user ''@'localhost'".
        // The setup passes the driver it wrote into the siteaccesses
        // (database_settings), so that comes first.
        $chosen = $this->setting( 'database_settings' );
        $implementation = is_array( $chosen ) && !empty( $chosen['DatabaseImplementation'] )
            ? trim( (string)$chosen['DatabaseImplementation'] ) : '';
        $siteINI = eZINI::instance( 'site.ini' );
        if ( $implementation === '' )
            $implementation = $siteINI->hasVariable( 'DatabaseSettings', 'DatabaseImplementation' )
                ? $siteINI->variable( 'DatabaseSettings', 'DatabaseImplementation' )
                : 'ezmysqli';

        $settings['DatabaseSettings'] = array(
            'DatabaseImplementation' => $implementation,
            'Server' => $db ? $db->Server : 'localhost',
            'Port' => $db ? $db->Port : '',
            'User' => $db ? $db->User : '',
            'Password' => $db ? $db->Password : '',
            'Database' => $db ? $db->DB : '',
            // Empty meant the database server's default: latin1 on a stock MySQL,
            // which transliterates what it cannot store, silently
            'Charset' => ( $db && $db->Charset ) ? $db->Charset : 'utf-8',
            'Socket' => ( $db && $db->SocketPath ) ? $db->SocketPath : 'disabled',
            'SQLOutput' => 'disabled'
        );
        // This file is written with discard_old_values: what is not set here is
        // gone after every installation. So the site's working settings are
        // written here, not added by hand afterwards.
        $settings['SearchSettings'] = array(
            'DelayedIndexing' => 'disabled'
        );
        $settings['DebugSettings'] = array(
            'DebugOutput' => 'disabled',
            'DebugRedirection' => 'disabled'
        );
        $settings['TemplateSettings'] = array(
            'Debug' => 'disabled',
            'ShowXHTMLCode' => 'enabled',
            'ShowUsedTemplates' => 'disabled'
        );
        // Let an anonymous visitor's browser (and a proxy) keep a page for five
        // minutes. The kernel sent "no-cache" and an Expires date in 1997 with
        // every page, so every navigation paid the full render - about 800 ms
        // against 77 ms from a stored copy on the demo front page. Only for
        // anonymous visitors (OnlyForAnonymous): a signed-in editor always gets
        // the no-cache headers, so a personalised page never reaches a cache.
        // The cost: a visitor may see a page up to five minutes old after an
        // edit. Lower max-age for a site that publishes constantly;
        // CustomHeader=disabled goes back to the kernel's headers.
        $settings['HTTPHeaderSettings'] = array(
            'CustomHeader' => 'enabled',
            'OnlyForAnonymous' => 'enabled',
            'OnlyForContent' => 'enabled',
            'HeaderList' => array( 'Cache-Control', 'Expires' ),
            'Cache-Control' => array( '/' => 'public, max-age=300' ),
            'Expires' => array( '/' => '300' )
        );
        // A session of its own for the admin siteaccess. With PHP's default
        // PHPSESSID every siteaccess shares one session, so signing in to the
        // administration signs you in on the public site too - and a signed-in
        // page is uncacheable and several times slower. "custom" names the
        // session from SessionNamePrefix plus the siteaccess wherever
        // SessionNamePerSiteAccess is enabled: the public siteaccesses disable
        // it (one session across languages), the admin siteaccess keeps it.
        $settings['Session'] = array(
            'SessionNameHandler' => 'custom'
        );
        $settings['UserSettings'] = array( 
            'LogoutRedirect' => '/' 
        );
        // sevenx_authentication_2fa: its login handler before the standard one (its INSTALL.md, "Extension
        // activation"). Without a 2FA method set up for a user (Enforce2FA=disabled) the login is as before.
        if ( $this->twoFactorAuthenticationAvailable() )
            $settings['UserSettings']['LoginHandler'] = array( 'sevenxUser2fa', 'standard' );
        // The static cache is generated from Setup > Cache > Static content
        // cache and served by the web server from var/<var dir>/static, ahead
        // of the front controller. It is installed switched off: with it on,
        // every publish refreshes the cached pages of every address the
        // object appears at, on every public siteaccess, over HTTP and inside
        // the editor's request (a no-change publish of one product took six
        // seconds). Everything it needs is written all the same, here and in
        // staticcache.ini (commonStaticCacheINISettings()), so switching it on
        // is this one setting. Do not switch it on without generating the
        // cache: a generated page is only refreshed on publish while it is on.
        $settings['ContentSettings'] = array(
            'StaticCache' => 'disabled',
            'StaticCacheHandler' => 'eZStaticCache'
        );
        $settings['EmbedViewModeSettings'] = array( 
            'AvailableViewModes' => array( 
                'embed', 
                'embed-inline' 
            ), 
            'InlineViewModes' => array( 
                'embed-inline' 
            ) 
        );
        $accessType = $this->setting( 'access_type' );
        $siteaccessTypes = $this->setting( 'siteaccess_urls' );
        // set 'language settings'
        $translationSA = array();

        // Primary user siteaccess label.
        $userSiteaccess = $this->setting( 'user_siteaccess' );
        $primaryLanguage = $this->setting( 'primary_language' );
        $translationSA[$userSiteaccess] = $this->translationSALabel( $userSiteaccess, $primaryLanguage );

        foreach ( $siteaccessTypes['translation'] as $name => $urlInfo )
        {
            if ( !isset( $translationSA[$name] ) )
                $translationSA[$name] = $this->translationSALabel( $name );
        }
        // No [RegionalSettings] is written to settings/override on purpose.
        // The override outranks every siteaccess, so anything language-related
        // set here is forced on all of them: Locale pinned the German Bold
        // siteaccess to eng-US, which left its content German (that follows
        // ContentSettings/TranslationList) but every template string English -
        // 'Search' for 'Suche', 'Accept all' for 'Alles akzeptieren'. And
        // TranslationSA, being a whole-array setting, wiped the switcher
        // entries the theme extension ships for its own siteaccesses.
        //
        // Each siteaccess declares its own language instead. The switcher map
        // is written only for the admin siteaccess: see mainTranslationSAMap()
        // and adminSiteINISettings().
        $portMatch = array();
        $hostMatch = array();
        // get info about translation siteacceses.
        foreach ($siteaccessTypes as $siteaccessList)
        {
            foreach ($siteaccessList as $siteaccessName => $urlInfo)
            {
                switch ($accessType)
                {
                    case 'port':
                        {
                            $port = $urlInfo['port'];
                            $portMatch[$port] = $siteaccessName;
                        }
                        break;
                    case 'hostname':
                        {
                            $host = $urlInfo['host'];
                            $hostMatch[] = $host . ';' . $siteaccessName;
                        }
                }
            }
        }
        switch ($accessType)
        {
            case 'port':
                {
                    $settings['PortAccessSettings'] = $portMatch;
                }
                break;
            case 'hostname':
                {
                    $settings['SiteAccessSettings']['HostMatchMapItems'] = $hostMatch;
                }
                break;
        }
        return array(
            'name' => 'site.ini',
            'discard_old_values' => true,
            'settings' => $settings
        );
    }

    /**
     * settings/override/override.ini.append.php for a new installation.
     *
     * How each content class is printed by the pdf export. The kernel template
     * the export renders a node with, node/view/pdf.tpl, prints every attribute
     * of a class in storage order and labels none of them, so a class carrying
     * teaser copies of its fields comes out with its headline, its photograph
     * and its introduction twice over, and any numeric field appears as a bare
     * number with nothing to say what it is.
     *
     * The templates themselves live in the explayouts extension, under
     * design/standard/override/templates, and that extension ships these same
     * rules. They are written here as well so they are visible to an
     * administrator reading settings/override, and so the printed output does
     * not quietly change if the extension's design registration is ever turned
     * off.
     */
    function commonOverrideINISettings()
    {
        $settings = array();

        foreach ( $this->pdfViewOverrides() as $name => $classIdentifier )
        {
            $settings[$name] = array(
                'Source'    => 'node/view/pdf.tpl',
                'MatchFile' => $name . '.tpl',
                'Subdir'    => 'templates',
                'Match'     => array( 'class_identifier' => $classIdentifier ) );
        }

        return array(
            'name' => 'override.ini',
            'settings' => $settings
        );
    }

    /**
     * The pdf view overrides this solution provides, as override block name to
     * the class the block matches.
     *
     * Only classes this installation actually has are listed: an override
     * naming a class that was never created is harmless, but it is also noise
     * in a settings file somebody has to read.
     */
    function pdfViewOverrides()
    {
        $candidates = array( 'pdf_category' => 'ng_category',
                             'pdf_recipe'   => 'ng_recipe' );

        $overrides = array();
        foreach ( $candidates as $name => $classIdentifier )
        {
            if ( eZContentClass::fetchByIdentifier( $classIdentifier, false ) )
                $overrides[$name] = $classIdentifier;
        }

        return $overrides;
    }

    /**
     * settings/override/staticcache.ini.append.php for a new installation.
     *
     * The shipped defaults could not generate anything: CachedSiteAccesses was
     * empty, which made the generator iterate nothing and report success, and
     * HostName was "localhost", which sent every fetch to a host that is not
     * this site. Both are written here explicitly so an installation does not
     * depend on the kernel's fallbacks, and so an administrator opening the
     * file can see what the site is actually doing.
     *
     * Only the public siteaccesses are listed. An administration siteaccess
     * requires a login and its pages are per user, so a shared static copy of
     * them would be both useless and a disclosure.
     */
    function commonStaticCacheINISettings()
    {
        $settings = array();
        $settings['CacheSettings'] = array(
            // Empty: each siteaccess is fetched from its own
            // site.ini [SiteSettings] SiteURL. The setting is deprecated and
            // anything non empty overrides every site's own host.
            'HostName' => '',
            'SourceProtocol' => 'http',
            // Relative to the var directory, so this resolves to
            // var/<var dir>/static.
            'StaticStorageDir' => 'static',
            // Deep enough for a real content tree. The eZ default of 3 allowed
            // two path segments and silently dropped everything below.
            'MaxCacheDepth' => '12',
            'CachedURLArray' => array( '/', '/*' ),
            'AlwaysUpdateArray' => array( '/' ),
            'CachedSiteAccesses' => $this->publicSiteaccessList(),
            'CronjobCacheClear' => 'disabled',
            'AppendGeneratedTime' => 'true'
        );

        return array(
            'name' => 'staticcache.ini',
            'reset_arrays' => true,
            'settings' => $settings
        );
    }

    /**
     * settings/override/httpcache.ini.append.php for a new installation.
     *
     * The kernel's default caches the siteaccess called site only. The HTTP
     * cache serves siteaccesses matched by host, by URI and by host and URI,
     * so every public siteaccess of this installation is listed: the main
     * site, its translation siteaccesses and the secondary sites reached by
     * path (/bold/, /bold_ger/). The administration siteaccess is never
     * listed: its pages are per user and need a login.
     *
     * Only which siteaccesses are cached is set here. Whether the cache is on
     * stays the kernel's default (httpcache.ini [HttpCacheSettings] Enabled),
     * to be switched on per installation.
     */
    function commonHttpCacheINISettings()
    {
        return array(
            'name' => 'httpcache.ini',
            'reset_arrays' => true,
            'settings' => array(
                'HttpCacheSettings' => array(
                    'CachedSiteAccesses' => $this->publicSiteaccessList(),
                ),
            ),
        );
    }

    /**
     * The siteaccesses a visitor is served, which is every one this
     * installation provides except the administration interface.
     */
    function publicSiteaccessList()
    {
        $admin = $this->setting( 'admin_siteaccess' );
        $adminUI = (string)$this->setting( 'adminui_siteaccess' );
        $public = array();
        foreach ( $this->servedSiteaccessList() as $siteaccess )
        {
            if ( $siteaccess === $admin || $siteaccess === $adminUI || $siteaccess === '' )
                continue;
            $public[] = $siteaccess;
        }

        return $public;
    }

    function commonMenuINISettings()
    {
        //setup vars
        $settings = array();
        //comment out the line below in order to unlock all menus in ministration interface
        //$settings['TopAdminMenu'] = array( 'Tabs' => array( 'content', 'media', 'shop', 'my_account') );
        return array( 
            'name' => 'menu.ini', 
            'reset_arrays' => true, 
            'settings' => $settings 
        );
    }

    function commonContentINISettings()
    {
        $settings = array( 
            'object' => array( 
                'AvailableClasses' => array( 
                    '0' => 'itemized_sub_items', 
                    '1' => 'itemized_subtree_items', 
                    '2' => 'highlighted_object', 
                    '3' => 'vertically_listed_sub_items', 
                    '4' => 'horizontally_listed_sub_items' 
                ), 
                'ClassDescription' => array( 
                    'itemized_sub_items' => 'Itemized Sub Items', 
                    'itemized_subtree_items' => 'Itemized Subtree Items', 
                    'highlighted_object' => 'Highlighted Object', 
                    'vertically_listed_sub_items' => 'Vertically Listed Sub Items', 
                    'horizontally_listed_sub_items' => 'Horizontally Listed Sub Items' 
                ), 
                'CustomAttributes' => array( 
                    '0' => 'offset', 
                    '1' => 'limit' 
                ), 
                'CustomAttributesDefaults' => array( 
                    'offset' => '0', 
                    'limit' => '5' 
                ) 
            ), 
            'embed' => array( 
                'AvailableClasses' => array( 
                    '0' => 'itemized_sub_items', 
                    '1' => 'itemized_subtree_items', 
                    '2' => 'highlighted_object', 
                    '3' => 'vertically_listed_sub_items', 
                    '4' => 'horizontally_listed_sub_items' 
                ), 
                'ClassDescription' => array( 
                    'itemized_sub_items' => 'Itemized Sub Items', 
                    'itemized_subtree_items' => 'Itemized Subtree Items', 
                    'highlighted_object' => 'Highlighted Object', 
                    'vertically_listed_sub_items' => 'Vertically Listed Sub Items', 
                    'horizontally_listed_sub_items' => 'Horizontally Listed Sub Items' 
                ), 
                'CustomAttributes' => array( 
                    '0' => 'offset', 
                    '1' => 'limit' 
                ), 
                'CustomAttributesDefaults' => array( 
                    'offset' => '0', 
                    'limit' => '5' 
                ) 
            ), 
            'table' => array( 
                'AvailableClasses' => array( 
                    '0' => 'list', 
                    '1' => 'cols', 
                    '2' => 'comparison', 
                    '3' => 'default' 
                ), 
                'ClassDescription' => array( 
                    'list' => 'List', 
                    'cols' => 'Timetable', 
                    'comparison' => 'Comparison Table', 
                    'default' => 'Default' 
                ), 
                'CustomAttributes' => array( 
                    '0' => 'summary', 
                    '1' => 'caption' 
                ), 
                'Defaults' => array( 
                    'rows' => '2', 
                    'cols' => '2', 
                    'width' => '100%', 
                    'border' => '0', 
                    'class' => 'default' 
                ) 
            ), 
            'td' => array( 
                'CustomAttributes' => array( 
                    '0' => 'valign' 
                ) 
            ), 
            'th' => array( 
                'CustomAttributes' => array( 
                    '0' => 'scope', 
                    '1' => 'abbr', 
                    '2' => 'valign' 
                ) 
            ), 
            'factbox' => array( 
                'CustomAttributes' => array( 
                    '0' => 'align', 
                    '1' => 'title' 
                ), 
                'CustomAttributesDefaults' => array( 
                    'align' => 'right', 
                    'title' => 'factbox' 
                ) 
            ), 
            'quote' => array( 
                'CustomAttributes' => array( 
                    '0' => 'align', 
                    '1' => 'author' 
                ), 
                'CustomAttributesDefaults' => array( 
                    'align' => 'right', 
                    'autor' => 'Quote author' 
                ) 
            ), 
            'CustomTagSettings' => array( 
                'AvailableCustomTags' => array( 
                    '0' => 'underline' 
                ), 
                'IsInline' => array( 
                    'underline' => 'true' 
                ) 
            ), 
            'embed-type_images' => array( 
                'AvailableClasses' => array() 
            ) 
        );
        return array( 
            'name' => 'content.ini', 
            'settings' => $settings 
        );
    }

    function commonViewCacheINISettings()
    {
        $settings = array( 
            'ViewCacheSettings' => array( 
                'SmartCacheClear' => 'enabled', 
                'ClearRelationTypes' => array( 
                    'common', 
                    'reverse_common', 
                    'reverse_embedded', 
                    'reverse_attribute' 
                ) 
            ), 
            'forum_reply' => array( 
                'DependentClassIdentifier' => array( 
                    'forum_topic', 
                    'forum' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating', 
                    '3' => 'siblings' 
                ) 
            ), 
            'forum_topic' => array( 
                'DependentClassIdentifier' => array( 
                    'forum' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating', 
                    '3' => 'siblings' 
                ) 
            ), 
            'folder' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'folder' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating' 
                ) 
            ), 
            'gallery' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'folder' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating',
                    '3' => 'children'
                ) 
            ), 
            'image' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'gallery' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating', 
                    '3' => 'siblings' 
                ) 
            ), 
            'event' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'event_calendar' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating' 
                ) 
            ), 
            'article' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'folder', 
                    '1' => 'frontpage' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating' 
                ) 
            ), 
            'article_mainpage' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'folder', 
                    '1' => 'frontpage' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating' 
                ) 
            ), 
            'article_subpage' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'article_mainpage' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating', 
                    '3' => 'siblings' 
                ) 
            ), 
            'blog_post' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'frontpage', 
                    '1' => 'blog' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating' 
                ) 
            ), 
            'product' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'folder', 
                    '1' => 'frontpage' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating' 
                ) 
            ), 
            'infobox' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'folder' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating' 
                ) 
            ), 
            'documentation_page' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'documentation_page' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating' 
                ) 
            ), 
            'banner' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'frontpage' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating' 
                ) 
            ), 
            'geo_article' => array( 
                'DependentClassIdentifier' => array( 
                    '0' => 'frontpage' 
                ), 
                'ClearCacheMethod' => array( 
                    '0' => 'object', 
                    '1' => 'parent', 
                    '2' => 'relating' 
                ) 
            )
        );
        return array( 
            'name' => 'viewcache.ini', 
            'settings' => $settings 
        );
    }

    function commonForumINISettings()
    {
        $settings = array();
        $settings['ForumSettings'] = array( 
            'StickyUserGroupArray' => array( 
                12 
            ) 
        );
        return array( 
            'name' => 'forum.ini', 
            'reset_arrays' => false, 
            'settings' => $settings 
        );
    }

    function commonOEAttributesINISettings()
    {
        $settings = array( 
            'CustomAttribute_table_summary' => array( 
                'Name' => 'Summary (WAI)', 
                'Required' => 'true' 
            ), 
            'CustomAttribute_scope' => array( 
                'Name' => 'Scope', 
                'Title' => 'The scope attribute defines a way to associate header cells and data cells in a table.', 
                'Type' => 'select', 
                'Selection' => array( 
                    '0' => '', 
                    'col' => 'Column', 
                    'row' => 'Row' 
                ) 
            ), 
            'CustomAttribute_valign' => array( 
                'Title' => 'Lets you define the vertical alignment of the table cell/ header.', 
                'Type' => 'select', 
                'Selection' => array( 
                    '0' => '', 
                    'top' => 'Top', 
                    'middle' => 'Middle', 
                    'bottom' => 'Bottom', 
                    'baseline' => 'Baseline' 
                ) 
            ), 
            'Attribute_table_border' => array( 
                'Type' => 'htmlsize', 
                'AllowEmpty' => 'true' 
            ), 
            'CustomAttribute_embed_offset' => array( 
                'Type' => 'int', 
                'AllowEmpty' => 'true' 
            ), 
            'CustomAttribute_embed_limit' => array( 
                'Type' => 'int', 
                'AllowEmpty' => 'true' 
            ) 
        );
        return array( 
            'name' => 'ezoe_attributes.ini', 
            'settings' => $settings 
        );
    }

    function commonXMLINISettings()
    {
        return array( 
            'name' => 'ezxml.ini', 
            'settings' => array( 
                'TagSettings' => array( 
                    'TagPresets' => array( 
                        '0' => '', 
                        'mini' => 'Simple formatting' 
                    ) 
                ) 
            ) 
        );
    }

    ///////////////////////////////////////////////////////////////////////////
    // User siteaccess INI settings
    ///////////////////////////////////////////////////////////////////////////
    function siteINISettings()
    {
        $settings = array();
        $settings[] = $this->siteMenuINISettings();
        $settings[] = $this->siteOverrideINISettings();
        $settings[] = $this->siteToolbarINISettings();
        $settings[] = $this->siteSiteINISettings();
        $settings[] = $this->siteImageINISettings();
        $settings[] = $this->siteContentINISettings();
        $settings[] = $this->siteDesignINISettings();
        $settings[] = $this->siteBrowseINISettings();
        $settings[] = $this->siteTemplateINISettings();
        $settings[] = $this->siteContentStructureMenuINISettings();
        $settings[] = $this->siteODFINISettings();
        return $settings;
    }

    function siteSiteINISettings()
    {
        $settings = array();
        $primaryLanguage = $this->setting( 'primary_language' );
        $settings['RegionalSettings'] = array( 
            'Locale' => $primaryLanguage ? $primaryLanguage : 'eng-US',
            'ContentObjectLocale' => $primaryLanguage ? $primaryLanguage : 'eng-US',
            'SiteLanguageList' => $this->siteLanguageList(),
            'ShowUntranslatedObjects' => 'disabled' 
        );
        $settings['SiteAccessSettings'] = array( 
            'RequireUserLogin' => 'false', 
            'ShowHiddenNodes' => 'false',
            // PathPrefix is deliberately not written here.
            //
            // It cannot be computed at this point - install() runs before the
            // content packages, so the home node does not exist yet - and
            // writing an empty value is worse than writing nothing: a
            // siteaccess setting overrides an extension's, so an explicit
            // PathPrefix= would mask the value sevenx_themes_media ships and
            // leave every page below the site root on a 404. Omitting the key
            // lets the theme's value apply.
        );
        $siteaccessUrl = $this->setting( 'siteaccess_urls' );
        $adminSiteaccessName = $this->setting( 'admin_siteaccess' );
        $settings['SiteSettings'] = array( 
            'LoginPage' => 'embedded', 
            'AdditionalLoginFormActionURL' => 'http://' . $siteaccessUrl['admin'][$adminSiteaccessName]['url'] . '/user/login' 
        );
        $settings['Session'] = array( 
            'SessionNamePerSiteAccess' => 'disabled' 
        );
        return array( 
            'name' => 'site.ini', 
            'settings' => $settings 
        );
    }

    function siteDesignINISettings()
    {
        $settings = array( 
            'name' => 'design.ini', 
            'reset_arrays' => false, 
            'settings' => array( 
                'JavaScriptSettings' => array( 
                    'JavaScriptList' => array( 
                        'insertmedia.js'
                    ) 
                )
		/* , 
                'StylesheetSettings' => array( 
                    'CSSFileList' => array( 
                    ) 
                ) */
            ) 
        );
        return $settings;
    }

    function siteContentStructureMenuINISettings()
    {
        $contentStructureMenu = array( 
            'name' => 'contentstructuremenu.ini', 
            'reset_arrays' => true, 
            'settings' => array( 
                'TreeMenu' => array( 
                    'ShowClasses' => array( 
                        'folder', 
                        'documentation_page', 
                        'frontpage', 
                        'forums' 
                    ), 
                    'ToolTips' => 'disabled' 
                ) 
            ) 
        );
        return $contentStructureMenu;
    }

    function siteMenuINISettings()
    {
        return array( 
            'name' => 'menu.ini', 
            'reset_arrays' => true, 
            'settings' => array( 
                'MenuSettings' => array( 
                    'AvailableMenuArray' => array( 
                        'TopOnly', 
                        'LeftOnly', 
                        'DoubleTop', 
                        'LeftTop' 
                    ) 
                ), 
                'SelectedMenu' => array( 
                    'CurrentMenu' => 'LeftTop', 
                    'TopMenu' => 'flat_top', 
                    'LeftMenu' => 'flat_left' 
                ), 
                'TopOnly' => array( 
                    'TitleText' => 'Only top menu', 
                    'MenuThumbnail' => 'menu/top_only.jpg', 
                    'TopMenu' => 'flat_top', 
                    'LeftMenu' => '' 
                ), 
                'LeftOnly' => array( 
                    'TitleText' => 'Left menu', 
                    'MenuThumbnail' => 'menu/left_only.jpg', 
                    'TopMenu' => '', 
                    'LeftMenu' => 'flat_left' 
                ), 
                'DoubleTop' => array( 
                    'TitleText' => 'Double top menu', 
                    'MenuThumbnail' => 'menu/double_top.jpg', 
                    'TopMenu' => 'double_top', 
                    'LeftMenu' => '' 
                ), 
                'LeftTop' => array( 
                    'TitleText' => 'Left and top', 
                    'MenuThumbnail' => 'menu/left_top.jpg', 
                    'TopMenu' => 'flat_top', 
                    'LeftMenu' => 'flat_left' 
                ), 
                'MenuContentSettings' => array( 
                    'TopIdentifierList' => array( 
                        'folder', 
                        'feedback_form', 
                        'article', 
                        'link', 
                    ), 
                    'LeftIdentifierList' => array( 
                        'folder', 
                        'feedback_form' 
                    ) 
                ) 
            ) 
        );
    }

    function siteOverrideINISettings()
    {
        return array( 
            'name' => 'override.ini', 
            'discard_old_values' => true, 
            'settings' => array( 
                'full_article' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/article.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'article' 
                    ) 
                ), 
                'full_geo_article' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/geo_article.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'geo_article' 
                    ) 
                ), 
                'full_article_mainpage' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/article_mainpage.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'article_mainpage' 
                    ) 
                ), 
                'full_article_subpage' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/article_subpage.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'article_subpage' 
                    ) 
                ), 
                'full_banner' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/banner.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'banner' 
                    ) 
                ), 
                'full_blog' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/blog.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'blog' 
                    ) 
                ), 
                'full_blog_post' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/blog_post.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'blog_post' 
                    ) 
                ), 
                'full_comment' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/comment.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'comment' 
                    ) 
                ), 
                'full_documentation_page' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/documentation_page.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'documentation_page' 
                    ) 
                ), 
                'full_event_calendar' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/event_calendar.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'event_calendar' 
                    ) 
                ), 
                'full_event' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/event.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'event' 
                    ) 
                ), 
                'full_feedback_form' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/feedback_form.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'feedback_form' 
                    ) 
                ), 
                'full_file' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/file.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'file' 
                    ) 
                ), 
                'full_flash' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/flash.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'flash' 
                    ) 
                ), 
                'full_folder' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/folder.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'folder' 
                    ) 
                ), 
                'full_forum' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/forum.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum' 
                    ) 
                ), 
                'full_forum_reply' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/forum_reply.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum_reply' 
                    ) 
                ), 
                'full_forum_topic' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/forum_topic.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum_topic' 
                    ) 
                ), 
                'full_forums' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/forums.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forums' 
                    ) 
                ), 
                'full_frontpage' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/frontpage.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'frontpage' 
                    ) 
                ), 
                'full_gallery' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/gallery.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'gallery' 
                    ) 
                ), 
                'full_image' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'full_infobox' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/infobox.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'infobox' 
                    ) 
                ), 
                'full_link' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/link.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'link' 
                    ) 
                ), 
                'full_multicalendar' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/multicalendar.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'multicalendar' 
                    ) 
                ), 
                'full_poll' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/poll.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'poll' 
                    ) 
                ), 
                'full_product' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/product.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'product' 
                    ) 
                ), 
                'full_quicktime' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/quicktime.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'quicktime' 
                    ) 
                ), 
                'full_real_video' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/real_video.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'real_video' 
                    ) 
                ), 
                'full_silverlight' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/silverlight.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'silverlight' 
                    ) 
                ), 
                'full_windows_media' => array( 
                    'Source' => 'node/view/full.tpl', 
                    'MatchFile' => 'full/windows_media.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'windows_media' 
                    ) 
                ), 
                'line_article' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/article.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'article' 
                    ) 
                ), 
                'line_geo_article' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/geo_article.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'geo_article' 
                    ) 
                ), 
                'line_article_mainpage' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/article_mainpage.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'article_mainpage' 
                    ) 
                ), 
                'line_article_subpage' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/article_subpage.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'article_subpage' 
                    ) 
                ), 
                'line_banner' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/banner.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'banner' 
                    ) 
                ), 
                'line_blog' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/blog.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'blog' 
                    ) 
                ), 
                'line_blog_post' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/blog_post.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'blog_post' 
                    ) 
                ), 
                'line_comment' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/comment.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'comment' 
                    ) 
                ), 
                'line_documentation_page' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/documentation_page.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'documentation_page' 
                    ) 
                ), 
                'line_event_calendar' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/event_calendar.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'event_calendar' 
                    ) 
                ), 
                'line_event' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/event.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'event' 
                    ) 
                ), 
                'line_feedback_form' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/feedback_form.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'feedback_form' 
                    ) 
                ), 
                'line_file' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/file.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'file' 
                    ) 
                ), 
                'line_flash' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/flash.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'flash' 
                    ) 
                ), 
                'line_folder' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/folder.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'folder' 
                    ) 
                ), 
                'line_forum' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/forum.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum' 
                    ) 
                ), 
                'line_forum_reply' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/forum_reply.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum_reply' 
                    ) 
                ), 
                'line_forum_topic' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/forum_topic.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum_topic' 
                    ) 
                ), 
                'line_forums' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/forums.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forums' 
                    ) 
                ), 
                'line_gallery' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/gallery.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'gallery' 
                    ) 
                ), 
                'line_image' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'line_infobox' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/infobox.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'infobox' 
                    ) 
                ), 
                'line_link' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/link.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'link' 
                    ) 
                ), 
                'line_multicalendar' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/multicalendar.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'multicalendar' 
                    ) 
                ), 
                'line_poll' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/poll.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'poll' 
                    ) 
                ), 
                'line_product' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/product.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'product' 
                    ) 
                ), 
                'line_silverlight' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/silverlight.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'silverlight' 
                    ) 
                ), 
                'line_quicktime' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/quicktime.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'quicktime' 
                    ) 
                ), 
                'line_real_video' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/real_video.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'real_video' 
                    ) 
                ), 
                'line_windows_media' => array( 
                    'Source' => 'node/view/line.tpl', 
                    'MatchFile' => 'line/windows_media.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'windows_media' 
                    ) 
                ), 
                'edit_comment' => array( 
                    'Source' => 'content/edit.tpl', 
                    'MatchFile' => 'edit/comment.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'comment' 
                    ) 
                ), 
                'edit_file' => array( 
                    'Source' => 'content/edit.tpl', 
                    'MatchFile' => 'edit/file.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'file' 
                    ) 
                ), 
                'edit_forum_topic' => array( 
                    'Source' => 'content/edit.tpl', 
                    'MatchFile' => 'edit/forum_topic.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum_topic' 
                    ) 
                ), 
                'edit_ezsubtreesubscription_forum_topic' => array( 
                    'Source' => 'content/datatype/edit/ezsubtreesubscription.tpl', 
                    'MatchFile' => 'datatype/edit/forum_topic.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum_topic' 
                    ) 
                ), 
                'edit_forum_reply' => array( 
                    'Source' => 'content/edit.tpl', 
                    'MatchFile' => 'edit/forum_reply.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum_reply' 
                    ) 
                ), 
                'highlighted_object' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/highlighted_object.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'classification' => 'highlighted_object' 
                    ) 
                ), 
                'embed_article' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/article.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'article' 
                    ) 
                ), 
                'embed_banner' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/banner.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'banner' 
                    ) 
                ), 
                'embed_file' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/file.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'file' 
                    ) 
                ), 
                'embed_flash' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/flash.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'flash' 
                    ) 
                ), 
                'itemized_sub_items' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/itemized_sub_items.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'classification' => 'itemized_sub_items' 
                    ) 
                ), 
                'vertically_listed_sub_items' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/vertically_listed_sub_items.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'classification' => 'vertically_listed_sub_items' 
                    ) 
                ), 
                'horizontally_listed_sub_items' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/horizontally_listed_sub_items.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'classification' => 'horizontally_listed_sub_items' 
                    ) 
                ), 
                'itemized_subtree_items' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/itemized_subtree_items.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'classification' => 'itemized_subtree_items' 
                    ) 
                ), 
                'embed_folder' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/folder.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'folder' 
                    ) 
                ), 
                'embed_forum' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/forum.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum' 
                    ) 
                ), 
                'embed_gallery' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/gallery.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'gallery' 
                    ) 
                ), 
                'embed_image' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'embed_poll' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/poll.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'poll' 
                    ) 
                ), 
                'embed_product' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/product.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'product' 
                    ) 
                ), 
                'embed_quicktime' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/quicktime.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'quicktime' 
                    ) 
                ), 
                'embed_real_video' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/real_video.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'real_video' 
                    ) 
                ), 
                'embed_windows_media' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/windows_media.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'windows_media' 
                    ) 
                ), 
                'embed_inline_image' => array( 
                    'Source' => 'content/view/embed-inline.tpl', 
                    'MatchFile' => 'embed-inline/image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'embed_itemizedsubitems_gallery' => array( 
                    'Source' => 'content/view/itemizedsubitems.tpl', 
                    'MatchFile' => 'itemizedsubitems/gallery.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'gallery' 
                    ) 
                ), 
                'embed_itemizedsubitems_forum' => array( 
                    'Source' => 'content/view/itemizedsubitems.tpl', 
                    'MatchFile' => 'itemizedsubitems/forum.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'forum' 
                    ) 
                ), 
                'embed_itemizedsubitems_folder' => array( 
                    'Source' => 'content/view/itemizedsubitems.tpl', 
                    'MatchFile' => 'itemizedsubitems/folder.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'folder' 
                    ) 
                ), 
                'embed_itemizedsubitems_event_calendar' => array( 
                    'Source' => 'content/view/itemizedsubitems.tpl', 
                    'MatchFile' => 'itemizedsubitems/event_calendar.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'event_calendar' 
                    ) 
                ), 
                'embed_itemizedsubitems_documentation_page' => array( 
                    'Source' => 'content/view/itemizedsubitems.tpl', 
                    'MatchFile' => 'itemizedsubitems/documentation_page.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'documentation_page' 
                    ) 
                ), 
                'embed_itemizedsubitems_itemized_sub_items' => array( 
                    'Source' => 'content/view/itemizedsubitems.tpl', 
                    'MatchFile' => 'itemizedsubitems/itemized_sub_items.tpl', 
                    'Subdir' => 'templates' 
                ), 
                'embed_event_calendar' => array( 
                    'Source' => 'content/view/embed.tpl', 
                    'MatchFile' => 'embed/event_calendar.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'event_calendar' 
                    ) 
                ), 
                'embed_horizontallylistedsubitems_article' => array( 
                    'Source' => 'node/view/horizontallylistedsubitems.tpl', 
                    'MatchFile' => 'horizontallylistedsubitems/article.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'article' 
                    ) 
                ), 
                'embed_horizontallylistedsubitems_event' => array( 
                    'Source' => 'node/view/horizontallylistedsubitems.tpl', 
                    'MatchFile' => 'horizontallylistedsubitems/event.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'event' 
                    ) 
                ), 
                'embed_horizontallylistedsubitems_image' => array( 
                    'Source' => 'node/view/horizontallylistedsubitems.tpl', 
                    'MatchFile' => 'horizontallylistedsubitems/image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'embed_horizontallylistedsubitems_product' => array( 
                    'Source' => 'node/view/horizontallylistedsubitems.tpl', 
                    'MatchFile' => 'horizontallylistedsubitems/product.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'product' 
                    ) 
                ), 
                'factbox' => array( 
                    'Source' => 'content/datatype/view/ezxmltags/factbox.tpl', 
                    'MatchFile' => 'datatype/ezxmltext/factbox.tpl', 
                    'Subdir' => 'templates' 
                ), 
                'quote' => array( 
                    'Source' => 'content/datatype/view/ezxmltags/quote.tpl', 
                    'MatchFile' => 'datatype/ezxmltext/quote.tpl', 
                    'Subdir' => 'templates' 
                ), 
                'table_cols' => array( 
                    'Source' => 'content/datatype/view/ezxmltags/table.tpl', 
                    'MatchFile' => 'datatype/ezxmltext/table_cols.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'classification' => 'cols' 
                    ) 
                ), 
                'table_comparison' => array( 
                    'Source' => 'content/datatype/view/ezxmltags/table.tpl', 
                    'MatchFile' => 'datatype/ezxmltext/table_comparison.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'classification' => 'comparison' 
                    ) 
                ), 
                'image_galleryline' => array( 
                    'Source' => 'node/view/galleryline.tpl', 
                    'MatchFile' => 'galleryline/image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'image_galleryslide' => array( 
                    'Source' => 'node/view/galleryslide.tpl', 
                    'MatchFile' => 'galleryslide/image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'article_listitem' => array( 
                    'Source' => 'node/view/listitem.tpl', 
                    'MatchFile' => 'listitem/article.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'article' 
                    ) 
                ), 
                'image_listitem' => array( 
                    'Source' => 'node/view/listitem.tpl', 
                    'MatchFile' => 'listitem/image.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'image' 
                    ) 
                ), 
                'billboard_banner' => array( 
                    'Source' => 'content/view/billboard.tpl', 
                    'MatchFile' => 'billboard/banner.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'banner' 
                    ) 
                ), 
                'billboard_flash' => array( 
                    'Source' => 'content/view/billboard.tpl', 
                    'MatchFile' => 'billboard/flash.tpl', 
                    'Subdir' => 'templates', 
                    'Match' => array( 
                        'class_identifier' => 'flash' 
                    ) 
                ) 
            ) 
        );
    }

    function siteToolbarINISettings()
    {
        $toolbar = array( 
            'name' => 'toolbar.ini', 
            'reset_arrays' => true, 
            'settings' => array( 
                'Toolbar_right' => array( 
                    'Tool' => array( 
                        'node_list' 
                    ) 
                ), 
                'Toolbar_top' => array( 
                    'Tool' => array( 
                        'login', 
                        'searchbox' 
                    ) 
                ), 
                'Toolbar_bottom' => array( 
                    'Tool' => array() 
                ), 
                'Tool_right_node_list_1' => array( 
                    'parent_node' => '2', 
                    'title' => 'Latest', 
                    'show_subtree' => '', 
                    'limit' => 5 
                ) 
            ) 
        );
        return $toolbar;
    }

    // Only the image aliases belong here. [ImageConverterSettings] and the
    // [GD] / [ImageMagick] sections are deliberately left out: the converter
    // order (GD first, ImageMagick as the fallback) comes from the kernel's
    // settings/image.ini, and the setup wizard enables ImageMagick in
    // settings/override when it finds the convert program. Writing either
    // into the siteaccess would pin this site to today's order: with
    // reset_arrays an ImageConverters list here replaces the kernel list.
    function siteImageINISettings()
    {
        $settings = array( 
            'name' => 'image.ini', 
            'reset_arrays' => true, 
            'settings' => array( 
                'AliasSettings' => array( 
                    'AliasList' => array( 
                        '0' => 'small', 
                        '1' => 'medium', 
                        '2' => 'listitem', 
                        '3' => 'articleimage', 
                        '4' => 'articlethumbnail', 
                        '5' => 'gallerythumbnail', 
                        '6' => 'galleryline', 
                        '7' => 'imagelarge', 
                        '8' => 'large', 
                        '9' => 'rss', 
                        '10' => 'logo', 
                        '11' => 'infoboximage', 
                        '12' => 'billboard' 
                    ) 
                ), 
                'small' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scaledownonly=100;160' 
                    ) 
                ), 
                'medium' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scaledownonly=200;290' 
                    ) 
                ), 
                'large' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scaledownonly=360;440' 
                    ) 
                ), 
                'rss' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scale=88;31' 
                    ) 
                ), 
                'logo' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scaleheight=36' 
                    ) 
                ), 
                'listitem' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scaledownonly=130;190' 
                    ) 
                ), 
                'articleimage' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scaledownonly=170;350' 
                    ) 
                ), 
                'articlethumbnail' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scaledownonly=70;150' 
                    ) 
                ), 
                'gallerythumbnail' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scaledownonly=105;100' 
                    ) 
                ), 
                'galleryline' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scaledownonly=70;150' 
                    ) 
                ), 
                'imagelarge' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scaledownonly=550;730' 
                    ) 
                ), 
                'infoboximage' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scalewidth=75' 
                    ) 
                ), 
                'billboard' => array( 
                    'Reference' => '', 
                    'Filters' => array( 
                        '0' => 'geometry/scalewidth=764' 
                    ) 
                ) 
            ) 
        );
        return $settings;
    }

    function siteContentINISettings()
    {
        $settings = array( 
            'name' => 'content.ini', 
            'reset_arrays' => false, 
            'settings' => array( 
                'VersionView' => array( 
                    'AvailableSiteDesignList' => array( 
                        $this->setting( 'main_site_design' ) 
                    ) 
                ), 
                'ObjectRelationDataTypeSettings' => array( 
                    'ClassAttributeStartNode' => array( 
                        '236;AddRelatedBannerImageToDataType' 
                    ) 
                ) 
            ) 
        );
        return $settings;
    }

    function siteBrowseINISettings()
    {
        $settings = array( 
            'name' => 'browse.ini', 
            'reset_arrays' => false, 
            'settings' => array( 
                'BrowseSettings' => array( 
                    'AliasList' => array( 
                        'banners' => '59' 
                    ) 
                ), 
                'AddRelatedBannerImageToDataType' => array( 
                    'StartNode' => 'banners', 
                    'SelectionType' => 'single', 
                    'ReturnType' => 'ObjectID' 
                ) 
            ) 
        );
        return $settings;
    }

    function siteTemplateINISettings()
    {
        $settings = array( 
            'name' => 'template.ini', 
            'settings' => array( 
                'CharsetSettings' => array( 
                    'DefaultTemplateCharset' => 'utf-8' 
                ) 
            ) 
        );
        return $settings;
    }

    function siteODFINISettings()
    {
        // update 'article' class attributes info
        $articleExtraAttributes = array( 
            'caption' => 'caption', 
            'publish_date' => 'publish_date', 
            'unpublish_date' => 'unpublish_date' 
        );
        return array( 
            'name' => 'odf.ini', 
            'settings' => array( 
                'article' => array( 
                    'Attribute' => $articleExtraAttributes 
                ) 
            ) 
        );
    }
}
?>

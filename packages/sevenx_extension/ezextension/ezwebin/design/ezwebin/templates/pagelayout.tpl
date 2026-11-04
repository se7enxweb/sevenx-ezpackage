<!DOCTYPE html>
<html lang="{$site.http_equiv.Content-language|wash}" class="ezwebin-html">
<head>
{def $basket_is_empty   = cond( $current_user.is_logged_in, fetch( shop, basket ).is_empty, 1 )
     $user_hash         = concat( $current_user.role_id_list|implode( ',' ), ',', $current_user.limited_assignment_value_list|implode( ',' ) )}

{if is_set( $extra_cache_key )|not}
    {def $extra_cache_key = ''}
{/if}
{* Error pages can arrive with the same URI while their title and path name the error: key them
   by error type and number too (every other page keeps the plain URI key) *}
{def $uri_cache_key = $module_result.uri}
{if is_set( $module_result.errorType )}
{set $uri_cache_key = concat( $module_result.uri, '|error|', $module_result.errorType, '|', first_set( $module_result.errorNumber, '' ) )}
{/if}

{cache-block keys=array( $uri_cache_key, $basket_is_empty, $current_user.contentobject_id, $extra_cache_key )}
{def $pagedata         = ezpagedata()
     $pagestyle        = $pagedata.css_classes
     $locales          = fetch( 'content', 'translation_list' )
     $pagedesign       = $pagedata.template_look
     $current_node_id  = $pagedata.node_id}

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
{* The color mode is set before the stylesheets are drawn (a file, not an inline script: CSP script-src 'self') *}
{ezscript( array( 'ezwebin-theme.js' ) )}
{include uri='design:page_head.tpl'}
{include uri='design:page_head_style.tpl'}
{include uri='design:page_head_script.tpl'}

</head>
{* data-exp-user: the signed-in user's object id, read by ezwebin.js for the .limitdisplay-user-<id> elements
   (in place of the inline <style> of page_head_displaystyles.tpl); the block is keyed by the user's id *}
{def $skin = ezini( 'StylesheetSettings', 'SiteCSS', 'design.ini' )}
{if $skin|contains( 'ezwebin_design_' )}{set $skin = $skin|explode( 'ezwebin_design_' ).1|explode( '/' ).0}{else}{set $skin = 'default'}{/if}
<body class="ezwebin" data-exp-user="{$current_user.contentobject_id|wash}" data-ezwebin-skin="{$skin|wash}">
{undef $skin}
<a class="skip-link" href="#main">{'Skip to main content'|i18n( 'design/ezwebin/pagelayout' )}</a>
<!-- Complete page area: START -->

{* $pagestyle switches the side columns: "sidemenu"/"nosidemenu" and "extrainfo"/"noextrainfo" *}
<div id="page" class="{$pagestyle}">

  {if and( is_set( $pagedata.persistent_variable.extra_template_list ),
             $pagedata.persistent_variable.extra_template_list|count() )}
    {foreach $pagedata.persistent_variable.extra_template_list as $extra_template}
      {include uri=concat('design:extra/', $extra_template)}
    {/foreach}
  {/if}

  <!-- Header area: START -->
  {include uri='design:page_header.tpl'}
  <!-- Header area: END -->

  {cache-block keys=array( $uri_cache_key, $user_hash, $extra_cache_key )}

  <!-- Top menu area: START -->
  {if $pagedata.top_menu}
    {include uri='design:page_topmenu.tpl'}
  {/if}
  <!-- Top menu area: END -->

  <!-- Path area: START -->
  {if $pagedata.show_path}
    {include uri='design:page_toppath.tpl'}
  {/if}
  <!-- Path area: END -->

  <!-- Toolbar area: START -->
  {if and( $pagedata.website_toolbar, $pagedata.is_edit|not)}
    {include uri='design:page_toolbar.tpl'}
  {/if}
  <!-- Toolbar area: END -->

  <!-- Columns area: START -->
  <div id="columns-position">
  <div id="columns">

    <!-- Side menu area: START -->
    {if $pagedata.left_menu}
        {include uri='design:page_leftmenu.tpl'}
    {/if}
    <!-- Side menu area: END -->

  {/cache-block}
{/cache-block}
    <!-- Main area: START -->
    {include uri='design:page_mainarea.tpl'}
    <!-- Main area: END -->
{cache-block keys=array( $uri_cache_key, $user_hash, $access_type.name, $extra_cache_key )}

    {if is_unset($pagedesign)}
        {def $pagedata   = ezpagedata()
             $pagedesign = $pagedata.template_look}
    {/if}

    <!-- Extra area: START -->
    {if $pagedata.extra_menu}
        {include uri='design:page_extramenu.tpl'}
    {/if}
    <!-- Extra area: END -->

  </div>
  </div>
  <!-- Columns area: END -->

  <!-- Footer area: START -->
  {include uri='design:page_footer.tpl'}
  <!-- Footer area: END -->

</div>
<!-- Complete page area: END -->

<!-- Footer script area: START -->
{include uri='design:page_footer_script.tpl'}
<!-- Footer script area: END -->

{/cache-block}

{* This comment will be replaced with actual debug report (if debug is on). *}
<!--DEBUG_REPORT-->
</body>
</html>

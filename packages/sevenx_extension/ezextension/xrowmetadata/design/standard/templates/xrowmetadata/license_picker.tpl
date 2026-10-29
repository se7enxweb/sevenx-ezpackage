{* The license picker of the metadata edit forms (object edit, class edit, the adminui edit form).

   Parameters:
     picker_id      unique id of the picker in the page
     select_name    the name the chosen license id is posted as ('' = use inherited)
     selected       the chosen license id, '' for none
     inherited      the license that applies without a choice (array with id, name, source: class|installation), or false
     inherit_label  optional: the text of the "use inherited" option when nothing is inherited
     custom_target  optional: id of the element with the custom license fields; shown only for a custom license
     bootstrap      optional: true() for the Bootstrap classes of the adminui design

   The list is grouped (each license once, in its first group: public domain, non-free by type, content, copyleft,
   permissive, open source, free software); the group filter shows every license of a group, wherever it is listed.
   Retired licenses (deprecated, or retired by Creative Commons) are hidden until "Show retired licenses" is
   checked, except the chosen one. Without JavaScript every license is listed. *}
{if is_set( $bootstrap )|not}{def $bootstrap = false()}{/if}
{if is_set( $custom_target )|not}{def $custom_target = ''}{/if}
{if is_set( $inherit_label )|not}{def $inherit_label = 'Use inherited: none'|i18n( 'design/standard/class/datatype' )}{/if}
{def $lp_choices = xrowmetadata_license_choices( $selected )
     $lp_info = xrowmetadata_license_info( $selected )
     $lp_shown = cond( $lp_info, $lp_info, $inherited )
     $lp_input_class = cond( $bootstrap, 'form-control', 'box' )
     $lp_has_retired = false()}
{foreach $lp_choices as $lp_group}{foreach $lp_group.options as $lp_option}{if $lp_option.retired}{set $lp_has_retired = true()}{break}{/if}{/foreach}{if $lp_has_retired}{break}{/if}{/foreach}

<div class="xrowmetadata-license-picker" id="{$picker_id|wash()}"
     data-custom-target="{$custom_target|wash()}"
     data-count="{'%shown of %total licenses shown'|i18n( 'design/standard/class/datatype' )|wash()}"
     data-terms-of="{'Terms of %name:'|i18n( 'design/standard/class/datatype' )|wash()}"
     data-allowed="{'allowed'|i18n( 'design/standard/class/datatype' )|wash()}"
     data-not-allowed="{'not allowed'|i18n( 'design/standard/class/datatype' )|wash()}"
     data-required="{'required'|i18n( 'design/standard/class/datatype' )|wash()}"
     data-not-required="{'not required'|i18n( 'design/standard/class/datatype' )|wash()}"
     data-not-stated="{'not stated'|i18n( 'design/standard/class/datatype' )|wash()}"
     data-read-terms="{'Read the license'|i18n( 'design/standard/class/datatype' )|wash()}"
     data-term-labels="{concat( 'Commercial use'|i18n( 'design/standard/class/datatype' ), '|', 'Derivatives'|i18n( 'design/standard/class/datatype' ), '|', 'Share-alike'|i18n( 'design/standard/class/datatype' ), '|', 'Attribution'|i18n( 'design/standard/class/datatype' ) )|wash()}">

    <p class="xrowmetadata-license-inherited{if $bootstrap} help-block{/if}">
    {if $inherited}
        {if eq( $inherited.source, 'class' )}
            {'Inherited from class: %name'|i18n( 'design/standard/class/datatype', '', hash( '%name', $inherited.name|wash() ) )}
        {else}
            {'Inherited from installation: %name'|i18n( 'design/standard/class/datatype', '', hash( '%name', $inherited.name|wash() ) )}
        {/if}
    {else}
        {'No license is inherited: without a choice here the page states no license.'|i18n( 'design/standard/class/datatype' )}
    {/if}
    </p>

    <div class="xrowmetadata-license-filter">
        <span class="xrowmetadata-license-filter-item xrowmetadata-license-filter-search">
            <label for="{$picker_id|wash()}-search">{'Search licenses'|i18n( 'design/standard/class/datatype' )}:</label>
            <input type="search" id="{$picker_id|wash()}-search" class="{$lp_input_class} xrowmetadata-license-search" autocomplete="off"
                   placeholder="{'Name or SPDX id, e.g. CC BY 4.0 or GPL-3.0'|i18n( 'design/standard/class/datatype' )|wash()}" />
        </span>
        <span class="xrowmetadata-license-filter-item">
            <label for="{$picker_id|wash()}-group">{'Group'|i18n( 'design/standard/class/datatype' )}:</label>
            <select id="{$picker_id|wash()}-group" class="{if $bootstrap}form-control {/if}xrowmetadata-license-group">
                <option value="">{'All groups'|i18n( 'design/standard/class/datatype' )}</option>
                <option value="os">{'Open source'|i18n( 'design/standard/class/datatype' )}</option>
                <option value="fs">{'Free software'|i18n( 'design/standard/class/datatype' )}</option>
                <option value="cl">{'Copyleft'|i18n( 'design/standard/class/datatype' )}</option>
                <option value="pm">{'Permissive'|i18n( 'design/standard/class/datatype' )}</option>
                <option value="ct">{'Content (Creative Commons)'|i18n( 'design/standard/class/datatype' )}</option>
                <option value="pd">{'Public domain'|i18n( 'design/standard/class/datatype' )}</option>
                <option value="nf">{'Non-free'|i18n( 'design/standard/class/datatype' )}</option>
            </select>
        </span>
        {if $lp_has_retired}
        <span class="xrowmetadata-license-filter-item{if $bootstrap} checkbox{/if}">
            <label><input type="checkbox" class="xrowmetadata-license-retired" /> {'Show retired licenses'|i18n( 'design/standard/class/datatype' )}</label>
        </span>
        {/if}
        <span class="xrowmetadata-license-count" aria-live="polite"></span>
    </div>

    <label class="xrowmetadata-license-select-label" for="{$picker_id|wash()}-select">{'License'|i18n( 'design/standard/class/datatype' )}:</label>
    <select name="{$select_name|wash()}" id="{$picker_id|wash()}-select" size="10" class="{if $bootstrap}form-control {/if}xrowmetadata-license-select">
        <option value="" class="xrowmetadata-license-inherit"{if $selected|eq('')} selected="selected"{/if}{if $inherited} data-t="{$inherited.terms_code|wash()}" data-u="{$inherited.url|wash()}" data-n="{$inherited.name|wash()}"{/if}>
            {if $inherited}{'Use inherited: %name'|i18n( 'design/standard/class/datatype', '', hash( '%name', $inherited.name|wash() ) )}{else}{$inherit_label|wash()}{/if}
        </option>
        {if and( $selected|ne(''), $lp_info|not )}
        <option value="{$selected|wash()}" selected="selected" data-k="1">{'%id (not in the license catalogue)'|i18n( 'design/standard/class/datatype', '', hash( '%id', $selected|wash() ) )}</option>
        {/if}
        {foreach $lp_choices as $lp_group}
        <optgroup label="{$lp_group.label|wash()}">
            {foreach $lp_group.options as $lp_option}<option value="{$lp_option.id|wash()}" data-g="{$lp_option.group_codes|wash()}" data-t="{$lp_option.terms_code|wash()}"{if $lp_option.url_short|ne('')} data-u="{$lp_option.url_short|wash()}"{/if}{if $lp_option.retired} data-r="1"{/if}{if $lp_option.custom} data-c="1"{/if}{if $lp_option.id|eq( $selected )} selected="selected"{/if}>{$lp_option.label|wash()}{if $lp_option.retired} - {'retired'|i18n( 'design/standard/class/datatype' )}{/if}</option>
            {/foreach}
        </optgroup>
        {/foreach}
    </select>

    {* The terms summary of the license that applies (the chosen one, else the inherited one). JavaScript keeps it current. *}
    <div class="xrowmetadata-license-summary{if $bootstrap} help-block{/if}">
    {if $lp_shown}
        <span class="xrowmetadata-license-summary-name">{'Terms of %name:'|i18n( 'design/standard/class/datatype', '', hash( '%name', $lp_shown.name|wash() ) )}</span>
        <ul class="xrowmetadata-license-terms">
        {foreach $lp_shown.terms_list as $lp_term}
            <li>{$lp_term.label|wash()}:
            {if eq( $lp_term.value, 'unknown' )}{'not stated'|i18n( 'design/standard/class/datatype' )}
            {elseif or( eq( $lp_term.key, 'commercial' ), eq( $lp_term.key, 'derivatives' ) )}{if eq( $lp_term.value, 'yes' )}{'allowed'|i18n( 'design/standard/class/datatype' )}{else}{'not allowed'|i18n( 'design/standard/class/datatype' )}{/if}
            {else}{if eq( $lp_term.value, 'yes' )}{'required'|i18n( 'design/standard/class/datatype' )}{else}{'not required'|i18n( 'design/standard/class/datatype' )}{/if}{/if}</li>
        {/foreach}
        </ul>
        {if $lp_shown.url|ne('')}<a class="xrowmetadata-license-url" href="{$lp_shown.url|wash()}" target="_blank" rel="noopener noreferrer license">{'Read the license'|i18n( 'design/standard/class/datatype' )}</a>{/if}
    {/if}
    </div>
</div>

{* The picker's look is in stylesheets/xrowmetadata.css, loaded by design.ini [StylesheetSettings] *}
{* The picker's script (design/standard/javascript/xrowmetadata_license_picker.js) sets up every picker of the page once *}
{ezscript( array( 'xrowmetadata_license_picker.js' ) )}
{undef $lp_choices $lp_info $lp_shown $lp_input_class $lp_has_retired}

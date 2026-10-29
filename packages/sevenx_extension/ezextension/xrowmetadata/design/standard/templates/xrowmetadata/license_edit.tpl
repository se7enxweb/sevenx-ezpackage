{* The license block of the metadata attribute edit form (included by content/datatype/edit/xrowmetadata.tpl, and by
   the adminui edit form with bootstrap=true()).

   Parameters: attribute, attribute_base, bootstrap (optional).

   Posted with the other metadata fields: [license] ('' = use inherited), [license_holder], [license_year],
   [license_custom_url], [license_custom_terms]. When the class sets the license (license_allowed false) the block is
   read-only and posts nothing; the stored values are kept. *}
{if is_set( $bootstrap )|not}{def $bootstrap = false()}{/if}
{if xrowmetadata_license_enabled()}
{def $le_meta = $attribute.content
     $le_name = concat( $attribute_base, '_xrowmetadata_data_array_', $attribute.id )
     $le_id = concat( 'xrowmetadata_license_', $attribute.id )
     $le_input_class = cond( $bootstrap, 'form-control', 'box' )
     $le_inherited = $le_meta.license_inherited}
<div class="block xrowmetadata-license{if $le_meta.license_allowed|not} xrowmetadata-license-by-class{/if}" id="{$le_id}">
    <fieldset>
    <legend>{'License'|i18n( 'design/standard/class/datatype' )}</legend>
    {if $le_meta.license_allowed|not}
        {* The class sets the license *}
        {def $le_effective = $le_meta.license_effective}
        {if $le_effective}
            <p class="xrowmetadata-license-chosen"><strong>{$le_effective.name|wash()}</strong> ({$le_effective.id|wash()})
            {if $le_effective.url|ne('')} <a href="{$le_effective.url|wash()}" target="_blank" rel="noopener noreferrer license">{'Read the license'|i18n( 'design/standard/class/datatype' )}</a>{/if}</p>
            <p class="xrowmetadata-license-note{if $bootstrap} help-block{/if}">{if eq( $le_effective.source, 'class' )}{'Set by the class'|i18n( 'design/standard/class/datatype' )}{else}{'Inherited from installation: %name'|i18n( 'design/standard/class/datatype', '', hash( '%name', $le_effective.name|wash() ) )}{/if}</p>
        {else}
            <p class="xrowmetadata-license-none">{'The class sets no license, and the installation has no default license.'|i18n( 'design/standard/class/datatype' )}</p>
        {/if}
        {if $le_meta.license|ne('')}
            <p class="xrowmetadata-license-unused{if $bootstrap} help-block{/if}">{'A license chosen earlier for this object is kept, but not stated while the class sets the license.'|i18n( 'design/standard/class/datatype' )}</p>
        {/if}
        {undef $le_effective}
    {else}
        {include uri='design:xrowmetadata/license_picker.tpl'
                 picker_id=concat( $le_id, '_picker' )
                 select_name=concat( $le_name, '[license]' )
                 selected=$le_meta.license
                 inherited=$le_inherited
                 custom_target=concat( $le_id, '_custom' )
                 bootstrap=$bootstrap}

        <div class="xrowmetadata-license-custom" id="{$le_id}_custom">
            <div class="element">
                <label for="{$le_id}_custom_url">{'Address of the license terms'|i18n( 'design/standard/class/datatype' )}:</label>
                <input id="{$le_id}_custom_url" class="{$le_input_class}" type="url" name="{$le_name}[license_custom_url]" size="70" value="{$le_meta.license_custom_url|wash()}" placeholder="https://" />
            </div>
            <div class="element">
                <label for="{$le_id}_custom_terms">{'License terms'|i18n( 'design/standard/class/datatype' )}:</label>
                <textarea id="{$le_id}_custom_terms" class="{$le_input_class}" name="{$le_name}[license_custom_terms]" rows="4" cols="70">{$le_meta.license_custom_terms|wash()}</textarea>
            </div>
            <p class="{if $bootstrap}help-block{/if}">{'A custom license needs the address of its terms or the text of its terms.'|i18n( 'design/standard/class/datatype' )}</p>
        </div>
    {/if}

    {if $le_meta.license_allowed}
    <div class="xrowmetadata-license-holder">
        <div class="element">
            <label for="{$le_id}_holder">{'Copyright holder'|i18n( 'design/standard/class/datatype' )}:</label>
            <input id="{$le_id}_holder" class="{$le_input_class}" type="text" name="{$le_name}[license_holder]" size="50" value="{$le_meta.license_holder|wash()}" />
        </div>
        <div class="element">
            <label for="{$le_id}_year">{'Year'|i18n( 'design/standard/class/datatype' )}:</label>
            <input id="{$le_id}_year" class="{$le_input_class}" type="text" inputmode="numeric" name="{$le_name}[license_year]" size="12" maxlength="11" value="{$le_meta.license_year|wash()}" placeholder="{currentdate()|datetime( 'custom', '%Y' )}" />
        </div>
    </div>
    {/if}
    </fieldset>
</div>
{undef $le_meta $le_name $le_id $le_input_class $le_inherited}
{/if}

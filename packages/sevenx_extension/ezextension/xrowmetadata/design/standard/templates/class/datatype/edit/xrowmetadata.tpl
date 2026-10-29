<div class="block" id="ezobjectrelation_browse_{$class_attribute.id}">
    <label>{'Default Open Graph image'|i18n( 'design/standard/class/datatype' )}:</label>
    {if $class_attribute.content}
        {def $mt_og_object = $class_attribute.content}
        {def $mt_image_attr = false()}
        {if $mt_og_object.data_map.site_opengraph_image}{set $mt_image_attr = $mt_og_object.data_map.site_opengraph_image}{/if}
        {if and(not($mt_image_attr), $mt_og_object.data_map.image)}{set $mt_image_attr = $mt_og_object.data_map.image}{/if}
        {if and(not($mt_image_attr), $mt_og_object.data_map.site_logo)}{set $mt_image_attr = $mt_og_object.data_map.site_logo}{/if}
        {if and(not($mt_image_attr), $mt_og_object.data_map.file)}{set $mt_image_attr = $mt_og_object.data_map.file}{/if}
        {def $mt_image_path = ''}
        {if $mt_image_attr}
            {if eq($mt_image_attr.data_type_string, 'ezimage')}
                {set $mt_image_path = $mt_image_attr.content.original.full_path}
            {elseif eq($mt_image_attr.data_type_string, 'ezbinaryfile')}
                {set $mt_image_path = $mt_image_attr.content.filepath}
            {/if}
        {/if}
        <input type="hidden" name="xrowmetadata_og_image_{$class_attribute.id}" value="{$mt_og_object.id|wash()}" />
        <table class="list" cellspacing="0">
            <tr>
                <th>{'Name'|i18n( 'design/standard/content/datatype' )}</th>
                <th>{'Type'|i18n( 'design/standard/content/datatype' )}</th>
                <th>{'Action'|i18n( 'design/standard/content/datatype' )}</th>
            </tr>
            <tr>
                <td>
                    {$mt_og_object.name|wash()}
                    {if $mt_image_path|ne('')}
                        <br /><img src="{concat('/', $mt_image_path)}" alt="{$mt_og_object.name|wash()}" class="xrowmetadata-class-og-image-thumb" />
                    {/if}
                </td>
                <td>{if $mt_image_attr}{$mt_image_attr.data_type_string|wash()}{else}{$mt_og_object.class_name|wash()}{/if}</td>
                <td>
                    <input class="button ezobject-relation-remove-button" type="submit" name="CustomActionButton[{$class_attribute.id}_remove_object]" value="{'Remove object'|i18n( 'design/standard/content/datatype' )}" />
                </td>
            </tr>
        </table>
    {else}
        <input type="hidden" name="xrowmetadata_og_image_{$class_attribute.id}" value="" />
        <p class="ezobject-relation-no-relation">{'There are no related object.'|i18n( 'design/standard/content/datatype' )}</p>
        <input class="button ezobject-relation-add-button" type="submit" name="CustomActionButton[{$class_attribute.id}_browse_object]" value="{'Add an existing object'|i18n( 'design/standard/content/datatype' )}" title="{'Browse to add an existing object'|i18n( 'design/standard/content/datatype' )}" />
    {/if}
</div>

<div class="block" id="xrowmetadata_object_og_image_{$class_attribute.id}">
    <input type="hidden" name="xrowmetadata_object_og_image_shown_{$class_attribute.id}" value="1" />
    <label><input type="checkbox" name="xrowmetadata_object_og_image_{$class_attribute.id}" value="1" {if $class_attribute.data_int3|ne(1)}checked="checked"{/if} /> {'Allow a per-object Open Graph image'|i18n( 'design/standard/class/datatype' )}</label>
    <p>{'When cleared, every object shares the default image above, the object edit form shows it read-only, and an image an object chose earlier is kept but not used.'|i18n( 'design/standard/class/datatype' )}</p>
</div>

{* The license of the objects of this class: the default (else the installation default applies) and whether an
   object may choose its own. Kept in the class attribute's defaults (data_text5): 'license', 'allow_object_license'. *}
{if xrowmetadata_license_enabled()}
{def $cl_settings = xrowmetadata_license_class_settings( $class_attribute )}
<div class="block xrowmetadata-class-license" id="xrowmetadata_class_license_{$class_attribute.id}">
    <label>{'Default license'|i18n( 'design/standard/class/datatype' )}:</label>
    {include uri='design:xrowmetadata/license_picker.tpl'
             picker_id=concat( 'xrowmetadata_class_license_', $class_attribute.id, '_picker' )
             select_name=concat( 'xrowmetadata_default_license_', $class_attribute.id )
             selected=$cl_settings.default_license
             inherited=xrowmetadata_license_inherited( '' )}
    <p>{'An object without a license of its own states this license; without one here, the default license of the installation applies.'|i18n( 'design/standard/class/datatype' )}</p>
</div>
<div class="block" id="xrowmetadata_object_license_{$class_attribute.id}">
    <input type="hidden" name="xrowmetadata_object_license_shown_{$class_attribute.id}" value="1" />
    <label><input type="checkbox" name="xrowmetadata_object_license_{$class_attribute.id}" value="1" {if $cl_settings.allow_object_license}checked="checked"{/if} /> {'Allow a per-object license'|i18n( 'design/standard/class/datatype' )}</label>
    <p>{'When cleared, every object states the default license above, the object edit form shows it read-only, and a license an object chose earlier is kept but not used.'|i18n( 'design/standard/class/datatype' )}</p>
</div>
{undef $cl_settings}
{/if}

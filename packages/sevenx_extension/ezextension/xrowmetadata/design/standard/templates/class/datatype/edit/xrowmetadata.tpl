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
                        <br /><img src="{concat('/', $mt_image_path)}" alt="{$mt_og_object.name|wash()}" style="max-width:200px; max-height:100px;" />
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

<div class="block">
    <label>{'Default Open Graph image'|i18n( 'design/standard/class/datatype' )}:</label>
    {if $class_attribute.content}
        <p>{$class_attribute.content.name|wash()}</p>
    {else}
        <p><i>{'There are no related object.'|i18n( 'design/standard/content/datatype' )}</i></p>
    {/if}
</div>

<div class="block">
    <label>{'Allow a per-object Open Graph image'|i18n( 'design/standard/class/datatype' )}:</label>
    <p>{if $class_attribute.data_int3|ne(1)}{'Yes'|i18n( 'design/standard/class/datatype' )}{else}{'No, set by the class'|i18n( 'design/standard/class/datatype' )}{/if}</p>
</div>

{if xrowmetadata_license_enabled()}
{def $cl_settings = xrowmetadata_license_class_settings( $class_attribute )
     $cl_info = xrowmetadata_license_info( $cl_settings.default_license )
     $cl_inherited = xrowmetadata_license_inherited( '' )}
<div class="block">
    <label>{'Default license'|i18n( 'design/standard/class/datatype' )}:</label>
    {if $cl_info}
        <p>{$cl_info.name|wash()} ({$cl_info.id|wash()})</p>
    {elseif $cl_settings.default_license|ne('')}
        <p>{'%id (not in the license catalogue)'|i18n( 'design/standard/class/datatype', '', hash( '%id', $cl_settings.default_license|wash() ) )}</p>
    {elseif $cl_inherited}
        <p><i>{'Inherited from installation: %name'|i18n( 'design/standard/class/datatype', '', hash( '%name', $cl_inherited.name|wash() ) )}</i></p>
    {else}
        <p><i>{'None'|i18n( 'design/standard/class/datatype' )}</i></p>
    {/if}
</div>

<div class="block">
    <label>{'Allow a per-object license'|i18n( 'design/standard/class/datatype' )}:</label>
    <p>{if $cl_settings.allow_object_license}{'Yes'|i18n( 'design/standard/class/datatype' )}{else}{'No, set by the class'|i18n( 'design/standard/class/datatype' )}{/if}</p>
</div>
{undef $cl_settings $cl_info $cl_inherited}
{/if}

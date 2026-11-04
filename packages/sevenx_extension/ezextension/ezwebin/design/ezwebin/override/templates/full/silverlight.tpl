<div class="border-box">

<div class="content-view-full">
    <div class="class-silverlight">

    <div class="attribute-header">
        <h1>{$node.name|wash()}</h1>
    </div>

    <div class="attribute-long">
        {attribute_view_gui attribute=$node.data_map.description}
    </div>

    {def $attribute = $node.data_map.file
         $width = '1'
	 $height = '1'}

    {if $attribute.content.width|gt( 0 )}
        {set $width = $attribute.content.width}
    {/if}

    {if $attribute.content.height|gt( 0 )}
        {set $height = $attribute.content.height}
    {/if}

    {* Browsers no longer run the Silverlight plug-in: the application is offered as a download *}
    <p class="content-media-download">
        <a href={concat( "content/download/", $attribute.contentobject_id, "/", $attribute.content.contentobject_attribute_id, "/", $attribute.content.original_filename)|ezurl} download>{$attribute.content.original_filename|wash}</a>
    </p>

    </div>
</div>

</div>

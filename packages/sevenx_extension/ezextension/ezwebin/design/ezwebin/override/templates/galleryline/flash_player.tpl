{* Flash player - Gallery line view *}
<div class="content-view-galleryline">
    <div class="class-flash-player">

    <div class="attribute-image">
    {if $node.data_map.thumbnail.has_content}
        {attribute_view_gui attribute=$node.data_map.thumbnail image_class=gallerythumbnail href=$node.url_alias|ezurl}
    {else}
        <a class="gallery-thumb gallery-thumb-empty attribute-thumbnail" href={$node.url_alias|ezurl}><span class="sr-only">{$node.name|wash}</span></a>
    {/if}
    </div>

    <div class="attribute-name">
        <p>{$node.name|shorten(24)|wash}</p>
    </div>

    </div>
</div>

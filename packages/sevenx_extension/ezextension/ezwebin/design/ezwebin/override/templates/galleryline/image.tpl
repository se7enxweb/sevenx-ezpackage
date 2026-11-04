{* Image - Gallery line view: a thumbnail that opens the image page. In a gallery (data-ezwebin-gallery, full/gallery.tpl)
   ezwebin.js opens it in the slideshow instead: data-ezwebin-src is the large image, the caption is copied from
   .gallery-caption. The thumbnail size comes from the stylesheet (the old inline width/height style is gone). *}
{def $image = $node.data_map.image.content}
<div class="content-view-galleryline">
    <div class="class-image">

    <div class="attribute-image">
    {if $image.is_valid}
        <a class="gallery-thumb" href={$node.url_alias|ezurl} data-ezwebin-slide="{$node.node_id}"
           data-ezwebin-src={$image[imagelarge].full_path|ezroot} data-ezwebin-width="{$image[imagelarge].width}" data-ezwebin-height="{$image[imagelarge].height}">
            <img src={$image[gallerythumbnail].full_path|ezroot} width="{$image[gallerythumbnail].width}" height="{$image[gallerythumbnail].height}" alt="{if $image.alternative_text}{$image.alternative_text|wash}{else}{$node.name|wash}{/if}" loading="lazy" decoding="async">
        </a>
    {else}
        <a class="gallery-thumb gallery-thumb-empty" href={$node.url_alias|ezurl}><span class="sr-only">{$node.name|wash}</span></a>
    {/if}
    </div>

    <div class="attribute-name">
        <p>{$node.name|shorten(24)|wash}</p>
    </div>

    {if $node.data_map.caption.has_content}
    <div class="gallery-caption" hidden>{attribute_view_gui attribute=$node.data_map.caption}</div>
    {/if}

    </div>
</div>
{undef $image}

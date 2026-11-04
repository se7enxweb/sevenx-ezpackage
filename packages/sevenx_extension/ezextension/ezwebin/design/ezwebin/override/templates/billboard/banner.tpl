{* Banner - Billboard view: the hero of a front page. The image fills the band, the banner's name is its headline
   and its URL the call to action; an image map (image_map) keeps the old plain image. *}
{def $size = "billboard"
     $image = $object.data_map.image.content
     $alternative_text = $object.data_map.name.content}
{if $image[$size].alternative_text}
    {set $alternative_text = $image[$size].alternative_text}
{/if}

{if eq( $object.data_map.image_map.content, true() )}
    <img usemap="#banner_map" src={$image[$size].full_path|ezroot} alt="{$alternative_text|wash}" width="{$image[$size].width}" height="{$image[$size].height}">
    {$object.data_map.image_map.content}
{else}
    <div class="hero">
        <img class="hero-image" src={$image[$size].full_path|ezroot} alt="" width="{$image[$size].width}" height="{$image[$size].height}" fetchpriority="high">
        <div class="hero-body">
            <p class="hero-title">{$object.data_map.name.content|wash}</p>
            {if $object.data_map.url.content}
            <a class="hero-cta" href={$object.data_map.url.content|ezurl}>{'More'|i18n( 'design/ezwebin/pagelayout' )}<span aria-hidden="true"> &rarr;</span><span class="sr-only">: {$object.data_map.name.content|wash}</span></a>
            {/if}
        </div>
    </div>
{/if}
{undef $size $image $alternative_text}

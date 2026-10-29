{* The Open Graph image of a page for its head: og:image (with width, height,
   type and alt) and twitter:image. $meta is the result of metadata( <node id> ).
   The image is the object's own choice, else the class default; nothing is
   printed when neither can be shown.
   Parameters: meta (required), twitter (optional, default true). *}
{if and( is_set( $meta ), $meta )}
{def $og_info = $meta.og_image_info}
{if $og_info}
<meta property="og:image" content="{$og_info.url|wash}" />
{if $og_info.url|begins_with( 'https://' )}<meta property="og:image:secure_url" content="{$og_info.url|wash}" />
{/if}
{if $meta.og_image_type|ne('')}<meta property="og:image:type" content="{$meta.og_image_type|wash}" />
{elseif $og_info.type|ne('')}<meta property="og:image:type" content="{$og_info.type|wash}" />
{/if}
{if $og_info.width|ne('')}<meta property="og:image:width" content="{$og_info.width|wash}" />
{/if}
{if $og_info.height|ne('')}<meta property="og:image:height" content="{$og_info.height|wash}" />
{/if}
{if $meta.og_image_alt|ne('')}<meta property="og:image:alt" content="{$meta.og_image_alt|wash}" />
{elseif $og_info.alt|ne('')}<meta property="og:image:alt" content="{$og_info.alt|wash}" />
{/if}
{if or( is_set( $twitter )|not, $twitter )}<meta name="twitter:image" content="{$og_info.url|wash}" />
{/if}
{/if}
{undef $og_info}
{/if}

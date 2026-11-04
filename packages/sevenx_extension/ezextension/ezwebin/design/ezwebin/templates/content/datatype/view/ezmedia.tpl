{* The media datatype. Browsers no longer run plug-ins (Flash, QuickTime, Windows Media, RealPlayer, Silverlight):
   the plug-in types are played by the browser's own <video> player where it can (QuickTime, Windows Media and
   RealPlayer files that are MP4/WebM/MOV), and Flash and Silverlight files are offered as a download. The width,
   height, autoplay, loop and controls settings of the attribute are kept; the player never gets wider than its
   column (content.css). No plug-in scripts, no inline styles. *}
{if $attribute.content.filename}
{def $media_url = concat( "content/download/", $attribute.contentobject_id, "/", $attribute.content.contentobject_attribute_id, "/", $attribute.content.original_filename )|ezurl( 'no' )
     $media_type = $attribute.contentclass_attribute.data_text1}

 {switch name=mediaType match=$media_type}
 {case in=array( 'flash', 'silverlight' )}
 <p class="content-media-download">
     <a href="{$media_url|wash}" download>{$attribute.content.original_filename|wash}</a>
 </p>
 {/case}

 {case match=html5_audio}
 <audio class="content-media-player" src="{$media_url|wash}"
 {if $attribute.content.is_autoplay}autoplay="autoplay"{/if}
 {if $attribute.content.is_loop}loop="loop"{/if}
 {if $attribute.content.has_controller}controls="controls"{/if}
 preload="none">
 {'Your browser does not support html5 audio.'|i18n( 'design/ezwebin/view/ezmedia' )}
 <a href="{$media_url|wash}" download>{$attribute.content.original_filename|wash}</a>
 </audio>
 {/case}

 {case}
 {* html5_video, quick_time, windows_media_player, real_player *}
 <video class="content-media-player" src="{$media_url|wash}"
 {if $attribute.content.width|gt( 0 )}width="{$attribute.content.width}"{/if}
 {if $attribute.content.height|gt( 0 )}height="{$attribute.content.height}"{/if}
 {if $attribute.content.is_autoplay}autoplay="autoplay" muted="muted"{/if}
 {if $attribute.content.is_loop}loop="loop"{/if}
 {if or( $attribute.content.has_controller, $media_type|ne( 'html5_video' ) )}controls="controls"{/if}
 playsinline preload="metadata">
 {'Your browser does not support html5 video.'|i18n( 'design/ezwebin/view/ezmedia' )}
 <a href="{$media_url|wash}" download>{$attribute.content.original_filename|wash}</a>
 </video>
 {/case}
 {/switch}

{undef $media_url $media_type}
{else}
 {'No media file is available.'|i18n( 'design/ezwebin/view/ezmedia' )}
{/if}

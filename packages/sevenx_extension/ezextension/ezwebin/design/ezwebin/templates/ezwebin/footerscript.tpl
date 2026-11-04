{* ezwebin::footerscript::<object id>::<modified>: the footer_script attribute of the site's template look object,
   as the content of a packed script file (page_footer_script.tpl). Only a template_look object gives anything. *}
{def $look = fetch( 'content', 'object', hash( 'object_id', $arguments[0]|int ) )}
{if and( $look, $look.class_identifier|eq( 'template_look' ), is_set( $look.data_map.footer_script ) )}{$look.data_map.footer_script.content}{/if}
{undef $look}

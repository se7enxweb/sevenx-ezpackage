{* The scripts at the end of the page: the design's behavior (ezwebin.js: menus, color mode switch, gallery
   slideshow, keyboard navigation, collapsible attribute groups) and the site's own footer script of the
   template look object (footer_script, e.g. for statistics). That script is not printed inline (a Content
   Security Policy without 'unsafe-inline' would block it): ezjscore packs it into a file of its own through the
   template server function ezwebin::footerscript (design:ezwebin/footerscript.tpl). The object's modification
   time is part of the call, so the packed file is renewed when the script is edited. *}
{ezscript( array( 'ezwebin.js' ) )}
{if and( is_set( $pagedesign.data_map.footer_script ), $pagedesign.data_map.footer_script.has_content )}
{ezscript( array( concat( 'ezwebin::footerscript::', $pagedesign.id, '::', $pagedesign.modified ) ) )}
{/if}
{* Override this template and place javascript based statistics here (like a web analytics tag) *}

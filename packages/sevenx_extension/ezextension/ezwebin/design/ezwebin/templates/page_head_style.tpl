{* The design's stylesheets, packed by ezjscore: core.css (design tokens, light and dark, base elements),
   pagelayout.css (the page grid, header, menus, footer), content.css (the content views), then the site's
   design.ini CSSFileList / FrontendCSSFileList.

   The color stylesheets of the old site design packages (design.ini [StylesheetSettings] SiteCSS and ClassesCSS,
   such as ezwebin_design_blue) were drawn for the fixed-width layout and fight the tokens, so they are only loaded
   where design.ini [EzwebinSettings] LegacySiteCSS=enabled. The package's color is kept: its name (blue, gray,
   cleangray ...) is set as body[data-ezwebin-skin] by pagelayout.tpl and picks the accent tokens of core.css. *}
{if is_unset( $load_css_file_list )}
 {def $load_css_file_list = true()}
{/if}

{if $load_css_file_list}
 {ezcss_load( array( 'core.css',
 'debug.css',
 'pagelayout.css',
 'content.css',
 'websitetoolbar.css',
 ezini( 'StylesheetSettings', 'CSSFileList', 'design.ini' ),
 ezini( 'StylesheetSettings', 'FrontendCSSFileList', 'design.ini' ) ))}
{else}
 {ezcss_load( array( 'core.css',
 'debug.css',
 'pagelayout.css',
 'content.css',
 'websitetoolbar.css' ))}
{/if}

{if ezini( 'EzwebinSettings', 'LegacySiteCSS', 'design.ini' )|eq( 'enabled' )}
<link rel="stylesheet" href={ezini('StylesheetSettings','ClassesCSS','design.ini')|ezroot()}>
<link rel="stylesheet" href={ezini('StylesheetSettings','SiteCSS','design.ini')|ezroot()}>
{/if}
<link rel="stylesheet" href={"stylesheets/print.css"|ezdesign} media="print">

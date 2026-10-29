{* A visible license notice for a page footer or below an article, e.g.
     © 2026 Holder · Licensed under CC BY 4.0
     © 2026 Holder · Licensed under GNU General Public License v2.0 or later
     Dedicated to the public domain with CC0 1.0 Universal
     Public Domain Mark 1.0 · Free of known copyright restrictions
     © 2026 Holder · All rights reserved
   The copyright sign is printed only for a license or "all rights reserved",
   never for public domain content. The license name links to its text when
   the license has an http(s) URL.

   Parameters, all optional:
     node        the node (or node id); default: the page of this request
     license     a result of xrowmetadata_license() already fetched
     rel_license true() to put rel="license" on the link; off by default,
                 since license_head.tpl already states it in the head
     class       CSS class of the element, default xrowmetadata-license-notice
     tag         the element, p (default), div, small or span *}
{if is_set( $license )|not}
    {if is_set( $node )}{def $license = xrowmetadata_license( $node )}{else}{def $license = xrowmetadata_license()}{/if}
{/if}
{if $license}
{def $ln_tag = 'p'}
{if and( is_set( $tag ), array( 'div', 'small', 'span', 'p' )|contains( $tag ) )}{set $ln_tag = $tag}{/if}
{def $ln_class = cond( and( is_set( $class ), $class|ne( '' ) ), $class, 'xrowmetadata-license-notice' )}
{def $ln_name = $license.name|wash}
{if $license.url|ne( '' )}
    {set $ln_name = concat( '<a href="', $license.url|wash, '"', cond( and( is_set( $rel_license ), $rel_license, $license.kind|ne( 'mark' ) ), ' rel="license"', '' ), '>', $license.name|wash, '</a>' )}
{/if}
{def $ln_owner = ''}
{if and( or( $license.kind|eq( 'license' ), $license.kind|eq( 'reserved' ) ), or( $license.holder|ne( '' ), $license.year|ne( '' ) ) )}
    {set $ln_owner = concat( '©', cond( $license.year|ne( '' ), concat( ' ', $license.year|wash ), '' ), cond( $license.holder|ne( '' ), concat( ' ', $license.holder|wash ), '' ) )}
{/if}
<{$ln_tag} class="{$ln_class|wash}" data-license="{$license.id|wash}">{if $ln_owner|ne( '' )}{$ln_owner} · {/if}{switch match=$license.kind}{case match='mark'}{'%license · Free of known copyright restrictions'|i18n( 'extension/xrowmetadata/license', '', hash( '%license', $ln_name ) )}{/case}{case match='dedication'}{'Dedicated to the public domain with %license'|i18n( 'extension/xrowmetadata/license', '', hash( '%license', $ln_name ) )}{/case}{case match='reserved'}{if $license.url|ne( '' )}{'Terms of use: %license'|i18n( 'extension/xrowmetadata/license', '', hash( '%license', $ln_name ) )}{else}{'All rights reserved'|i18n( 'extension/xrowmetadata/license' )}{/if}{/case}{case}{'Licensed under %license'|i18n( 'extension/xrowmetadata/license', '', hash( '%license', $ln_name ) )}{/case}{/switch}</{$ln_tag}>
{undef $ln_tag $ln_class $ln_name $ln_owner}
{/if}

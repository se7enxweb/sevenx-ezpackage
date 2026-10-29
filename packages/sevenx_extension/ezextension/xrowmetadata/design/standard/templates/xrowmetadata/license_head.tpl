{* The content license of a page for its head, printed once per page:
     <link rel="license" href="...">         HTML link type "license"
     <meta name="dcterms.license" ...>       Dublin Core (DC-HTML), with its schema link
     <script type="application/ld+json">     schema.org WebPage "license"
   Open Graph has no license property, so no og: tag is printed.
   A public domain mark (PDM 1.0, US government work, expired copyright) is not
   a license: it gets dcterms.rights and JSON-LD, never rel="license".
   Each part follows [LicenseSettings] ShowLinkRelLicense, ShowInOpenGraph
   (the Dublin Core meta tag) and ShowJsonLd in xrowmetadata.ini.

   Parameters, all optional:
     node     the node (or node id); default: the page of this request
     license  a result of xrowmetadata_license() already fetched
     json_ld  false() when the page layout merges $license.json_ld_node or
              $license.json_ld_property into JSON-LD of its own *}
{if is_set( $license )|not}
    {if is_set( $node )}{def $license = xrowmetadata_license( $node )}{else}{def $license = xrowmetadata_license()}{/if}
{/if}
{if $license}
{if $license.show.link_rel}
<link rel="license" href="{$license.url|wash}" />

{/if}
{if $license.show.meta}
<link rel="schema.dcterms" href="http://purl.org/dc/terms/" />
<meta name="{$license.meta_name|wash}" content="{$license.meta_content|wash}" />

{/if}
{if and( $license.show.json_ld, or( is_set( $json_ld )|not, $json_ld ) )}
{$license.json_ld_script}

{/if}
{/if}

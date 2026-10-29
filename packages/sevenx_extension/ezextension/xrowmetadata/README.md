# Xrow Meta Data

# ﻿Installation

After this the extension has been installed. You should be able to generate the sitemap 
using the runcronjobs.php script. See "xrowsitemap.ini" for more configuration options.

### `php runcronjobs.php sitemap;`

`php runcronjobs.php sitemap;`

	Running cronjob part 'sitemap'
	Running extension/xrowmetadata/cronjobs/sitemap.php at: 14.10.2011 12:31
	Generating Regular Sitemaps...
	
	Generating Sitemap for Siteaccess XYZ 
	
	Adding 578 nodes to the sitemap.
	578 / 578 [+++++++++++++++++++++++++++++++++++++++++++++++++++++++++>] 100,00%
	
	Sitemap var/storage/sitemap/XYZ/urlset_standard_XYZ.xml for siteaccess XYZ has been generated.
	
	Completing extension/xrowmetadata/cronjobs/sitemap.php at: 14.10.2011 12:31
	Elapsed time: 00:00:08

This will create a file for every siteaccess within your eZ Publish root directory. These files are usually named "sitemap_access.xml", but you can change that in the INI file.

Please ensure that your Apache rewrite rules permit access to the XML File. This can be done by adding the following line to your .htaccess or Apache configuration file:

    RewriteRule ^sitemap[^/]*\.xml - [L]

Finally you need to attached the Metadatatype to some of your content classes and add the following code to you head of your HTML document.

{def $meta = metadata( $module_result.node_id ) }
{if $meta}
    {if $meta.title}
        <title>{$meta.title|wash}</title>
    {/if}
    {if $meta.keywords}
    	<meta name="keywords" content="{$meta.keywords|implode(',')|wash}" />
    {/if}
    {if $meta.description}
        <meta name="description" content="{$meta.description|wash}" />
    {/if}
{else} 
    <title>{$site_title}</title>
    {foreach $site.meta as $key => $item }
    <meta name="{$key|wash}" content="{$item|wash}" />
    {/foreach}
{/if}

Register sitemap in robots.txt:

Option 1.)
    Add this to the end of the "robots.txt" file
  
`Sitemap: http://www.example.com/sitemaps/index`

Option 2.)
    Add this to the end of the ".htaccess" file

    RewriteRule ^robots\.txt$ - [C]
    RewriteRule .* http://%{HTTP_HOST}/sitemaps/robots [P]
    
    RewriteRule ^sitemap\.xml$ - [C]
    RewriteRule .* http://%{HTTP_HOST}/sitemaps/index [P]

# Open Graph image

The class attribute can name a default image object (class edit, "Default Open
Graph image"). Each object can choose its own image in the metadata attribute's
edit form ("Choose image", "Remove image"); it overrides the class default.

- The choice is stored in the attribute (`<og_image>` and `data_int`) and as an
  attribute-level object relation, so it shows in the object's relations and
  republishing the image clears the page's view cache (with `reverse_attribute`
  in `[ViewCacheSettings] ClearRelationTypes`).
- An image in the trash, unpublished or deleted is passed over and the class
  default is shown. A deleted image is also taken out of the attribute.
- "Allow a per-object Open Graph image" (class edit, on by default): when it
  is cleared the class sets the image. The object edit form shows the class
  default read-only ("Set by the class") without "Choose image" and "Remove
  image", the page shows the class default only, and storing takes no own
  image. An own image stored earlier stays in the attribute but is not used,
  and its object relation is removed when the attribute is next stored;
  checking the box again brings it back. The switch is kept in the class
  attribute's `data_int3` (`1` = the class sets the image; `0` or empty, as
  in every class saved before 1.4.8, = allowed).
- Width and height: `og:image:width` and `og:image:height` are the size of
  the image alias, printed next to the image in the edit form. The form no
  longer has width and height inputs (an image is always an object, there is
  no manual image URL); values stored by earlier versions are kept in the
  attribute (`<og_image_width>`, `<og_image_height>`) but not printed. Alt
  and type still override the image's own.
- `[OpenGraph]` in `xrowmetadata.ini`: the classes that may be chosen
  (`ImageClasses[]`), where the browser starts, the image attributes searched
  and the image alias (`opengraph`, at most 1200 x 1200, in `image.ini`).
- In the page head:

      {def $meta = metadata( $node.node_id )}
      {include uri='design:xrowmetadata/opengraph_image.tpl' meta=$meta}

  prints `og:image` (absolute URL of the alias, with width, height, type and
  alt) and `twitter:image`. `$meta.og_image` is the id of the image shown,
  `$meta.og_image_info` its URL and size, `$meta.og_image_source` `object` or
  `class`, `$meta.og_image_allowed` whether the class allows an own image.

# Content licenses

Since 1.4.9 a page can state the license of its content. The license is chosen
from a catalogue of 1,332 licenses (the SPDX license list plus every Creative
Commons license ever published, public domain tools and non-free terms):

- per object in the metadata attribute's edit form ("License"), when the class
  allows it ("Allow a per-object license"),
- per class ("Default license" in the class edit),
- per installation (`[LicenseSettings] DefaultLicense` in `xrowmetadata.ini`,
  shipped as `GPL-2.0-or-later`).

The page head prints `<link rel="license">`, `dcterms.license` and the
schema.org `license` in JSON-LD (Open Graph has no license property), and every
response for the page carries `Link: <url>; rel="license"` and
`X-Content-License: <id>` (`[LicenseHeaderSettings]`, on by default). The
template operator `xrowmetadata_license( node )` returns the resolved license,
and `design:xrowmetadata/license_notice.tpl` prints a visible notice.

    {include uri='design:xrowmetadata/license_head.tpl' node=$module_result.node_id}

Everything else is in [doc/licenses.md](doc/licenses.md): levels and
precedence, the catalogue and how to refresh it, settings, output formats, the
HTTP header and caching, troubleshooting.

# Troubleshooting & support

Send email to service [at] xrow [dot] de



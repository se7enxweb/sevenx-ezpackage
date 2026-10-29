<?php /*

[EditorInputSettings]
MaxTitle=65
MaxDescription=150
MaxPageLimit=20

# If enabled, keywords will be hidden in the 'Other options' block
HideKeywords=disabled

[OpenGraph]
# The classes an editor may choose as the Open Graph image of an object,
# in the metadata attribute's edit form ("Choose image").
ImageClasses[]
ImageClasses[]=image

# Where the object browser starts: a node id or a node alias (content, media, users).
BrowseStartNode=media

# The attributes searched, in this order, for the picture of the chosen
# object. An ezimage attribute is shown in the image alias below; when none
# of these has content, the first ezimage attribute with content is used.
ImageAttributes[]
ImageAttributes[]=site_opengraph_image
ImageAttributes[]=image
ImageAttributes[]=site_logo
ImageAttributes[]=file

# The image alias printed as og:image and twitter:image (absolute URL),
# with its width and height. The alias is defined in this extension's
# image.ini.append.php. An alias that cannot be made falls back to original.
ImageAlias=opengraph

# The alias of the preview in the edit form.
ThumbnailAlias=small

# The scheme and host of og:image when the request has no host (a script).
# Empty: https:// and [SiteSettings] SiteURL.
SiteURL=

[LicenseSettings]
# The content license of a page: chosen per object in the metadata attribute
# (when the class allows it), else the class attribute's default license,
# else DefaultLicense below. Licenses are ids of the catalogue
# share/licenses/licenses.json: SPDX ids such as GPL-2.0-or-later, CC-BY-4.0
# or CC0-1.0, and LicenseRef-* ids for what SPDX does not list (ported and
# retired Creative Commons licenses, all rights reserved, custom terms).
# false: no license is resolved, shown or sent anywhere.
Enabled=true

# The installation default, used when neither the object nor its class
# chooses one: GNU GPL v2.0 or later. An override (settings/override or a
# siteaccess) can choose another id, or leave it empty for no default:
#   DefaultLicense=
DefaultLicense=GPL-2.0-or-later

# The copyright holder named with the license when the object names none
# (e.g. the publisher). Empty: none.
DefaultHolder=

# The licenses editors may choose, by group of the catalogue:
#   open_source    approved by the Open Source Initiative
#   free_software  free (libre) according to the Free Software Foundation
#   copyleft       changed versions must keep the license (strong, weak, network)
#   permissive     few conditions besides keeping the notice
#   content        for texts, images, media, documentation and data (every
#                  Creative Commons tool, GFDL, Open Data Commons, ...)
#   public_domain  public domain dedications and marks (CC0, PDM, ...)
#   non_free       non-commercial, no-derivatives, proprietary, custom terms
# A license is allowed when it is in one of the groups. Empty: all groups.
#   AllowedGroups[]=content
#   AllowedGroups[]=public_domain
AllowedGroups[]

# The licenses editors may choose, by id. When not empty only these are
# allowed and AllowedGroups is not used. Empty: all of AllowedGroups.
# The installation default applies whether or not it is listed; an object or
# class license that is not allowed (any more) is passed over, and the next
# level (class default, installation default) applies instead.
#   AllowedLicenses[]=CC-BY-4.0
#   AllowedLicenses[]=CC-BY-SA-4.0
AllowedLicenses[]

# What the page head prints. Open Graph has no license property, so
# ShowInOpenGraph prints <meta name="dcterms.license"> next to the Open Graph
# tags; ShowLinkRelLicense prints <link rel="license">; ShowJsonLd the
# schema.org "license" in the page's JSON-LD.
ShowInOpenGraph=enabled
ShowLinkRelLicense=enabled
ShowJsonLd=enabled

[LicenseHeaderSettings]
# Send the license of the page as HTTP response headers, decided for every
# request (cached pages too) when a license resolves for the URI.
Enabled=enabled

# Link: <url>; rel="license" (RFC 8288), for a license with a URL.
HeaderLink=enabled

# A header with the license id (SPDX id or LicenseRef-*). Empty: not sent.
HeaderName=X-Content-License

# Only for these addresses: globs on the address after the siteaccess, with
# a leading slash ("/news/*" covers /news too). Empty: every address.
IncludePaths[]

# Never for these addresses (same globs).
ExcludePaths[]
ExcludePaths[]=/user/*
ExcludePaths[]=/ezjscore/*
ExcludePaths[]=/content/edit/*

# Only when the request views a node (content/view or its URL alias): any
# other address gets no header, not even the installation default.
OnlyContentViews=enabled

*/ ?>

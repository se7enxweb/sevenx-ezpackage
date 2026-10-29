<?php /*

[TemplateSettings]
ExtensionAutoloadPath[]=xrowmetadata

[RegionalSettings]
TranslationExtensions[]=xrowmetadata

[RoleSettings]
PolicyOmitList[]=sitemaps/index
PolicyOmitList[]=sitemaps/robots

# The license of a page as HTTP headers (Link rel="license", X-Content-License),
# see xrowmetadata.ini [LicenseHeaderSettings]. Every listener does nothing when
# the headers are switched off there.
[Event]
Listeners[]=request/preinput@xrowMetaDataLicenseHeader::reset
Listeners[]=content/view@xrowMetaDataLicenseHeader::noteNode
Listeners[]=response/preoutput@xrowMetaDataLicenseHeader::preOutput
Listeners[]=httpcache/store/headers@xrowMetaDataLicenseHeader::cacheHeaders

*/ ?>
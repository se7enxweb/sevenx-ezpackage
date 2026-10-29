<?php
/**
 * The code of extension/xrowmetadata/cronjobs/newssitemap.php, moved into a class (#207 stage 1). The file extension/xrowmetadata/cronjobs/newssitemap.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 * @description Generate the news XML sitemaps of the configured siteaccesses
 */

namespace Exponential\Cronjob\Extension\Xrowmetadata
{

class Newssitemap extends \Exponential\Runnable\CronjobPart
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $ini = \eZINI::instance( 'site.ini' );
        $xrowsitemapINI = \eZINI::instance( 'xrowsitemap.ini' );

        //getting custom set site access or default access
        if ( $xrowsitemapINI->hasVariable( 'SitemapSettings', 'AvailableSiteAccessList' ) )
        {
            $siteAccessArray = $xrowsitemapINI->variable( 'SitemapSettings', 'AvailableSiteAccessList' );
        }
        else
        {
            $siteAccessArray = array(
                $ini->variable( 'SiteSettings', 'DefaultAccess' )
            );
        }

        if ( $xrowsitemapINI->variable( 'Settings', 'NewsSitemap' ) == 'enabled' )
        {
            if ( ! $isQuiet )
            {
                $cli->output( "Generating News Sitemaps...\n" );
            }
            \xrowSitemapTools::siteaccessCallFunction( $siteAccessArray, 'xrowSitemapTools::createNewsSitemap' );
        }

        \xrowSitemapTools::ping();
    }
}

}

<?php
/**
 * The code of extension/xrowmetadata/modules/sitemaps/robots.php, moved into a class (#207 stage 1). The file extension/xrowmetadata/modules/sitemaps/robots.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */

namespace Exponential\View\Extension\Xrowmetadata\Sitemaps
{

class Robots extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $xrowsitemapINI = \eZINI::instance( 'xrowsitemap.ini' );
        if ( $xrowsitemapINI->hasVariable( 'SitemapSettings', 'RobotsPath' ) )
        {
            $robotspath = $xrowsitemapINI->variable( 'SitemapSettings', 'RobotsPath' );
        }
        else
        {
            $robotspath = 'robots.txt';
        }
        $content = "Sitemap: https://" . $_SERVER['HTTP_HOST'] . "/sitemaps/index\n";

        if ( file_exists( $robotspath ) )
        {
            $content .= file_get_contents( $robotspath );
        }
        else
        {
            $content .= '';
        }

        // Set header settings
        header( 'Content-Type: text/plain; charset=UTF-8' );
        header( 'Content-Length: ' . strlen( $content ) );
        header( 'X-Powered-By: eZ Publish' );

        while ( @ob_end_clean() );

        echo $content;

        \eZExecution::cleanExit();

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}

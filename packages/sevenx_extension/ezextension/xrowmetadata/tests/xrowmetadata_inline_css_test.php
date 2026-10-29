<?php
/**
 * The templates of xrowmetadata carry no inline CSS: no <style> block and no style="" attribute. A
 * Content-Security-Policy without 'unsafe-inline' for style-src drops those. Their rules are in
 * design/standard/stylesheets/xrowmetadata.css, loaded by settings/design.ini.append.php [StylesheetSettings].
 * Reads the files only, without the kernel or a database.
 *
 *   php extension/xrowmetadata/tests/xrowmetadata_inline_css_test.php
 *
 *  XC-01 - no <style> block and no style="" attribute in any template (template comments left out)
 *  XC-02 - design.ini loads xrowmetadata.css for the administration and the public designs, and the file exists
 *  XC-03 - every thumbnail class and every license picker layout class (picker, filter, select, terms, count) is defined in it
 *
 * Prints PASS/FAIL per check and a summary; the exit code is 1 on a failure.
 *
 * @license GNU General Public License v2.0 (or any later version)
 */

$failures = 0;
function xcCheck( $ok, $message )
{
    global $failures;
    echo ( $ok ? 'PASS' : 'FAIL' ), ": $message\n";
    if ( !$ok )
        $failures++;
}

$root = dirname( __DIR__ );
$sheetPath = 'design/standard/stylesheets/xrowmetadata.css';

$templates = array();
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/design', FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $file )
    if ( substr( (string)$file, -4 ) === '.tpl' )
        $templates[substr( (string)$file, strlen( $root ) + 1 )] = preg_replace( '/\{\*.*?\*\}/s', '', file_get_contents( (string)$file ) );
ksort( $templates );

// XC-01
$inline = array();
foreach ( $templates as $path => $source )
    if ( preg_match_all( '/<style\b/i', $source ) + preg_match_all( '/(?<![\w.\-])style\s*=\s*["\'{]/i', $source ) )
        $inline[] = $path;
xcCheck( count( $templates ) > 0 && !$inline, 'XC-01 no inline CSS in ' . count( $templates ) . ' templates' . ( $inline ? ': ' . implode( ', ', $inline ) : '' ) );

// XC-02
$ini = (string)@file_get_contents( $root . '/settings/design.ini.append.php' );
xcCheck( is_file( $root . '/' . $sheetPath )
         && preg_match( '/^\[StylesheetSettings\]\s*$/m', $ini )
         && preg_match( '/^BackendCSSFileList\[\]=xrowmetadata\.css\s*$/m', $ini )
         && preg_match( '/^FrontendCSSFileList\[\]=xrowmetadata\.css\s*$/m', $ini ),
         "XC-02 design.ini loads $sheetPath for the administration and the public designs" );

// XC-03
$sheet = is_file( $root . '/' . $sheetPath ) ? preg_replace( '~/\*.*?\*/~s', '', file_get_contents( $root . '/' . $sheetPath ) ) : '';
$missing = array();
foreach ( $templates as $path => $source )
    if ( preg_match_all( '/class="([^"]*)"/', $source, $m ) )
        foreach ( $m[1] as $list )
            foreach ( preg_split( '/\s+/', trim( preg_replace( '/\{.*?\}/s', ' ', $list ) ) ) as $class )
                if ( preg_match( '/^xrowmetadata-(.*-thumb|license-(picker|filter.*|select|terms|count))$/', $class ) && !preg_match( '/\.' . preg_quote( $class, '/' ) . '(?![\w-])/', $sheet ) )
                    $missing[$class] = true;
xcCheck( !$missing, 'XC-03 every thumbnail and license picker class is defined' . ( $missing ? ': ' . implode( ', ', array_keys( $missing ) ) : '' ) );

echo $failures ? "FAIL: $failures\n" : "PASS: all\n";
exit( $failures ? 1 : 0 );

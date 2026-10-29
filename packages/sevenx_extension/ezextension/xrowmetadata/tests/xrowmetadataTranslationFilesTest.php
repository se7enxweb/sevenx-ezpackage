<?php
/**
 * The translation files of the extension (DB-less): every translations/<locale>/translation.ts is well-formed Qt
 * Linguist XML with a <TS> root, names each source only once per context, and every finished translation carries
 * the same %placeholders as its source, so i18n() can fill them in.
 */

use PHPUnit\Framework\TestCase;

class xrowmetadataTranslationFilesTest extends TestCase
{
    /** %1, %name and the %<name>_start / %<name>_end link markers that are written right next to a word */
    const PLACEHOLDER = '/%(?:\d+|[a-z_]+?_(?:start|end)|[A-Za-z_][A-Za-z0-9_]*)/';

    public static function files()
    {
        $files = array();
        foreach ( glob( dirname( __DIR__ ) . '/translations/*/translation.ts' ) as $file )
            $files[basename( dirname( $file ) )] = array( $file );
        return $files;
    }

    protected static function placeholders( $text )
    {
        preg_match_all( self::PLACEHOLDER, (string)$text, $m );
        $list = array_values( array_unique( $m[0] ) );
        sort( $list );
        return $list;
    }

    public function testThereAreTranslationFiles()
    {
        $this->assertNotEmpty( self::files() );
    }

    public function testTranslationFilesAreValid()
    {
        foreach ( self::files() as $args )
            $this->checkFile( $args[0] );
    }

    protected function checkFile( $file )
    {
        $doc = new DOMDocument();
        $this->assertTrue( @$doc->load( $file ), "$file is not well-formed XML" );
        $this->assertSame( 'TS', $doc->documentElement->nodeName );
        $xpath = new DOMXPath( $doc );
        foreach ( $xpath->query( '/TS/context' ) as $context )
        {
            $name = $xpath->evaluate( 'string(name)', $context );
            $seen = array();
            foreach ( $xpath->query( 'message', $context ) as $message )
            {
                $source = $xpath->evaluate( 'string(source)', $message );
                $key = $source . "\0" . $xpath->evaluate( 'string(comment)', $message );
                $this->assertArrayNotHasKey( $key, $seen, "$file: '$source' twice in $name" );
                $seen[$key] = true;
                $translation = $xpath->query( 'translation', $message )->item( 0 );
                if ( !$translation || $translation->getAttribute( 'type' ) !== '' || trim( $translation->textContent ) === '' )
                    continue;
                $this->assertSame( self::placeholders( $source ), self::placeholders( $translation->textContent ),
                                   "$file ($name): the translation of '$source' has other placeholders" );
            }
        }
    }
}

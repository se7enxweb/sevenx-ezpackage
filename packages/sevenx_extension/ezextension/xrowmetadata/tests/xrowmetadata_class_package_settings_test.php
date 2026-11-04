<?php
/**
 * Unit tests of the class-level settings in a package: serializeContentClassAttribute()
 * writes the default license, the per-object license switch and the Open Graph image
 * source into <datatype-parameters>, unserializeContentClassAttribute() reads them back,
 * and a package without them (empty <datatype-parameters/>) leaves a class attribute as
 * it was. Nothing is read from or written to the database: the class attributes are stub
 * objects and no default image is set.
 *
 *   php bin/php/ezexec.php extension/xrowmetadata/tests/xrowmetadata_class_package_settings_test.php -s admin
 *
 * Prints PASS/FAIL per check and a summary; the exit code is 1 on a failure.
 *
 * @license GNU General Public License v2.0 (or any later version)
 */

if ( !class_exists( 'xrowMetaDataType', false ) )
{
    require_once dirname( __DIR__ ) . '/datatypes/xrowmetadata/xrowmetadatatype.php';
}

class xrowMetaDataClassPackageTestClassAttribute
{
    public $values;
    function __construct( array $values = array() ) { $this->values = $values; }
    function attribute( $name ) { return isset( $this->values[$name] ) ? $this->values[$name] : null; }
    function setAttribute( $name, $value ) { $this->values[$name] = $value; }
}

$failures = 0;
$check = function ( $condition, $message ) use ( &$failures )
{
    echo ( $condition ? 'PASS ' : 'FAIL ' ), $message, "\n";
    if ( !$condition )
    {
        $failures++;
    }
};

$parameters = function ( $type, $classAttribute )
{
    $dom = new DOMDocument( '1.0', 'utf-8' );
    $attributeNode = $dom->createElement( 'attribute' );
    $parametersNode = $dom->createElement( 'datatype-parameters' );
    $attributeNode->appendChild( $parametersNode );
    $dom->appendChild( $attributeNode );
    $type->serializeContentClassAttribute( $classAttribute, $attributeNode, $parametersNode );
    return array( $attributeNode, $parametersNode, $dom->saveXML( $parametersNode ) );
};
$read = function ( $xml )
{
    $dom = new DOMDocument( '1.0', 'utf-8' );
    $dom->loadXML( '<attribute>' . $xml . '</attribute>' );
    return array( $dom->documentElement, $dom->documentElement->getElementsByTagName( 'datatype-parameters' )->item( 0 ) );
};

$type = new xrowMetaDataType();

// 1. Export of a configured class attribute
$configured = new xrowMetaDataClassPackageTestClassAttribute( array(
    'data_text5' => serialize( array( 'license' => 'GPL-2.0-or-later', 'allow_object_license' => 0 ) ),
    'data_int3' => 1, 'data_int4' => 0 ) );
list( , , $xml ) = $parameters( $type, $configured );
$check( $xml === '<datatype-parameters><default-license>GPL-2.0-or-later</default-license><allow-object-license>false</allow-object-license><og-image-source>class</og-image-source></datatype-parameters>',
        "export of a configured class attribute: $xml" );

// 2. Export of a class attribute saved before the settings existed
$old = new xrowMetaDataClassPackageTestClassAttribute( array( 'data_text5' => '', 'data_int3' => null, 'data_int4' => 0 ) );
list( , , $xml ) = $parameters( $type, $old );
$check( $xml === '<datatype-parameters><default-license></default-license><allow-object-license>true</allow-object-license><og-image-source>object</og-image-source></datatype-parameters>',
        "export of an unconfigured class attribute: $xml" );

// 3. Import sets every setting
list( $attributeNode, $parametersNode ) = $read( '<datatype-parameters><default-license>GPL-2.0-or-later</default-license><allow-object-license>true</allow-object-license><og-image-source>object</og-image-source></datatype-parameters>' );
$new = new xrowMetaDataClassPackageTestClassAttribute( array( 'data_text5' => '', 'data_int3' => 1, 'data_int4' => 0 ) );
$type->unserializeContentClassAttribute( $new, $attributeNode, $parametersNode );
$default = xrowMetaDataType::classAttributeDefault( $new );
$check( isset( $default['license'] ) && $default['license'] === 'GPL-2.0-or-later', 'import: default license GPL-2.0-or-later' );
$check( isset( $default['allow_object_license'] ) && (int) $default['allow_object_license'] === 1, 'import: per-object license allowed' );
$check( (int) $new->attribute( 'data_int3' ) === 0, 'import: og-image-source object -> data_int3 0' );

// 4. Round trip: what is exported imports to the same settings
list( $attributeNode, $parametersNode ) = $parameters( $type, $configured );
$copy = new xrowMetaDataClassPackageTestClassAttribute( array( 'data_text5' => '', 'data_int3' => 0, 'data_int4' => 0 ) );
$type->unserializeContentClassAttribute( $copy, $attributeNode, $parametersNode );
$check( xrowMetaDataType::classLicenseSettings( $copy ) === xrowMetaDataType::classLicenseSettings( $configured )
        && (int) $copy->attribute( 'data_int3' ) === 1, 'round trip keeps license, per-object license and og source' );

// 5. An empty <datatype-parameters/> changes nothing
list( $attributeNode, $parametersNode ) = $read( '<datatype-parameters/>' );
$kept = new xrowMetaDataClassPackageTestClassAttribute( array(
    'data_text5' => serialize( array( 'license' => 'CC-BY-4.0' ) ), 'data_int3' => 1, 'data_int4' => 0 ) );
$type->unserializeContentClassAttribute( $kept, $attributeNode, $parametersNode );
$default = xrowMetaDataType::classAttributeDefault( $kept );
$check( isset( $default['license'] ) && $default['license'] === 'CC-BY-4.0' && !isset( $default['allow_object_license'] )
        && (int) $kept->attribute( 'data_int3' ) === 1, 'an older package without the settings leaves the class attribute as it was' );

// 6. A license not in the catalogue is not taken
list( $attributeNode, $parametersNode ) = $read( '<datatype-parameters><default-license>No-Such-License-1.0</default-license></datatype-parameters>' );
$unknown = new xrowMetaDataClassPackageTestClassAttribute( array( 'data_text5' => '', 'data_int3' => 0, 'data_int4' => 0 ) );
$type->unserializeContentClassAttribute( $unknown, $attributeNode, $parametersNode );
$default = xrowMetaDataType::classAttributeDefault( $unknown );
$check( !isset( $default['license'] ), 'a license not in the catalogue is not set' );

echo $failures ? "FAIL $failures check(s)\n" : "PASS all checks\n";
if ( $failures )
{
    exit( 1 );
}

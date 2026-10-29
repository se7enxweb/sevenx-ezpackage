<?php

/**
 * Template operators of the license picker (xrowMetaDataLicensePicker), for
 * templates that have no metadata attribute content at hand, such as the
 * class edit form:
 *
 *   xrowmetadata_license_enabled()               true when licenses can be chosen
 *   xrowmetadata_license_choices( $selected )    the option groups of the picker
 *   xrowmetadata_license_info( $id )             a catalogue entry, or false
 *   xrowmetadata_license_inherited( $class_id )  class default, else installation default, or false
 *   xrowmetadata_license_class_settings( $class_attribute )  array( default_license, allow_object_license )
 */
class xrowMetaDataLicensePickerOperator
{
    function operatorList()
    {
        return array( 'xrowmetadata_license_enabled', 'xrowmetadata_license_choices', 'xrowmetadata_license_info', 'xrowmetadata_license_inherited', 'xrowmetadata_license_class_settings' );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array(
            'xrowmetadata_license_enabled' => array(),
            'xrowmetadata_license_choices' => array(
                'selected' => array( 'type' => 'string', 'required' => false, 'default' => '' ) ),
            'xrowmetadata_license_info' => array(
                'id' => array( 'type' => 'string', 'required' => false, 'default' => '' ) ),
            'xrowmetadata_license_inherited' => array(
                'class_default' => array( 'type' => 'string', 'required' => false, 'default' => '' ) ),
            'xrowmetadata_license_class_settings' => array(
                'class_attribute' => array( 'type' => 'object', 'required' => true, 'default' => null ) ),
        );
    }

    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters )
    {
        switch ( $operatorName )
        {
            case 'xrowmetadata_license_enabled':
                $operatorValue = xrowMetaDataLicensePicker::enabled();
                break;
            case 'xrowmetadata_license_choices':
                $operatorValue = xrowMetaDataLicensePicker::choices( $namedParameters['selected'] );
                break;
            case 'xrowmetadata_license_info':
                $operatorValue = xrowMetaDataLicensePicker::info( $namedParameters['id'] );
                break;
            case 'xrowmetadata_license_inherited':
                $operatorValue = xrowMetaDataLicensePicker::inherited( $namedParameters['class_default'] );
                break;
            case 'xrowmetadata_license_class_settings':
                $classAttribute = $namedParameters['class_attribute'];
                $operatorValue = $classAttribute instanceof eZContentClassAttribute
                    ? xrowMetaDataType::classLicenseSettings( $classAttribute )
                    : array( 'default_license' => '', 'allow_object_license' => true );
                break;
        }
    }
}

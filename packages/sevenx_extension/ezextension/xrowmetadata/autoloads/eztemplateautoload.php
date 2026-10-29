<?php

$eZTemplateOperatorArray = array();
$eZTemplateOperatorArray[] = array(
    'script' => 'extension/xrowmetadata/autoloads/xrowmetadataoperator.php' ,
    'class' => 'xrowMetaDataOperator' ,
    'operator_names' => array(
        'metadata'
    )
);
$eZTemplateOperatorArray[] = array(
    'script' => 'extension/xrowmetadata/autoloads/xrowmetadatalicenseoperator.php' ,
    'class' => 'xrowMetaDataLicenseOperator' ,
    'operator_names' => array(
        'xrowmetadata_license'
    )
);
// The license picker of the edit forms
$eZTemplateOperatorArray[] = array(
    'script' => 'extension/xrowmetadata/autoloads/xrowmetadatalicensepickeroperator.php' ,
    'class' => 'xrowMetaDataLicensePickerOperator' ,
    'operator_names' => array( 'xrowmetadata_license_enabled', 'xrowmetadata_license_choices', 'xrowmetadata_license_info', 'xrowmetadata_license_inherited', 'xrowmetadata_license_class_settings' )
);

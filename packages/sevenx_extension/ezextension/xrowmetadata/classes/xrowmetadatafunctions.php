<?php

class xrowMetaDataFunctions
{
    static function fetchByNode( eZContentObjectTreeNode $node )
    {
        $attributes = $node->attribute( 'data_map' );
        foreach ( $attributes as $attribute )
        {
            if ( $attribute->DataTypeString == 'xrowmetadata' and $attribute->hasContent() )
            {
                return $attribute->content();
            }
        }
        return false;
    }

    static function fetchByObject( eZContentObject $object )
    {
        $attributes = $object->fetchDataMap();
        foreach ( $attributes as $attribute )
        {
            if ( $attribute->DataTypeString == 'xrowmetadata' and $attribute->hasContent() )
            {
                return $attribute->content();
            }
        }

        $class = $object->attribute( 'content_class' );
        if ( !$class )
        {
            return false;
        }
        foreach ( $class->fetchAttributes() as $classAttribute )
        {
            if ( $classAttribute->attribute( 'data_type_string' ) == 'xrowmetadata' )
            {
                $dataType = eZDataType::create( 'xrowmetadata' );
                $default = $dataType->classAttributeDefault( $classAttribute );
                if ( isset( $default['og_image'] ) and (int) $default['og_image'] > 0 )
                {
                    $meta = new xrowMetaData();
                    $meta->og_image = (int) $default['og_image'];
                    return $meta;
                }
            }
        }
        return false;
    }
}

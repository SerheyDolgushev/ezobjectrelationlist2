<?php

/**
 * @class   eZObjectRelationList2Type
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    18 Jul 2014
 * */
class eZObjectRelationList2Type extends eZObjectRelationListType {

    const DATA_TYPE_STRING = 'ezobjectrelationlist2';

    public function __construct() {
        $this->eZDataType(
            self::DATA_TYPE_STRING, ezpI18n::tr( 'kernel/classes/datatypes', 'Object relations 2' ), array( 'serialize_supported' => true )
        );
    }

    public function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute ) {
        $r = parent::fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute );
        if( $r === false ) {
            return $r;
        }

        $postVariableName  = $base . '_relation_extra_data_' . $contentObjectAttribute->attribute( 'id' );
        $relationExtraData = $http->postVariable( $postVariableName, array() );
        $content           = $contentObjectAttribute->content();

        foreach( $content['relation_list'] as $key => $relation ) {
            $extraData = array();
            if( isset( $relationExtraData[$relation['contentobject_id']] ) ) {
                $extraData = $relationExtraData[$relation['contentobject_id']];
            }

            $content['relation_list'][$key] = array_merge( $extraData, $relation );
        }

        $contentObjectAttribute->setContent( $content );

        return true;
    }

    public static function createObjectDOMDocument( $content ) {
        $doc                  = new DOMDocument( '1.0', 'utf-8' );
        $root                 = $doc->createElement( 'related-objects' );
        $relationList         = $doc->createElement( 'relation-list' );
        $attributeDefinitions = static::contentObjectArrayXMLMap();

        foreach( $content['relation_list'] as $relationItem ) {
            unset( $relationElement );
            $relationElement = $doc->createElement( 'relation-item' );

            foreach( $relationItem as $attributeKey => $value ) {
                if( is_scalar( $value ) === false ) {
                    continue;
                }

                if( in_array( $attributeKey, $attributeDefinitions ) ) {
                    if( $value === false ) {
                        continue;
                    }

                    $attributeKey = array_search( $attributeKey, $attributeDefinitions );
                    $relationElement->setAttribute( $attributeKey, $value );
                } else {
                    $extraAttribute = $doc->createElement( $attributeKey );
                    $extraAttribute->appendChild( $doc->createCDATASection( $value ) );
                    $relationElement->appendChild( $extraAttribute );
                }
            }

            $relationList->appendChild( $relationElement );
        }
        $root->appendChild( $relationList );
        $doc->appendChild( $root );
        return $doc;
    }

    public function createObjectContentStructure( $doc ) {
        $content      = $this->defaultObjectAttributeContent();
        $root         = $doc->documentElement;
        $relationList = $root->getElementsByTagName( 'relation-list' )->item( 0 );
        if( $relationList ) {
            $contentObjectArrayXMLMap = $this->contentObjectArrayXMLMap();
            $relationItems            = $relationList->getElementsByTagName( 'relation-item' );
            foreach( $relationItems as $relationItem ) {
                $hash = array();

                foreach( $relationItem->childNodes as $childNode ) {
                    $hash[$childNode->tagName] = $childNode->nodeValue;
                }

                foreach( $contentObjectArrayXMLMap as $attributeXMLName => $attributeKey ) {
                    $attributeValue      = $relationItem->hasAttribute( $attributeXMLName ) ? $relationItem->getAttribute( $attributeXMLName ) : false;
                    $hash[$attributeKey] = $attributeValue;
                }

                $content['relation_list'][] = $hash;
            }
        }

        return $content;
    }

    public static function getRelationExtraAttributes( $attr ) {
        $ini = eZINI::instance( 'ezobjectrelationlist2.ini' );

        $classAttr = $attr->attribute( 'contentclass_attribute' );
        if( $classAttr instanceof eZContentClassAttribute === false ) {
            return array();
        }

        $class = eZContentClass::fetch( $classAttr->attribute( 'contentclass_id' ) );
        if( $class instanceof eZContentClass === false ) {
            return array();
        }

        $iniGroup = 'RelationAttributes_' . $class->attribute( 'identifier' );
        if( $ini->hasVariable( $iniGroup, $classAttr->attribute( 'identifier' ) ) === false ) {
            return array();
        }

        return $ini->variable( $iniGroup, $classAttr->attribute( 'identifier' ) );
    }

}

eZDataType::register( eZObjectRelationList2Type::DATA_TYPE_STRING, 'eZObjectRelationList2Type' );

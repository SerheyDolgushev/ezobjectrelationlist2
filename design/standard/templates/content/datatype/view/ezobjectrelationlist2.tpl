{section show=$attribute.content.relation_list}
{def
    $relation_extra_attributes = array()
    $c_class = fetch( 'content', 'class', hash( 'class_id', $attribute.contentclass_attribute.contentclass_id ) )
}
{if ezini_hasvariable( concat( 'RelationAttributes_', $c_class.identifier ), $attribute.contentclass_attribute_identifier, 'ezobjectrelationlist2.ini' )}
    {set $relation_extra_attributes = ezini( concat( 'RelationAttributes_', $c_class.identifier ), $attribute.contentclass_attribute_identifier, 'ezobjectrelationlist2.ini' )}
{/if}

{section var=Relations loop=$attribute.content.relation_list}
{if $Relations.item.in_trash|not()}
    {content_view_gui view=embed content_object=fetch( content, object, hash( object_id, $Relations.item.contentobject_id ) )}{if $relation_extra_attributes|count} ({foreach $relation_extra_attributes as $attr => $name}{$name|i18n( 'design/standard/content/datatype' )}: {$Relations.item[$attr]}{delimiter},&nbsp;{/delimiter}{/foreach}){/if}<br />
{/if}
{/section}
{undef $relation_extra_attributes $c_class}
{section-else}
{'There are no related objects.'|i18n( 'design/standard/content/datatype' )}
{/section}
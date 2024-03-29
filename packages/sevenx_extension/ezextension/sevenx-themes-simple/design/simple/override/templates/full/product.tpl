{* Product - Full view *}

<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<form method="post" action={"content/action"|ezurl}>
<div class="content-view-full">
    <div class="class-product">

        <div class="attribute-header">
        <h1>{$node.name|wash()}</h1>
        </div>
        {if $node.data_map.images.has_content}
{if $node.data_map.images.content.relation_list}
<div class="attribute-image">
{* $node.data_map.images.content.relation_list.0|attribute(show,1)}
{$node.data_map.images.object|attribute(show,1) *}
            {foreach $node.data_map.images.content.relation_list as $item sequence array( 'bglight', 'bgdark' ) as $style}
              {def $object = fetch( content, object, hash( object_id, $item.contentobject_id ) )}
	      {*$object.data_map|attribute(show,1)*}
	      {attribute_view_gui image_class=large attribute=$object.data_map.image}
              {undef $object}
            {/foreach}
{/if}
            {if $node.data_map.caption.has_content}
            <div class="caption">
                {attribute_view_gui attribute=$node.data_map.caption}
            </div>
            {/if}
        </div>
        {else}
        {if $node.data_map.image.has_content}
        <div class="attribute-image">
            {attribute_view_gui image_class=large attribute=$node.data_map.image}
            {if $node.data_map.caption.has_content}
            <div class="caption">
                {attribute_view_gui attribute=$node.data_map.caption}
            </div>
            {/if}
        </div>
        {/if}
        {/if}

        {if true()}
        <div class="attribute-rating">
           {attribute_view_gui attribute=$node.object.data_map.rating}
        </div>
        {/if}

        <div class="attribute-short">
           {attribute_view_gui attribute=$node.object.data_map.short_description}
        </div>

        <div class="attribute-long">
           {attribute_view_gui attribute=$node.object.data_map.description}
        </div>

        {if $node.data_map.product_number.has_content}
        <div class="attribute-product-number">
           Product Number: {attribute_view_gui attribute=$node.object.data_map.product_number}
        </div>
        {/if}

        {if $node.data_map.product_size.has_content}
        <div class="attribute-product-size">
           Package Size: {attribute_view_gui attribute=$node.object.data_map.product_size}
        </div>
        {/if}
        <div class="attribute-price">
          <p>
           {attribute_view_gui attribute=$node.object.data_map.price}
          </p>
        </div>

        <div class="attribute-multi-options">
           {attribute_view_gui attribute=$node.object.data_map.additional_options}
        </div>

        {* Category. *}
        {def $product_category_attribute=ezini( 'VATSettings', 'ProductCategoryAttribute', 'shop.ini' )}
        {if and( $product_category_attribute, is_set( $node.data_map.$product_category_attribute ) )}
        <div class="attribute-long">
          <p>Category:&nbsp;{attribute_view_gui attribute=$node.data_map.$product_category_attribute}</p>
        </div>
        {/if}
        {undef $product_category_attribute}

        <div class="content-action">
            <input type="submit" class="defaultbutton" name="ActionAddToBasket" value="{"Add to basket"|i18n("design/ezwebin/full/product")}" />
{*
            <input class="button" type="submit" name="ActionAddToWishList" value="{"Add to wish list"|i18n("design/ezwebin/full/product")}" />
*}
            <input type="hidden" name="ContentNodeID" value="{$node.node_id}" />
            <input type="hidden" name="ContentObjectID" value="{$node.object.id}" />
            <input type="hidden" name="ViewMode" value="full" />
        </div>

       {* Related products. *}
       {def $related_purchase=fetch( 'shop', 'related_purchase', hash( 'contentobject_id', $node.object.id, 'limit', 10 ) )}
       {if $related_purchase}
        <div class="relatedorders">
            <h2>{'People who bought this also bought'|i18n( 'design/ezwebin/full/product' )}</h2>

            <ul>
            {foreach $related_purchase as $product}
                <li>{content_view_gui view=text_linked content_object=$product}</li>
            {/foreach}
            </ul>
        </div>
       {/if}
       {undef $related_purchase}
   </div>
</div>
</form>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>

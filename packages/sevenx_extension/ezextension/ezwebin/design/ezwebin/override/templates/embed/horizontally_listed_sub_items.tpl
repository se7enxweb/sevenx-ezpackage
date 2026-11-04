{* Embed - Horizontally listed sub items: the first children of the embedded object as a row of cards (three per
   row on wide screens, one on phones), in their order. object_parameters: limit (default 3), offset. *}
<div class="content-view-embed embed-cards">
    {def $limit = 3
         $offset = 0}
    {if is_set( $object_parameters.limit )}
        {set $limit = $object_parameters.limit}
    {/if}
    {if is_set( $object_parameters.offset )}
        {set $offset = $object_parameters.offset}
    {/if}
    {def $children = fetch( 'content', 'list', hash( 'parent_node_id', $object.main_node_id,
                                                    'limit', $limit,
                                                    'offset', $offset,
                                                    'class_filter_type', 'exclude',
                                                    'class_filter_array', ezini( 'MenuContentSettings', 'ExtraIdentifierList', 'menu.ini' ),
                                                    'sort_by', $object.main_node.sort_array ) )}
    <div class="section-heading">
        <h2>{$object.name|wash()}</h2>
        <a class="section-more" href={$object.main_node.url_alias|ezurl}>{'Show all'|i18n( 'design/ezwebin/pagelayout' )}<span class="sr-only">: {$object.name|wash()}</span></a>
    </div>
    <div class="card-grid">
    {foreach $children as $child}
        {node_view_gui view=horizontallylistedsubitems content_node=$child}
    {/foreach}
    </div>
    {undef $children $limit $offset}
</div>

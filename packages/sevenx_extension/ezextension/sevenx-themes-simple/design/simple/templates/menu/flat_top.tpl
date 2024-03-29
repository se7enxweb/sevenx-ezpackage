<div class="topmenu-design">
    <!-- Top menu content: START -->
	<ul>
    {def $root_node=fetch( 'content', 'node', hash( 'node_id', $indexpage ) )
         $top_menu_items=fetch( 'content', 'list', hash( 'parent_node_id', $root_node.node_id,
		 												 'sort_by', $root_node.sort_array,
		 												 'class_filter_type', 'include',
		 												 'class_filter_array', ezini( 'MenuContentSettings', 'TopIdentifierList', 'menu.ini' ) ) )
		 $top_menu_items_count = $top_menu_items|count()
		 $item_class = array()
         $current_node_in_path = cond(and($current_node_id, gt($module_result.path|count, $pagerootdepth)), $module_result.path[$pagerootdepth].node_id, 0  )}
    {set $pagerootdepth = $root_node.depth}

    {if $top_menu_items_count}
       {foreach $top_menu_items as $key => $item}
            {set $item_class = cond($current_node_in_path|eq($item.node_id), array("selected"), array())}
            {if $key|eq(0)}
                {set $item_class = $item_class|append("firstli")}
            {/if}
            {if $top_menu_items_count|eq( $key|inc )}
                {set $item_class = $item_class|append("lastli")}
            {/if}
            {if $item.node_id|eq( $current_node_id )}
                {set $item_class = $item_class|append("current")}
            {/if}

            {if eq( $item.class_identifier, 'link')}
            <li id="node_id_{$item.node_id}"{if $item_class} class="{$item_class|implode(" ")}"{/if}><div><a href={if eq( $ui_context, 'browse' )}{concat("content/browse/", $item.node_id)|ezurl}{else}{$item.data_map.location.content|ezurl}{/if} {* target="_blank" *} {if eq( $ui_context, 'edit' )} onclick="return false;"{/if}><span>{$item.name|wash()}</span></a></div></li>
            {else}
      		<li id="node_id_{$item.node_id}"{if $item_class} class="{$item_class|implode(" ")}"{/if}><div><a href={if eq( $ui_context, 'browse' )}{concat("content/browse/", $item.node_id)|ezurl}{else}{$item.url_alias|ezurl}{/if}{if eq( $ui_context, 'edit' )} onclick="return false;"{/if}><span>{$item.name|wash()}</span></a></div></li>
    		{/if}
      	{/foreach}
    {/if}
    {undef $root_node $top_menu_items $item_class $top_menu_items_count $current_node_in_path}
    </ul>
    <!-- Top menu content: END -->
</div>

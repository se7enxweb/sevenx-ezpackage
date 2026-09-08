{* DropDown Menu : START *}

{def $root_node = fetch( 'content', 'node', hash( 'node_id', $indexpage ) )
     $top_menu_class_filter = ezini( 'MenuContentSettings', 'TopIdentifierList', 'menu.ini' )
     $top_menu_items = fetch( 'content', 'list', hash('parent_node_id', $root_node.node_id,
                                                      'sort_by', $root_node.sort_array,
							      'class_filter_type', 'include', 'class_filter_array', $top_menu_class_filter, 'ignore_visibility', false() ))
     $top_menu_items_count = $top_menu_items|count()
     $item_class = array()
     $level_2_items = 0
     $level_2_items_count = 0
     $level_3_items = 0
     $level_3_items_count = 0
     $top_level_exclude_items = array( 180 )
     $level_2_limit = 7
     $current_node_id = first_set($module_result.node_id, 0 )
     $path_node_ids = array()}
{foreach $module_result.path as $path_node}
  {set $path_node_ids = $path_node_ids|append($path_node.node_id)}
{/foreach}
<ul class="navbar-nav">
{if $top_menu_items_count}
{foreach $top_menu_items as $key => $item}
{set $item_class = array()}
{set $item_class = $item_class|append("nav-link")}
{set $level_2_items = fetch('content', 'list', hash( 'parent_node_id', $item.node_id, 'sort_by', array( 'priority', false() ), 'class_filter_type', 'include', 'class_filter_array', $top_menu_class_filter, 'limit', $level_2_limit, 'ignore_visibility', false() ))}
{set $level_2_items_count = $level_2_items|count()}
{if $path_node_ids|contains($item.node_id)}
{set $item_class = $item_class|append("active")}
{/if}
{if $key|eq(0)}
{set $item_class = $item_class|append("firstli")}
{/if}
{if $top_menu_items_count|eq($key|inc)}
{set $item_class = $item_class|append("lastli")}
{/if}
{if $item.node_id|eq($current_node_id)}
{set $item_class = $item_class|append("current")}
{/if}

{if eq( $item.class_identifier, 'link')}

<li class="dropdown {if $path_node_ids|contains($item.node_id)}active{/if}{if $item.node_id|eq($current_node_id)} current{/if}" id="node_id_{$item.node_id}">
<a 
  {if eq( $ui_context, 'browse' )}
href={concat("content/browse/", $item.node_id)|ezurl}
  {else}
href={$item.data_map.location.content|ezurl}
{if and( is_set( $item.data_map.open_in_new_window ), $item.data_map.open_in_new_window.data_int )}
 target="_blank"
{/if}
  {/if}
  {if $pagedata.is_edit}
onclick="return false;"
  {/if}
  title="{$item.data_map.location.data_text|wash}"
  {if $item_class}class="{$item_class|implode(" ")}"{/if}
  rel={$item.url_alias|ezurl}>
{if $item.data_map.location.data_text}{$item.data_map.location.data_text|wash()}{else}{$item.name|wash()}{/if}</a>
  </li>
{else}
{if or( $item.children_count|eq(0), $item.node_id|eq( 180 ) )}
<li class="dropdown {if $path_node_ids|contains($item.node_id)}active{/if}{if $item.node_id|eq($current_node_id)} current{/if}">
  <a role="button" href={if eq( $ui_context, 'browse' )}{concat("content/browse/", $item.node_id)|ezurl}{else}{$item.url_alias|ezurl}{/if}
 {if $item_class}class="{$item_class|implode(" ")}"{/if}
 {if $pagedata.is_edit}
  onclick="return false;"
 {/if}>{$item.name|wash()}</a>
{else}
<li class="dropdown {if $path_node_ids|contains($item.node_id)}active{/if}{if $item.node_id|eq($current_node_id)} current{/if}">
  <div class="dropdown-button">
    <a href={$item.url_alias|ezurl} role="button">{$item.name|wash()}</a>
    <button type="button" class="dropdown-toggle" aria-label="dropdown toggler button"></button>
  </div>

{/if}
{/if}

{if $top_level_exclude_items|contains( $item.node_id )}{continue}{/if}

{if $level_2_items|count()}
<div class="dropdown-menu">
<div>
<ul class="dropdown-nav">

{foreach $level_2_items as $key => $item}
 {set $item_class = array()}
 {set $item_class = $item_class|append("dropdown-link")}
 {set $level_3_items = fetch('content', 'list', hash( 'parent_node_id', $item.node_id, 'sort_by', array( 'priority', false() ), 'class_filter_type', 'include', 'class_filter_array', $top_menu_class_filter, 'limit', $level_2_limit, 'ignore_visibility', false() ))}
 {set $level_3_items_count = $level_3_items|count()}
 {if $path_node_ids|contains($item.node_id)}
  {set $item_class = $item_class|append("active")}
 {/if}
 {if $key|eq(0)}
  {set $item_class = $item_class|append("firstli")}
 {/if}
 {if $level_2_items_count|eq( $key|inc )}
  {set $item_class = $item_class|append("lastli")}
 {/if}
 {if $item.node_id|eq( $current_node_id )}
  {set $item_class = $item_class|append("current")}
 {/if}
{if eq( $item.class_identifier, 'link')}
<li id="node_id_{$item.node_id}" class="{if $path_node_ids|contains($item.node_id)}active{/if}{if $item.node_id|eq($current_node_id)} current{/if}">
<a
{if eq( $ui_context, 'browse' )} href={concat("content/browse/", $item.node_id)|ezurl}{else} href={$item.data_map.location.content|ezurl}
{if and( is_set( $item.data_map.open_in_new_window ), $item.data_map.open_in_new_window.data_int )} target="_blank" {/if}
{/if}
{if $pagedata.is_edit} onclick="return false;" {/if}
title="{$item.data_map.location.data_text|wash}"
role="button"
{if $item_class} class="{$item_class|implode(" ")}"{/if}
rel={$item.url_alias|ezurl}>{if $item.data_map.location.data_text}{$item.data_map.location.data_text|wash()}{else}{$item.name|wash()}{/if}</a>
  </li>
{else}
{if $level_3_items_count|eq(0)}
<li class="{if $path_node_ids|contains($item.node_id)}active{/if}{if $item.node_id|eq($current_node_id)} current{/if}">
<a
href={if eq( $ui_context, 'browse' )}{concat("content/browse/", $item.node_id)|ezurl}{else}{$item.url_alias|ezurl}{/if}
{if $item_class} class="{$item_class|implode(" ")}"{/if}
{if $pagedata.is_edit} onclick="return false;"{/if}>{$item.name|wash()}</a>
</li>
{else}
<li class="dropdown {if $path_node_ids|contains($item.node_id)}active{/if}{if $item.node_id|eq($current_node_id)} current{/if}" id="node_id_{$item.node_id}">
  <div class="dropdown-button">
    <a href={$item.url_alias|ezurl} role="button">{$item.name|wash()}</a>
    <button type="button" class="dropdown-toggle" aria-label="dropdown toggler button"></button>
  </div>
  <div class="dropdown-menu">
  <div>
  <ul class="dropdown-nav">
{foreach $level_3_items as $key => $item}
 {set $item_class = array()}
 {set $item_class = $item_class|append("dropdown-link")}
 {if $path_node_ids|contains($item.node_id)}
  {set $item_class = $item_class|append("active")}
 {/if}
 {if $key|eq(0)}
  {set $item_class = $item_class|append("firstli")}
 {/if}
 {if $level_3_items_count|eq( $key|inc )}
  {set $item_class = $item_class|append("lastli")}
 {/if}
 {if $item.node_id|eq( $current_node_id )}
  {set $item_class = $item_class|append("current")}
 {/if}
{if eq( $item.class_identifier, 'link')}
<li id="node_id_{$item.node_id}" class="{if $path_node_ids|contains($item.node_id)}active{/if}{if $item.node_id|eq($current_node_id)} current{/if}">
<a
{if eq( $ui_context, 'browse' )} href={concat("content/browse/", $item.node_id)|ezurl}{else} href={$item.data_map.location.content|ezurl}
{if and( is_set( $item.data_map.open_in_new_window ), $item.data_map.open_in_new_window.data_int )} target="_blank" {/if}
{/if}
{if $pagedata.is_edit} onclick="return false;" {/if}
title="{$item.data_map.location.data_text|wash}"
role="button"
{if $item_class} class="{$item_class|implode(" ")}"{/if}
rel={$item.url_alias|ezurl}>{if $item.data_map.location.data_text}{$item.data_map.location.data_text|wash()}{else}{$item.name|wash()}{/if}</a>
  </li>
{else}
<li class="{if $path_node_ids|contains($item.node_id)}active{/if}{if $item.node_id|eq($current_node_id)} current{/if}">
<a
href={if eq( $ui_context, 'browse' )}{concat("content/browse/", $item.node_id)|ezurl}{else}{$item.url_alias|ezurl}{/if}
{if $item_class} class="{$item_class|implode(" ")}"{/if}
{if $pagedata.is_edit} onclick="return false;"{/if}>{$item.name|wash()}</a>
</li>
 {/if}
{/foreach}
  </ul>
  </div>
  </div>
</li>
{/if}
{/if}
{/foreach}
</ul>
 </div>
</div>
</li>
 {/if}
{/foreach}
  {/if}
 </ul>
</div>

{undef $level_2_items $level_2_items_count $level_3_items $level_3_items_count $root_node $top_menu_items $item_class $top_menu_items_count $current_node_id $path_node_ids}

{* DropDown Menu : END *}
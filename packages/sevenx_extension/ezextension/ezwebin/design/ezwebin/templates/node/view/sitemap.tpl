{def $page_limit=10
     $col_count=2
     $sub_children=0
     $children=fetch('content','list',hash('parent_node_id', $node.node_id,
                                           'limit', $page_limit,
                                           'offset', $view_parameters.offset,
                                           'sort_by', $node.sort_array))}
<div class="border-box">

<div class="content-view-sitemap">

<div class="attribute-header">
    <h1 class="long">{"Site map"|i18n("design/ezwebin/view/sitemap")} {$node.name|wash}</h1>
</div>

<ul class="sitemap-grid">
{foreach $children as $key => $child}
    <li class="sitemap-section">
    <h2><a href={$child.url_alias|ezurl}>{$child.name|wash}</a></h2>
    {if $child.class_identifier|eq( 'event_calendar' )}
        {set $sub_children=fetch('content','list',hash( 'parent_node_id', $child.node_id,
                                                        'limit', $page_limit,
                                                        'sort_by', array( 'attribute', false(), 'event/from_time' ) ) )}
    {else}
        {set $sub_children=fetch('content','list',hash( 'parent_node_id', $child.node_id,
                                                        'limit', $page_limit,
                                                        'sort_by', $child.sort_array))}
    {/if}
    {if $sub_children|count}
    <ul>
    {foreach $sub_children as $sub_child}
    <li><a href={$sub_child.url_alias|ezurl}>{$sub_child.name|wash}</a></li>
    {/foreach}
    </ul>
    {/if}
    </li>
{/foreach}
</ul>

</div>

</div>
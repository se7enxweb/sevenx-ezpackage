{* Event Calendar - Full Program view *}
{def
    $event_node = $node.node_id
    $curr_ts = currentdate()
    $curr_today = $curr_ts|datetime( custom, '%j')
    $curr_year = $curr_ts|datetime( custom, '%Y')
    $curr_month = $curr_ts|datetime( custom, '%n')
    $temp_oldest_event = ''
    $temp_newest_event = ''

    $temp_offset = cond( ne($view_parameters.offset, ''), $view_parameters.offset, 0)
    $daymode = false()
    $direction = ""
    $newer_event_count = fetch( 'content', 'list_count', hash(
            'parent_node_id', $event_node,
            'class_filter_type',  'include',
            'class_filter_array', array( 'event' ),
               'attribute_filter',    array( 'or',
                    array( 'event/from_time', '>=', $curr_ts  ),
                    array( 'event/to_time', '>=', $curr_ts  )
            )    ))
    $older_event_count = fetch( 'content', 'list_count', hash(
            'parent_node_id', $event_node,
            'class_filter_type',  'include',
            'class_filter_array', array( 'event' ),
               'attribute_filter',
            array( 'and', array( 'event/from_time', '<', $curr_ts  ),
                    array( 'event/to_time', '<', $curr_ts  )
            )    ))
}
{if ge($temp_offset,0)}
{set $temp_offset = $temp_offset|abs}
{def $events = fetch( 'content', 'list', hash(
            'parent_node_id', $event_node,
            'sort_by', array( 'attribute', true(), 'event/from_time' ),
            'class_filter_type',  'include',
            'class_filter_array', array( 'event' ),
            'limit', 15,
            'offset', $temp_offset|mul(15),
            'attribute_filter', array( 'or',
                    array( 'event/from_time', '>=', $curr_ts  ),
                    array( 'event/to_time', '>=', $curr_ts  )
            )    ))
}
{set $newer_event_count = $newer_event_count|sub( 15|mul( $temp_offset|inc ) )}
{else}
{set $temp_offset = $temp_offset|abs|dec
     $direction = "-"}
{def $events = fetch( 'content', 'list', hash(
            'parent_node_id', $event_node,
            'sort_by', array( 'attribute', true(), 'event/from_time' ),
            'class_filter_type',  'include',
            'class_filter_array', array( 'event' ),
            'limit', 15,
            'offset', $temp_offset|mul(15),
            'attribute_filter', array( 'and',
                    array( 'event/from_time', '<', $curr_ts  ),
                    array( 'event/to_time', '<', $curr_ts  )
            )))
}
{set $older_event_count = $older_event_count|sub( 15|mul( $temp_offset|inc ) )}
{/if}

{foreach $events as $event}
{if or(eq($temp_newest_event,''),gt($event.object.data_map.from_time.content.timestamp, $temp_newest_event))}
    {set $temp_newest_event=$event.object.data_map.from_time.content.timestamp}
{/if}
{if or(eq($temp_oldest_event,''),lt($event.object.data_map.from_time.content.timestamp, $temp_oldest_event))}
    {set $temp_oldest_event=$event.object.data_map.from_time.content.timestamp}
{/if}
{/foreach}

{if eq($temp_oldest_event|datetime(custom,"%M"),  $temp_newest_event|datetime(custom,"%M"))}
{set $daymode=true()}
{/if}

{def $ec_base = concat( $node.url_alias, '/(show)/program/(offset)/' )}
<div class="border-box">
<div class="content-view-full">
 <div class="class-event-calendar event-calendar-programview">
    <div class="attribute-header event-calendar-header">
        <h1>{$node.name|wash()}</h1>
        {include uri='design:parts/event_tabs.tpl' node=$node show='program'}
    </div>

    {if $events|count}
    <ol class="event-list">
    {foreach $events as $event}
        <li>{include uri='design:parts/event_item.tpl' event=$event heading='h2'}</li>
    {/foreach}
    </ol>
    {else}
    <p class="empty-state">{'No items found.'|i18n( 'design/ezwebin/full/event_view_calendar' )}</p>
    {/if}

    <nav class="event-pager" aria-label="{$node.name|wash}">
{if $direction}{* '-' direction: past events *}
    {if $older_event_count|gt(0)}
        <a class="event-pager-prev" href={concat( $ec_base, '-', $temp_offset|sum(2) )|ezurl}>{"Past events"|i18n("design/ezwebin/full/event_view_program")}</a>
    {/if}
    {if $temp_offset|gt(0)}
        <a class="event-pager-next" href={concat( $ec_base, '-', $temp_offset )|ezurl}>{"Future events"|i18n("design/ezwebin/full/event_view_program")}</a>
    {elseif $newer_event_count|gt(0)}
        <a class="event-pager-next" href={concat( $ec_base, '0' )|ezurl}>{"Future events"|i18n("design/ezwebin/full/event_view_program")}</a>
    {/if}
{else}
    {if $temp_offset|gt(0)}
        <a class="event-pager-prev" href={concat( $ec_base, $temp_offset|dec )|ezurl}>{"Past events"|i18n("design/ezwebin/full/event_view_program")}</a>
    {elseif $older_event_count|gt(0)}
        <a class="event-pager-prev" href={concat( $ec_base, '-1' )|ezurl}>{"Past events"|i18n("design/ezwebin/full/event_view_program")}</a>
    {/if}
    {if $newer_event_count|gt(0)}
        <a class="event-pager-next" href={concat( $ec_base, $temp_offset|inc )|ezurl}>{"Future events"|i18n("design/ezwebin/full/event_view_program")}</a>
    {/if}
{/if}
    </nav>
 </div>
</div>
</div>
{undef}

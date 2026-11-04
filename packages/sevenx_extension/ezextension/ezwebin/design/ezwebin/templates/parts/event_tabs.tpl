{* The two views of an event calendar (full/event_calendar.tpl): upcoming events and the month. Parameters: node, show. *}
<nav class="event-tabs" aria-label="{$node.name|wash}">
    <a class="event-tab" href={concat( $node.url_alias, '/(show)/program' )|ezurl}{if eq( $show, 'program' )} aria-current="page"{/if}>{'Future events'|i18n( 'design/ezwebin/full/event_view_program' )}</a>
    <a class="event-tab" href={concat( $node.url_alias, '/(show)/calendar' )|ezurl}{if eq( $show, 'calendar' )} aria-current="page"{/if}>{'Calendar'|i18n( 'design/ezwebin/full/event_view_calendar' )}</a>
</nav>

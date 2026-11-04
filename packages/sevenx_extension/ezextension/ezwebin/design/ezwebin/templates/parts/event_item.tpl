{* One event in a list (calendar month, upcoming list, line view, front page): a date badge, the title, the time
   (one day: 16:00 - 17:00; several days: both dates), the category, the start of its text and an .ics download.
   Parameters: event (the node), heading (default h3), summary (default true), past (optional, true greys it). *}
{def $ev_dm = $event.data_map
     $ev_from = $ev_dm.from_time.content.timestamp
     $ev_to = cond( $ev_dm.to_time.has_content, $ev_dm.to_time.content.timestamp, $ev_dm.from_time.content.timestamp )
     $ev_heading = first_set( $heading, 'h3' )
     $ev_past = first_set( $past, gt( currentdate(), $ev_to ) )}
<article class="event-item{if $ev_past} is-past ezagenda_event_old{/if}">
    <time class="event-badge" datetime="{$ev_from|datetime( custom, '%Y-%m-%dT%H:%i' )}">
        <span class="event-badge-month">{$ev_from|datetime( custom, '%M' )|extract_left( 3 )}</span>
        <span class="event-badge-day">{$ev_from|datetime( custom, '%j' )}</span>
    </time>
    <div class="event-body">
        <{$ev_heading} class="event-title"><a href={$event.url_alias|ezurl}>{$event.name|wash}</a></{$ev_heading}>
        <p class="event-meta">
            <span class="event-time">
            {if eq( $ev_from|datetime( custom, '%Y%m%d' ), $ev_to|datetime( custom, '%Y%m%d' ) )}
                {$ev_from|l10n( 'shortdate' )}, {$ev_from|l10n( 'shorttime' )}{if ne( $ev_from, $ev_to )} &ndash; {$ev_to|l10n( 'shorttime' )}{/if}
            {else}
                {$ev_from|l10n( 'shortdatetime' )} &ndash; {$ev_to|l10n( 'shortdatetime' )}
            {/if}
            </span>
            {if $ev_dm.category.has_content}
            {foreach $ev_dm.category.content.keywords as $ev_keyword}<span class="event-tag ezagenda_keyword">{$ev_keyword|wash}</span>{/foreach}
            {/if}
        </p>
        {if and( first_set( $summary, true() ), $ev_dm.text.has_content )}
        <p class="event-text">{$ev_dm.text.content.output.output_text|strip_tags|simplify|trim|shorten( 160 )}</p>
        {/if}
    </div>
    {include uri='design:parts/event_ics.tpl' event=$event}
</article>
{undef $ev_dm $ev_from $ev_to $ev_heading $ev_past}

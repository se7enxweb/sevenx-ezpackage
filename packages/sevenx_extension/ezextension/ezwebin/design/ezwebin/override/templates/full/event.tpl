{set-block scope=root variable=cache_ttl}600{/set-block}
{* Event - Full view: a header with the date badge, the time and the category, the text, the .ics download and the
   way back to its calendar *}
{def $ev_dm = $node.data_map
     $ev_from = $ev_dm.from_time.content.timestamp
     $ev_to = cond( $ev_dm.to_time.has_content, $ev_dm.to_time.content.timestamp, $ev_from )}
<div class="border-box">
<div class="content-view-full">
    <div class="class-event event-full{if gt( currentdate(), $ev_to )} is-past{/if}">

    <header class="event-hero">
        <time class="event-badge event-badge-large" datetime="{$ev_from|datetime( custom, '%Y-%m-%dT%H:%i' )}">
            <span class="event-badge-month">{$ev_from|datetime( custom, '%M' )|extract_left( 3 )}</span>
            <span class="event-badge-day">{$ev_from|datetime( custom, '%j' )}</span>
        </time>
        <div class="attribute-header">
            <h1>{if $ev_dm.title.has_content}{$ev_dm.title.content|wash()}{else}{$node.name|wash()}{/if}</h1>
            <p class="event-meta">
                <span class="event-time">
                {if eq( $ev_from|datetime( custom, '%Y%m%d' ), $ev_to|datetime( custom, '%Y%m%d' ) )}
                    {$ev_from|l10n( 'date' )}, {$ev_from|l10n( 'shorttime' )}{if ne( $ev_from, $ev_to )} &ndash; {$ev_to|l10n( 'shorttime' )}{/if}
                {else}
                    {$ev_from|l10n( 'shortdatetime' )} &ndash; {$ev_to|l10n( 'shortdatetime' )}
                {/if}
                </span>
                {if $ev_dm.category.has_content}
                {foreach $ev_dm.category.content.keywords as $ev_keyword}<span class="event-tag ezagenda_keyword">{$ev_keyword|wash}</span>{/foreach}
                {/if}
            </p>
        </div>
    </header>

    {if $ev_dm.text.has_content}
        <div class="attribute-long">{attribute_view_gui attribute=$ev_dm.text}</div>
    {/if}

    <div class="event-actions">
        {include uri='design:parts/event_ics.tpl' event=$node}
        <a class="section-more event-back" href={$node.parent.url_alias|ezurl}>{$node.parent.name|wash}</a>
    </div>

    </div>
</div>
</div>
{undef $ev_dm $ev_from $ev_to}

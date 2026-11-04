{* The event as an iCalendar file (.ics) to add to a calendar app: a data: link with the download attribute, built
   from the event's title, start, end and text, in the site's local time (floating times). Parameters: event. *}
{def $ics_dm = $event.data_map
     $ics_from = $ics_dm.from_time.content.timestamp
     $ics_to = cond( $ics_dm.to_time.has_content, $ics_dm.to_time.content.timestamp, $ics_from )
     $ics_text = cond( $ics_dm.text.has_content, $ics_dm.text.content.output.output_text|strip_tags|simplify|trim, '' )
     $ics = concat( 'BEGIN:VCALENDAR%0D%0AVERSION:2.0%0D%0APRODID:-//Exponential//ezwebin//EN%0D%0ABEGIN:VEVENT%0D%0AUID:',
                    $event.object.remote_id|rawurlencode, '%0D%0ADTSTAMP:', $event.object.modified|datetime( custom, '%Y%m%dT%H%i%s' ),
                    '%0D%0ADTSTART:', $ics_from|datetime( custom, '%Y%m%dT%H%i%s' ),
                    '%0D%0ADTEND:', $ics_to|datetime( custom, '%Y%m%dT%H%i%s' ),
                    '%0D%0ASUMMARY:', $event.name|explode( ',' )|implode( '\,' )|explode( ';' )|implode( '\;' )|rawurlencode,
                    '%0D%0ADESCRIPTION:', $ics_text|shorten( 400 )|explode( ',' )|implode( '\,' )|explode( ';' )|implode( '\;' )|rawurlencode,
                    '%0D%0AURL:', $event.url_alias|ezurl( 'no', 'full' )|rawurlencode,
                    '%0D%0AEND:VEVENT%0D%0AEND:VCALENDAR%0D%0A' )}
<a class="event-ics" href="data:text/calendar;charset=utf-8,{$ics}" download="{$event.url_alias|explode( '/' )|reverse|extract( 0, 1 ).0|wash}.ics">
    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4M12 13v5M9.5 15.5 12 18l2.5-2.5"/></svg>
    <span>{'Download'|i18n( 'design/ezwebin/full/event_view_calendar' )}<span class="sr-only">: {$event.name|wash} (.ics)</span></span>
</a>
{undef $ics_dm $ics_from $ics_to $ics_text $ics}

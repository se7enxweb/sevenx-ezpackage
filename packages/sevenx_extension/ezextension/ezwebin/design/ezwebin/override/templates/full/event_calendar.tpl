{* Event Calendar - Full view: the upcoming list (program) or the month (calendar), as the calendar's "view" setting
   says; the view parameter /(show)/program or /(show)/calendar switches between them (the tabs) *}
{set-block scope=root variable=cache_ttl}400{/set-block}
{def $ec_default = $node.data_map.view.class_content.options[$node.data_map.view.value[0]].name|downcase()
     $ec_show = cond( and( is_set( $view_parameters.show ), array( 'program', 'calendar' )|contains( $view_parameters.show ) ), $view_parameters.show, $ec_default )}
{if array( 'program', 'calendar' )|contains( $ec_show )|not}{set $ec_show = 'program'}{/if}
{include uri=concat( 'design:full/event_view_', $ec_show, '.tpl' ) event_tabs=concat( 'design:parts/event_tabs.tpl' ) event_show=$ec_show}
{undef $ec_default $ec_show}

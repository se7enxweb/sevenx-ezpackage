{if and( is_set( $DesignKeys:used.url_alias ), $DesignKeys:used.url_alias|count|ge(1) )}
    {def $avail_translation = language_switcher( $DesignKeys:used.url_alias )}
{else}
    {def $avail_translation = language_switcher( $site.uri.original_uri)}
{/if}
{if $avail_translation|count|gt( 1 )}
<nav id="languages" aria-label="{'Languages'|i18n( 'design/ezwebin/pagelayout' )|wash}">
    <ul>
    {foreach $avail_translation as $siteaccess => $lang}
        <li{if $siteaccess|eq($access_type.name)} class="current_siteaccess"{/if}><a href={$lang.url|ezurl}{if $siteaccess|eq($access_type.name)} aria-current="true"{/if}>{$lang.text|wash}</a></li>
    {/foreach}
    </ul>
</nav>
{/if}
{undef $avail_translation}

<div id="logo">
{if $pagedesign.data_map.image.content.is_valid|not()}
    <a class="site-name" href={"/"|ezurl} rel="home">{ezini('SiteSettings','SiteName')|wash}</a>
{else}
    {def $logo = $pagedesign.data_map.image.content[original]}
    <a href={"/"|ezurl} rel="home"><img src={$logo.full_path|ezroot} alt="{if $logo.text|trim}{$logo.text|wash}{else}{ezini('SiteSettings','SiteName')|wash}{/if}" width="{$logo.width}" height="{$logo.height}"></a>
    {undef $logo}
{/if}
</div>

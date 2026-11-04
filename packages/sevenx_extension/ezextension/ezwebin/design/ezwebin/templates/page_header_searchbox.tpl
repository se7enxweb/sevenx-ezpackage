<div id="searchbox">
  <form action={"/content/search"|ezurl} method="post" role="search">
  <div id="searchbox-inner">
    <label for="searchtext" class="sr-only">{'Search text:'|i18n('design/ezwebin/pagelayout')}</label>
    {if $pagedata.is_edit}
    <input id="searchtext" name="SearchText" type="search" value="" size="12" disabled="disabled">
    <button id="searchbutton" class="button-disabled" type="submit" disabled="disabled">{'Search'|i18n('design/ezwebin/pagelayout')}</button>
    {else}
    <input id="searchtext" name="SearchText" type="search" value="" size="12" autocomplete="off">
    <button id="searchbutton" class="button" type="submit">{'Search'|i18n('design/ezwebin/pagelayout')}</button>
    {include uri='design:parts/front_search_suggest.tpl'}
        {if eq( $ui_context, 'browse' )}
         <input name="Mode" type="hidden" value="browse">
        {/if}
    {/if}
  </div>
  </form>
</div>

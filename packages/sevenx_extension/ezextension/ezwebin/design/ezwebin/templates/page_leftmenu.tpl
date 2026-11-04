    {* The section navigation. On small screens it is collapsed behind its button (ezwebin.js). *}
    <nav id="sidemenu-position" aria-label="{'In this section'|i18n( 'design/ezwebin/pagelayout' )|wash}">
      <div id="sidemenu">
        <button type="button" class="sidemenu-toggle" id="ezwebin-sidemenu-toggle" hidden aria-expanded="false" aria-controls="sidemenu-body">{'In this section'|i18n( 'design/ezwebin/pagelayout' )|wash}</button>
        <div class="sidemenu-body" id="sidemenu-body">
        {if is_array( $pagedata.left_menu )}
            {foreach $pagedata.left_menu as $left_menu}
                {include uri=concat('design:menu/', $left_menu, '.tpl')}
                {delimiter}<hr class="hr">{/delimiter}
            {/foreach}
        {else}
            {include uri=concat('design:menu/', $pagedata.left_menu, '.tpl')}
        {/if}
        </div>
      </div>
    </nav>

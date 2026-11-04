    <aside id="extrainfo-position">
      <div id="extrainfo">
        {if is_array( $pagedata.extra_menu )}
            {foreach $pagedata.extra_menu as $extra_menu}
                {include uri=concat('design:parts/', $extra_menu, '.tpl')}
                {delimiter}<hr class="hr">{/delimiter}
            {/foreach}
        {else}
            {include uri=concat('design:parts/', $pagedata.extra_menu, '.tpl')}
        {/if}
      </div>
    </aside>

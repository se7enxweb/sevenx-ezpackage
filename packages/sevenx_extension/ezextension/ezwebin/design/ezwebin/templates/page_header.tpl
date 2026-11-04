  {* The page header: logo, search, the color mode switch and the menu button (small screens), then the
     language switcher and the site links. The skip link is the first element of the body (pagelayout.tpl). *}
  <header id="header-position">
  <div id="header">
    {include uri='design:page_header_logo.tpl'}

    <div class="header-tools">
      {include uri='design:page_header_searchbox.tpl'}

      <button type="button" class="header-button header-theme-toggle" id="ezwebin-theme-toggle" hidden
              data-label-light="{'Color mode: Light'|i18n( 'design/ezwebin/pagelayout' )|wash}"
              data-label-dark="{'Color mode: Dark'|i18n( 'design/ezwebin/pagelayout' )|wash}"
              data-label-system="{'Color mode: Follow system'|i18n( 'design/ezwebin/pagelayout' )|wash}">
        <svg class="header-icon theme-icon-light" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4.5"/><path d="M12 1.5v2.5M12 20v2.5M1.5 12H4M20 12h2.5M4.6 4.6l1.8 1.8M17.6 17.6l1.8 1.8M4.6 19.4l1.8-1.8M17.6 6.4l1.8-1.8"/></svg>
        <svg class="header-icon theme-icon-dark" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.5a8.5 8.5 0 1 0 11 11z"/></svg>
        <svg class="header-icon theme-icon-system" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/></svg>
        <span class="sr-only header-theme-label">{'Color mode: Follow system'|i18n( 'design/ezwebin/pagelayout' )|wash}</span>
      </button>
      <span class="sr-only" id="ezwebin-theme-status" role="status" aria-live="polite"></span>

      {if $pagedata.top_menu}
      <button type="button" class="header-button header-menu-toggle" id="ezwebin-menu-toggle" hidden aria-expanded="false" aria-controls="topmenu">
        <svg class="header-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        <span class="header-menu-label">{'Menu'|i18n( 'design/ezwebin/pagelayout' )|wash}</span>
      </button>
      {/if}
    </div>

    <div id="usermenu">
      {include uri='design:page_header_languages.tpl'}

      {include uri='design:page_header_links.tpl'}
    </div>
  </div>
  </header>

  {* The main navigation. On small screens it is collapsed behind the header's menu button (ezwebin.js); without
     scripts it stays open. The arrow keys move between its links (ezwebin.js, data-ezwebin-keynav). *}
  <nav id="topmenu-position" aria-label="{'Main navigation'|i18n( 'design/ezwebin/pagelayout' )|wash}">
  <div id="topmenu">
    {include uri=concat('design:menu/', $pagedata.top_menu, '.tpl')}
  </div>
  </nav>

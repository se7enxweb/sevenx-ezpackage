  <!-- Footer area: START -->
  <footer id="footer">
    {if $pagedesign.data_map.footer_text.has_content}
    <div class="footer-text">
        {$pagedesign.data_map.footer_text.content}
    </div>
    {/if}
    {if $pagedesign.data_map.hide_powered_by.data_int|not}
    <p class="footer-powered">
        {'Powered by'|i18n( 'design/ezwebin/pagelayout' )} <a href="https://exponential.earth">Exponential</a>
    </p>
    {/if}
  </footer>
  <!-- Footer area: END -->

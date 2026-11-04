  <!-- Path content: START -->
  <ol class="path-list">
  {foreach $pagedata.path_array as $path}
  {if $path.url}
    <li><a href={cond( is_set( $path.url_alias ), $path.url_alias,
                                        $path.url )|ezurl}>{$path.text|wash}</a></li>
  {else}
    <li><span class="path-text" aria-current="page">{$path.text|wash}</span></li>
  {/if}
  {/foreach}
  </ol>
  <!-- Path content: END -->

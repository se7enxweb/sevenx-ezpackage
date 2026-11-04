{* The tag cloud: the size of a tag (100% to 200% of the text, by its count) is a class, tagcloud-size-100 ...
   tagcloud-size-200 in steps of 10 (content.css), in place of an inline font-size style *}
<ul class="tagcloud-list">
{foreach $tag_cloud as $tag}
<li><a class="tagcloud-size-{$tag['font_size']|div( 10 )|round|mul( 10 )}" href={concat( "/content/keyword/", $tag['tag']|rawurlencode )|ezurl()} title="{'%count objects tagged with \'%tag\''|i18n( 'design/ezwebin/tagcloud/tagcloud',, hash( '%count', $tag['count'], '%tag', $tag['tag']|wash() ) )}">{$tag['tag']|wash()}</a></li>
{/foreach}
</ul>

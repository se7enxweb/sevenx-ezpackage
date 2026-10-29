{foreach $tag_cloud as $tag}
<a href={concat( "/content/keyword/", $tag['tag']|rawurlencode )|ezurl()} style="font-size: {$tag['font_size']}%" title="{'%count objects tagged with \'%tag\''|i18n( 'design/ezwebin/tagcloud/tagcloud',, hash( '%count', $tag['count'], '%tag', $tag['tag']|wash() ) )}">{$tag['tag']|wash()}</a> 
{/foreach}
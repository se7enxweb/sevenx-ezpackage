<div class="attribute-relatedcontent">
    <h1>{"Related content"|i18n("design/ezwebin/full/image")}</h1>
    {foreach $related_content|reverse() as $related_object max 7}
        {node_view_gui view=galleryline content_node=$related_object.object.main_node}
    {/foreach}
</div>
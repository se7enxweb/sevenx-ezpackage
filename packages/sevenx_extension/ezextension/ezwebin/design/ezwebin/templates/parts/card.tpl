{* A content card: image, kicker (date or price), title and summary of $node, linked to its page. Used by the line
   and horizontally listed views of articles, folders, products, events and blog posts, so lists, front page rows
   and folder pages look alike. The image: the node's image attribute (an image or an image relation), else its
   first image child. Parameters: node, heading (h2 or h3, default h3), view (the view's class name). *}
{def $card_image = false()
     $card_alias = 'articlethumbnail'
     $card_text = false()
     $card_kicker = ''
     $card_heading = first_set( $heading, 'h3' )
     $card_dm = $node.data_map}
{if and( is_set( $card_dm.image ), $card_dm.image.has_content )}
    {if $card_dm.image.data_type_string|eq( 'ezimage' )}
        {set $card_image = $card_dm.image.content}
    {elseif and( $card_dm.image.data_type_string|eq( 'ezobjectrelation' ), is_set( $card_dm.image.content.data_map.image ) )}
        {set $card_image = $card_dm.image.content.data_map.image.content}
    {/if}
{/if}
{if $card_image|not}
    {def $card_first = fetch( 'content', 'list', hash( 'parent_node_id', $node.node_id, 'limit', 1,
                                                       'class_filter_type', 'include', 'class_filter_array', array( 'image' ) ) )}
    {if $card_first|count}{set $card_image = $card_first.0.data_map.image.content}{/if}
    {undef $card_first}
{/if}
{foreach array( 'intro', 'short_description', 'description', 'text', 'body' ) as $card_field}
    {if and( $card_text|not, is_set( $card_dm[$card_field] ), $card_dm[$card_field].has_content )}{set $card_text = $card_dm[$card_field]}{/if}
{/foreach}
{if is_set( $card_dm.publication_date )}
    {set $card_kicker = $card_dm.publication_date.content.timestamp|l10n( 'shortdate' )}
{elseif is_set( $card_dm.from_time )}
    {set $card_kicker = $card_dm.from_time.content.timestamp|l10n( 'shortdatetime' )}
{elseif and( is_set( $card_dm.price ), $card_dm.price.has_content )}
    {set $card_kicker = $card_dm.price.content.price|l10n( 'currency' )}
{/if}
<article class="card {first_set( $view, 'content-view-line' )} class-{$node.class_identifier|wash}">
    {if and( $card_image, $card_image.is_valid )}
    <a class="card-media" href={$node.url_alias|ezurl} tabindex="-1" aria-hidden="true">
        <img src={$card_image[$card_alias].full_path|ezroot} width="{$card_image[$card_alias].width}" height="{$card_image[$card_alias].height}" alt="" loading="lazy" decoding="async">
    </a>
    {/if}
    <div class="card-body">
        {if $card_kicker}<p class="card-kicker">{$card_kicker|wash}</p>{/if}
        <{$card_heading} class="card-title"><a href={$node.url_alias|ezurl}>{$node.name|wash}</a></{$card_heading}>
        {if $card_text}
        <div class="card-text">{$card_text.content.output.output_text|strip_tags|simplify|trim|shorten( 160 )}</div>
        {/if}
    </div>
</article>
{undef $card_image $card_alias $card_text $card_kicker $card_heading $card_dm}

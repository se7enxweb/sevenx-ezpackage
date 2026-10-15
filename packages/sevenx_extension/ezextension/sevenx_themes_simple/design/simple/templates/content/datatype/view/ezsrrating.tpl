{def $rating = $attribute.content}

<div class="hreview-aggregate">
   <span class="item">
      <span class="fn">{$attribute.object.name|wash}</span>
   </span>

    <ul id="ezsr_rating_{$attribute.id}" class="ezsr-star-rating">
    <li id="ezsr_rating_percent_{$attribute.id}" class="ezsr-current-rating" style="width:{$rating.rounded_average|div(5)|mul(100)}%;">{'Currently %current_rating out of 5 Stars.'|i18n('extension/ezstarrating/datatype', '', hash( '%current_rating', concat('<span>', $rating.rounded_average|wash, '</span>') ))}</li>
    {for 1 to 5 as $num}
	<li><a href="JavaScript:void(0);" id="ezsr_{$attribute.id}_{$attribute.version}_{$num}" title="{'Rate %rating stars out of 5'|i18n('extension/ezstarrating/datatype', '', hash( '%rating', $num ))}" class="ezsr-stars-{$num}" rel="nofollow" onfocus="this.blur();">{$num}</a></li>
    {/for}
    </ul>

    <span class="rating">
	{'Rating: %current_rating/5'|i18n('extension/ezstarrating/datatype', '', hash( '%current_rating', concat('<span id="ezsr_average_', $attribute.id, '" class="average ezsr-average-rating">', $rating.rating|wash, '</span>') ))}
	({'%rating_count votes cast'|i18n('extension/ezstarrating/datatype', '', hash( '%rating_count', concat('<span id="ezsr_total_', $attribute.id, '" class="votes">', $rating.rating_count|wash, '</span>') ))})
	{if $attribute.data_int} {'disabled'|i18n('extension/ezstarrating/datatype')}.{/if}
    </span>

    <p id="ezsr_just_rated_{$attribute.id}" class="ezsr-just-rated hide">{'Thank you for rating!'|i18n('extension/ezstarrating/datatype', 'When rating')}</p>
    <p id="ezsr_has_rated_{$attribute.id}" class="ezsr-has-rated hide">{'You have already rated this page, you can only rate it once!'|i18n('extension/ezstarrating/datatype', 'When rating')}</p>
    <p id="ezsr_changed_rating_{$attribute.id}" class="ezsr-changed-rating hide">{'Your rating has been changed, thanks for rating!'|i18n('extension/ezstarrating/datatype', 'When rating')}</p>
</div>

{run-once}
{ezcss_require( 'star_rating.css' )}
{* Enable rating code if not disabled on attribute and user has access to rate! *}
{if and( $attribute.data_int|not, has_access_to_limitation( 'ezjscore', 'call', hash( 'FunctionList', 'ezstarrating_rate' ) ))}
    {* The rating runs on jQuery (design/standard/javascript/ezstarrating_jquery.js) *}
    {ezscript_require( array( 'ezjsc::jquery', 'ezjsc::jqueryio', 'ezstarrating_jquery.js' ) )}
{else}
    {if fetch( 'user', 'current_user' ).is_logged_in}
        <p id="ezsr_no_permission_{$attribute.id}" class="ezsr-no-permission">{"You don't have access to rate this page."|i18n( 'extension/ezstarrating/datatype' )}</p>
    {else}
        {if ezmodule( 'user/register' )}
            <p id="ezsr_no_permission_{$attribute.id}" class="ezsr-no-permission">{'%login_link_startLog in%login_link_end or %create_link_startcreate a user account%create_link_end to rate this page.'|i18n( 'extension/ezstarrating/datatype', , hash( '%login_link_start', concat( '<a href="', '/user/login'|ezurl('no'), '">' ), '%login_link_end', '</a>', '%create_link_start', concat( '<a href="', "/user/register"|ezurl('no'), '">' ), '%create_link_end', '</a>' ) )}</p>
        {else}
            <p id="ezsr_no_permission_{$attribute.id}" class="ezsr-no-permission">{'%login_link_startLog in%login_link_end to rate this page.'|i18n( 'extension/ezstarrating/datatype', , hash( '%login_link_start', concat( '<a href="', '/user/login'|ezurl('no'), '">' ), '%login_link_end', '</a>' ) )}</p>
        {/if}
    {/if}
{/if}
{/run-once}
{undef $rating}
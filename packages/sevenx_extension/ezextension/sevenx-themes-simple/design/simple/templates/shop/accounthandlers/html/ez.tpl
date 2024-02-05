<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
<td valign="top">
<p>
<b>{"Customer"|i18n("design/standard/shop")}</b>
</p>
<p>
{'Name'|i18n('design/standard/shop')}: {$order.account_information.first_name|wash} {$order.account_information.last_name|wash}<br />
{'Email'|i18n('design/standard/shop')}: {$order.account_information.email|wash}<br />
{'Phone'|i18n('design/standard/shop')}: {$order.account_information.phone|wash}<br />
</p>
</td>
<td valign="top">

<p>
<b>{"Address"|i18n("design/standard/shop")}</b>
</p>
<p>
{'Company'|i18n('design/standard/shop')}: {$order.account_information.street1|wash}<br />
{'Street'|i18n('design/standard/shop')}: {$order.account_information.street2|wash}<br />
{'Zip'|i18n('design/standard/shop')}: {$order.account_information.zip|wash}<br />
{'City'|i18n('design/standard/shop')}: {$order.account_information.city|wash}<br />
{'State'|i18n('design/standard/shop')}: {$order.account_information.state|wash}<br />
{'Country/region'|i18n('design/standard/shop')}: {$order.account_information.country|wash}<br />
</p>
</td>
</tr>
</table>

{if $order.account_information.comment}
<p>
<b>{'Comment'|i18n( 'design/standard/shop' )}</b>
</p>
<p>
{$order.account_information.comment|wash|nl2br}
</p>
{/if}

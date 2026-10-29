<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc float-break">

<div class="user-forgotpassword">

{* demo mode (EXP_DEMO_MODE=1): shown to everyone, so it says nothing about the address typed; the text comes from
   the kernel *}
{if first_set( $demo_mode_text, '' )|ne( '' )}
<div class="warning">
<p>{$demo_mode_text|wash}</p>
</div>
{/if}

{if $link}
<p>
{"If an account is registered with the email address %1, a mail has been sent to it. This email contains a link you need to click so that we can confirm that the correct user is getting the new password."|i18n('design/standard/user/forgotpassword',,array($email|wash))}
</p>
{else}
   {if $wrong_email}
   <div class="warning">
   <h2>{"Please enter a valid email address."|i18n('design/standard/user/forgotpassword')}</h2>
   </div>
   {/if}
   {if $generated}
   <p>
   {"Password was successfully generated and sent to: %1"|i18n('design/ezwebin/user/forgotpassword',,array($email|wash))}
   </p>
   {else}
      {if $wrong_key}
      <div class="warning">
      <h2>{"The key is invalid or has been used. "|i18n('design/ezwebin/user/forgotpassword')}</h2>
      </div>
      {else}
      <form method="post" name="forgotpassword" action={"/user/forgotpassword/"|ezurl}>

      <div class="attribute-header">
      <h1 class="long">{"Have you forgotten your password?"|i18n('design/ezwebin/user/forgotpassword')}</h1>
      </div>

      <p>
      {"If you have forgotten your password, enter your email address and we will create a new password and send it to you."|i18n('design/ezwebin/user/forgotpassword')}
      </p>

      <div class="block">
      <label for="email">{"Email"|i18n('design/ezwebin/user/forgotpassword')}:</label>
      <div class="labelbreak"></div>
      <input class="halfbox" type="text" name="UserEmail" size="40" value="{$wrong_email|wash}">
      </div>

      <div class="buttonblock">
      <input class="button" type="submit" name="GenerateButton" value="{'Generate new password'|i18n('design/ezwebin/user/forgotpassword')}">
      </div>
      </form>
      {/if}
   {/if}
{/if}

</div>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>
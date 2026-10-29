{* Change password (user/password) in the ezwebin design: one card with the current password, the new one with its
   requirements, the new one again, "Change password" and "Cancel". Field names, the form token and the button
   names are those the view reads (oldPassword, newPassword, confirmPassword, OKButton, CancelButton).

   Every problem is said next to its field (aria-describedby) and in a summary at the top of the form that links
   to the fields, and the first field with a problem gets the focus. On an Exponential with the modern password
   page (6.0.15 on: $field_errors, $password_rules) the kernel's stylesheet exp_password_page.css and script
   exp_password_field.js (design/standard) add the live checklist, the strength meter, show/hide, "passwords
   match" and "Generate a strong password"; every control they need is in the markup with the hidden attribute,
   so without JavaScript none of them is shown. On an older kernel the old flags become the same inline errors.
   The typed passwords are never written back into the page. *}
{if is_set( $field_errors )}
    {def $pw_errors = $field_errors
         $pw_rules = $password_rules
         $pw_min = $min_length
         $pw_t = $password_js_config.i18n
         $pw_changed = $password_changed
         $pw_modern = true()}
{else}
    {def $pw_min = cond( ezini_hasvariable( 'UserSettings', 'MinPasswordLength' ), ezini( 'UserSettings', 'MinPasswordLength' ), 3 )
         $pw_errors = hash( 'oldPassword', cond( $oldPasswordNotValid, array( 'Please retype your old password.'|i18n( 'design/ezwebin/user/password' ) ), array() ),
                            'newPassword', cond( $newPasswordTooShort, array( 'The new password must be at least %1 characters long, please retype your new password.'|i18n( 'design/ezwebin/user/password', '', array( $pw_min ) ) ), array() ),
                            'confirmPassword', cond( $newPasswordNotMatch, array( "Password didn't match, please retype your new password."|i18n( 'design/ezwebin/user/password' ) ), array() ) )
         $pw_rules = array( hash( 'id', 'length', 'min', $pw_min, 'failed', $newPasswordTooShort, 'text', 'At least %1 characters'|i18n( 'design/ezwebin/user/password', '', array( $pw_min ) ) ) )
         $pw_t = hash( 'show', '', 'ruleUnmet', '', 'strength', '' )
         $pw_changed = and( $message, not( or( $oldPasswordNotValid, $newPasswordNotMatch, $newPasswordTooShort ) ) )
         $pw_modern = false()}
{/if}
{def $pw_ids = hash( 'oldPassword', 'password-old', 'newPassword', 'password-new', 'confirmPassword', 'password-confirm' )
     $pw_first_error = cond( $pw_errors.oldPassword|count, 'password-old', $pw_errors.newPassword|count, 'password-new',
                             $pw_errors.confirmPassword|count, 'password-confirm', '' )}
{if $pw_modern}<link rel="stylesheet" type="text/css" href={'stylesheets/exp_password_page.css'|ezdesign} />{/if}
<div class="border-box">
<div class="border-tl"><div class="border-tr"><div class="border-tc"></div></div></div>
<div class="border-ml"><div class="border-mr"><div class="border-mc">

<div class="exp-pw">
<div class="attribute-header">
<h1 class="long">{'Change your password'|i18n( 'design/ezwebin/user/password' )}</h1>
</div>

{if $pw_changed}
<div class="exp-pw-notice exp-pw-success" role="status">
    <h2>{'Your password has been changed'|i18n( 'design/ezwebin/user/password' )}</h2>
    <p>{'You are still signed in here. Use the new password the next time you sign in.'|i18n( 'design/ezwebin/user/password' )}</p>
    {if or( and( is_set( $other_sessions_signed_out ), $other_sessions_signed_out ), and( is_set( $sessions_ended ), $sessions_ended|gt( 0 ) ) )}<p>{'You were signed out on your other devices.'|i18n( 'design/ezwebin/user/password' )}</p>{/if}
    {if and( is_set( $notification_sent ), $notification_sent )}<p>{'We sent a confirmation to your e-mail address.'|i18n( 'design/ezwebin/user/password' )}</p>{/if}
    <p class="exp-pw-actions"><a class="defaultbutton exp-pw-button exp-pw-primary" href={cond( is_set( $redirect_uri ), $redirect_uri, '/user/edit' )|ezurl}>{'Continue'|i18n( 'design/ezwebin/user/password' )}</a></p>
</div>
{else}

<form class="exp-pw-card" action={concat( $module.functions.password.uri, '/', $userID )|ezurl} method="post" name="Password" novalidate{if $pw_modern} data-exp-password-form data-exp-password-config="{$password_js_config_json|wash}"{/if}>
{if $pw_first_error}
    <div class="exp-pw-notice exp-pw-summary" id="password-error-summary" role="alert" tabindex="-1" aria-labelledby="password-error-summary-title" data-exp-password-summary>
        <h2 id="password-error-summary-title">{'Your password was not changed'|i18n( 'design/ezwebin/user/password' )}</h2>
        <ul>
        {foreach $pw_ids as $pw_field => $pw_id}{foreach $pw_errors[$pw_field] as $pw_text}
            <li><a href="#{$pw_id}">{$pw_text|wash}</a></li>
        {/foreach}{/foreach}
        </ul>
    </div>
{elseif and( is_set( $error_summary ), $error_summary|count )}
    <div class="exp-pw-notice exp-pw-summary" id="password-error-summary" role="alert" tabindex="-1" data-exp-password-summary>
        <h2>{'Your password was not changed'|i18n( 'design/ezwebin/user/password' )}</h2>
        <ul>{foreach $error_summary as $pw_error}<li>{$pw_error.text|wash}</li>{/foreach}</ul>
    </div>
{elseif first_set( $demo_mode_locked, false() )}
    {* demo mode (EXP_DEMO_MODE=1): an administrator's password cannot be changed; the text comes from the kernel *}
    <div class="exp-pw-notice exp-pw-summary" role="status"><p>{$demo_mode_text|wash}</p></div>
{elseif first_set( $password_change_required, false() )}
    {* the account is marked "change password at next sign-in": nothing else opens until it is changed *}
    <div class="exp-pw-notice exp-pw-summary" role="status"><p>{'Choose a new password before you continue: an administrator asked you to replace the password you have now.'|i18n( 'design/standard/user/password' )}</p></div>
{/if}
    <p class="exp-pw-who">{'Signed in as %1.'|i18n( 'design/ezwebin/user/password', '', array( $userAccount.login|wash ) )}</p>

    <div class="block exp-pw-field{if $pw_errors.oldPassword|count} exp-pw-has-error{/if}">
        <label for="password-old">{'Current password'|i18n( 'design/ezwebin/user/password' )}</label>
        {if $pw_errors.oldPassword|count}<p class="exp-pw-error" id="password-old-error">{foreach $pw_errors.oldPassword as $pw_text}<span>{$pw_text|wash}</span>{/foreach}</p>{/if}
        <div class="exp-pw-row">
            <input id="password-old" class="halfbox" type="password" name="oldPassword" autocomplete="current-password" required data-exp-password="current"{if $pw_errors.oldPassword|count} aria-invalid="true" aria-describedby="password-old-error"{/if}{if eq( $pw_first_error, 'password-old' )} autofocus{/if} />
            {if $pw_modern}<button type="button" class="exp-pw-toggle" aria-controls="password-old" aria-pressed="false" data-exp-password-toggle="password-old" hidden><span data-exp-password-toggle-text>{$pw_t.show|wash}</span><span class="exp-pw-vh"> {'current password'|i18n( 'design/ezwebin/user/password' )}</span></button>{/if}
        </div>
    </div>

    <div class="block exp-pw-field{if $pw_errors.newPassword|count} exp-pw-has-error{/if}">
        <label for="password-new">{'New password'|i18n( 'design/ezwebin/user/password' )}</label>
        {if $pw_errors.newPassword|count}<p class="exp-pw-error" id="password-new-error">{foreach $pw_errors.newPassword as $pw_text}<span>{$pw_text|wash}</span>{/foreach}</p>{/if}
        <div class="exp-pw-row">
            <input id="password-new" class="halfbox" type="password" name="newPassword" autocomplete="new-password" required minlength="{$pw_min}" data-exp-password="new" aria-describedby="{if $pw_errors.newPassword|count}password-new-error {/if}password-new-rules"{if $pw_errors.newPassword|count} aria-invalid="true"{/if}{if eq( $pw_first_error, 'password-new' )} autofocus{/if} />
            {if $pw_modern}<button type="button" class="exp-pw-toggle" aria-controls="password-new" aria-pressed="false" data-exp-password-toggle="password-new" hidden><span data-exp-password-toggle-text>{$pw_t.show|wash}</span><span class="exp-pw-vh"> {'new password'|i18n( 'design/ezwebin/user/password' )}</span></button>{/if}
        </div>
        <p class="exp-pw-rules-title" id="password-new-rules-title">{'Your new password needs:'|i18n( 'design/ezwebin/user/password' )}</p>
        <ul class="exp-pw-rules" id="password-new-rules" aria-labelledby="password-new-rules-title" data-exp-password-rules>
        {foreach $pw_rules as $pw_rule}
            <li data-exp-password-rule="{$pw_rule.id|wash}"{if $pw_rule.failed} data-state="unmet" data-failed="true"{/if}>{$pw_rule.text|wash}<span class="exp-pw-vh">, </span><span class="exp-pw-vh" data-exp-password-rule-state>{if $pw_rule.failed}{$pw_t.ruleUnmet|wash}{/if}</span></li>
        {/foreach}
        </ul>
        {if $pw_modern}
        <div class="exp-pw-meter" data-exp-password-meter hidden>
            <span class="exp-pw-meter-caption">{$pw_t.strength|wash}:</span>
            <span class="exp-pw-meter-bar" aria-hidden="true"><span></span></span>
            <span class="exp-pw-meter-label" data-exp-password-meter-text aria-live="polite"></span>
        </div>
        <p class="exp-pw-generate-row"><button type="button" class="button exp-pw-button" data-exp-password-generate hidden>{'Generate a strong password'|i18n( 'design/ezwebin/user/password' )}</button></p>
        <p class="exp-pw-status" data-exp-password-status role="status" hidden></p>
        {/if}
    </div>

    <div class="block exp-pw-field{if $pw_errors.confirmPassword|count} exp-pw-has-error{/if}">
        <label for="password-confirm">{'Confirm new password'|i18n( 'design/ezwebin/user/password' )}</label>
        {if $pw_errors.confirmPassword|count}<p class="exp-pw-error" id="password-confirm-error">{foreach $pw_errors.confirmPassword as $pw_text}<span>{$pw_text|wash}</span>{/foreach}</p>{/if}
        <div class="exp-pw-row">
            <input id="password-confirm" class="halfbox" type="password" name="confirmPassword" autocomplete="new-password" required data-exp-password="confirm"{if $pw_errors.confirmPassword|count} aria-invalid="true" aria-describedby="password-confirm-error"{/if}{if eq( $pw_first_error, 'password-confirm' )} autofocus{/if} />
            {if $pw_modern}<button type="button" class="exp-pw-toggle" aria-controls="password-confirm" aria-pressed="false" data-exp-password-toggle="password-confirm" hidden><span data-exp-password-toggle-text>{$pw_t.show|wash}</span><span class="exp-pw-vh"> {'new password again'|i18n( 'design/ezwebin/user/password' )}</span></button>{/if}
        </div>
        {if $pw_modern}<p class="exp-pw-match" data-exp-password-match aria-live="polite" hidden></p>{/if}
    </div>

    <div class="buttonblock exp-pw-actions">
        <input class="defaultbutton exp-pw-button exp-pw-primary" type="submit" name="OKButton" value="{'Change password'|i18n( 'design/ezwebin/user/password' )}" />
        <input class="button exp-pw-button" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'design/ezwebin/user/password' )}" formnovalidate />
    </div>
</form>
{if $pw_modern}{ezscript( array( 'exp_password_field.js' ) )}{/if}
{/if}
</div>

</div></div></div>
<div class="border-bl"><div class="border-br"><div class="border-bc"></div></div></div>
</div>
{undef $pw_errors $pw_rules $pw_min $pw_t $pw_changed $pw_modern $pw_ids $pw_first_error}

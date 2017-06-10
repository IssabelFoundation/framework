<script type='text/javascript' src="modules/{$module_name}/themes/default/js/change_password.js"></script>
<table class='tabForm' width='100%' >
    <tr class='letra12'>
        <td align='left'><b>{$CURRENT_PASSWORD}</b></td>
        <td align='left'><input type='password' id='curr_pass' name='curr_pass' value='' /></td>
    </tr>
    <tr class='letra12'>
        <td align='left'><b>{$NEW_PASSWORD}</b></td>
        <td align='left'><input type='password' id='curr_pass_new' name='curr_pass_new' value='' /></td>
    </tr>
    <tr class='letra12'>
        <td align='left'><b>{$RETYPE_PASSWORD}</b></td>
        <td align='left'><input type='password' id='curr_pass_renew' name='curr_pass_renew' value='' /></td>
    </tr>
    <tr class='letra12'>
        <td align='center' colspan='2'><input type='button' id='sendChanPass' name='sendChanPss' value='{$CHANGE_PASSWORD_BTN}' /></td>
    </tr>
</table>
<input type="hidden" id="lblCurrentPassAlert" value="{$CURRENT_PASSWORD_ALERT}" />
<input type="hidden" id="lblNewRetypePassAlert"   value="{$NEW_RETYPE_PASSWORD_ALERT}" />
<input type="hidden" id="lblPassNoTMatchAlert" value="{$PASSWORDS_NOT_MATCH}" />

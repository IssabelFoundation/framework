{if $userExtension_success == 1}
{literal}
<script type="text/javascript">
if (window.opener && !window.opener.closed) {
    window.opener.location.reload();
}
window.close();
</script>
{/literal}
{else}
<form method="POST">
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
<tr>
  <td>
    <table width="100%" cellpadding="4" cellspacing="0" border="0">
      <tr>
        <td align="left">
          <button class="button" type="submit" name="save" value="{$SAVE}"><i class='fa fa-save'></i> {$SAVE}</button>
          {if $editUserExtension ne 'yes'}<input class="button" type="submit" name="cancel" value="{$CANCEL}">{/if}
        </td>
        {if $mode ne 'view'}
          <td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
        {/if}
     </tr>
   </table>
  </td>
</tr>
<tr>
  <td>
    <fieldset>
        <legend><b>{$LBL_CORE_FIELDS}</b></legend>
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
          <tr>
            <td width="20%">{$name.LABEL}: {if $mode ne 'view'}<span  class="required">*</span>{/if}</td>
            <td width="30%">{$name.INPUT}</td>
            <td width="25%">{$description.LABEL}:</td>
            <td width="25%">{$description.INPUT}</td>
          </tr>
          <tr>
            <td>{$password1.LABEL}: {if $mode ne 'view'}<span  class="required">*</span>{/if}</td>
            <td>{$password1.INPUT}</td>
            <td>{$password2.LABEL}: {if $mode ne 'view'}<span class="required">*</span>{/if}</td>
            <td>{$password2.INPUT}</td>
          </tr>
          <tr>
            <td>{$group.LABEL}: {if $mode ne 'view'}<span  class="required">*</span>{/if}</td>
            <td>{$group.INPUT}</td>
            <td colspan="2">&nbsp;</td>
          </tr>
        </table>
    </fieldset>
    {$PLUGIN_CONTENT}
  </td>
</tr>
</table>
<input type="hidden" name="id_user" value="{$id_user}">
</form>
{/if}
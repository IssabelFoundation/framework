<link href="modules/{$module_name}/themes/default/css/styles.css" rel="stylesheet" />
<div onKeyPress="return checkSubmit(event)">
<div id="moduleContainer">
    
    <div class="div_content_style">
    <div class="title_login_register">{$registration_server}</div>
    <div class="text_info_registration">
        {$INFO_REGISTER}
        <div class="close_info" onclick="hideInfoRegistration()">x</div>
    </div>
	
    <div class="info_registration" onclick="showInfoRegistration()">?</div>
        <div id='cloud-login-content'>
           <div id="cloud-login-logo">
                <img src="modules/{$module_name}/images/issabel_logo_mini.png" alt="issabel log" />
            </div>
            <div class="cloud-login-line">
                <img src="modules/{$module_name}/images/icon_user.png" height="18px" alt="issabel log" class="cloud-login-img-input"/>
                <input type="text" id="input_user" name="input_user" class="cloud-login-input" defaultVal="{$EMAIL}"/>
            </div>
            <div class="cloud-login-line">
                <img src="modules/{$module_name}/images/icon_password.png" alt="issabel log" class="cloud-login-img-input"/>
                <input type="password" id="input_pass" name="input_pass" class="cloud-login-input" defaultVal="{$PASSWORD}"/>
            </div>
            <div class="cloud-login-line action_register_button" >                
                <input type="button" name="input_signup" class="cloud-signup-button" onclick="showPopupCloudRegister('{$registration}',540,405)" value="{$SIGNUP_ACTION}" style="margin-left:20px" />
                <input type="button" name="input_register" class="cloud-login-button" onclick="registrationByAccount();" value="{$REGISTER_ACTION}"/>
                <input type="hidden" name="msgtmp" id="msgtmp" value="{$sending}" />
            </div>
            <div class="cloud-login-line" >
                <a class="cloud-link_subscription" href="https://my.issabel.com/forgot.php" >{$FORGET_PASSWORD}</a>
            </div>
            <div class="cloud-login-line" >
                {$REGISTER_RECOMMENDATION}
            </div>
            <div class="cloud-login-line" >
                {$PATREON}
            </div>
            <div id="msnTextErr" align="center"></div>
            
            <div class="cloud-footernote"><a href="http://www.issabel.org" style="text-decoration: none;" target='_blank'>Issabel</a> {$ISSABEL_LICENSED} <a href="http://www.opensource.org/licenses/gpl-license.php" style="text-decoration: none;" target='_blank'>GPL</a>. 2006 - {$currentyear}.</div>
             
        </div>
    </div>
</div>
</div>
{literal}
<script src="modules/{/literal}{$module_name}{literal}/themes/default/js/javascript.js" type="text/javascript"></script>


{/literal}

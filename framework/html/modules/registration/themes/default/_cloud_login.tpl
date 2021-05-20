<link href="modules/{$module_name}/themes/default/css/styles.css" rel="stylesheet" />
<script src="{$WEBPATH}themes/{$THEMENAME}/js/lottie.min.js"></script>
<script type="text/javascript">
var baseurl = '';

$(document).ready(function() {
    var anim;
    var animData = {
        container: document.getElementById('animIssabel'),
        renderer: 'svg',
        loop: false,
        autoplay: true,
        rendererSettings: {
            progressiveLoad: false
        },
        path: '{$WEBPATH}themes/{$THEMENAME}/images/animIssabel.json'
    };
    setTimeout(function(){
        anim = bodymovin.loadAnimation(animData);
        anim.setSpeed(2);
    }, 500);
});
</script>

<div onKeyPress="return checkSubmit(event)">
<div id="moduleContainer" style="overflow-y:hidden;overflow-x:hidden">
    
    <div class="div_content_style" style="width:99%;margin:auto;">
    <div class="title_login_register">{$registration_server}</div>
    <div class="text_info_registration">
        {$INFO_REGISTER}
        <div class="close_info" onclick="hideInfoRegistration()">x</div>
    </div>
	
    <div class="info_registration" onclick="showInfoRegistration()">?</div>
        <div id='cloud-login-content' style="width:98%;margin:auto;">
            <div id="animIssabel" style='width:90px; height:90px; margin:auto;'></div>
            </br>
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
            <div style="height:15px;" id="msnTextErr" align="center"></div>
            <div class="cloud-login-line" >
                <a class="cloud-link_subscription" href="https://my.issabel.com/forgot.php" >{$FORGET_PASSWORD}</a>
                </br>
                {$REGISTER_RECOMMENDATION}
            </div>
            <div style="height:20px;" class="cloud-login-line" >
                <div class="btn-shine" style="width:98%;margin:auto;">
                  {$PATREON_LEGEND}
                </div>
            </div>
            <div class="cloud-login-line">
                {$PATREON}
            </div>
            <div class="cloud-footernote" style="width:95%;margin:auto;"><a href="http://www.issabel.org" style="text-decoration: none;" target='_blank'>Issabel</a> {$ISSABEL_LICENSED} <a href="http://www.opensource.org/licenses/gpl-license.php" style="text-decoration: none;" target='_blank'>GPL</a>. 2006 - {$currentyear}.</div>
             
        </div>
    </div>
</div>
</div>
{literal}
<script src="modules/{/literal}{$module_name}{literal}/themes/default/js/javascript.js" type="text/javascript"></script>


{/literal}

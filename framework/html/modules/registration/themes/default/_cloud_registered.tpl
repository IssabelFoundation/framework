<link href="modules/{$module_name}/themes/default/css/styles.css" rel="stylesheet" />

<div id="moduleContainer">
    <div class="div_content_style">
    <div class="title_login_register">{$registered_server}</div>
        <div class="cloud-login-line" style="height: 84px">
            <img src="modules/{$module_name}/images/issabel_logo_mini.png" alt="issabel log" />
        </div>

        <div class="div_table_style">
            <div class="div_tr1_style">
                <div class="div_td1_style">{$identitykeylbl}</div>
                <div class="div_td2_style"><b id="identitykey" class="b-style"></b></div>               
            </div>
            <div class="div_tr1_style">
                <div class="div_td1_style">{$companyReg.LABEL}</div>
                <div class="div_td2_style"><b id="companyReg" class="b-style"></b></div>              
            </div>
            <div class="div_tr1_style">
                <div class="div_td1_style">{$countryReg.LABEL}</div>
                <div class="div_td2_style"><b id="countryReg" class="b-style"></b></div>               
            </div>
            <div class="div_tr1_style">
                <div class="div_td1_style">{$cityReg.LABEL}</div>
                <div class="div_td2_style" style="width:140px"><b id="cityReg" class="b-style"></b></div>              
                <div class="div_td1_style" style="width:75px">{$phoneReg.LABEL}</div>
                <div class="div_td2_style" style="width:140px"><b id="phoneReg" class="b-style"></b></div> 
            </div>                     
            <div class="div_tr1_style">
                <div class="div_td1_style">{$contactNameReg.LABEL}</div>
                <div class="div_td2_style"><b id="contactNameReg" class="b-style"></b></div>                              
            </div>
            <div class="div_tr1_style">
                <div class="div_td1_style">{$emailReg.LABEL}</div>
                <div class="div_td2_style"><b id="emailReg" class="b-style"></b></div>
            </div>
            <div class="cloud-login-line" ></div>
            <div class="cloud-login-line" >
                {$PATREON}
            </div>
            <div id="msnTextErr" align="center" style="height:58px;"></div>
            <div class="cloud-footernote"><a href="http://www.issabel.org" style="text-decoration: none;" target='_blank'>Issabel</a> {$ISSABEL_LICENSED} <a href="http://www.opensource.org/licenses/gpl-license.php" style="text-decoration: none;" target='_blank'>GPL</a>. 2006 - {$currentyear}.</div>
            <br>
        </div>
    </div>
</div>

{literal}
<script src="modules/{/literal}{$module_name}{literal}/themes/default/js/javascript.js" type="text/javascript"></script>
{/literal}

<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2018 Issabel Foundation                                |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Initial Developer of the Original Code is Issabel Foundation     |
  +----------------------------------------------------------------------+
  $Id: IssabelFirstBoot.class.php, Tue 12 Mar 2019 03:10:12 PM EDT, nicolas@issabel.com
*/

class IssabelFirstBoot {

    var $_DB; // instancia de la clase paloDB
    var $errMsg;

    var $lang = array();

    function __construct() {
        if(is_file("/etc/asterisk/firstboot")) { 

            $this->lang['es']['Set console root password']='Elija la contraseña root de consola';
            $this->lang['es']['Please enter the root password for console/ssh access. It must be at least 8 characters long and contain letters and numbers.']='Por favor ingrese una contraseña para acceso root desde consola/ssh. Debe tener al menos 8 caracteres de largo, incluyendo letras o números.';
            $this->lang['es']['Set web admin password']='Elija la contraseña para el administrador web';
            $this->lang['es']['Please enter the admin password for web access']='Por favor ingrese una contraseña para el usuario admin utilizado para la administración/configuración via web/navegador';
            $this->lang['es']['Set MariaDB root password']='Elija la contraseña root de la base de datos MariaDB';
            $this->lang['es']['Please enter the root password for MariaDB database access']='Por favor ingrese una contraseña para el usuario root de la base de datos MariaDB';
            $this->lang['es']['Next']='Siguiente';
            $this->lang['es']['Previous']='Anterior';
            $this->lang['es']['Finish']='Finalizar';
            $this->lang['es']['password']='contraseña';
            $this->lang['es']['confirm password']='confirmar contraseña';
            $this->lang['es']['Issabel Initial Setup']='Configuración Inicial Issabel';
            $this->lang['es']['Setting Passwords. Please wait.']='Estableciendo contraseñas. Por favor espere.';
            $this->lang['es']['Passwords do not match']='Las contraseñas no coinciden';
            $this->lang['es']['Must contain at least one uppercase letter, one lowercase letter and a number. No symbols allowed.']='Debe contener al menos una letra mayúscula, una minúscula y un número. No se permiten símbolos.';

            $this->_show_form($_POST);
            die();
        }
    }

    function _show_form($post) {

        global $arrConf;
        global $arrConfModule;

        if(isset($post['rootpwd'])) {

            $consolepwd = $post['consolepwd'];
            $rootpwd    = $post['rootpwd'];
            $mariadbpwd = $post['mariadbpwd'];

            if(!preg_match("/^[a-zA-Z\d@]+$/", $consolepwd)) {
                die('no');
            }

            if(!preg_match("/^[a-zA-Z\d@]+$/", $rootpwd)) {
                die('no');
            }

            if(!preg_match("/^[a-zA-Z\d@]+$/", $mariadbpwd)) {
                die('no');
            }

            $sComando = "/usr/bin/issabel-helper change_issabel_passwords -a $consolepwd -m $mariadbpwd -r $rootpwd";
            $output = $ret = NULL;
            exec($sComando, $output, $ret);

            if($ret==0) {
                unlink("/etc/asterisk/firstboot");
            }
            die();
        }

        $phplang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

        $langarray = "var lang = {};\n";

        $missing_lang = array('en');
        if(!isset($this->lang[$phplang])) {
            $missing_lang[]=$phplang;
        }
        $missing_lang = array_unique($missing_lang);

        foreach($missing_lang as $iso) {
            $langarray.="lang['$iso']={}\n";
            foreach($this->lang['es'] as $text=>$trans) {
                $langarray.="lang['$iso']['$text']='".$text."';\n";
            }
        }

        foreach($this->lang as $iso=>$data) {
            $langarray.="lang['$iso']={}\n";
            foreach($data as $text => $value) {
                $langarray.="lang['$iso']['$text']='".$value."';\n";
            }
            $langarray.="if(typeof(lang['$iso']['Passwords do not match'])==='undefined') { lang['$iso']['Passwords do not match']='Passwords do not match'; }";
            $langarray.="if(typeof(lang['$iso']['Must contain at least one uppercase letter, one lowercase letter and a number. No symbols allowed.'])==='undefined') { lang['$iso']['Must contain at least one uppercase letter, one lowercase letter and a number. No symbols allowed.']='Must contain at least one uppercase letter, one lowercase letter and a number. No symbols allowed.'; }";
        }


echo "

<!DOCTYPE html>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en-us' lang='en-us' >
<head>
    <title>Issabel Initial Setup</title>
    <meta http-equiv='content-type' content='text/html; charset=UTF-8'/>
    <meta http-equiv='imagetoolbar' content='false'/>
    <meta name='MSSmartTagsPreventParsing' content='true'/>
    <meta name='description' content=''/>
    <meta name='keywords' content=''/>

    <link href='themes/tenant/css/bootstrap.css' rel='stylesheet' id='bootstrap-css'>
    <script src='libs/js/jquery/jquery-1.11.2.min.js'></script>
    <script src='themes/tenant/js/bootstrap.min.js'></script>

<style>
/*
 *  * Specific styles of signin component
 *   */
/*
 *  * General styles
 *   */
body, html {
    height: 100%;
    background-repeat: no-repeat;
    _background-image: linear-gradient(rgb(104, 145, 162), rgb(12, 97, 33));
    background-image: linear-gradient(rgb(104, 145, 162), rgb(40, 21, 56));
}

.card-container.card {
    max-width: 850px;
    padding: 40px;
}

.btn {
    font-weight: 700;
    height: 36px;
    -moz-user-select: none;
    -webkit-user-select: none;
    user-select: none;
    cursor: default;
}

/*
 *  * Card component
 *   */
.card {
    background-color: #F7F7F7;
    /* just in case there no content*/
    padding: 20px 25px 30px;
    margin: 0 auto 25px;
    margin-top: 50px;
    /* shadows and rounded borders */
    -moz-border-radius: 2px;
    -webkit-border-radius: 2px;
    border-radius: 2px;
    -moz-box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);
    -webkit-box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);
    box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);
}

.profile-img-card {
    width: 96px;
    height: 96px;
    margin: 0 auto 10px;
    display: block;
    -moz-border-radius: 50%;
    -webkit-border-radius: 50%;
    border-radius: 50%;
}

/*
 *  * Form styles
 *   */
.profile-name-card {
    font-size: 16px;
    font-weight: bold;
    text-align: center;
    margin: 10px 0 0;
    min-height: 1em;
}

.reauth-email {
    display: block;
    color: #404040;
    line-height: 2;
    margin-bottom: 10px;
    font-size: 14px;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
}

.form-signin #inputEmail,
.form-signin #inputPassword {
    direction: ltr;
    height: 44px;
    font-size: 16px;
}

.form-signin input[type=email],
.form-signin input[type=password],
.form-signin input[type=text],
.form-signin button {
    width: 100%;
    display: block;
    _margin-bottom: 10px;
    z-index: 1;
    position: relative;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
}

.form-signin .form-control:focus {
    border-color: rgb(104, 145, 162);
    outline: 0;
    -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgb(104, 145, 162);
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgb(104, 145, 162);
}

.btn.btn-signin {
    /*background-color: #4d90fe; */
    background-color: rgb(104, 145, 162);
    /* background-color: linear-gradient(rgb(104, 145, 162), rgb(12, 97, 33));*/
    padding: 0px;
    font-weight: 700;
    font-size: 14px;
    height: 36px;
    -moz-border-radius: 3px;
    -webkit-border-radius: 3px;
    border-radius: 3px;
    border: none;
    -o-transition: all 0.218s;
    -moz-transition: all 0.218s;
    -webkit-transition: all 0.218s;
    transition: all 0.218s;
}

.btn.btn-signin:hover,
.btn.btn-signin:active,
.btn.btn-signin:focus {
    background-color: rgb(12, 97, 33);
}

.password-verdict {
    color: #000;
}

.input-group-addon { border: 1px solid #aaa; border-rigth: 0 !important;}
.form-control { border: 1px solid #aaa !important; }


#wrapper{
    background-color:#f9f9f9;
    width:785px;
    overflow:hidden;
}
#steps{
    width:800px;
    overflow:hidden;
}
.step{
    float:left;
    width:800px;
    height: 300px;
    position: relative;
}
fieldset.step div.nav {
  position: absolute;
  bottom: 0;
  left: 0;
}

#navigation{
    height:45px;
}
#navigation ul{
    list-style:none;
    float:left;
    margin-left:22px;
padding-inline-start:0;
}
#navigation ul li{
    float:left;
    border-right:1px solid #ccc;
    border-left:1px solid #ccc;
    position:relative;
    margin:0px 2px;
}

#navigation ul li a{
    display:block;
    height:45px;
    background-color:#444;
    color:#777;
    outline:none;
    font-weight:bold;
    text-decoration:none;
    line-height:45px;
    padding:0px 20px;
    border-right:1px solid #fff;
    border-left:1px solid #fff;
    background:#f0f0f0;
    background:
        -webkit-gradient(
        linear,
        left bottom,
        left top,
        color-stop(0.09, rgb(240,240,240)),
        color-stop(0.55, rgb(227,227,227)),
        color-stop(0.78, rgb(240,240,240))
        );
    background:
        -moz-linear-gradient(
        center bottom,
        rgb(240,240,240) 9%,
        rgb(227,227,227) 55%,
        rgb(240,240,240) 78%
        )
}
#navigation ul li a:hover,
#navigation ul li.selected a{
    background:#d8d8d8;
    color:#666;
    text-shadow:1px 1px 1px #fff;
}

span.checked{
    background:transparent url(../images/checked.png) no-repeat top left;
    position:absolute;
    top:0px;
    left:1px;
    width:20px;
    height:20px;
}
span.error{
    background:transparent url(../images/error.png) no-repeat top left;
    position:absolute;
    top:0px;
    left:1px;
    width:20px;
    height:20px;
}

#steps form fieldset{
    border:none;
    padding-bottom:20px;
}
#steps form legend{
    text-align:left;
    background-color:#f0f0f0;
    color:#666;
    font-size:24px;
    text-shadow:1px 1px 1px #fff;
    font-weight:bold;
    float:left;
    width:98%;
    padding:5px 0px 5px 10px;
    margin:10px 0px;
    border-bottom:1px solid #fff;
    border-top:1px solid #d9d9d9;
}
#steps form p{
    float:left;
    clear:both;
    margin:5px 0px;
    _background-color:#f4f4f4;
    _border:1px solid #fff;
    width:800px;
    padding:10px;
}
#steps form p label{
    width:160px;
    float:left;
    text-align:right;
    margin-right:15px;
    line-height:26px;
    color:#666;
    text-shadow:1px 1px 1px #fff;
    font-weight:bold;
}
#steps form input:not([type=radio]),
#steps form textarea,
#steps form select{
    background: #ffffff;
    border: 1px solid #ddd;
    -moz-border-radius: 3px;
    -webkit-border-radius: 3px;
    border-radius: 3px;
    outline: none;
    padding: 5px;
    width: 95%;
    float:left;
    
}
.input-group {
margin-left:15px !important;
}
#steps form input:focus{
    -moz-box-shadow:0px 0px 3px #aaa;
    -webkit-box-shadow:0px 0px 3px #aaa;
    box-shadow:0px 0px 3px #aaa;
    background-color:#FFFEEF;
}
#steps form p.submit{
    background:none;
    border:none;
    -moz-box-shadow:none;
    -webkit-box-shadow:none;
    box-shadow:none;
}
#steps form button {
    border:none;
    outline:none;
    -moz-border-radius: 10px;
    -webkit-border-radius: 10px;
    border-radius: 10px;
    color: #ffffff;
    display: block;
    cursor:pointer;
    margin: 0px auto;
    clear:both;
    padding: 7px 25px;
    text-shadow: 0 1px 1px #777;
    font-weight:bold;
    font-family:'Century Gothic', Helvetica, sans-serif;
    font-size:22px;
    -moz-box-shadow:0px 0px 3px #aaa;
    -webkit-box-shadow:0px 0px 3px #aaa;
    box-shadow:0px 0px 3px #aaa;
    background:#4797ED;
}
#steps p { font-size: 1.3em; }

#steps form button:hover {
    background:#d8d8d8;
    color:#666;
    text-shadow:1px 1px 1px #fff;
}
</style>

</head>

<body>
<div class='xcontainer'>
<div class='row'>
<!--div class='span4'>&nbsp;</div>
<div class='span4'-->

    <div class='container'>
        <div class='card card-container'>
            <img id='profile-img' style='display:block; margin: 0 auto;' src='themes/tenant/images/issabel_logo_mini.png' />

<div id='wrapper'>
    <div id='steps'>

            <form class='form-boot' id='form-boot' method='post'>

            <fieldset class='step'>
                <legend><span class='translate' data-text='Set console root password'></span></legend>

                <p data-text='Please enter the root password for console/ssh access. It must be at least 8 characters long and contain letters and numbers.' class='translate'></p>

                <div class='form-group'>
                  <div class='input-group'>
                    <span class='input-group-addon'><i class='glyphicon glyphicon-lock color-blue'></i></span>
                    <input id='rootpwd' name='rootpwd' pattern='(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[\dA-Za-z@]{8,}' required  placeholder='password' class='form-control' type='password' onchange=\"notpattern(this, form.rootpwd_confirm)\">
                  </div>
                </div>

                <div class='form-group'>
                  <div class='input-group'>
                    <span class='input-group-addon'><i class='glyphicon glyphicon-lock color-blue'></i></span>
                    <input id='rootpwd_confirm' name='rootpwd_confirm' pattern='(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[\dA-Za-z@]{8,}' required placeholder='confirm password' class='form-control' type='password' onchange=\"notmatch(this)\">
                  </div>
                </div>

                <div style='width:760px;' class='nav'>
                    <div style='float:right;'> <a class='stepbutton btn btn-primary' data-index='2'><span class='translate' data-text='Next'></span></a></div>
                </div>
            </fieldset>

            <fieldset class='step'>
                <legend><span class='translate' data-text='Set MariaDB root password'></span></legend>

                <p data-text='Please enter the root password for MariaDB database access' class='translate'></p>

                <div class='form-group'>
                  <div class='input-group'>
                    <span class='input-group-addon'><i class='glyphicon glyphicon-lock color-blue'></i></span>
                    <input id='mariadbpwd' name='mariadbpwd' pattern='(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[\dA-Za-z@]{8,}' required placeholder='password' class='form-control' type='password' onchange=\"notpattern(this, form.mariadbpwd_confirm)\">
                  </div>
                </div>

                <div class='form-group'>
                  <div class='input-group'>
                    <span class='input-group-addon'><i class='glyphicon glyphicon-lock color-blue'></i></span>
                    <input id='mariadbpwd_confirm' required name='mariadbpwd_confirm'  pattern='(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[\dA-Za-z@]{8,}' placeholder='confirm password' class='form-control' type='password' onchange=\"notmatch(this)\">
                  </div>
                </div>

                <div style='width:760px;' class='nav'>
                    <div style='float:left; margin-left:15px;'><a class='stepbutton btn btn-primary' data-index='1'><span class='translate' data-text='Previous'></span></a></div>
                    <div style='float:right;'><a class='stepbutton btn btn-primary' data-index='3'><span class='translate' data-text='Next'></span></a></div>
                </div>
            </fieldset>


            <fieldset class='step'>
                <legend><span class='translate' data-text='Set web admin password'></span></legend>

                <p data-text='Please enter the admin password for web access' class='translate'></p>

                <div class='form-group'>
                  <div class='input-group'>
                    <span class='input-group-addon'><i class='glyphicon glyphicon-lock color-blue'></i></span>
                    <input id='consolepwd' name='consolepwd' placeholder='password' pattern='(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[\dA-Za-z@]{8,}' class='form-control' type='password' required  onchange=\"notpattern(this, form.consolepwd_confirm)\">
                  </div>
                </div>

                <div class='form-group'>
                  <div class='input-group'>
                    <span class='input-group-addon'><i class='glyphicon glyphicon-lock color-blue'></i></span>
                    <input id='consolepwd_confirm' name='consolepwd_confirm' pattern='(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[\dA-Za-z@]{8,}' placeholder='confirm password' class='form-control' type='password' required  onchange=\"notmatch(this)\">
                  </div>
                </div>

                <div style='width:760px;' class='nav'>
                    <div style='float:left; margin-left:15px;'><a class='stepbutton btn btn-primary' data-index='2'><span class='translate' data-text='Previous'></span></a></div>
                    <div style='float:right;'><a class='stepbutton btn btn-primary' data-index='4'><span class='translate' data-text='Finish'></span></a></div>
                </div>
            </fieldset>

            <fieldset class='step'>
            <legend><span class='translate' data-text='Setting Passwords. Please wait.'></span></legend>

            <div style='display:none;'><input type=submit></div>
            <progress id='progressbar' style='width:98%; -webkit-appearance:none; appearance:none;' max='100' value='0'></progress>

            <div style='width:760px;' class='nav'>
            </div>
 
            </fieldset>
 
            </form><!-- /form -->

</div>
</div>

        </div><!-- /card-container -->
    </div><!-- /container -->
</div>
<div class='span4'>&nbsp;</div>
</div>
</div>

<script>

$langarray

var userLang = navigator.language || navigator.userLanguage; 

userLang = userLang.split('-')[0];

if(typeof(lang[userLang]) !== 'undefined') {

    // translate elements
    var elms = document.getElementsByClassName('translate');
    for(var i=0; i<elms.length;i++) {
       el = elms[i];
       if (el.dataset) {
           if(typeof(lang[userLang][el.dataset.text]) !== 'undefined') {
             el.innerHTML=lang[userLang][el.dataset.text];
           } else {
             el.innerHTML = el.dataset.text;
           }
       }
    }

    // translate placeholders
    \$('input').each(function() {
        txt = \$(this).attr('placeholder');
        if(typeof(lang[userLang][txt]) !== 'undefined') {
            \$(this).attr('placeholder',lang[userLang][txt]);
        }
    });

    var curtitle = document.title;
    if(typeof(lang[userLang][curtitle]) !== 'undefined') {
        document.title = lang[userLang][curtitle];
    }
} else {
    // default to english
    var elms = document.getElementsByClassName('translate');
    for(var i=0; i<elms.length;i++) {
       el = elms[i];
       if (el.dataset) {
           el.innerHTML = el.dataset.text;
       }
    }
}

function notpattern(el,confirmel) {
    el.setCustomValidity(el.validity.patternMismatch ? lang[userLang]['Must contain at least one uppercase letter, one lowercase letter and a number. No symbols allowed.'] : ''); if(el.checkValidity()) { confirmel.pattern = el.value; }
}


function notmatch(el) {
    el.setCustomValidity(el.validity.patternMismatch ? lang[userLang]['Passwords do not match'] : '');
}


\$(function() {

    var fieldsetCount = \$('#form-boot').children().length;
    var percentage  = 0;
    var current     = 1;

    /*
    sum and save the widths of each one of the fieldsets
    set the final sum as the total width of the steps element
    */
    var stepsWidth    = 0;
    var widths         = new Array();
    \$('#steps .step').each(function(i){
        var \$step         = \$(this);
        widths[i]          = stepsWidth;
        stepsWidth         += \$step.width();
    });
    \$('#steps').width(stepsWidth);

    \$('#form-boot').children(':first').find(':input:first').focus();    

     \$('a.stepbutton').bind('click',function(e){
         var prev = current;
         var hasError = validateStep(prev);

         if(hasError===true) {

             var \$myForm = $('#form-boot');

             if(! \$myForm[0].checkValidity()) {
                 \$myForm.find(':submit').click();
             }

         } else  {

             current = \$(this).data('index');
             \$('#steps').stop().animate({
                 marginLeft: '-' + widths[current-1] + 'px'
             },500,function(){
                 if(current == fieldsetCount)
                    changePasswords();
                  else
                    validateStep(prev);
                 \$('#form-boot').children(':nth-child('+ parseInt(current) +')').find(':input:first').focus();
             });
          }
          e.preventDefault();
     });

    /*
    clicking on the tab (on the last input of each fieldset), makes the form
    slide to the next step
    */
    \$('#form-boot > fieldset').each(function(){
        var \$fieldset = \$(this);
        \$fieldset.find(':input:last').keydown(function(e){
               
            if (e.which == 9){
                var hasError = validateStep(current);
                if(hasError==false) {
                    \$('#navigation li:nth-child(' + (parseInt(current)+1) + ') a').click();
                    /* force the blur for validation */
                    \$(this).blur();
                }
                e.preventDefault();
            }
        });
    });


    function changePasswords() {
        var rootpwd = \$('#rootpwd').val();
        var consolepwd = \$('#consolepwd').val();
        var mariadbpwd = \$('#mariadbpwd').val();
        //console.log('root '+rootpwd);
        //console.log('mariadb '+mariadbpwd);
        //console.log('console '+consolepwd);

        $.ajax({
           method: 'POST',
           data: { 
               rootpwd: rootpwd,
               consolepwd: consolepwd,
               mariadbpwd: mariadbpwd
           }
        }).done(function() { 
            window.location.reload(true); 
        });

        updateprogress();
        
    }

    function updateprogress() {
        percentage=percentage+1;
        if(percentage<=100) {
            //console.log(percentage);
            \$('#progressbar').val(percentage);
            setTimeout(updateprogress,1000);
        }
    }

    function validateStep(step){

        if(step == fieldsetCount) return;

        var hasError = false;
        \$('#form-boot').children(':nth-child('+ parseInt(step) +')').find(':input:not(button)').each(function(){
            var \$this = \$(this);

            if(\$this[0].checkValidity() === false) {
                hasError = true;
                \$this.css('background-color','#FFEDEF');
            } else {
                \$this.css('background-color','#FFFFFF');
            }
        });
        return hasError;
    }

});
</script>



</body>
</html>

";


    }
}

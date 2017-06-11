<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0.4                                                |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
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
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: SOAPhandler.class.php,v 1.0 2011-03-31 14:33:00 Alberto Santos F.  asantos@palosanto.com Exp $*/

$document_root = $_SERVER["DOCUMENT_ROOT"];
require_once("$document_root/libs/misc.lib.php");
require_once("$document_root/configs/default.conf.php");
require_once("$document_root/libs/paloSantoDB.class.php");
require_once("$document_root/libs/paloSantoACL.class.php");
require_once("$document_root/libs/WSDLcreator.class.php");

class SOAPhandler
{
    /**
     * SOAP Address
     *
     * @var string
     */
    private $soapAddress;

    /**
     * Name of the class that contains the functional points for SOAP
     *
     * @var string
     */
    private $className;

    /**
     * Description error message
     *
     * @var array
     */
    private $errorMSG;

    /**
     * SOAP Server Object
     *
     * @var object
     */
    private $objSOAPServer;

    /**
     * Name for the WSDL
     *
     * @var string
     */
    private $wsdlName;

    /**
     * URN of namespace target WSDL.
     *
     * @var string
     */
    private $targetNamespace;

    /**
     * Constructor
     *
     * @param  string   $className         Name of the class that contains the functional points for SOAP
     * @param  string   $soapAddress       SOAP Address
     */
    public function SOAPhandler($className, $soapAddress=null)
    {
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
            $urlSOAP = 'https://'.$_SERVER['SERVER_NAME'];
        else $urlSOAP = 'http://'.$_SERVER['SERVER_NAME'];
        if($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443)
            $urlSOAP .= ':'.$_SERVER['SERVER_PORT'];
        if(isset($_SERVER['SCRIPT_NAME']))
            $urlSOAP .= $_SERVER['SCRIPT_NAME'];

        $soapAddress = isset($soapAddress)?$soapAddress:$urlSOAP;
        $this->soapAddress     = $soapAddress;

        $this->className       = $className;
        $this->errorMSG        = NULL;
        $this->objSOAPServer   = NULL;
        $this->wsdlName        = "genericName_WSDL";
        $this->targetNamespace = "http://cloud.issabel.org/webservices";
        header('Content-Type: text/xml; charset=utf-8');
    }

    /**
     * Function that exports the WSDL, if it has not already done so 
     *
     * @param   string     $mode           The modes are: 'download', 'file' and 'print', by default is 'print'.
     * @param   string     $targetFile     If $mode is 'file', the $targetFile param is the path write directory
     * @return  boolean   True if the WSDL was exported successfully, or false if an error exists
     */
    public function exportWSDL($mode="print", $targetFile=null)
    {
        if(empty($this->wsdlName)){
            $this->errorMSG["fc"] = 'ERROR';
            $this->errorMSG["fm"] = 'Internal Error';
            $this->errorMSG["fd"] = "WSDL name isn't defined.";
            $this->errorMSG["cn"] = get_class($this);
            return false;
        }

        if(empty($this->soapAddress)){
            $this->errorMSG["fc"] = 'ERROR';
            $this->errorMSG["fm"] = 'Internal Error';
            $this->errorMSG["fd"] = "WSDL soapAddress isn't defined.";
            $this->errorMSG["cn"] = get_class($this);
            return false;
        }

        if(!class_exists($this->className)){
            $this->errorMSG["fc"] = 'ERROR';
            $this->errorMSG["fm"] = 'Internal Error';
            $this->errorMSG["fd"] = "The class ({$this->className}) isn't a class.";
            $this->errorMSG["cn"] = get_class($this);
            return false;
        }

        if(!preg_match("/^SOAP_[[:alnum:]]+$/",$this->className)){
            $this->errorMSG["fc"] = 'BADFORMAT ERROR';
            $this->errorMSG["fm"] = 'Internal Error';
            $this->errorMSG["fd"] = "The class name ({$this->className}) isn't a valid format. A valid format is \"SOAP_SomeName\".";
            $this->errorMSG["cn"] = get_class($this);
            return false;
        }
        else{
            $name = explode("_",$this->className);
            $this->wsdlName = $name[1];
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET' && array_search('wsdl', array_map('strtolower', array_keys($_GET))) !== FALSE) {
            $objWSDL = new WSDLcreator($this->wsdlName,$this->targetNamespace,$this->soapAddress);
            //$objWSDL->enableCache(); //mejorar tengo un error en el nombre de archivo cache

            $arrFP = call_user_func(array($this->className,'getFP'));
            $objWSDL->generate($arrFP);
            $objWSDL->export($mode, $targetFile);

            if($objWSDL->getError()){
                $this->errorMSG["fc"] = 'ERROR';
                $this->errorMSG["fm"] = 'Internal Error';
                $this->errorMSG["fd"] = "WSDL Error: ".$objWSDL->getError();
                $this->errorMSG["cn"] = get_class($this);
                return false;
            }
            exit;
        }
        return true;
    }

    /**
     * Function that verifies if the user in the variable $_SERVER['PHP_AUTH_USER'] is correctly authenticated
     *
     * @return  boolean   True if the authentication was successfully, or false if not
     */
    public function authentication()
    {
        global $arrConf;
        // Obligar a pedir un usuario y contraseña de ACL
        if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] == '') {
            $this->errorMSG["fc"] = 'UNAUTHORIZED';
            $this->errorMSG["fm"] = 'Not authorized';
            $this->errorMSG["fd"] = 'This method requires username/password authentication.';
            $this->errorMSG["cn"] = get_class($this);
            return false;
        }

        $pDB = new paloDB($arrConf['issabel_dsn']['acl']);
        $pACL = new paloACL($pDB);
        if(!empty($pACL->errMsg)) {
            $this->errorMSG["fc"] = 'UNAUTHORIZED';
            $this->errorMSG["fm"] = 'Authentication failed';
            $this->errorMSG["fd"] = 'Unable to authenticate due to DB error: '.$pACL->errMsg;
            $this->errorMSG["cn"] = get_class($this);
            return false;
        }

        if (!$pACL->authenticateUser($_SERVER['PHP_AUTH_USER'], md5($_SERVER['PHP_AUTH_PW']))) {
            $this->errorMSG["fc"] = 'UNAUTHORIZED';
            $this->errorMSG["fm"] = 'Authentication failed';
            $this->errorMSG["fd"] = 'Invalid username or password';
            $this->errorMSG["cn"] = get_class($this);
            return false;
        }

        return true;
    }

    /**
     * Procedure that starts the SOAP Server. It stores the object SoapServer in the class attribute objSOAPServer and sets the
     * necessary parameteres.
     *
     * @param   boolean     $cache     if TRUE the cache will be enabled, FALSE it will be disabled
     */
    public function execute($cache=false)
    {
        $cache = ($cache)? "1":"0";
        ini_set("soap.wsdl_cache_enabled", $cache);
        $this->objSOAPServer = new SoapServer($this->soapAddress."?WSDL");

        $this->objSOAPServer->setClass($this->className,$this->objSOAPServer);
        $this->objSOAPServer->handle();
    }

    /**
     * 
     * Function that returns the error message as a SOAP Fault
     *
     * @return  string   Message error if had an error, or NULL if not
     */
    public function getError()
    {
        if(is_array($this->errorMSG) && count($this->errorMSG) > 0){
            $faultcode = isset($this->errorMSG["fc"])?$this->errorMSG["fc"]:"UNKNOWN ERROR";
            $faultmsg  = isset($this->errorMSG["fm"])?$this->errorMSG["fm"]:$faultcode;
            $faultdesc = isset($this->errorMSG["fd"])?$this->errorMSG["fd"]:$faultcode;
            $classname = isset($this->errorMSG["cn"])?$this->errorMSG["cn"]:get_class($this);
            return $this->createSOAPFault($faultcode,$faultmsg,$faultdesc,$classname);
        }
        else return NULL;
    }

    /**
     * Function that creates a SOAP fault
     *
     * @param   string     $faultcode    Code for the SOAP fault
     * @param   string     $faultmsg     Short Message for the SOAP fault
     * @param   string     $faultdesc    Long Description of the error for the SOAP fault
     * @param   string     $classname    Class name where the error started
     * @return  string     Returns the xml for the SOAP fault 
     */
    private function createSOAPFault($faultcode, $faultmsg, $faultdesc, $classname)
    {
        header('Content-Type: text/xml; charset=utf-8');
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="IssabelWebService"');

        return <<<SOAP_FAULT
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
<SOAP-ENV:Body>
<SOAP-ENV:Fault>
<faultcode>$faultcode</faultcode>
<faultstring>$faultmsg</faultstring>
<faultactor>$classname</faultactor>
<detail>$faultdesc</detail>
</SOAP-ENV:Fault>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
SOAP_FAULT;
    }

    //private function autoLoadClass
}
?>

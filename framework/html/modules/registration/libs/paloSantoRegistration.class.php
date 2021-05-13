<?php

/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0.0-31                                               |
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
  $Id: paloSantoRegistration.class.php,v 1.1 2011-02-25 10:08:51 Eduardo Cueva ecueva@palosanto.com Exp $ */

class paloSantoRegistration {

    var $_DB;
    var $errMsg;
    var $_webserviceURL;

    public function paloSantoRegistration(&$pDB, $url) {
        $this->_webserviceURL = $url;
        
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB = & $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string) $pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    public function getDataLocalRegister() {
        if(!$this->columnExists("has_account")){
            if(!$this->addColumnTableRegister("has_account char(3) default 'no'")) {
                $this->errMsg = "The column 'has_account' does not exist and could not be created";
                return null;
            }
        }
        
        $query = "SELECT 
            id             AS id,
            contact_name   AS contactNameReg,
            email          AS emailReg,
            phone          AS phoneReg,
            company        AS companyReg,
            address        AS addressReg,
            city           AS cityReg,
            country        AS countryReg,
            idPartner      AS idPartnerReg,
            has_account    AS has_account
            FROM register";
        $result = $this->_DB->getFirstRowQuery($query, true);

        if ($result == FALSE) {
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }

    public function processSaveDataRegister($data, $method=NULL) {

        $this->renameKeyFile();

        // 1er. Verifico si la tabla register.db existe
        if (!$this->tableRegisterExists()) {
            if (!$this->createTableRegister()) {
                $this->errMsg = "The table register does not exist and could not be created";
                return false;
            }                
        }
        
        //// 2do. Verifico si las columnas has_account u link_auto_login existen
        if(!$this->columnExists("has_account")){
            if(!$this->addColumnTableRegister("has_account char(3) default 'no'")) {
                $this->errMsg = "The column 'has_account' does not exist and could not be created";
                return false;
            }
        }
        
        if(!$this->columnExists("link_auto_login")){
            if(!$this->addColumnTableRegister("link_auto_login varchar(100)")) {
                $this->errMsg = "The column 'link_auto_login' does not exist and could not be created";
                return false;
            }
        }

        // 3ro. Se debe verificar si ya existe algo en la base local, 
        // si existe entonces es una actualizacion si no es una insercion
        $newAccount_password = null;
        if($method=="byAccount")
            $dataOK = array("",$data[0],"","","","","");
        else if($method=="newAccount"){
            $newAccount_password =  array_shift($data);
            $dataOK   = $data;        
        }
        
        $dataOK[4] = (isset($dataOK[4]) & $dataOK[4] != "") ? $dataOK[4] : ""; //address
        $dataOK[7] =  ""; //idpartner        
        $dataOK[8] =  "yes"; //has_account, si el resultado del webservice es exitoso se inserta en db
        $this->_DB->beginTransaction();
        
        $DATA = $this->getDataLocalRegister();
        if (isset($DATA) & $DATA != "") { // actualizacion
            $dataOK[9] = 1; //id
            $status  = $this->updateDataRegister($dataOK);
        } else { // insercion
            $status  = $this->insertDataRegister($dataOK);
        }

        if (!$status) {
            $this->errMsg = "There are some problem with the local database. Information cannot be saved in database.";
            $this->_DB->rollBack();
            return false;
        }
        
        // 3ero. Saving to web service
        $rsa_key = "";
        if (!is_file("/etc/issabel.key")) {
            if(is_readable('/etc/ssh/ssh_host_rsa_key.pub')) {
                $rsa_key = file_get_contents('/etc/ssh/ssh_host_rsa_key.pub');
            } else { $rsa_key = '1234'; }
        } else {
            $rsa_key = file_get_contents("/etc/issabel.key");
        }
        
        if($method=="byAccount")
            $dataOK = array("byAccount", $data[0], $data[1], $rsa_key);
        else if($method=="newAccount"){
            $dataOK[8] = trim($rsa_key);
            $dataOK[9] = $newAccount_password;
            $dataOK = array_merge(array("newAccount"),$dataOK);
        }
        
        $response = $this->sendDataWebService($dataOK);
        
        if ($response == null) {
            $this->errMsg = "Impossible connect to Issabel Web services. Please check your internet connection.";
            $this->_DB->rollBack();
            return false;
        }
        
        $arrResponse = explode("|",$response);

        if (!(is_array($arrResponse) && count($arrResponse)==3)) {
            $this->errMsg = "Your information cannot be saved. Please try again.";
            $this->_DB->rollBack();
            return false;
        } 
        
        if($arrResponse[0]=="ERROR") {
            if($arrResponse[2]=="PWD INVALID FORMAT")
                $this->errMsg = "* Password: Must be at least 10 ";
            else 
                $this->errMsg = $arrResponse[1];
            
            $this->_DB->rollBack();
            return false;
        }
        
        $h = popen('/usr/bin/issabel-helper issabelkey', 'w');
        fwrite($h, $arrResponse[1]); // sid
        pclose($h);           
        $this->updateLinkAutoLogin($arrResponse[2],1);  //link_auto_login, TODO validar
        
        // 4to. Obtengo toda la información de la cuenta 
        // para guardarla en la base local, puesto que en el 
        // caso de byAccount solo se ingresaron pocos datos
        // correo y clave, entonces es necesario traer los datos
        // antes registrados en la cuenta cloud issabel.
        if($method=="byAccount") {
            $AccountData = $this->getDataServerRegistration();
            if(!(is_array($AccountData)&& count($AccountData)>0)){
                $this->errMsg = "Your information cannot be saved. Please try again.";
                $this->_DB->rollBack();
                return false;
            }
            $updtData[0] = $AccountData['contactNameReg'];
            $updtData[1] = $AccountData['emailReg'];
            $updtData[2] = $AccountData['phoneReg'];
            $updtData[3] = $AccountData['companyReg'];
            $updtData[4] = $AccountData['addressReg'];
            $updtData[5] = $AccountData['cityReg'];
            $updtData[6] = $AccountData['countryReg'];
            $updtData[7] = $AccountData['idPartnerReg'];
            $updtData[8] = $AccountData['has_account'];
            $updtData[9] = 1;

            $status  = $this->updateDataRegister($updtData);                    
            if (!$status) {
                $this->errMsg = "There are some problem with the local database. Information cannot be saved in database.";
                $this->_DB->rollBack();
                return false;
            }                    
        }            
        $this->_DB->commit();
        return true;
    }
    
    public function isRegistered() {
        //si el archivo existe entonces tenemos 2 casos
        //1ero. es un registro completo o 2do es un
        //registro incompleto.

        $this->renameKeyFile();

        if (is_file("/etc/issabel.key")) { 
            if($this->columnExists("has_account")) {
                $result = $this->_DB->getFirstRowQuery("select has_account from register;", true);
                if (is_array($result) && count($result)>0){
                    return ($result['has_account']=="yes")?"yes-all":"yes-inc";
                }
                else return "yes-inc";
            }
            else {
                //intento crear la columna 
                if(!$this->addColumnTableRegister("has_account char(3) default 'no'")) {
                    $this->errMsg = "The column 'has_account' does not exist and could not be created";
                    return "yes-inc";
                }

                //Actualizo el valor de la columna
                //con la info desde webservice
                $dataWebService = $this->getDataServerRegistration();
                if(!(is_array($dataWebService) && count($dataWebService)>0)) // no se puedo conectar al webservice
                    return "yes-inc";
                
                if($this->updateHasAccount(1,$dataWebService['has_account']))
                        return ($dataWebService['has_account']=="yes")?"yes-all":"yes-inc";
                else return "yes-inc";
            }
        }
        else return "no";
    }
    
    public function isRegisteredInfo() {
        $info = array();
        $info['registered'] = $this->isRegistered();
        
        $this->renameKeyFile();

        switch ($info['registered']) {
            case "yes-all":
            $info['label'] = _tr("Registered");
            $info['color'] = "#008800";
            $key = file_get_contents("/etc/issabel.key");
            $info['sid']   = trim($key);            
            break; 
            case "yes-inc":
                $info['label'] = _tr("Incomplete registration");
                $info['color'] = "yellow";
                $key = file_get_contents("/etc/issabel.key");
                $info['sid']   = trim($key);
                break;
            case "no":
            default:
                $info['label'] = _tr("Register");
                $info['color'] = "#FF0000";
                $info['sid']   = "";
        }
 
        $key = substr(md5(microtime()),rand(0,26),20);
        $info['sid']   = trim($key);            
        
        return $info;
    }
    
    private function insertDataRegister($data) {
        $query = "INSERT INTO register(contact_name, email, phone, company, address, city, country, idPartner,has_account) VALUES(?,?,?,?,?,?,?,?,?)";
        $result = $this->_DB->genQuery($query, $data);
        if ($result == FALSE) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    private function updateDataRegister($data) {
        $query = "UPDATE register SET contact_name=?, email=?, phone=?, company=?, address=?, city=?, country=?, idPartner=?, has_account=? WHERE id=?";
        $result = $this->_DB->genQuery($query, $data);
        if ($result == FALSE) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }
    
    public function getLinkAutoLogin() {
        $result = $this->_DB->getFirstRowQuery("SELECT link_auto_login from register where id=?", false, array(1));
        if(!(is_array($result) && count($result)>0)){
            $this->errMsg = $this->_DB->errMsg;
            return "";
        }        
        return $result[0];
    }
    
    public function getUsername() {
        $result = $this->_DB->getFirstRowQuery("SELECT email from register where id=?", false, array(1));
        if(!(is_array($result) && count($result)>0)){
            $this->errMsg = $this->_DB->errMsg;
            return "";
        }        
        return $result[0];
    }
    
    private function updateLinkAutoLogin($link, $id) {
        $query = "UPDATE register SET link_auto_login=? WHERE id=?";
        $result = $this->_DB->genQuery($query, array($link,$id));
        if ($result == FALSE) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    private function updateHasAccount($id, $has_account) {
        $query = "UPDATE register SET has_account=? WHERE id=?";
        $result = $this->_DB->genQuery($query, array($has_account,$id));
        if ($result == FALSE) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }
    
    private function createTableRegister() {
        $query = "CREATE TABLE register(
            id              integer primary key,
            contact_name    varchar(50),
            email           varchar(50),
            phone           varchar(20),
            company         varchar(50),
            address         varchar(100),
            city            varchar(25),
            country         varchar(25),
            idPartner       varchar(25),
            has_account     char(3) default 'no',
            link_auto_login varchar(100)
        )";
        return $this->_DB->genExec($query);
    }
    
    private function addColumnTableRegister($column) {
        $query = "ALTER TABLE register ADD COLUMN $column;";
        return $this->_DB->genExec($query);
    }

    private function tableRegisterExists() {
        $query = "SELECT * FROM register";
        $result = $this->_DB->genQuery($query);
        if ($result === false) {
            if (preg_match("/No such table/i", $this->_DB->errMsg))
                return false;
            else
                return true;
        }
        else
            return true;
    }
    
    private function columnExists($column) {
        $query = "SELECT $column FROM register";
        $result = $this->_DB->genQuery($query);
        if ($result === false) {
            if (preg_match("/No such column/i", $this->_DB->errMsg))
                return false;
            else
                return true;
        }
        else
            return true;
    }

    private function _getSOAP() {
        ini_set("soap.wsdl_cache_enabled", "0");

        /* La presencia de xdebug activo interfiere con las excepciones de
         * SOAP arrojadas por SoapClient, convirtiéndolas en errores 
         * fatales. Por lo tanto se desactiva la extensión. */
        if (function_exists("xdebug_disable"))
            xdebug_disable();

        return @new SoapClient($this->_webserviceURL, array( "connection_timeout" => 5 ));
    }

    public function processGetDataRegister() {
        //1ero. Verifico existencia de registro
        $registered = $this->isRegistered();
        if($registered=="no") {
            $this->errMsg = "Your Issabel Server is not registered";
            return null;
        }
        
        $this->renameKeyFile();

        //2do. obtengo datos desde el webservice
        $dataWebService = $this->getDataServerRegistration();

        if($dataWebService) {
            $updtData    = array();
            $updtData[0] = $dataWebService['contactNameReg'];
            $updtData[1] = $dataWebService['emailReg'];
            $updtData[2] = $dataWebService['phoneReg'];
            $updtData[3] = $dataWebService['companyReg'];
            $updtData[4] = $dataWebService['addressReg'];
            $updtData[5] = $dataWebService['cityReg'];
            $updtData[6] = $dataWebService['countryReg'];
            $updtData[7] = $dataWebService['idPartnerReg'];
            $updtData[8] = $dataWebService['has_account'];
            $updtData[9] = 1;

            //3ero. Mantengo la base local actualizada, puede darse que desde la 
            //administración del cloud.issabel.org haya cambiado.
            if (!$this->updateDataRegister($updtData)) {
                $this->errMsg = "There are some problem with the local database. Information cannot be saved in database.";
            }   
            return $dataWebService;
        }
        else {
            //4to. obtengo datos desde la base local
            $this->errMsg  = "Impossible connect to Issabel Web services. Please check your internet connection. Showing local information cache.";
            $dataLocalCache = $this->getDataLocalRegister();
            
            if($dataLocalCache) {
                $dataLocalCache['identitykeyReg'] = file_get_contents("/etc/issabel.key");
                return $dataLocalCache;
            }
            else {
                $this->errMsg = "Unable to get local information cache. Nor from Issabel Web services.";
                return null;
            }               
        }
    }
   
    private function renameKeyFile() {
        if(is_file("/etc/elastix.key")) {
            $sComando = '/usr/bin/issabel-helper renamekey';
            $output = $ret = NULL;
            exec($sComando, $output, $ret);
        }
    }
 
    private function getDataServerRegistration() {

        $this->renameKeyFile();

        if (is_file("/etc/issabel.key"))
            $serverKey = file_get_contents("/etc/issabel.key");
        else
            return null;

        try {
            $client = $this->_getSOAP();
            $content = $client->getDataServerRegistration($serverKey);
            return $content;
        } catch (SoapFault $e) {
            return null;
        }
    }
    
    private function sendDataWebService($data) {
        try {
            $client  = $this->_getSOAP();
            $content = $client->saveInstallation($data);
            return $content;
        } catch (SoapFault $e) {
            return null;
        }
    }
    
    public function isStrongPassword($password){
        if(strlen($password)>=10){
            if(preg_match("/[a-z]+/",$password)){
                if(preg_match("/[A-Z]+/",$password)){
                    if(preg_match("/[0-9]+/",$password)){
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
?>

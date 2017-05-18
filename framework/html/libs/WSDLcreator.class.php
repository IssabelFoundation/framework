<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4                                                |
  | http://www.elastix.com                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
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
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: WSDLcreator.class.php,v 1.0 2011-03-18 15:30:00 Bruno Macias V.  bmacias@elastix.org Exp $*/
$root = $_SERVER["DOCUMENT_ROOT"];
require_once("$root/libs/misc.lib.php");

class WSDLcreator
{
    /**
     * Description error message
     *
     * @var string
     */
    private $errorMSG;

    /**
     * Name attribute for entity wsdl:definitions.
     *
     * @var string
     */
    private $nameWSDL;

    /**
     * URN of namespace target WSDL.
     *
     * @var string
     */
    private $targetNamespace;

    /**
     * Address location WSDL functions implementation.
     *
     * @var string
     */
    private $soapAddress;

    /**
     * WSDL XMLObject
     *
     * @var object
     */
    private $objWSDL;

    /**
     * Schema type XMLNodeObject
     *
     * @var object
     */
    private $node_SchemaTypes;

    /**
     * PortType XMLNodeObject
     *
     * @var object
     */
    private $node_PortType;

    /**
     * Binding XMLNodeObject
     *
     * @var object
     */
    private $node_Binding;

    /**
     * Service XMLNodeObject
     *
     * @var object
     */
    private $node_Service;

    /**
     * Prefix namespace for schema
     *
     * @var object
     */
    private $prefix_ns_schema;

    /**
     * Prefix namespace for soap
     *
     * @var object
     */
    private $prefix_ns_soap;

    /**
     * Prefix namespace for wsdl
     *
     * @var object
     */
    private $prefix_ns_wsdl;

    /**
     * URN of namespace for XMLschema
     *
     * @var string
     */
    private $ns_schema;

    /**
     * URN of namespace for soap schema 
     *
     * @var string
     */
    private $ns_soap;

    /**
     * URN of namespace for wsdl schema
     *
     * @var string
     */
    private $ns_wsdl;

    /**
     * Prefix namespace for elastix
     *
     * @var object
     */
    private $prefix_ns_elx;

    /**
     * Array with internal variable types
     *
     * @var array
     */
    private $schema_DataTypes;

    /**
     * enable cache process
     *
     * @var object
     */
    private $enableCache;

    /**
     * location file cache
     *
     * @var object
     */
    private $path_FileCache;

    /**
     * Constructor
     *
     * @param  string   $nameWSDL          Name attribute for entity wsdl:definitions.
     * @param  string   $targetNamespace   URN of namespace target WSDL.
     * @param  string   $soapAddress       Address location WSDL functions implementation.
     */
    public function WSDLcreator($nameWSDL, $targetNamespace="http://www.elastix.org/webservices", $soapAddress)
    {
        /*
            PRIMITIVE DATA TYPES
            string            |gDay               |byte
            date              |gMonth             |short
            time              |hexBinary          |int
            float             |base64Binary       |long
            boolean           |anyURI             |ENTITY
            positiveInteger   |QName              |IDREF
            dateTime          |NOTATION           |ID
            integer           |negativeInteger    |NCName
            double            |nonPositiveInteger |NMTOKENS
            decimal           |unsignedByte       |ENTITIES
            duration          |unsignedShort      |pattern
            gYearMonth        |unsignedInt        |IDREFS
            gYear             |unsignedLong       |...
            gMonthDay         |nonNegativeInteger |
        */

        $this->nameWSDL         = $nameWSDL;
        $this->targetNamespace  = $targetNamespace;
        $this->soapAddress      = $soapAddress;
        $this->objWSDL          = null;
        $this->node_SchemaTypes = null;
        $this->node_PortType    = null;
        $this->node_Binding     = null;
        $this->node_Service     = null;
        $this->prefix_ns_schema = "xsd";
        $this->prefix_ns_soap   = "soap";
        $this->prefix_ns_wsdl   = "wsdl";
        $this->prefix_ns_elx    = "elx";
        $this->ns_schema        = "http://www.w3.org/2001/XMLSchema";
        $this->ns_soap          = "http://schemas.xmlsoap.org/wsdl/soap/";
        $this->ns_wsdl          = "http://schemas.xmlsoap.org/wsdl/";
        $this->enableCache      = false;
        $this->path_FileCache   = "";

        $this->schema_DataTypes = array(
            "string"=>"string",        "array"=>"Array",          "boolean"=>"boolean",    "gday"=>"gDay",
            "integer"=>"integer",      "double"=>"double",        "float"=>"float",        "number"=>"float",
            "datetime"=>"dateTime",    "gmonthday"=>"gMonthDay",  "gmonth"=>"gMonth",      "gyearmonth"=>"gYearMonth",
            "pattern"=>"pattern",      "anytype"=>"anyType",      "gyear" =>"gYear",       "date"=>"date",
            "time"=>"time",            "duration"=>"duration",    "short"=>"short",        "long"=>"long",
            "byte"=>"byte",            "hexbinary"=>"hexBinary",  "base64Binary"=>"base64Binary",
            "positiveinteger"=>"positiveInteger",       "negativeinteger"=>"negativeInteger",
            "nonpositiveinteger"=>"nonPositiveInteger", "nonnegativeinteger"=>"nonNegativeInteger",
            "decimal"=>"decimal");
    }

    /**
     * 
     *
     *
     *
     * @param   array    $arrFP   Array definitions of functions points (fp)
     * @return  bool     TRUE if WSDL was generated, FALSE if not
     */
    public function generate($arrFP)
    {
        if(!($this->enableCache && file_exists($this->path_FileCache))){
            if($this->createWSDL_TPL()===false) return false;

            if(is_array($arrFP) && count($arrFP)>0){
                foreach($arrFP as $function => $params){
                    if($this->setMessage($function,$params)){
                        if($this->setPortType_Operation($function)){
                            if(!$this->setBinding_Operation($function)) return false;
                        }
                        else return false;
                    }
                    else return false;
                }
                return true;
            }
            else{
                $this->errorMSG = "Failed, function points isn't defined.";
                return false;
            }
        }
        else return true;
    }

    /**
     * 
     *
     *
     *
     * @return  bool     TRUE if was activated cache with write file sucessfull, FALSE if not.
     */
    public function enableCache()
    {
        $path = ini_get("session.save_path");
        session_commit();
        session_start();
        $id = session_id();
        $this->path_FileCache = "$path/{$this->nameWSDL}_{$id}.wsdl";
        if(!file_exists($this->path_FileCache)){
            if($this->export("file",$this->path_FileCache)){
                $this->enableCache = true;
                return true;
            }
            else{
                $this->errorMSG = "Failed, write cache file.";
                $this->enableCache = false;
                return false;
            }
        }
        $this->enableCache = true;
        return true;
    }

    /**
     * 
     *
     *
     * @param   string    $mode         The modes are: download, file and print, by default is print.
     * @param   string    $targetFile   If mode is file, the $targetFile param is the path write directory
     * @return  mixed     
     */
    public function export($mode="print", $targetFile=null)
    {
        switch($mode){
            case "download":
                if($this->enableCache && file_exists($this->path_FileCache))
                    $wsdl = file_get_contents($this->path_FileCache);
                else
                    $wsdl = $this->getWSDL();

                header("Content-Type: application/force-download");
                header("Content-Disposition: attachment; filename=".$this->nameWSDL.".wsdl");
                header("Accept-Ranges: bytes");
                header("Content-Length: " . strlen($wsdl));
                echo $wsdl;
                break;

            case "file":
                if($this->enableCache && file_exists($this->path_FileCache))
                    $wsdl = file_get_contents($this->path_FileCache);
                else
                    $wsdl = $this->getWSDL();

                if(is_null($targetFile)) $targetFile = "/tmp/{$this->nameWSDL}.wsdl";
                return file_put_contents($targetFile,$wsdl);
                break;

            default:  //print
                if($this->enableCache && file_exists($this->path_FileCache))
                    $wsdl = file_get_contents($this->path_FileCache);
                else
                    $wsdl = $this->getWSDL();

                header('Content-Type: text/xml; charset=utf-8');
                echo $wsdl;
                break;
        }
    }

    /**
     * 
     * Function that returns the error message
     *
     * @return  string   Message error if had an error.
     */
    public function getError()
    {
        return $this->errorMSG;
    }



    /* Funciones de uso interno private    */

    /**
     * 
     * Function that returns the xml object as a string
     *
     * @return  string   The XML as text WSDL if exists, else FALSE if not exists.
     */
    private function getWSDL()
    {
        if(is_object($this->objWSDL))
            return $this->objWSDL->asXML();
        else{
            $this->errorMSG = "WSDL has not been generated.";
            return false;
        }

    }

    /**
     * 
     *
     *
     *
     * @return  bool   TRUE if template XMLObject (with format WSDL) was created, FALSE if not.
     */
    private function createWSDL_TPL()
    {
        if(empty($this->nameWSDL)){
            $this->errorMSG = "WSDL name isn't defined.";
            return false;
        }

        if(empty($this->targetNamespace)){
            $this->errorMSG = "WSDL targetNamespace isn't defined.";
            return false;
        }

        if(empty($this->soapAddress)){
            $this->errorMSG = "WSDL soapAddress isn't defined.";
            return false;
        }


        // Create Document XML with namespace
        // xsd  for namespace (schema),  http://www.w3.org/2001/XMLSchema
        // soap for namespace (soap),    http://schemas.xmlsoap.org/wsdl/soap/
        // elx  for namespace (elastix), ex. http://www.elastix.org/webservices/
        try{
            $doc  = new DOMDocument("1.0","UTF-8");
            $root = $doc->createElementNS($this->ns_wsdl,"{$this->prefix_ns_wsdl}:definitions");
            $doc->appendChild($root);
            $doc->createAttributeNS($this->ns_schema,      "{$this->prefix_ns_schema}:schema");
            $doc->createAttributeNS($this->ns_soap, "{$this->prefix_ns_soap}:soap");
            $doc->createAttributeNS($this->targetNamespace,                  "{$this->prefix_ns_elx}:elastix");

            $attrname = $doc->createAttribute('name'); //WSDL name Attribute
            $root->appendChild($attrname);
            $valname  = $doc->createTextNode($this->nameWSDL);
            $attrname->appendChild($valname);

            $attrtns = $doc->createAttribute('targetNamespace');  //WSDL tns Attribute
            $root->appendChild($attrtns);
            $valtns  = $doc->createTextNode($this->targetNamespace);
            $attrtns->appendChild($valtns);

            $tplWSDL = simplexml_import_dom($doc);

            if (!$tplWSDL){ echo "ERROR";
                $this->errorMSG = "Failed loading XML\n";
                foreach(libxml_get_errors() as $error)
                    $this->errorMSG .= "\t".$error->message;
                return false;
            }

            //Types
            $objTypes = $tplWSDL->addChild( "{$this->prefix_ns_wsdl}:types",   null, $this->ns_wsdl);
            $objSchem = $objTypes->addChild("{$this->prefix_ns_schema}:schema",null, $this->ns_schema);
            $objSchem->addAttribute("elementFormDefault","qualified");
            $objSchem->addAttribute("targetNamespace",$this->targetNamespace);
            $this->node_SchemaTypes = $objSchem;

            //Port Type
            $objPortType = $tplWSDL->addChild("{$this->prefix_ns_wsdl}:portType",null, $this->ns_wsdl);
            $objPortType->addAttribute("name","{$this->nameWSDL}_SOAPPort");
            $this->node_PortType = $objPortType;

            //Binding
            $objBinding = $tplWSDL->addChild("{$this->prefix_ns_wsdl}:binding",null, $this->ns_wsdl);
            $objBinding->addAttribute("name","{$this->nameWSDL}_SOAP");
            $objBinding->addAttribute("type","{$this->prefix_ns_elx}:{$this->nameWSDL}_SOAPPort");
            $objSOAPBin = $objBinding->addChild("{$this->prefix_ns_soap}:binding",null, $this->ns_soap);
            $objSOAPBin->addAttribute("style","document");
            $objSOAPBin->addAttribute("transport","http://schemas.xmlsoap.org/soap/http");
            $this->node_Binding = $objBinding;

            //Service
            $objService = $tplWSDL->addChild("{$this->prefix_ns_wsdl}:service",null, $this->ns_wsdl);
            $objService->addAttribute("name","{$this->nameWSDL}_Service");
            $objPort = $objService->addChild("{$this->prefix_ns_wsdl}:port",null, $this->ns_wsdl);
            $objPort->addAttribute("name","{$this->nameWSDL}_Port");
            $objPort->addAttribute("binding","{$this->prefix_ns_elx}:{$this->nameWSDL}_SOAP");
            $objAddress = $objPort->addChild("{$this->prefix_ns_soap}:address",null, $this->ns_soap);
            $objAddress->addAttribute("location",$this->soapAddress);
            $this->node_Service = $objService;

            $this->objWSDL = $tplWSDL;
        }
        catch(DOMException $ex){
            $this->errorMSG = "Failed generate WSDL - " . $ex->getMessage();
            return false;
        }
        return true;
    }

    /**
     * 
     *
     *
     * @param   string    $function    Name function point for attribute name on entity wsdl:message.
     * @param   array     $arrParams   Array parameters (IN/OUT) for entity wsdl:message
     * @return  bool      TRUE if entity message was created on WSDL, FALSE if not.
     */
    private function setMessage($function, $arrParams)
    {
        if(empty($function)){
            $this->errorMSG = "Error - Message name function isn't defined";
            return false;
        }

        if(!is_array($arrParams) || count($arrParams)==0){
            $this->errorMSG = "Error - Message Params (IN/OUT) isn't defined";
            return false;
        }

        try{
            foreach($arrParams as $param => $arrParamType){
                $type = ($param == "params_IN")?"":"Response";
                $name1 = ($type!="")?"{$function}_{$type}":$function;

                if($this->setSchemaTypes($name1, $arrParamType)){
                    $objMsg = $this->objWSDL->addChild("{$this->prefix_ns_wsdl}:message",null, $this->ns_wsdl);
                    $type = ($param == "params_IN")?"Request":"Response";
                    $name2 = "{$function}_{$type}";
                    $objMsg->addAttribute("name",$name2);
                    $objPar = $objMsg->addChild("{$this->prefix_ns_wsdl}:part",null, $this->ns_wsdl);
                    $objPar->addAttribute("name",$param);
                    $objPar->addAttribute("element","{$this->prefix_ns_elx}:$name1");
                }
                else return false;
            }
        }
        catch(Exception $ex){
            $this->errorMSG = "Failed generate Message WSDL section ".$ex->getMessage();
            return false;
        }

        return true;
    }

    /**
     * 
     *
     *
     * @param   string    $nameElement    Name Element for schema entity element xsd:element.
     * @param   array     $arrParamType   Array types of parameter IN or OUT. 
     * @return  bool      TRUE if entity element was created on WSDL, FALSE if not.
     */
    private function setSchemaTypes($nameElement, $arrParamType)
    {
        if(!is_object($this->node_SchemaTypes)){
            $this->errorMSG = "Error - XMLNodeObject SchemaTypes is null";
            return false;
        }

        if(empty($nameElement)){
            $this->errorMSG = "Error - Name element/SchemaTypes isn't defined";
            return false;
        }

        try{
            $objElement = $this->node_SchemaTypes->addChild("{$this->prefix_ns_schema}:element",null, $this->ns_schema);
            $objElement->addAttribute("name",$nameElement);
            $isok = $this->addElements_SchemaTypes($objElement,$arrParamType);
            if($isok === false) return false;
        }
        catch(Exception $ex){
            $this->errorMSG = "Failed generate SchemaTypes WSDL section ".$ex->getMessage();
            return false;
        }

        return true;
    }

    /**
     * 
     *
     *
     * @param   object    $objElement     Object node type Element for recursive create complexType elements.
     * @param   array     $arrParamType   Array types of parameter IN or OUT. 
     * @return  bool      TRUE if entity element was created on WSDL, FALSE if not.
     */
    private function addElements_SchemaTypes($objElement, $arrParamType)
    {
        $objComplex = $objElement->addChild("{$this->prefix_ns_schema}:complexType",null, $this->ns_schema);

        if(is_array($arrParamType) && count($arrParamType)>0){ // if there aren't type defined, create empty element.
            $objSequenc = $objComplex->addChild("{$this->prefix_ns_schema}:sequence",   null, $this->ns_schema);

            foreach($arrParamType as $param => $data){
                $objElement = $objSequenc->addChild("{$this->prefix_ns_schema}:element",null, $this->ns_schema);

                if(isset($param) && $param!="")
                    $objElement->addAttribute("name",$param);
                else
                    $objElement->addAttribute("name","param");

                if(isset($data["required"]) && $data["required"]==false)
                    $objElement->addAttribute("nillable","true");

                if(isset($data["minOccurs"]))
                    $objElement->addAttribute("minOccurs","$data[minOccurs]");

                if(isset($data["maxOccurs"]))
                    $objElement->addAttribute("maxOccurs","$data[maxOccurs]");

                $datatype = $this->isValid_DataType($data["type"], $param);
                if($datatype === false)
                    return false;
                else{
                    if($datatype == "Array"){
                        if(isset($data['params']) && count($data['params'])>0){
                            $isok = $this->addElements_SchemaTypes($objElement,$data['params']);
                            if($isok === false) return false;
                        }
                        else
                            $objElement->addAttribute("type","{$this->prefix_ns_schema}:Array");
                    }
                    else
                        $objElement->addAttribute("type","{$this->prefix_ns_schema}:$datatype");
                }
            }
        }
        return true;
    }

    /**
     * 
     *
     *
     * @param   string    $datatype     Data type to valid.
     * @param   string    $param        Name param of data type. 
     * @return  mixed     String with correct name data type, FALSE if $datatype is not exists.
     */
    private function isValid_DataType($datatype, $param)
    {
        if(empty($datatype))
            return "anyType";

        $lower = strtolower($datatype);
        if(array_key_exists($lower,$this->schema_DataTypes)){
            return $this->schema_DataTypes[$lower];
        }
        else{
            $str = "";
            foreach($this->schema_DataTypes as $k => $v)
                $str = $str . "| $v ";

            $this->errorMSG = "Error - Data type isn't defined or not exits [$datatype] parameter $param. Valid data types are: $str";
            return false;
        }
    }

     /**
     * 
     *
     *
     * @param   string    $function     Name function point for attribute name on entity wsdl:operation section portType.
     * @return  bool      TRUE if entity Operation/PortType was created on WSDL, FALSE if not.
     */
    private function setPortType_Operation($function)
    {
        if(!is_object($this->node_PortType)){
            $this->errorMSG = "Error - XMLNodeObject PortType is null";
            return false;
        }

        if(empty($function)){
            $this->errorMSG = "Error - Operation PortType name function isn't defined";
            return false;
        }

        try{
            $objOperation = $this->node_PortType->addChild("{$this->prefix_ns_wsdl}:operation",null, $this->ns_wsdl);
            $objOperation->addAttribute("name",$function);
            $objIN  = $objOperation->addChild("{$this->prefix_ns_wsdl}:input", null, $this->ns_wsdl);
            $objOUT = $objOperation->addChild("{$this->prefix_ns_wsdl}:output",null, $this->ns_wsdl);

            $objIN->addAttribute( "message","{$this->prefix_ns_elx}:{$function}_Request");
            $objOUT->addAttribute("message","{$this->prefix_ns_elx}:{$function}_Response");
        }
        catch(Exception $ex){
            $this->errorMSG = "Failed generate Operation PortType WSDL section ".$ex->getMessage();
            return false;
        }

        return true;
    }

     /**
     * 
     *
     *
     * @param   string    $function     Name function point for attribute name on entity wsdl:operation section Binding.
     * @return  bool      TRUE if entity Operation/Binding was created on WSDL, FALSE if not.
     */
    private function setBinding_Operation($function)
    {
        if(!is_object($this->node_Binding)){
            $this->errorMSG = "Error - XMLNodeObject Binding is null";
            return false;
        }

        if(empty($function)){
            $this->errorMSG = "Error - Operation Binding name function isn't defined";
            return false;
        }

        try{
            $objOperation = $this->node_Binding->addChild("{$this->prefix_ns_wsdl}:operation",null, $this->ns_wsdl);
            $objOperation->addAttribute("name",$function);

            $objSOAP_Oper = $objOperation->addChild("{$this->prefix_ns_soap}:operation",null, $this->ns_soap);
            $objSOAP_Oper->addAttribute("soapAction","{$this->targetNamespace}/$function");
            $objSOAP_Oper->addAttribute("style","document");

            $objIN  = $objOperation->addChild("{$this->prefix_ns_wsdl}:input", null, $this->ns_wsdl);
            $objOUT = $objOperation->addChild("{$this->prefix_ns_wsdl}:output",null, $this->ns_wsdl);

            $objIN_Body  = $objIN->addChild("{$this->prefix_ns_soap}:body", null, $this->ns_soap);
            $objOUT_Body = $objOUT->addChild("{$this->prefix_ns_soap}:body",null, $this->ns_soap);

            $objIN_Body->addAttribute("use","literal");
            $objOUT_Body->addAttribute("use","literal");
            $objIN_Body->addAttribute("namespace", $this->targetNamespace);
            $objOUT_Body->addAttribute("namespace", $this->targetNamespace);
        }
        catch(Exception $ex){
            $this->errorMSG = "Failed generate Operation Binding WSDL section ".$ex->getMessage();
            return false;
        }

        return true;
    }
}
?>
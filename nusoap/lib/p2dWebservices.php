<?php
/*** 2015 PJ CONSEIL** NOTICE OF LICENSE** This source file is subject to License* You may not distribute this module even for free** @author    PJ CONSEIL* @copyright 2014 PJ CONSEIL* @license   NoLicence* @version   RC2*/

/**

NuSOAP - Web Services Toolkit for PHP

Copyright (c) 2002 NuSphere Corporation

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
along with this program. If not, see <http://www.gnu.org/licenses/>.

The NuSOAP project home is:
http://sourceforge.net/projects/nusoap/

The primary support for NuSOAP is the Help forum on the project home page.

If you have any questions or comments, please email:

Dietrich Ayala
dietrich@ganx4.com
http://dietrich.ganx4.com/nusoap

NuSphere Corporation
http://www.nusphere.com

*/

/*
 *	Some of the standards implmented in whole or part by NuSOAP:
 *
 *	SOAP 1.1 (http://www.w3.org/TR/2000/NOTE-SOAP-20000508/)
 *	WSDL 1.1 (http://www.w3.org/TR/2001/NOTE-wsdl-20010315)
 *	SOAP Messages With Attachments (http://www.w3.org/TR/SOAP-attachments)
 *	XML 1.0 (http://www.w3.org/TR/2006/REC-xml-20060816/)
 *	Namespaces in XML 1.0 (http://www.w3.org/TR/2006/REC-xml-names-20060816/)
 *	XML Schema 1.0 (http://www.w3.org/TR/xmlschema-0/)
 *	RFC 2045 Multipurpose Internet Mail Extensions (MIME) Part One: Format of Internet Message Bodies
 *	RFC 2068 Hypertext Transfer Protocol -- HTTP/1.1
 *	RFC 2617 HTTP Authentication: Basic and Digest Access Authentication
 */

/* load classes

// necessary classes
require_once('class.soapclient.php');
require_once('class.soap_val.php');
require_once('class.soap_parser.php');
require_once('class.soap_fault.php');

// transport classes
require_once('class.p2dWebservices_transport_http.php');

// optional add-on classes
require_once('class.xmlschema.php');
require_once('class.wsdl.php');

// server class
require_once('class.soap_server.php');*/

// class variable emulation
// cf. http://www.webkreator.com/php/techniques/php-static-class-variables.html
$GLOBALS['_transient']['static']['p2dWebservices_base']['globalDebugLevel'] = 9;

/**
*
* p2dWebservices_base
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @author   Scott Nichol <snichol@users.sourceforge.net>
* @access   public
*/
class p2dWebservices_base {
	/**
	 * Identification for HTTP headers.
	 *
	 * @var string
	 * @access private
	 */
	var $title = 'NuSOAP';
	/**
	 * Version for HTTP headers.
	 *
	 * @var string
	 * @access private
	 */
	var $version = '0.9.5';
	/**
	 * CVS revision for HTTP headers.
	 *
	 * @var string
	 * @access private
	 */
	var $revision = '1.15';
    /**
     * Current error string (manipulated by getError/setError)
	 *
	 * @var string
	 * @access private
	 */
	var $error_str = '';
    /**
     * Current debug string (manipulated by debug/appendDebug/clearDebug/getDebug/getDebugAsXMLComment)
	 *
	 * @var string
	 * @access private
	 */
    var $debug_str = '';
    /**
	 * toggles automatic encoding of special characters as entities
	 * (should always be true, I think)
	 *
	 * @var boolean
	 * @access private
	 */
	var $charencoding = true;
	/**
	 * the debug level for this instance
	 *
	 * @var	integer
	 * @access private
	 */
	var $debugLevel;

    /**
	* set schema version
	*
	* @var      string
	* @access   public
	*/
	var $XMLSchemaVersion = 'http://www.w3.org/2001/XMLSchema';

    /**
	* charset encoding for outgoing messages
	*
	* @var      string
	* @access   public
	*/
    var $soap_defencoding = 'ISO-8859-1';
	//var $soap_defencoding = 'UTF-8';

	/**
	* namespaces in an array of prefix => uri
	*
	* this is "seeded" by a set of constants, but it may be altered by code
	*
	* @var      array
	* @access   public
	*/
	var $namespaces = array(
		'SOAP-ENV' => 'http://schemas.xmlsoap.org/soap/envelope/',
		'xsd' => 'http://www.w3.org/2001/XMLSchema',
		'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
		'SOAP-ENC' => 'http://schemas.xmlsoap.org/soap/encoding/'
		);

	/**
	* namespaces used in the current context, e.g. during serialization
	*
	* @var      array
	* @access   private
	*/
	var $usedNamespaces = array();

	/**
	* XML Schema types in an array of uri => (array of xml type => php type)
	* is this legacy yet?
	* no, this is used by the p2dWebservices_xmlschema class to verify type => namespace mappings.
	* @var      array
	* @access   public
	*/
	var $typemap = array(
	'http://www.w3.org/2001/XMLSchema' => array(
		'string'=>'string','boolean'=>'boolean','float'=>'double','double'=>'double','decimal'=>'double',
		'duration'=>'','dateTime'=>'string','time'=>'string','date'=>'string','gYearMonth'=>'',
		'gYear'=>'','gMonthDay'=>'','gDay'=>'','gMonth'=>'','hexBinary'=>'string','base64Binary'=>'string',
		// abstract "any" types
		'anyType'=>'string','anySimpleType'=>'string',
		// derived datatypes
		'normalizedString'=>'string','token'=>'string','language'=>'','NMTOKEN'=>'','NMTOKENS'=>'','Name'=>'','NCName'=>'','ID'=>'',
		'IDREF'=>'','IDREFS'=>'','ENTITY'=>'','ENTITIES'=>'','integer'=>'integer','nonPositiveInteger'=>'integer',
		'negativeInteger'=>'integer','long'=>'integer','int'=>'integer','short'=>'integer','byte'=>'integer','nonNegativeInteger'=>'integer',
		'unsignedLong'=>'','unsignedInt'=>'','unsignedShort'=>'','unsignedByte'=>'','positiveInteger'=>''),
	'http://www.w3.org/2000/10/XMLSchema' => array(
		'i4'=>'','int'=>'integer','boolean'=>'boolean','string'=>'string','double'=>'double',
		'float'=>'double','dateTime'=>'string',
		'timeInstant'=>'string','base64Binary'=>'string','base64'=>'string','ur-type'=>'array'),
	'http://www.w3.org/1999/XMLSchema' => array(
		'i4'=>'','int'=>'integer','boolean'=>'boolean','string'=>'string','double'=>'double',
		'float'=>'double','dateTime'=>'string',
		'timeInstant'=>'string','base64Binary'=>'string','base64'=>'string','ur-type'=>'array'),
	'http://soapinterop.org/xsd' => array('SOAPStruct'=>'struct'),
	'http://schemas.xmlsoap.org/soap/encoding/' => array('base64'=>'string','array'=>'array','Array'=>'array'),
    'http://xml.apache.org/xml-soap' => array('Map')
	);

	/**
	* XML entities to convert
	*
	* @var      array
	* @access   public
	* @deprecated
	* @see	expandEntities
	*/
	var $xmlEntities = array('quot' => '"','amp' => '&',
		'lt' => '<','gt' => '>','apos' => "'");

	/**
	* constructor
	*
	* @access	public
	*/
	function p2dWebservices_base() {
		$this->debugLevel = $GLOBALS['_transient']['static']['p2dWebservices_base']['globalDebugLevel'];
	}

	/**
	* gets the global debug level, which applies to future instances
	*
	* @return	integer	Debug level 0-9, where 0 turns off
	* @access	public
	*/
	function getGlobalDebugLevel() {
		return $GLOBALS['_transient']['static']['p2dWebservices_base']['globalDebugLevel'];
	}

	/**
	* sets the global debug level, which applies to future instances
	*
	* @param	int	$level	Debug level 0-9, where 0 turns off
	* @access	public
	*/
	function setGlobalDebugLevel($level) {
		$GLOBALS['_transient']['static']['p2dWebservices_base']['globalDebugLevel'] = $level;
	}

	/**
	* gets the debug level for this instance
	*
	* @return	int	Debug level 0-9, where 0 turns off
	* @access	public
	*/
	function getDebugLevel() {
		return $this->debugLevel;
	}

	/**
	* sets the debug level for this instance
	*
	* @param	int	$level	Debug level 0-9, where 0 turns off
	* @access	public
	*/
	function setDebugLevel($level) {
		$this->debugLevel = $level;
	}

	/**
	* adds debug data to the instance debug string with formatting
	*
	* @param    string $string debug data
	* @access   private
	*/
	function debug($string){
		if ($this->debugLevel > 0) {
			$this->appendDebug($this->getmicrotime().' '.get_class($this).": $string\n");
		}
	}

	/**
	* adds debug data to the instance debug string without formatting
	*
	* @param    string $string debug data
	* @access   public
	*/
	function appendDebug($string){
		if ($this->debugLevel > 0) {
			// it would be nice to use a memory stream here to use
			// memory more efficiently
			$this->debug_str .= $string;
		}
	}

	/**
	* clears the current debug data for this instance
	*
	* @access   public
	*/
	function clearDebug() {
		// it would be nice to use a memory stream here to use
		// memory more efficiently
		$this->debug_str = '';
	}

	/**
	* gets the current debug data for this instance
	*
	* @return   debug data
	* @access   public
	*/
	function &getDebug() {
		// it would be nice to use a memory stream here to use
		// memory more efficiently
		return $this->debug_str;
	}

	/**
	* gets the current debug data for this instance as an XML comment
	* this may change the contents of the debug data
	*
	* @return   debug data as an XML comment
	* @access   public
	*/
	function &getDebugAsXMLComment() {
		// it would be nice to use a memory stream here to use
		// memory more efficiently
		while (strpos($this->debug_str, '--')) {
			$this->debug_str = str_replace('--', '- -', $this->debug_str);
		}
		$ret = "<!--\n" . $this->debug_str . "\n-->";
    	return $ret;
	}

	/**
	* expands entities, e.g. changes '<' to '&lt;'.
	*
	* @param	string	$val	The string in which to expand entities.
	* @access	private
	*/
	function expandEntities($val) {
		if ($this->charencoding) {
	    	$val = str_replace('&', '&amp;', $val);
	    	$val = str_replace("'", '&apos;', $val);
	    	$val = str_replace('"', '&quot;', $val);
	    	$val = str_replace('<', '&lt;', $val);
	    	$val = str_replace('>', '&gt;', $val);
	    }
	    return $val;
	}

	/**
	* returns error string if present
	*
	* @return   mixed error string or false
	* @access   public
	*/
	function getError(){
		if($this->error_str != ''){
			return $this->error_str;
		}
		return false;
	}

	/**
	* sets error string
	*
	* @return   boolean $string error string
	* @access   private
	*/
	function setError($str){
		$this->error_str = $str;
	}

	/**
	* detect if array is a simple array or a struct (associative array)
	*
	* @param	mixed	$val	The PHP array
	* @return	string	(arraySimple|arrayStruct)
	* @access	private
	*/
	function isArraySimpleOrStruct($val) {
        $keyList = array_keys($val);
		foreach ($keyList as $keyListValue) {
			if (!is_int($keyListValue)) {
				return 'arrayStruct';
			}
		}
		return 'arraySimple';
	}

	/**
	* serializes PHP values in accordance w/ section 5. Type information is
	* not serialized if $use == 'literal'.
	*
	* @param	mixed	$val	The value to serialize
	* @param	string	$name	The name (local part) of the XML element
	* @param	string	$type	The XML schema type (local part) for the element
	* @param	string	$name_ns	The namespace for the name of the XML element
	* @param	string	$type_ns	The namespace for the type of the element
	* @param	array	$attributes	The attributes to serialize as name=>value pairs
	* @param	string	$use	The WSDL "use" (encoded|literal)
	* @param	boolean	$soapval	Whether this is called from soapval.
	* @return	string	The serialized element, possibly with child elements
    * @access	public
	*/
	function serialize_val($val,$name=false,$type=false,$name_ns=false,$type_ns=false,$attributes=false,$use='encoded',$soapval=false) {
		$this->debug("in serialize_val: name=$name, type=$type, name_ns=$name_ns, type_ns=$type_ns, use=$use, soapval=$soapval");
		$this->appendDebug('value=' . $this->varDump($val));
		$this->appendDebug('attributes=' . $this->varDump($attributes));

    	if (is_object($val) && get_class($val) == 'soapval' && (! $soapval)) {
    		$this->debug("serialize_val: serialize soapval");
        	$xml = $val->serialize($use);
			$this->appendDebug($val->getDebug());
			$val->clearDebug();
			$this->debug("serialize_val of soapval returning $xml");
			return $xml;
        }
		// force valid name if necessary
		if (is_numeric($name)) {
			$name = '__numeric_' . $name;
		} elseif (! $name) {
			$name = 'noname';
		}
		// if name has ns, add ns prefix to name
		$xmlns = '';
        if($name_ns){
			$prefix = 'nu'.rand(1000,9999);
			$name = $prefix.':'.$name;
			$xmlns .= " xmlns:$prefix=\"$name_ns\"";
		}
		// if type is prefixed, create type prefix
		if($type_ns != '' && $type_ns == $this->namespaces['xsd']){
			// need to fix this. shouldn't default to xsd if no ns specified
		    // w/o checking against typemap
			$type_prefix = 'xsd';
		} elseif($type_ns){
			$type_prefix = 'ns'.rand(1000,9999);
			$xmlns .= " xmlns:$type_prefix=\"$type_ns\"";
		}
		// serialize attributes if present
		$atts = '';
		if($attributes){
			foreach($attributes as $k => $v){
				$atts .= " $k=\"".$this->expandEntities($v).'"';
			}
		}
		// serialize null value
		if (is_null($val)) {
    		$this->debug("serialize_val: serialize null");
			if ($use == 'literal') {
				// TODO: depends on minOccurs
				$xml = "<$name$xmlns$atts/>";
				$this->debug("serialize_val returning $xml");
	        	return $xml;
        	} else {
				if (isset($type) && isset($type_prefix)) {
					$type_str = " xsi:type=\"$type_prefix:$type\"";
				} else {
					$type_str = '';
				}
				$xml = "<$name$xmlns$type_str$atts xsi:nil=\"true\"/>";
				$this->debug("serialize_val returning $xml");
	        	return $xml;
        	}
		}
        // serialize if an xsd built-in primitive type
        if($type != '' && isset($this->typemap[$this->XMLSchemaVersion][$type])){
    		$this->debug("serialize_val: serialize xsd built-in primitive type");
        	if (is_bool($val)) {
        		if ($type == 'boolean') {
	        		$val = $val ? 'true' : 'false';
	        	} elseif (! $val) {
	        		$val = 0;
	        	}
			} else if (is_string($val)) {
				$val = $this->expandEntities($val);
			}
			if ($use == 'literal') {
				$xml = "<$name$xmlns$atts>$val</$name>";
				$this->debug("serialize_val returning $xml");
	        	return $xml;
        	} else {
				$xml = "<$name$xmlns xsi:type=\"xsd:$type\"$atts>$val</$name>";
				$this->debug("serialize_val returning $xml");
	        	return $xml;
        	}
        }
		// detect type and serialize
		$xml = '';
		switch(true) {
			case (is_bool($val) || $type == 'boolean'):
		   		$this->debug("serialize_val: serialize boolean");
        		if ($type == 'boolean') {
	        		$val = $val ? 'true' : 'false';
	        	} elseif (! $val) {
	        		$val = 0;
	        	}
				if ($use == 'literal') {
					$xml .= "<$name$xmlns$atts>$val</$name>";
				} else {
					$xml .= "<$name$xmlns xsi:type=\"xsd:boolean\"$atts>$val</$name>";
				}
				break;
			case (is_int($val) || is_long($val) || $type == 'int'):
		   		$this->debug("serialize_val: serialize int");
				if ($use == 'literal') {
					$xml .= "<$name$xmlns$atts>$val</$name>";
				} else {
					$xml .= "<$name$xmlns xsi:type=\"xsd:int\"$atts>$val</$name>";
				}
				break;
			case (is_float($val)|| is_double($val) || $type == 'float'):
		   		$this->debug("serialize_val: serialize float");
				if ($use == 'literal') {
					$xml .= "<$name$xmlns$atts>$val</$name>";
				} else {
					$xml .= "<$name$xmlns xsi:type=\"xsd:float\"$atts>$val</$name>";
				}
				break;
			case (is_string($val) || $type == 'string'):
		   		$this->debug("serialize_val: serialize string");
				$val = $this->expandEntities($val);
				if ($use == 'literal') {
					$xml .= "<$name$xmlns$atts>$val</$name>";
				} else {
					$xml .= "<$name$xmlns xsi:type=\"xsd:string\"$atts>$val</$name>";
				}
				break;
			case is_object($val):
		   		$this->debug("serialize_val: serialize object");
		    	if (get_class($val) == 'soapval') {
		    		$this->debug("serialize_val: serialize soapval object");
		        	$pXml = $val->serialize($use);
					$this->appendDebug($val->getDebug());
					$val->clearDebug();
		        } else {
					if (! $name) {
						$name = get_class($val);
						$this->debug("In serialize_val, used class name $name as element name");
					} else {
						$this->debug("In serialize_val, do not override name $name for element name for class " . get_class($val));
					}
					foreach(get_object_vars($val) as $k => $v){
						$pXml = isset($pXml) ? $pXml.$this->serialize_val($v,$k,false,false,false,false,$use) : $this->serialize_val($v,$k,false,false,false,false,$use);
					}
				}
				if(isset($type) && isset($type_prefix)){
					$type_str = " xsi:type=\"$type_prefix:$type\"";
				} else {
					$type_str = '';
				}
				if ($use == 'literal') {
					$xml .= "<$name$xmlns$atts>$pXml</$name>";
				} else {
					$xml .= "<$name$xmlns$type_str$atts>$pXml</$name>";
				}
				break;
			break;
			case (is_array($val) || $type):
				// detect if struct or array
				$valueType = $this->isArraySimpleOrStruct($val);
                if($valueType=='arraySimple' || preg_match('/^ArrayOf/',$type)){
			   		$this->debug("serialize_val: serialize array");
					$i = 0;
					if(is_array($val) && count($val)> 0){
						foreach($val as $v){
	                    	if(is_object($v) && get_class($v) ==  'soapval'){
								$tt_ns = $v->type_ns;
								$tt = $v->type;
							} elseif (is_array($v)) {
								$tt = $this->isArraySimpleOrStruct($v);
							} else {
								$tt = gettype($v);
	                        }
							$array_types[$tt] = 1;
							// TODO: for literal, the name should be $name
							//DOL_CHANGE This is to have tag name with name reported by wsdl and not "item"
                            //$xml .= $this->serialize_val($v,'item',false,false,false,false,$use);
							$tmp=preg_replace('/s$/i','',$name);
                            $xml .= $this->serialize_val($v,$tmp?$tmp:'item',false,false,false,false,$use);
                            ++$i;
						}
						if(count($array_types) > 1){
							$array_typename = 'xsd:anyType';
						} elseif(isset($tt) && isset($this->typemap[$this->XMLSchemaVersion][$tt])) {
							if ($tt == 'integer') {
								$tt = 'int';
							}
							$array_typename = 'xsd:'.$tt;
						} elseif(isset($tt) && $tt == 'arraySimple'){
							$array_typename = 'SOAP-ENC:Array';
						} elseif(isset($tt) && $tt == 'arrayStruct'){
						    $array_typename = 'unnamed_struct_use_soapval';
						} else {
							// if type is prefixed, create type prefix
							if ($tt_ns != '' && $tt_ns == $this->namespaces['xsd']){
								 $array_typename = 'xsd:' . $tt;
							} elseif ($tt_ns) {
								$tt_prefix = 'ns' . rand(1000, 9999);
								$array_typename = "$tt_prefix:$tt";
								$xmlns .= " xmlns:$tt_prefix=\"$tt_ns\"";
							} else {
								$array_typename = $tt;
							}
						}
						$array_type = $i;
						if ($use == 'literal') {
							$type_str = '';
						} else if (isset($type) && isset($type_prefix)) {
							$type_str = " xsi:type=\"$type_prefix:$type\"";
						} else {
							$type_str = " xsi:type=\"SOAP-ENC:Array\" SOAP-ENC:arrayType=\"".$array_typename."[$array_type]\"";
						}
					// empty array
					} else {
						if ($use == 'literal') {
							$type_str = '';
						} else if (isset($type) && isset($type_prefix)) {
							$type_str = " xsi:type=\"$type_prefix:$type\"";
						} else {
							$type_str = " xsi:type=\"SOAP-ENC:Array\" SOAP-ENC:arrayType=\"xsd:anyType[0]\"";
						}
					}
					// TODO: for array in literal, there is no wrapper here
					$xml = "<$name$xmlns$type_str$atts>".$xml."</$name>";
				} else {
					// got a struct
			   		$this->debug("serialize_val: serialize struct");
					if(isset($type) && isset($type_prefix)){
						$type_str = " xsi:type=\"$type_prefix:$type\"";
					} else {
						$type_str = '';
					}
					if ($use == 'literal') {
						$xml .= "<$name$xmlns$atts>";
					} else {
						$xml .= "<$name$xmlns$type_str$atts>";
					}
					foreach($val as $k => $v){
						// Apache Map
						if ($type == 'Map' && $type_ns == 'http://xml.apache.org/xml-soap') {
							$xml .= '<item>';
							$xml .= $this->serialize_val($k,'key',false,false,false,false,$use);
							$xml .= $this->serialize_val($v,'value',false,false,false,false,$use);
							$xml .= '</item>';
						} else {
							$xml .= $this->serialize_val($v,$k,false,false,false,false,$use);
						}
					}
					$xml .= "</$name>";
				}
				break;
			default:
		   		$this->debug("serialize_val: serialize unknown");
				$xml .= 'not detected, got '.gettype($val).' for '.$val;
				break;
		}
		$this->debug("serialize_val returning $xml");
		return $xml;
	}

    /**
    * serializes a message
    *
    * @param string $body the XML of the SOAP body
    * @param mixed $headers optional string of XML with SOAP header content, or array of soapval objects for SOAP headers, or associative array
    * @param array $namespaces optional the namespaces used in generating the body and headers
    * @param string $style optional (rpc|document)
    * @param string $use optional (encoded|literal)
    * @param string $encodingStyle optional (usually 'http://schemas.xmlsoap.org/soap/encoding/' for encoded)
    * @return string the message
    * @access public
    */
    function serializeEnvelope($body,$headers=false,$namespaces=array(),$style='rpc',$use='encoded',$encodingStyle='http://schemas.xmlsoap.org/soap/encoding/'){
    // TODO: add an option to automatically run utf8_encode on $body and $headers
    // if $this->soap_defencoding is UTF-8.  Not doing this automatically allows
    // one to send arbitrary UTF-8 characters, not just characters that map to ISO-8859-1

	$this->debug("In serializeEnvelope length=" . strlen($body) . " body (max 1000 characters)=" . substr($body, 0, 1000) . " style=$style use=$use encodingStyle=$encodingStyle");
	$this->debug("headers:");
	$this->appendDebug($this->varDump($headers));
	$this->debug("namespaces:");
	$this->appendDebug($this->varDump($namespaces));

	// serialize namespaces
    $ns_string = '';
	foreach(array_merge($this->namespaces,$namespaces) as $k => $v){
		$ns_string .= " xmlns:$k=\"$v\"";
	}
	if($encodingStyle) {
		$ns_string = " SOAP-ENV:encodingStyle=\"$encodingStyle\"$ns_string";
	}

	// serialize headers
	if($headers){
		if (is_array($headers)) {
			$xml = '';
			foreach ($headers as $k => $v) {
				if (is_object($v) && get_class($v) == 'soapval') {
					$xml .= $this->serialize_val($v, false, false, false, false, false, $use);
				} else {
					$xml .= $this->serialize_val($v, $k, false, false, false, false, $use);
				}
			}
			$headers = $xml;
			$this->debug("In serializeEnvelope, serialized array of headers to $headers");
		}
		$headers = "<SOAP-ENV:Header>".$headers."</SOAP-ENV:Header>";
	}
	// serialize envelope
	return
	'<?xml version="1.0" encoding="'.$this->soap_defencoding .'"?'.">".
	'<SOAP-ENV:Envelope'.$ns_string.">".
	$headers.
	"<SOAP-ENV:Body>".
		$body.
	"</SOAP-ENV:Body>".
	"</SOAP-ENV:Envelope>";
    }

	/**
	 * formats a string to be inserted into an HTML stream
	 *
	 * @param string $str The string to format
	 * @return string The formatted string
	 * @access public
	 * @deprecated
	 */
    function formatDump($str){
		$str = htmlspecialchars($str);
		return nl2br($str);
    }

	/**
	* contracts (changes namespace to prefix) a qualified name
	*
	* @param    string $qname qname
	* @return	string contracted qname
	* @access   private
	*/
	function contractQname($qname){
		// get element namespace
		//$this->xdebug("Contract $qname");
		if (strrpos($qname, ':')) {
			// get unqualified name
			$name = substr($qname, strrpos($qname, ':') + 1);
			// get ns
			$ns = substr($qname, 0, strrpos($qname, ':'));
			$p = $this->getPrefixFromNamespace($ns);
			if ($p) {
				return $p . ':' . $name;
			}
			return $qname;
		} else {
			return $qname;
		}
	}

	/**
	* expands (changes prefix to namespace) a qualified name
	*
	* @param    string $qname qname
	* @return	string expanded qname
	* @access   private
	*/
	function expandQname($qname){
		// get element prefix
		if(strpos($qname,':') && !preg_match('/^http:\/\//',$qname)){
			// get unqualified name
			$name = substr(strstr($qname,':'),1);
			// get ns prefix
			$prefix = substr($qname,0,strpos($qname,':'));
			if(isset($this->namespaces[$prefix])){
				return $this->namespaces[$prefix].':'.$name;
			} else {
				return $qname;
			}
		} else {
			return $qname;
		}
	}

    /**
    * returns the local part of a prefixed string
    * returns the original string, if not prefixed
    *
    * @param string $str The prefixed string
    * @return string The local part
    * @access public
    */
	function getLocalPart($str){
		if($sstr = strrchr($str,':')){
			// get unqualified name
			return substr( $sstr, 1 );
		} else {
			return $str;
		}
	}

	/**
    * returns the prefix part of a prefixed string
    * returns false, if not prefixed
    *
    * @param string $str The prefixed string
    * @return mixed The prefix or false if there is no prefix
    * @access public
    */
	function getPrefix($str){
		if($pos = strrpos($str,':')){
			// get prefix
			return substr($str,0,$pos);
		}
		return false;
	}

	/**
    * pass it a prefix, it returns a namespace
    *
    * @param string $prefix The prefix
    * @return mixed The namespace, false if no namespace has the specified prefix
    * @access public
    */
	function getNamespaceFromPrefix($prefix){
		if (isset($this->namespaces[$prefix])) {
			return $this->namespaces[$prefix];
		}
		//$this->setError("No namespace registered for prefix '$prefix'");
		return false;
	}

	/**
    * returns the prefix for a given namespace (or prefix)
    * or false if no prefixes registered for the given namespace
    *
    * @param string $ns The namespace
    * @return mixed The prefix, false if the namespace has no prefixes
    * @access public
    */
	function getPrefixFromNamespace($ns) {
		foreach ($this->namespaces as $p => $n) {
			if ($ns == $n || $ns == $p) {
			    $this->usedNamespaces[$p] = $n;
				return $p;
			}
		}
		return false;
	}

	/**
    * returns the time in ODBC canonical form with microseconds
    *
    * @return string The time in ODBC canonical form with microseconds
    * @access public
    */
	function getmicrotime() {
		if (function_exists('gettimeofday')) {
			$tod = gettimeofday();
			$sec = $tod['sec'];
			$usec = $tod['usec'];
		} else {
			$sec = time();
			$usec = 0;
		}
		return strftime('%Y-%m-%d %H:%M:%S', $sec) . '.' . sprintf('%06d', $usec);
	}

	/**
	 * Returns a string with the output of var_dump
	 *
	 * @param mixed $data The variable to var_dump
	 * @return string The output of var_dump
	 * @access public
	 */
    function varDump($data) {
		ob_start();
		var_dump($data);
		$ret_val = ob_get_contents();
		ob_end_clean();
		return $ret_val;
	}

	/**
	* represents the object as a string
	*
	* @return	string
	* @access   public
	*/
	function __toString() {
		return $this->varDump($this);
	}
}

/**
* Contains information for a SOAP fault.
* Mainly used for returning faults from deployed functions
* in a server instance.
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @access public
*/


?><?php



/**
* parses an XML Schema, allows access to it's data, other utility methods.
* imperfect, no validation... yet, but quite functional.
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @author   Scott Nichol <snichol@users.sourceforge.net>
* @access   public
*/
class p2dWebservices_xmlschema extends p2dWebservices_base  {

	// files
	var $schema = '';
	var $xml = '';
	// namespaces
	var $enclosingNamespaces;
	// schema info
	var $schemaInfo = array();
	var $schemaTargetNamespace = '';
	// types, elements, attributes defined by the schema
	var $attributes = array();
	var $complexTypes = array();
	var $complexTypeStack = array();
	var $currentComplexType = null;
	var $elements = array();
	var $elementStack = array();
	var $currentElement = null;
	var $simpleTypes = array();
	var $simpleTypeStack = array();
	var $currentSimpleType = null;
	// imports
	var $imports = array();
	// parser vars
	var $parser;
	var $position = 0;
	var $depth = 0;
	var $depth_array = array();
	var $message = array();
	var $defaultNamespace = array();

	/**
	* constructor
	*
	* @param    string $schema schema document URI
	* @param    string $xml xml document URI
	* @param	string $namespaces namespaces defined in enclosing XML
	* @access   public
	*/
	function p2dWebservices_xmlschema($schema='',$xml='',$namespaces=array()){
		parent::p2dWebservices_base();
		$this->debug('p2dWebservices_xmlschema class instantiated, inside constructor');
		// files
		$this->schema = $schema;
		$this->xml = $xml;

		// namespaces
		$this->enclosingNamespaces = $namespaces;
		$this->namespaces = array_merge($this->namespaces, $namespaces);

		// parse schema file
		if($schema != ''){
			$this->debug('initial schema file: '.$schema);
			$this->parseFile($schema, 'schema');
		}

		// parse xml file
		if($xml != ''){
			$this->debug('initial xml file: '.$xml);
			$this->parseFile($xml, 'xml');
		}

	}

    /**
    * parse an XML file
    *
    * @param string $xml path/URL to XML file
    * @param string $type (schema | xml)
	* @return boolean
    * @access public
    */
	function parseFile($xml,$type){
		// parse xml file
		if($xml != ""){
			$xmlStr = @join("",@file($xml));
			if($xmlStr == ""){
				$msg = 'Error reading XML from '.$xml;
				$this->setError($msg);
				$this->debug($msg);
			return false;
			} else {
				$this->debug("parsing $xml");
				$this->parseString($xmlStr,$type);
				$this->debug("done parsing $xml");
			return true;
			}
		}
		return false;
	}

	/**
	* parse an XML string
	*
	* @param    string $xml path or URL
    * @param	string $type (schema|xml)
	* @access   private
	*/
	function parseString($xml,$type){
		// parse xml string
		if($xml != ""){

	    	// Create an XML parser.
	    	$this->parser = xml_parser_create();
	    	// Set the options for parsing the XML data.
	    	xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);

	    	// Set the object for the parser.
	    	xml_set_object($this->parser, $this);

	    	// Set the element handlers for the parser.
			if($type == "schema"){
		    	xml_set_element_handler($this->parser, 'schemaStartElement','schemaEndElement');
		    	xml_set_character_data_handler($this->parser,'schemaCharacterData');
			} elseif($type == "xml"){
				xml_set_element_handler($this->parser, 'xmlStartElement','xmlEndElement');
		    	xml_set_character_data_handler($this->parser,'xmlCharacterData');
			}

		    // Parse the XML file.
		    if(!xml_parse($this->parser,$xml,true)){
			// Display an error message.
				$errstr = sprintf('XML error parsing XML schema on line %d: %s',
				xml_get_current_line_number($this->parser),
				xml_error_string(xml_get_error_code($this->parser))
				);
				$this->debug($errstr);
				$this->debug("XML payload:\n" . $xml);
				$this->setError($errstr);
	    	}

			xml_parser_free($this->parser);
		} else{
			$this->debug('no xml passed to parseString()!!');
			$this->setError('no xml passed to parseString()!!');
		}
	}

	/**
	 * gets a type name for an unnamed type
	 *
	 * @param	string	Element name
	 * @return	string	A type name for an unnamed type
	 * @access	private
	 */
	function CreateTypeName($ename) {
		$scope = '';
		for ($i = 0; $i < count($this->complexTypeStack); $i++) {
			$scope .= $this->complexTypeStack[$i] . '_';
		}
		return $scope . $ename . '_ContainedType';
	}

	/**
	* start-element handler
	*
	* @param    string $parser XML parser object
	* @param    string $name element name
	* @param    string $attrs associative array of attributes
	* @access   private
	*/
	function schemaStartElement($parser, $name, $attrs) {

		// position in the total number of elements, starting from 0
		$pos = $this->position++;
		$depth = $this->depth++;
		// set self as current value for this depth
		$this->depth_array[$depth] = $pos;
		$this->message[$pos] = array('cdata' => '');
		if ($depth > 0) {
			$this->defaultNamespace[$pos] = $this->defaultNamespace[$this->depth_array[$depth - 1]];
		} else {
			$this->defaultNamespace[$pos] = false;
		}

		// get element prefix
		if($prefix = $this->getPrefix($name)){
			// get unqualified name
			$name = $this->getLocalPart($name);
		} else {
        	$prefix = '';
        }

        // loop thru attributes, expanding, and registering namespace declarations
        if(count($attrs) > 0){
        	foreach($attrs as $k => $v){
                // if ns declarations, add to class level array of valid namespaces
				if(preg_match('/^xmlns/',$k)){
                	//$this->xdebug("$k: $v");
                	//$this->xdebug('ns_prefix: '.$this->getPrefix($k));
                	if($ns_prefix = substr(strrchr($k,':'),1)){
                		//$this->xdebug("Add namespace[$ns_prefix] = $v");
						$this->namespaces[$ns_prefix] = $v;
					} else {
						$this->defaultNamespace[$pos] = $v;
						if (! $this->getPrefixFromNamespace($v)) {
							$this->namespaces['ns'.(count($this->namespaces)+1)] = $v;
						}
					}
					if($v == 'http://www.w3.org/2001/XMLSchema' || $v == 'http://www.w3.org/1999/XMLSchema' || $v == 'http://www.w3.org/2000/10/XMLSchema'){
						$this->XMLSchemaVersion = $v;
						$this->namespaces['xsi'] = $v.'-instance';
					}
				}
        	}
        	foreach($attrs as $k => $v){
                // expand each attribute
                $k = strpos($k,':') ? $this->expandQname($k) : $k;
                $v = strpos($v,':') ? $this->expandQname($v) : $v;
        		$eAttrs[$k] = $v;
        	}
        	$attrs = $eAttrs;
        } else {
        	$attrs = array();
        }
		// find status, register data
		switch($name){
			case 'all':			// (optional) compositor content for a complexType
			case 'choice':
			case 'group':
			case 'sequence':
				//$this->xdebug("compositor $name for currentComplexType: $this->currentComplexType and currentElement: $this->currentElement");
				$this->complexTypes[$this->currentComplexType]['compositor'] = $name;
				//if($name == 'all' || $name == 'sequence'){
				//	$this->complexTypes[$this->currentComplexType]['phpType'] = 'struct';
				//}
			break;
			case 'attribute':	// complexType attribute
            	//$this->xdebug("parsing attribute $attrs[name] $attrs[ref] of value: ".$attrs['http://schemas.xmlsoap.org/wsdl/:arrayType']);
            	$this->xdebug("parsing attribute:");
            	$this->appendDebug($this->varDump($attrs));
				if (!isset($attrs['form'])) {
					// TODO: handle globals
					$attrs['form'] = $this->schemaInfo['attributeFormDefault'];
				}
            	if (isset($attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'])) {
					$v = $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
					if (!strpos($v, ':')) {
						// no namespace in arrayType attribute value...
						if ($this->defaultNamespace[$pos]) {
							// ...so use the default
							$attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'] = $this->defaultNamespace[$pos] . ':' . $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
						}
					}
            	}
                if(isset($attrs['name'])){
					$this->attributes[$attrs['name']] = $attrs;
					$aname = $attrs['name'];
				} elseif(isset($attrs['ref']) && $attrs['ref'] == 'http://schemas.xmlsoap.org/soap/encoding/:arrayType'){
					if (isset($attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'])) {
	                	$aname = $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
	                } else {
	                	$aname = '';
	                }
				} elseif(isset($attrs['ref'])){
					$aname = $attrs['ref'];
                    $this->attributes[$attrs['ref']] = $attrs;
				}

				if($this->currentComplexType){	// This should *always* be
					$this->complexTypes[$this->currentComplexType]['attrs'][$aname] = $attrs;
				}
				// arrayType attribute
				if(isset($attrs['http://schemas.xmlsoap.org/wsdl/:arrayType']) || $this->getLocalPart($aname) == 'arrayType'){
					$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
                	$prefix = $this->getPrefix($aname);
					if(isset($attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'])){
						$v = $attrs['http://schemas.xmlsoap.org/wsdl/:arrayType'];
					} else {
						$v = '';
					}
                    if(strpos($v,'[,]')){
                        $this->complexTypes[$this->currentComplexType]['multidimensional'] = true;
                    }
                    $v = substr($v,0,strpos($v,'[')); // clip the []
                    if(!strpos($v,':') && isset($this->typemap[$this->XMLSchemaVersion][$v])){
                        $v = $this->XMLSchemaVersion.':'.$v;
                    }
                    $this->complexTypes[$this->currentComplexType]['arrayType'] = $v;
				}
			break;
			case 'complexContent':	// (optional) content for a complexType
				$this->xdebug("do nothing for element $name");
			break;
			case 'complexType':
				array_push($this->complexTypeStack, $this->currentComplexType);
				if(isset($attrs['name'])){
					// TODO: what is the scope of named complexTypes that appear
					//       nested within other c complexTypes?
					$this->xdebug('processing named complexType '.$attrs['name']);
					//$this->currentElement = false;
					$this->currentComplexType = $attrs['name'];
					$this->complexTypes[$this->currentComplexType] = $attrs;
					$this->complexTypes[$this->currentComplexType]['typeClass'] = 'complexType';
					// This is for constructs like
					//           <complexType name="ListOfString" base="soap:Array">
					//                <sequence>
					//                    <element name="string" type="xsd:string"
					//                        minOccurs="0" maxOccurs="unbounded" />
					//                </sequence>
					//            </complexType>
					if(isset($attrs['base']) && preg_match('/:Array$/',$attrs['base'])){
						$this->xdebug('complexType is unusual array');
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
					} else {
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'struct';
					}
				} else {
					$name = $this->CreateTypeName($this->currentElement);
					$this->xdebug('processing unnamed complexType for element ' . $this->currentElement . ' named ' . $name);
					$this->currentComplexType = $name;
					//$this->currentElement = false;
					$this->complexTypes[$this->currentComplexType] = $attrs;
					$this->complexTypes[$this->currentComplexType]['typeClass'] = 'complexType';
					// This is for constructs like
					//           <complexType name="ListOfString" base="soap:Array">
					//                <sequence>
					//                    <element name="string" type="xsd:string"
					//                        minOccurs="0" maxOccurs="unbounded" />
					//                </sequence>
					//            </complexType>
					if(isset($attrs['base']) && preg_match('/:Array$/',$attrs['base'])){
						$this->xdebug('complexType is unusual array');
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
					} else {
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'struct';
					}
				}
				$this->complexTypes[$this->currentComplexType]['simpleContent'] = 'false';
			break;
			case 'element':
				array_push($this->elementStack, $this->currentElement);
				if (!isset($attrs['form'])) {
					if ($this->currentComplexType) {
						$attrs['form'] = $this->schemaInfo['elementFormDefault'];
					} else {
						// global
						$attrs['form'] = 'qualified';
					}
				}
				if(isset($attrs['type'])){
					$this->xdebug("processing typed element ".$attrs['name']." of type ".$attrs['type']);
					if (! $this->getPrefix($attrs['type'])) {
						if ($this->defaultNamespace[$pos]) {
							$attrs['type'] = $this->defaultNamespace[$pos] . ':' . $attrs['type'];
							$this->xdebug('used default namespace to make type ' . $attrs['type']);
						}
					}
					// This is for constructs like
					//           <complexType name="ListOfString" base="soap:Array">
					//                <sequence>
					//                    <element name="string" type="xsd:string"
					//                        minOccurs="0" maxOccurs="unbounded" />
					//                </sequence>
					//            </complexType>
					if ($this->currentComplexType && $this->complexTypes[$this->currentComplexType]['phpType'] == 'array') {
						$this->xdebug('arrayType for unusual array is ' . $attrs['type']);
						$this->complexTypes[$this->currentComplexType]['arrayType'] = $attrs['type'];
					}
					$this->currentElement = $attrs['name'];
					$ename = $attrs['name'];
				} elseif(isset($attrs['ref'])){
					$this->xdebug("processing element as ref to ".$attrs['ref']);
					$this->currentElement = "ref to ".$attrs['ref'];
					$ename = $this->getLocalPart($attrs['ref']);
				} else {
					$type = $this->CreateTypeName($this->currentComplexType . '_' . $attrs['name']);
					$this->xdebug("processing untyped element " . $attrs['name'] . ' type ' . $type);
					$this->currentElement = $attrs['name'];
					$attrs['type'] = $this->schemaTargetNamespace . ':' . $type;
					$ename = $attrs['name'];
				}
				if (isset($ename) && $this->currentComplexType) {
					$this->xdebug("add element $ename to complexType $this->currentComplexType");
					$this->complexTypes[$this->currentComplexType]['elements'][$ename] = $attrs;
				} elseif (!isset($attrs['ref'])) {
					$this->xdebug("add element $ename to elements array");
					$this->elements[ $attrs['name'] ] = $attrs;
					$this->elements[ $attrs['name'] ]['typeClass'] = 'element';
				}
			break;
			case 'enumeration':	//	restriction value list member
				$this->xdebug('enumeration ' . $attrs['value']);
				if ($this->currentSimpleType) {
					$this->simpleTypes[$this->currentSimpleType]['enumeration'][] = $attrs['value'];
				} elseif ($this->currentComplexType) {
					$this->complexTypes[$this->currentComplexType]['enumeration'][] = $attrs['value'];
				}
			break;
			case 'extension':	// simpleContent or complexContent type extension
				$this->xdebug('extension ' . $attrs['base']);
				if ($this->currentComplexType) {
					$ns = $this->getPrefix($attrs['base']);
					if ($ns == '') {
						$this->complexTypes[$this->currentComplexType]['extensionBase'] = $this->schemaTargetNamespace . ':' . $attrs['base'];
					} else {
						$this->complexTypes[$this->currentComplexType]['extensionBase'] = $attrs['base'];
					}
				} else {
					$this->xdebug('no current complexType to set extensionBase');
				}
			break;
			case 'import':
			    if (isset($attrs['schemaLocation'])) {
					$this->xdebug('import namespace ' . $attrs['namespace'] . ' from ' . $attrs['schemaLocation']);
                    $this->imports[$attrs['namespace']][] = array('location' => $attrs['schemaLocation'], 'loaded' => false);
				} else {
					$this->xdebug('import namespace ' . $attrs['namespace']);
                    $this->imports[$attrs['namespace']][] = array('location' => '', 'loaded' => true);
					if (! $this->getPrefixFromNamespace($attrs['namespace'])) {
						$this->namespaces['ns'.(count($this->namespaces)+1)] = $attrs['namespace'];
					}
				}
			break;
			case 'include':
			    if (isset($attrs['schemaLocation'])) {
					$this->xdebug('include into namespace ' . $this->schemaTargetNamespace . ' from ' . $attrs['schemaLocation']);
                    $this->imports[$this->schemaTargetNamespace][] = array('location' => $attrs['schemaLocation'], 'loaded' => false);
				} else {
					$this->xdebug('ignoring invalid XML Schema construct: include without schemaLocation attribute');
				}
			break;
			case 'list':	// simpleType value list
				$this->xdebug("do nothing for element $name");
			break;
			case 'restriction':	// simpleType, simpleContent or complexContent value restriction
				$this->xdebug('restriction ' . $attrs['base']);
				if($this->currentSimpleType){
					$this->simpleTypes[$this->currentSimpleType]['type'] = $attrs['base'];
				} elseif($this->currentComplexType){
					$this->complexTypes[$this->currentComplexType]['restrictionBase'] = $attrs['base'];
					if(strstr($attrs['base'],':') == ':Array'){
						$this->complexTypes[$this->currentComplexType]['phpType'] = 'array';
					}
				}
			break;
			case 'schema':
				$this->schemaInfo = $attrs;
				$this->schemaInfo['schemaVersion'] = $this->getNamespaceFromPrefix($prefix);
				if (isset($attrs['targetNamespace'])) {
					$this->schemaTargetNamespace = $attrs['targetNamespace'];
				}
				if (!isset($attrs['elementFormDefault'])) {
					$this->schemaInfo['elementFormDefault'] = 'unqualified';
				}
				if (!isset($attrs['attributeFormDefault'])) {
					$this->schemaInfo['attributeFormDefault'] = 'unqualified';
				}
			break;
			case 'simpleContent':	// (optional) content for a complexType
				if ($this->currentComplexType) {	// This should *always* be
					$this->complexTypes[$this->currentComplexType]['simpleContent'] = 'true';
				} else {
					$this->xdebug("do nothing for element $name because there is no current complexType");
				}
			break;
			case 'simpleType':
				array_push($this->simpleTypeStack, $this->currentSimpleType);
				if(isset($attrs['name'])){
					$this->xdebug("processing simpleType for name " . $attrs['name']);
					$this->currentSimpleType = $attrs['name'];
					$this->simpleTypes[ $attrs['name'] ] = $attrs;
					$this->simpleTypes[ $attrs['name'] ]['typeClass'] = 'simpleType';
					$this->simpleTypes[ $attrs['name'] ]['phpType'] = 'scalar';
				} else {
					$name = $this->CreateTypeName($this->currentComplexType . '_' . $this->currentElement);
					$this->xdebug('processing unnamed simpleType for element ' . $this->currentElement . ' named ' . $name);
					$this->currentSimpleType = $name;
					//$this->currentElement = false;
					$this->simpleTypes[$this->currentSimpleType] = $attrs;
					$this->simpleTypes[$this->currentSimpleType]['phpType'] = 'scalar';
				}
			break;
			case 'union':	// simpleType type list
				$this->xdebug("do nothing for element $name");
			break;
			default:
				$this->xdebug("do not have any logic to process element $name");
		}
	}

	/**
	* end-element handler
	*
	* @param    string $parser XML parser object
	* @param    string $name element name
	* @access   private
	*/
	function schemaEndElement($parser, $name) {
		// bring depth down a notch
		$this->depth--;
		// position of current element is equal to the last value left in depth_array for my depth
		if(isset($this->depth_array[$this->depth])){
        	$pos = $this->depth_array[$this->depth];
        }
		// get element prefix
		if ($prefix = $this->getPrefix($name)){
			// get unqualified name
			$name = $this->getLocalPart($name);
		} else {
        	$prefix = '';
        }
		// move on...
		if($name == 'complexType'){
			$this->xdebug('done processing complexType ' . ($this->currentComplexType ? $this->currentComplexType : '(unknown)'));
			$this->xdebug($this->varDump($this->complexTypes[$this->currentComplexType]));
			$this->currentComplexType = array_pop($this->complexTypeStack);
			//$this->currentElement = false;
		}
		if($name == 'element'){
			$this->xdebug('done processing element ' . ($this->currentElement ? $this->currentElement : '(unknown)'));
			$this->currentElement = array_pop($this->elementStack);
		}
		if($name == 'simpleType'){
			$this->xdebug('done processing simpleType ' . ($this->currentSimpleType ? $this->currentSimpleType : '(unknown)'));
			$this->xdebug($this->varDump($this->simpleTypes[$this->currentSimpleType]));
			$this->currentSimpleType = array_pop($this->simpleTypeStack);
		}
	}

	/**
	* element content handler
	*
	* @param    string $parser XML parser object
	* @param    string $data element content
	* @access   private
	*/
	function schemaCharacterData($parser, $data){
		$pos = $this->depth_array[$this->depth - 1];
		$this->message[$pos]['cdata'] .= $data;
	}

	/**
	* serialize the schema
	*
	* @access   public
	*/
	function serializeSchema(){

		$schemaPrefix = $this->getPrefixFromNamespace($this->XMLSchemaVersion);
		$xml = '';
		// imports
		if (sizeof($this->imports) > 0) {
			foreach($this->imports as $ns => $list) {
				foreach ($list as $ii) {
					if ($ii['location'] != '') {
						$xml .= " <$schemaPrefix:import location=\"" . $ii['location'] . '" namespace="' . $ns . "\" />\n";
					} else {
						$xml .= " <$schemaPrefix:import namespace=\"" . $ns . "\" />\n";
					}
				}
			}
		}
		// complex types
		foreach($this->complexTypes as $typeName => $attrs){
			$contentStr = '';
			// serialize child elements
			if(isset($attrs['elements']) && (count($attrs['elements']) > 0)){
				foreach($attrs['elements'] as $element => $eParts){
					if(isset($eParts['ref'])){
						$contentStr .= "   <$schemaPrefix:element ref=\"$element\"/>\n";
					} else {
						$contentStr .= "   <$schemaPrefix:element name=\"$element\" type=\"" . $this->contractQName($eParts['type']) . "\"";
						foreach ($eParts as $aName => $aValue) {
							// handle, e.g., abstract, default, form, minOccurs, maxOccurs, nillable
							if ($aName != 'name' && $aName != 'type') {
								$contentStr .= " $aName=\"$aValue\"";
							}
						}
						$contentStr .= "/>\n";
					}
				}
				// compositor wraps elements
				if (isset($attrs['compositor']) && ($attrs['compositor'] != '')) {
					$contentStr = "  <$schemaPrefix:$attrs[compositor]>\n".$contentStr."  </$schemaPrefix:$attrs[compositor]>\n";
				}
			}
			// attributes
			if(isset($attrs['attrs']) && (count($attrs['attrs']) >= 1)){
				foreach($attrs['attrs'] as $attr => $aParts){
					$contentStr .= "    <$schemaPrefix:attribute";
					foreach ($aParts as $a => $v) {
						if ($a == 'ref' || $a == 'type') {
							$contentStr .= " $a=\"".$this->contractQName($v).'"';
						} elseif ($a == 'http://schemas.xmlsoap.org/wsdl/:arrayType') {
							$this->usedNamespaces['wsdl'] = $this->namespaces['wsdl'];
							$contentStr .= ' wsdl:arrayType="'.$this->contractQName($v).'"';
						} else {
							$contentStr .= " $a=\"$v\"";
						}
					}
					$contentStr .= "/>\n";
				}
			}
			// if restriction
			if (isset($attrs['restrictionBase']) && $attrs['restrictionBase'] != ''){
				$contentStr = "   <$schemaPrefix:restriction base=\"".$this->contractQName($attrs['restrictionBase'])."\">\n".$contentStr."   </$schemaPrefix:restriction>\n";
				// complex or simple content
				if ((isset($attrs['elements']) && count($attrs['elements']) > 0) || (isset($attrs['attrs']) && count($attrs['attrs']) > 0)){
					$contentStr = "  <$schemaPrefix:complexContent>\n".$contentStr."  </$schemaPrefix:complexContent>\n";
				}
			}
			// finalize complex type
			if($contentStr != ''){
				$contentStr = " <$schemaPrefix:complexType name=\"$typeName\">\n".$contentStr." </$schemaPrefix:complexType>\n";
			} else {
				$contentStr = " <$schemaPrefix:complexType name=\"$typeName\"/>\n";
			}
			$xml .= $contentStr;
		}
		// simple types
		if(isset($this->simpleTypes) && count($this->simpleTypes) > 0){
			foreach($this->simpleTypes as $typeName => $eParts){
				$xml .= " <$schemaPrefix:simpleType name=\"$typeName\">\n  <$schemaPrefix:restriction base=\"".$this->contractQName($eParts['type'])."\">\n";
				if (isset($eParts['enumeration'])) {
					foreach ($eParts['enumeration'] as $e) {
						$xml .= "  <$schemaPrefix:enumeration value=\"$e\"/>\n";
					}
				}
				$xml .= "  </$schemaPrefix:restriction>\n </$schemaPrefix:simpleType>";
			}
		}
		// elements
		if(isset($this->elements) && count($this->elements) > 0){
			foreach($this->elements as $element => $eParts){
				$xml .= " <$schemaPrefix:element name=\"$element\" type=\"".$this->contractQName($eParts['type'])."\"/>\n";
			}
		}
		// attributes
		if(isset($this->attributes) && count($this->attributes) > 0){
			foreach($this->attributes as $attr => $aParts){
				$xml .= " <$schemaPrefix:attribute name=\"$attr\" type=\"".$this->contractQName($aParts['type'])."\"\n/>";
			}
		}
		// finish 'er up
		$attr = '';
		foreach ($this->schemaInfo as $k => $v) {
			if ($k == 'elementFormDefault' || $k == 'attributeFormDefault') {
				$attr .= " $k=\"$v\"";
			}
		}
		$el = "<$schemaPrefix:schema$attr targetNamespace=\"$this->schemaTargetNamespace\"\n";
		foreach (array_diff($this->usedNamespaces, $this->enclosingNamespaces) as $nsp => $ns) {
			$el .= " xmlns:$nsp=\"$ns\"";
		}
		$xml = $el . ">\n".$xml."</$schemaPrefix:schema>\n";
		return $xml;
	}

	/**
	* adds debug data to the clas level debug string
	*
	* @param    string $string debug data
	* @access   private
	*/
	function xdebug($string){
		$this->debug('<' . $this->schemaTargetNamespace . '> '.$string);
	}

    /**
    * get the PHP type of a user defined type in the schema
    * PHP type is kind of a misnomer since it actually returns 'struct' for assoc. arrays
    * returns false if no type exists, or not w/ the given namespace
    * else returns a string that is either a native php type, or 'struct'
    *
    * @param string $type name of defined type
    * @param string $ns namespace of type
    * @return mixed
    * @access public
    * @deprecated
    */
	function getPHPType($type,$ns){
		if(isset($this->typemap[$ns][$type])){
			//print "found type '$type' and ns $ns in typemap<br>";
			return $this->typemap[$ns][$type];
		} elseif(isset($this->complexTypes[$type])){
			//print "getting type '$type' and ns $ns from complexTypes array<br>";
			return $this->complexTypes[$type]['phpType'];
		}
		return false;
	}

	/**
    * returns an associative array of information about a given type
    * returns false if no type exists by the given name
    *
	*	For a complexType typeDef = array(
	*	'restrictionBase' => '',
	*	'phpType' => '',
	*	'compositor' => '(sequence|all)',
	*	'elements' => array(), // refs to elements array
	*	'attrs' => array() // refs to attributes array
	*	... and so on (see addComplexType)
	*	)
	*
	*   For simpleType or element, the array has different keys.
    *
    * @param string $type
    * @return mixed
    * @access public
    * @see addComplexType
    * @see addSimpleType
    * @see addElement
    */
	function getTypeDef($type){
		//$this->debug("in getTypeDef for type $type");
		if (substr($type, -1) == '^') {
			$is_element = 1;
			$type = substr($type, 0, -1);
		} else {
			$is_element = 0;
		}

		if((! $is_element) && isset($this->complexTypes[$type])){
			$this->xdebug("in getTypeDef, found complexType $type");
			return $this->complexTypes[$type];
		} elseif((! $is_element) && isset($this->simpleTypes[$type])){
			$this->xdebug("in getTypeDef, found simpleType $type");
			if (!isset($this->simpleTypes[$type]['phpType'])) {
				// get info for type to tack onto the simple type
				// TODO: can this ever really apply (i.e. what is a simpleType really?)
				$uqType = substr($this->simpleTypes[$type]['type'], strrpos($this->simpleTypes[$type]['type'], ':') + 1);
				$ns = substr($this->simpleTypes[$type]['type'], 0, strrpos($this->simpleTypes[$type]['type'], ':'));
				$etype = $this->getTypeDef($uqType);
				if ($etype) {
					$this->xdebug("in getTypeDef, found type for simpleType $type:");
					$this->xdebug($this->varDump($etype));
					if (isset($etype['phpType'])) {
						$this->simpleTypes[$type]['phpType'] = $etype['phpType'];
					}
					if (isset($etype['elements'])) {
						$this->simpleTypes[$type]['elements'] = $etype['elements'];
					}
				}
			}
			return $this->simpleTypes[$type];
		} elseif(isset($this->elements[$type])){
			$this->xdebug("in getTypeDef, found element $type");
			if (!isset($this->elements[$type]['phpType'])) {
				// get info for type to tack onto the element
				$uqType = substr($this->elements[$type]['type'], strrpos($this->elements[$type]['type'], ':') + 1);
				$ns = substr($this->elements[$type]['type'], 0, strrpos($this->elements[$type]['type'], ':'));
				$etype = $this->getTypeDef($uqType);
				if ($etype) {
					$this->xdebug("in getTypeDef, found type for element $type:");
					$this->xdebug($this->varDump($etype));
					if (isset($etype['phpType'])) {
						$this->elements[$type]['phpType'] = $etype['phpType'];
					}
					if (isset($etype['elements'])) {
						$this->elements[$type]['elements'] = $etype['elements'];
					}
					if (isset($etype['extensionBase'])) {
						$this->elements[$type]['extensionBase'] = $etype['extensionBase'];
					}
				} elseif ($ns == 'http://www.w3.org/2001/XMLSchema') {
					$this->xdebug("in getTypeDef, element $type is an XSD type");
					$this->elements[$type]['phpType'] = 'scalar';
				}
			}
			return $this->elements[$type];
		} elseif(isset($this->attributes[$type])){
			$this->xdebug("in getTypeDef, found attribute $type");
			return $this->attributes[$type];
		} elseif (preg_match('/_ContainedType$/', $type)) {
			$this->xdebug("in getTypeDef, have an untyped element $type");
			$typeDef['typeClass'] = 'simpleType';
			$typeDef['phpType'] = 'scalar';
			$typeDef['type'] = 'http://www.w3.org/2001/XMLSchema:string';
			return $typeDef;
		}
		$this->xdebug("in getTypeDef, did not find $type");
		return false;
	}

	/**
    * returns a sample serialization of a given type, or false if no type by the given name
    *
    * @param string $type name of type
    * @return mixed
    * @access public
    * @deprecated
    */
    function serializeTypeDef($type){
    	//print "in sTD() for type $type<br>";
	if($typeDef = $this->getTypeDef($type)){
		$str .= '<'.$type;
	    if(is_array($typeDef['attrs'])){
		foreach($typeDef['attrs'] as $attName => $data){
		    $str .= " $attName=\"{type = ".$data['type']."}\"";
		}
	    }
	    $str .= " xmlns=\"".$this->schema['targetNamespace']."\"";
	    if(count($typeDef['elements']) > 0){
		$str .= ">";
		foreach($typeDef['elements'] as $element => $eData){
		    $str .= $this->serializeTypeDef($element);
		}
		$str .= "</$type>";
	    } elseif($typeDef['typeClass'] == 'element') {
		$str .= "></$type>";
	    } else {
		$str .= "/>";
	    }
			return $str;
	}
    	return false;
    }

    /**
    * returns HTML form elements that allow a user
    * to enter values for creating an instance of the given type.
    *
    * @param string $name name for type instance
    * @param string $type name of type
    * @return string
    * @access public
    * @deprecated
	*/
	function typeToForm($name,$type){
		// get typedef
		if($typeDef = $this->getTypeDef($type)){
			// if struct
			if($typeDef['phpType'] == 'struct'){
				$buffer .= '<table>';
				foreach($typeDef['elements'] as $child => $childDef){
					$buffer .= "
					<tr><td align='right'>$childDef[name] (type: ".$this->getLocalPart($childDef['type'])."):</td>
					<td><input type='text' name='parameters[".$name."][$childDef[name]]'></td></tr>";
				}
				$buffer .= '</table>';
			// if array
			} elseif($typeDef['phpType'] == 'array'){
				$buffer .= '<table>';
				for($i=0;$i < 3; $i++){
					$buffer .= "
					<tr><td align='right'>array item (type: $typeDef[arrayType]):</td>
					<td><input type='text' name='parameters[".$name."][]'></td></tr>";
				}
				$buffer .= '</table>';
			// if scalar
			} else {
				$buffer .= "<input type='text' name='parameters[$name]'>";
			}
		} else {
			$buffer .= "<input type='text' name='parameters[$name]'>";
		}
		return $buffer;
	}

	/**
	* adds a complex type to the schema
	*
	* example: array
	*
	* addType(
	* 	'ArrayOfstring',
	* 	'complexType',
	* 	'array',
	* 	'',
	* 	'SOAP-ENC:Array',
	* 	array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'string[]'),
	* 	'xsd:string'
	* );
	*
	* example: PHP associative array ( SOAP Struct )
	*
	* addType(
	* 	'SOAPStruct',
	* 	'complexType',
	* 	'struct',
	* 	'all',
	* 	array('myVar'=> array('name'=>'myVar','type'=>'string')
	* );
	*
	* @param name
	* @param typeClass (complexType|simpleType|attribute)
	* @param phpType: currently supported are array and struct (php assoc array)
	* @param compositor (all|sequence|choice)
	* @param restrictionBase namespace:name (http://schemas.xmlsoap.org/soap/encoding/:Array)
	* @param elements = array ( name = array(name=>'',type=>'') )
	* @param attrs = array(
	* 	array(
	*		'ref' => "http://schemas.xmlsoap.org/soap/encoding/:arrayType",
	*		"http://schemas.xmlsoap.org/wsdl/:arrayType" => "string[]"
	* 	)
	* )
	* @param arrayType: namespace:name (http://www.w3.org/2001/XMLSchema:string)
	* @access public
	* @see getTypeDef
	*/
	function addComplexType($name,$typeClass='complexType',$phpType='array',$compositor='',$restrictionBase='',$elements=array(),$attrs=array(),$arrayType=''){
		$this->complexTypes[$name] = array(
	    'name'		=> $name,
	    'typeClass'	=> $typeClass,
	    'phpType'	=> $phpType,
		'compositor'=> $compositor,
	    'restrictionBase' => $restrictionBase,
		'elements'	=> $elements,
	    'attrs'		=> $attrs,
	    'arrayType'	=> $arrayType
		);

		$this->xdebug("addComplexType $name:");
		$this->appendDebug($this->varDump($this->complexTypes[$name]));
	}

	/**
	* adds a simple type to the schema
	*
	* @param string $name
	* @param string $restrictionBase namespace:name (http://schemas.xmlsoap.org/soap/encoding/:Array)
	* @param string $typeClass (should always be simpleType)
	* @param string $phpType (should always be scalar)
	* @param array $enumeration array of values
	* @access public
	* @see p2dWebservices_xmlschema
	* @see getTypeDef
	*/
	function addSimpleType($name, $restrictionBase='', $typeClass='simpleType', $phpType='scalar', $enumeration=array()) {
		$this->simpleTypes[$name] = array(
	    'name'			=> $name,
	    'typeClass'		=> $typeClass,
	    'phpType'		=> $phpType,
	    'type'			=> $restrictionBase,
	    'enumeration'	=> $enumeration
		);

		$this->xdebug("addSimpleType $name:");
		$this->appendDebug($this->varDump($this->simpleTypes[$name]));
	}

	/**
	* adds an element to the schema
	*
	* @param array $attrs attributes that must include name and type
	* @see p2dWebservices_xmlschema
	* @access public
	*/
	function addElement($attrs) {
		if (! $this->getPrefix($attrs['type'])) {
			$attrs['type'] = $this->schemaTargetNamespace . ':' . $attrs['type'];
		}
		$this->elements[ $attrs['name'] ] = $attrs;
		$this->elements[ $attrs['name'] ]['typeClass'] = 'element';

		$this->xdebug("addElement " . $attrs['name']);
		$this->appendDebug($this->varDump($this->elements[ $attrs['name'] ]));
	}
}

?><?php



/**
* For creating serializable abstractions of native PHP types.  This class
* allows element name/namespace, XSD type, and XML attributes to be
* associated with a value.  This is extremely useful when WSDL is not
* used, but is also useful when WSDL is used with polymorphic types, including
* xsd:anyType and user-defined types.
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @access   public
*/



?><?php



/**
* transport class for sending/receiving data via HTTP and HTTPS
* NOTE: PHP must be compiled with the CURL extension for HTTPS support
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @author   Scott Nichol <snichol@users.sourceforge.net>
* @access public
*/
class p2dWebservices_transport_http extends p2dWebservices_base {

	var $url = '';
	var $uri = '';
	var $digest_uri = '';
	var $scheme = '';
	var $host = '';
	var $port = '';
	var $path = '';
	var $request_method = 'POST';
	var $protocol_version = '1.0';
	var $encoding = '';
	var $outgoing_headers = array();
	var $incoming_headers = array();
	var $incoming_cookies = array();
	var $outgoing_payload = '';
	var $incoming_payload = '';
	var $response_status_line;	// HTTP response status line
	var $useSOAPAction = true;
	var $persistentConnection = false;
	var $ch = false;	// cURL handle
	var $ch_options = array();	// cURL custom options
	var $use_curl = false;		// force cURL use
	var $proxy = null;			// proxy information (associative array)
	var $username = '';
	var $password = '';
	var $authtype = '';
	var $digestRequest = array();
	var $certRequest = array();	// keys must be cainfofile (optional), sslcertfile, sslkeyfile, passphrase, certpassword (optional), verifypeer (optional), verifyhost (optional)
								// cainfofile: certificate authority file, e.g. '$pathToPemFiles/rootca.pem'
								// sslcertfile: SSL certificate file, e.g. '$pathToPemFiles/mycert.pem'
								// sslkeyfile: SSL key file, e.g. '$pathToPemFiles/mykey.pem'
								// passphrase: SSL key password/passphrase
								// certpassword: SSL certificate password
								// verifypeer: default is 1
								// verifyhost: default is 1

	/**
	* constructor
	*
	* @param string $url The URL to which to connect
	* @param array $curl_options User-specified cURL options
	* @param boolean $use_curl Whether to try to force cURL use
	* @access public
	*/
	function p2dWebservices_transport_http($url, $curl_options = NULL, $use_curl = false){
		parent::p2dWebservices_base();
		$this->debug("ctor url=$url use_curl=$use_curl curl_options:");
		$this->appendDebug($this->varDump($curl_options));
		$this->setURL($url);
		if (is_array($curl_options)) {
			$this->ch_options = $curl_options;
		}
		$this->use_curl = $use_curl;
		preg_match('/\$Revisio' . 'n: ([^ ]+)/', $this->revision, $rev);
		if (isset($rev[1])) $this->setHeader('User-Agent', $this->title.'/'.$this->version.' ('.$rev[1].')');
	}

	/**
	* sets a cURL option
	*
	* @param	mixed $option The cURL option (always integer?)
	* @param	mixed $value The cURL option value
	* @access   private
	*/
	function setCurlOption($option, $value) {
		$this->debug("setCurlOption option=$option, value=");
		$this->appendDebug($this->varDump($value));
		curl_setopt($this->ch, $option, $value);
	}

	/**
	* sets an HTTP header
	*
	* @param string $name The name of the header
	* @param string $value The value of the header
	* @access private
	*/
	function setHeader($name, $value) {
		$this->outgoing_headers[$name] = $value;
		$this->debug("set header $name: $value");
	}

	/**
	* unsets an HTTP header
	*
	* @param string $name The name of the header
	* @access private
	*/
	function unsetHeader($name) {
		if (isset($this->outgoing_headers[$name])) {
			$this->debug("unset header $name");
			unset($this->outgoing_headers[$name]);
		}
	}

	/**
	* sets the URL to which to connect
	*
	* @param string $url The URL to which to connect
	* @access private
	*/
	function setURL($url) {
		$this->url = $url;

		$u = parse_url($url);
		foreach($u as $k => $v){
			$this->debug("parsed URL $k = $v");
			$this->$k = $v;
		}

		// add any GET params to path
		if(isset($u['query']) && $u['query'] != ''){
            $this->path .= '?' . $u['query'];
		}

		// set default port
		if(!isset($u['port'])){
			if($u['scheme'] == 'https'){
				$this->port = 443;
			} else {
				$this->port = 80;
			}
		}

		$this->uri = $this->path;
		$this->digest_uri = $this->uri;

		// build headers
		if (!isset($u['port'])) {
			$this->setHeader('Host', $this->host);
		} else {
			$this->setHeader('Host', $this->host.':'.$this->port);
		}

		if (isset($u['user']) && $u['user'] != '') {
			$this->setCredentials(urldecode($u['user']), isset($u['pass']) ? urldecode($u['pass']) : '');
		}
	}

	/**
	* gets the I/O method to use
	*
	* @return	string	I/O method to use (socket|curl|unknown)
	* @access	private
	*/
	function io_method() {
		if ($this->use_curl || ($this->scheme == 'https') || ($this->scheme == 'http' && $this->authtype == 'ntlm') || ($this->scheme == 'http' && is_array($this->proxy) && $this->proxy['authtype'] == 'ntlm'))
			return 'curl';
		if (($this->scheme == 'http' || $this->scheme == 'ssl') && $this->authtype != 'ntlm' && (!is_array($this->proxy) || $this->proxy['authtype'] != 'ntlm'))
			return 'socket';
		return 'unknown';
	}

	/**
	* establish an HTTP connection
	*
	* @param    integer $timeout set connection timeout in seconds
	* @param	integer $response_timeout set response timeout in seconds
	* @return	boolean true if connected, false if not
	* @access   private
	*/
	function connect($connection_timeout=0,$response_timeout=30){
	  	// For PHP 4.3 with OpenSSL, change https scheme to ssl, then treat like
	  	// "regular" socket.
	  	// TODO: disabled for now because OpenSSL must be *compiled* in (not just
	  	//       loaded), and until PHP5 stream_get_wrappers is not available.
//	  	if ($this->scheme == 'https') {
//		  	if (version_compare(phpversion(), '4.3.0') >= 0) {
//		  		if (extension_loaded('openssl')) {
//		  			$this->scheme = 'ssl';
//		  			$this->debug('Using SSL over OpenSSL');
//		  		}
//		  	}
//		}
		$this->debug("connect connection_timeout $connection_timeout, response_timeout $response_timeout, scheme $this->scheme, host $this->host, port $this->port");
	  if ($this->io_method() == 'socket') {
		if (!is_array($this->proxy)) {
			$host = $this->host;
			$port = $this->port;
		} else {
			$host = $this->proxy['host'];
			$port = $this->proxy['port'];
		}

		// use persistent connection
		if($this->persistentConnection && isset($this->fp) && is_resource($this->fp)){
			if (!feof($this->fp)) {
				$this->debug('Re-use persistent connection');
				return true;
			}
			fclose($this->fp);
			$this->debug('Closed persistent connection at EOF');
		}

		// munge host if using OpenSSL
		if ($this->scheme == 'ssl') {
			$host = 'ssl://' . $host;
		}
		$this->debug('calling fsockopen with host ' . $host . ' connection_timeout ' . $connection_timeout);

		// open socket
		if($connection_timeout > 0){
			$this->fp = @fsockopen( $host, $this->port, $this->errno, $this->error_str, $connection_timeout);
		} else {
			$this->fp = @fsockopen( $host, $this->port, $this->errno, $this->error_str);
		}

		// test pointer
		if(!$this->fp) {
			$msg = 'Couldn\'t open socket connection to server ' . $this->url;
			if ($this->errno) {
				$msg .= ', Error ('.$this->errno.'): '.$this->error_str;
			} else {
				$msg .= ' prior to connect().  This is often a problem looking up the host name.';
			}
			$this->debug($msg);
			$this->setError($msg);
			return false;
		}

		// set response timeout
		$this->debug('set response timeout to ' . $response_timeout);
		socket_set_timeout( $this->fp, 200);//$response_timeout);//lvsi cyberoffice

		$this->debug('socket connected');
		return true;
	  } else if ($this->io_method() == 'curl') {
		if (!extension_loaded('curl')) {
//			$this->setError('cURL Extension, or OpenSSL extension w/ PHP version >= 4.3 is required for HTTPS');
			$this->setError('The PHP cURL Extension is required for HTTPS or NLTM.  You will need to re-build or update your PHP to include cURL or change php.ini to load the PHP cURL extension.');
			return false;
		}
		// Avoid warnings when PHP does not have these options
		if (defined('CURLOPT_CONNECTIONTIMEOUT'))
			$CURLOPT_CONNECTIONTIMEOUT = CURLOPT_CONNECTIONTIMEOUT;
		else
			$CURLOPT_CONNECTIONTIMEOUT = 78;
		if (defined('CURLOPT_HTTPAUTH'))
			$CURLOPT_HTTPAUTH = CURLOPT_HTTPAUTH;
		else
			$CURLOPT_HTTPAUTH = 107;
		if (defined('CURLOPT_PROXYAUTH'))
			$CURLOPT_PROXYAUTH = CURLOPT_PROXYAUTH;
		else
			$CURLOPT_PROXYAUTH = 111;
		if (defined('CURLAUTH_BASIC'))
			$CURLAUTH_BASIC = CURLAUTH_BASIC;
		else
			$CURLAUTH_BASIC = 1;
		if (defined('CURLAUTH_DIGEST'))
			$CURLAUTH_DIGEST = CURLAUTH_DIGEST;
		else
			$CURLAUTH_DIGEST = 2;
		if (defined('CURLAUTH_NTLM'))
			$CURLAUTH_NTLM = CURLAUTH_NTLM;
		else
			$CURLAUTH_NTLM = 8;

		$this->debug('connect using cURL');
		// init CURL
		$this->ch = curl_init();
		// set url
		$hostURL = ($this->port != '') ? "$this->scheme://$this->host:$this->port" : "$this->scheme://$this->host";
		// add path
		$hostURL .= $this->path;
		$this->setCurlOption(CURLOPT_URL, $hostURL);
		// follow location headers (re-directs)
		if (ini_get('safe_mode') || ini_get('open_basedir')) {
			$this->debug('safe_mode or open_basedir set, so do not set CURLOPT_FOLLOWLOCATION');
			$this->debug('safe_mode = ');
			$this->appendDebug($this->varDump(ini_get('safe_mode')));
			$this->debug('open_basedir = ');
			$this->appendDebug($this->varDump(ini_get('open_basedir')));
		} else {
			$this->setCurlOption(CURLOPT_FOLLOWLOCATION, 1);
		}
		// ask for headers in the response output
		$this->setCurlOption(CURLOPT_HEADER, 1);
		// ask for the response output as the return value
		$this->setCurlOption(CURLOPT_RETURNTRANSFER, 1);
		// encode
		// We manage this ourselves through headers and encoding
//		if(function_exists('gzuncompress')){
//			$this->setCurlOption(CURLOPT_ENCODING, 'deflate');
//		}
		// persistent connection
		if ($this->persistentConnection) {
			// I believe the following comment is now bogus, having applied to
			// the code when it used CURLOPT_CUSTOMREQUEST to send the request.
			// The way we send data, we cannot use persistent connections, since
			// there will be some "junk" at the end of our request.
			//$this->setCurlOption(CURL_HTTP_VERSION_1_1, true);
			$this->persistentConnection = false;
			$this->setHeader('Connection', 'close');
		}
		// set timeouts
		if ($connection_timeout != 0) {
			$this->setCurlOption($CURLOPT_CONNECTIONTIMEOUT, $connection_timeout);
		}
		if ($response_timeout != 0) {
			$this->setCurlOption(CURLOPT_TIMEOUT, $response_timeout);
		}

		if ($this->scheme == 'https') {
			$this->debug('set cURL SSL verify options');
			// recent versions of cURL turn on peer/host checking by default,
			// while PHP binaries are not compiled with a default location for the
			// CA cert bundle, so disable peer/host checking.
			//$this->setCurlOption(CURLOPT_CAINFO, 'f:\php-4.3.2-win32\extensions\curl-ca-bundle.crt');
			$this->setCurlOption(CURLOPT_SSL_VERIFYPEER, 0);
			$this->setCurlOption(CURLOPT_SSL_VERIFYHOST, 0);

			// support client certificates (thanks Tobias Boes, Doug Anarino, Eryan Ariobowo)
			if ($this->authtype == 'certificate') {
				$this->debug('set cURL certificate options');
				if (isset($this->certRequest['cainfofile'])) {
					$this->setCurlOption(CURLOPT_CAINFO, $this->certRequest['cainfofile']);
				}
				if (isset($this->certRequest['verifypeer'])) {
					$this->setCurlOption(CURLOPT_SSL_VERIFYPEER, $this->certRequest['verifypeer']);
				} else {
					$this->setCurlOption(CURLOPT_SSL_VERIFYPEER, 1);
				}
				if (isset($this->certRequest['verifyhost'])) {
					$this->setCurlOption(CURLOPT_SSL_VERIFYHOST, $this->certRequest['verifyhost']);
				} else {
					$this->setCurlOption(CURLOPT_SSL_VERIFYHOST, 1);
				}
				if (isset($this->certRequest['sslcertfile'])) {
					$this->setCurlOption(CURLOPT_SSLCERT, $this->certRequest['sslcertfile']);
				}
				if (isset($this->certRequest['sslkeyfile'])) {
					$this->setCurlOption(CURLOPT_SSLKEY, $this->certRequest['sslkeyfile']);
				}
				if (isset($this->certRequest['passphrase'])) {
					$this->setCurlOption(CURLOPT_SSLKEYPASSWD, $this->certRequest['passphrase']);
				}
				if (isset($this->certRequest['certpassword'])) {
					$this->setCurlOption(CURLOPT_SSLCERTPASSWD, $this->certRequest['certpassword']);
				}
			}
		}
		if ($this->authtype && ($this->authtype != 'certificate')) {
			if ($this->username) {
				$this->debug('set cURL username/password');
				$this->setCurlOption(CURLOPT_USERPWD, "$this->username:$this->password");
			}
			if ($this->authtype == 'basic') {
				$this->debug('set cURL for Basic authentication');
				$this->setCurlOption($CURLOPT_HTTPAUTH, $CURLAUTH_BASIC);
			}
			if ($this->authtype == 'digest') {
				$this->debug('set cURL for digest authentication');
				$this->setCurlOption($CURLOPT_HTTPAUTH, $CURLAUTH_DIGEST);
			}
			if ($this->authtype == 'ntlm') {
				$this->debug('set cURL for NTLM authentication');
				$this->setCurlOption($CURLOPT_HTTPAUTH, $CURLAUTH_NTLM);
			}
		}
		if (is_array($this->proxy)) {
			$this->debug('set cURL proxy options');
			if ($this->proxy['port'] != '') {
				$this->setCurlOption(CURLOPT_PROXY, $this->proxy['host'].':'.$this->proxy['port']);
			} else {
				$this->setCurlOption(CURLOPT_PROXY, $this->proxy['host']);
			}
			if ($this->proxy['username'] || $this->proxy['password']) {
				$this->debug('set cURL proxy authentication options');
				$this->setCurlOption(CURLOPT_PROXYUSERPWD, $this->proxy['username'].':'.$this->proxy['password']);
				if ($this->proxy['authtype'] == 'basic') {
					$this->setCurlOption($CURLOPT_PROXYAUTH, $CURLAUTH_BASIC);
				}
				if ($this->proxy['authtype'] == 'ntlm') {
					$this->setCurlOption($CURLOPT_PROXYAUTH, $CURLAUTH_NTLM);
				}
			}
		}
		$this->debug('cURL connection set up');
		return true;
	  } else {
		$this->setError('Unknown scheme ' . $this->scheme);
		$this->debug('Unknown scheme ' . $this->scheme);
		return false;
	  }
	}

	/**
	* sends the SOAP request and gets the SOAP response via HTTP[S]
	*
	* @param    string $data message data
	* @param    integer $timeout set connection timeout in seconds
	* @param	integer $response_timeout set response timeout in seconds
	* @param	array $cookies cookies to send
	* @return	string data
	* @access   public
	*/
	function send($data, $timeout=0, $response_timeout=30, $cookies=NULL) {

		$this->debug('entered send() with data of length: '.strlen($data));

		$this->tryagain = true;
		$tries = 0;
		while ($this->tryagain) {
			$this->tryagain = false;
			if ($tries++ < 2) {
				// make connnection
				if (!$this->connect($timeout, $response_timeout)){
					return false;
				}

				// send request
				if (!$this->sendRequest($data, $cookies)){
					return false;
				}

				// get response
				$respdata = $this->getResponse();
			} else {
				$this->setError("Too many tries to get an OK response ($this->response_status_line)");
			}
		}
		$this->debug('end of send()');
		return $respdata;
	}


	/**
	* sends the SOAP request and gets the SOAP response via HTTPS using CURL
	*
	* @param    string $data message data
	* @param    integer $timeout set connection timeout in seconds
	* @param	integer $response_timeout set response timeout in seconds
	* @param	array $cookies cookies to send
	* @return	string data
	* @access   public
	* @deprecated
	*/
	function sendHTTPS($data, $timeout=0, $response_timeout=30, $cookies) {
		return $this->send($data, $timeout, $response_timeout, $cookies);
	}

	/**
	* if authenticating, set user credentials here
	*
	* @param    string $username
	* @param    string $password
	* @param	string $authtype (basic|digest|certificate|ntlm)
	* @param	array $digestRequest (keys must be nonce, nc, realm, qop)
	* @param	array $certRequest (keys must be cainfofile (optional), sslcertfile, sslkeyfile, passphrase, certpassword (optional), verifypeer (optional), verifyhost (optional): see corresponding options in cURL docs)
	* @access   public
	*/
	function setCredentials($username, $password, $authtype = 'basic', $digestRequest = array(), $certRequest = array()) {
		$this->debug("setCredentials username=$username authtype=$authtype digestRequest=");
		$this->appendDebug($this->varDump($digestRequest));
		$this->debug("certRequest=");
		$this->appendDebug($this->varDump($certRequest));
		// cf. RFC 2617
		if ($authtype == 'basic') {
			$this->setHeader('Authorization', 'Basic '.base64_encode(str_replace(':','',$username).':'.$password));
		} elseif ($authtype == 'digest') {
			if (isset($digestRequest['nonce'])) {
				$digestRequest['nc'] = isset($digestRequest['nc']) ? $digestRequest['nc']++ : 1;

				// calculate the Digest hashes (calculate code based on digest implementation found at: http://www.rassoc.com/gregr/weblog/stories/2002/07/09/webServicesSecurityHttpDigestAuthenticationWithoutActiveDirectory.html)

				// A1 = unq(username-value) ":" unq(realm-value) ":" passwd
				$A1 = $username. ':' . (isset($digestRequest['realm']) ? $digestRequest['realm'] : '') . ':' . $password;

				// H(A1) = MD5(A1)
				$HA1 = md5($A1);

				// A2 = Method ":" digest-uri-value
				$A2 = $this->request_method . ':' . $this->digest_uri;

				// H(A2)
				$HA2 =  md5($A2);

				// KD(secret, data) = H(concat(secret, ":", data))
				// if qop == auth:
				// request-digest  = <"> < KD ( H(A1),     unq(nonce-value)
				//                              ":" nc-value
				//                              ":" unq(cnonce-value)
				//                              ":" unq(qop-value)
				//                              ":" H(A2)
				//                            ) <">
				// if qop is missing,
				// request-digest  = <"> < KD ( H(A1), unq(nonce-value) ":" H(A2) ) > <">

				$unhashedDigest = '';
				$nonce = isset($digestRequest['nonce']) ? $digestRequest['nonce'] : '';
				$cnonce = $nonce;
				if ($digestRequest['qop'] != '') {
					$unhashedDigest = $HA1 . ':' . $nonce . ':' . sprintf("%08d", $digestRequest['nc']) . ':' . $cnonce . ':' . $digestRequest['qop'] . ':' . $HA2;
				} else {
					$unhashedDigest = $HA1 . ':' . $nonce . ':' . $HA2;
				}

				$hashedDigest = md5($unhashedDigest);

				$opaque = '';
				if (isset($digestRequest['opaque'])) {
					$opaque = ', opaque="' . $digestRequest['opaque'] . '"';
				}

				$this->setHeader('Authorization', 'Digest username="' . $username . '", realm="' . $digestRequest['realm'] . '", nonce="' . $nonce . '", uri="' . $this->digest_uri . $opaque . '", cnonce="' . $cnonce . '", nc=' . sprintf("%08x", $digestRequest['nc']) . ', qop="' . $digestRequest['qop'] . '", response="' . $hashedDigest . '"');
			}
		} elseif ($authtype == 'certificate') {
			$this->certRequest = $certRequest;
			$this->debug('Authorization header not set for certificate');
		} elseif ($authtype == 'ntlm') {
			// do nothing
			$this->debug('Authorization header not set for ntlm');
		}
		$this->username = $username;
		$this->password = $password;
		$this->authtype = $authtype;
		$this->digestRequest = $digestRequest;
	}

	/**
	* set the soapaction value
	*
	* @param    string $soapaction
	* @access   public
	*/
	function setSOAPAction($soapaction) {
		$this->setHeader('SOAPAction', '"' . $soapaction . '"');
	}

	/**
	* use http encoding
	*
	* @param    string $enc encoding style. supported values: gzip, deflate, or both
	* @access   public
	*/
	function setEncoding($enc='gzip, deflate') {
		if (function_exists('gzdeflate')) {
			$this->protocol_version = '1.1';
			$this->setHeader('Accept-Encoding', $enc);
			if (!isset($this->outgoing_headers['Connection'])) {
				$this->setHeader('Connection', 'close');
				$this->persistentConnection = false;
			}
			// deprecated as of PHP 5.3.0
			//set_magic_quotes_runtime(0);
			$this->encoding = $enc;
		}
	}

	/**
	* set proxy info here
	*
	* @param    string $proxyhost use an empty string to remove proxy
	* @param    string $proxyport
	* @param	string $proxyusername
	* @param	string $proxypassword
	* @param	string $proxyauthtype (basic|ntlm)
	* @access   public
	*/
	function setProxy($proxyhost, $proxyport, $proxyusername = '', $proxypassword = '', $proxyauthtype = 'basic') {
		if ($proxyhost) {
			$this->proxy = array(
				'host' => $proxyhost,
				'port' => $proxyport,
				'username' => $proxyusername,
				'password' => $proxypassword,
				'authtype' => $proxyauthtype
			);
			if ($proxyusername != '' && $proxypassword != '' && $proxyauthtype = 'basic') {
				$this->setHeader('Proxy-Authorization', ' Basic '.base64_encode($proxyusername.':'.$proxypassword));
			}
		} else {
			$this->debug('remove proxy');
			$proxy = null;
			$this->unsetHeader('Proxy-Authorization');
		}
	}


	/**
	 * Test if the given string starts with a header that is to be skipped.
	 * Skippable headers result from chunked transfer and proxy requests.
	 *
	 * @param	string $data The string to check.
	 * @returns	boolean	Whether a skippable header was found.
	 * @access	private
	 */
	function isSkippableCurlHeader(&$data) {
		$skipHeaders = array(	'HTTP/1.1 100',
								'HTTP/1.0 301',
								'HTTP/1.1 301',
								'HTTP/1.0 302',
								'HTTP/1.1 302',
								'HTTP/1.0 401',
								'HTTP/1.1 401',
								'HTTP/1.0 200 Connection established');
		foreach ($skipHeaders as $hd) {
			$prefix = substr($data, 0, strlen($hd));
			if ($prefix == $hd) return true;
		}

		return false;
	}

	/**
	* decode a string that is encoded w/ "chunked' transfer encoding
 	* as defined in RFC2068 19.4.6
	*
	* @param    string $buffer
	* @param    string $lb
	* @returns	string
	* @access   public
	* @deprecated
	*/
	function decodeChunked($buffer, $lb){
		// length := 0
		$length = 0;
		$new = '';

		// read chunk-size, chunk-extension (if any) and CRLF
		// get the position of the linebreak
		$chunkend = strpos($buffer, $lb);
		if ($chunkend == FALSE) {
			$this->debug('no linebreak found in decodeChunked');
			return $new;
		}
		$temp = substr($buffer,0,$chunkend);
		$chunk_size = hexdec( trim($temp) );
		$chunkstart = $chunkend + strlen($lb);
		// while (chunk-size > 0) {
		while ($chunk_size > 0) {
			$this->debug("chunkstart: $chunkstart chunk_size: $chunk_size");
			$chunkend = strpos( $buffer, $lb, $chunkstart + $chunk_size);

			// Just in case we got a broken connection
		  	if ($chunkend == FALSE) {
		  	    $chunk = substr($buffer,$chunkstart);
				// append chunk-data to entity-body
		    	$new .= $chunk;
		  	    $length += strlen($chunk);
		  	    break;
			}

		  	// read chunk-data and CRLF
		  	$chunk = substr($buffer,$chunkstart,$chunkend-$chunkstart);
		  	// append chunk-data to entity-body
		  	$new .= $chunk;
		  	// length := length + chunk-size
		  	$length += strlen($chunk);
		  	// read chunk-size and CRLF
		  	$chunkstart = $chunkend + strlen($lb);

		  	$chunkend = strpos($buffer, $lb, $chunkstart) + strlen($lb);
			if ($chunkend == FALSE) {
				break; //Just in case we got a broken connection
			}
			$temp = substr($buffer,$chunkstart,$chunkend-$chunkstart);
			$chunk_size = hexdec( trim($temp) );
			$chunkstart = $chunkend;
		}
		return $new;
	}

	/**
	 * Writes the payload, including HTTP headers, to $this->outgoing_payload.
	 *
	 * @param	string $data HTTP body
	 * @param	string $cookie_str data for HTTP Cookie header
	 * @return	void
	 * @access	private
	 */
	function buildPayload($data, $cookie_str = '') {
		// Note: for cURL connections, $this->outgoing_payload is ignored,
		// as is the Content-Length header, but these are still created as
		// debugging guides.

		// add content-length header
		if ($this->request_method != 'GET') {
			$this->setHeader('Content-Length', strlen($data));
		}

		// start building outgoing payload:
		if ($this->proxy) {
			$uri = $this->url;
		} else {
			$uri = $this->uri;
		}
		$req = "$this->request_method $uri HTTP/$this->protocol_version";
		$this->debug("HTTP request: $req");
		$this->outgoing_payload = "$req\r\n";

		// loop thru headers, serializing
		foreach($this->outgoing_headers as $k => $v){
			$hdr = $k.': '.$v;
			$this->debug("HTTP header: $hdr");
			$this->outgoing_payload .= "$hdr\r\n";
		}

		// add any cookies
		if ($cookie_str != '') {
			$hdr = 'Cookie: '.$cookie_str;
			$this->debug("HTTP header: $hdr");
			$this->outgoing_payload .= "$hdr\r\n";
		}

		// header/body separator
		$this->outgoing_payload .= "\r\n";

		// add data
		$this->outgoing_payload .= $data;
	}

	/**
	* sends the SOAP request via HTTP[S]
	*
	* @param    string $data message data
	* @param	array $cookies cookies to send
	* @return	boolean	true if OK, false if problem
	* @access   private
	*/
	function sendRequest($data, $cookies = NULL) {
		// build cookie string
		$cookie_str = $this->getCookiesForRequest($cookies, (($this->scheme == 'ssl') || ($this->scheme == 'https')));

		// build payload
		$this->buildPayload($data, $cookie_str);

	  if ($this->io_method() == 'socket') {
		// send payload
		if(!fputs($this->fp, $this->outgoing_payload, strlen($this->outgoing_payload))) {
			$this->setError('couldn\'t write message data to socket');
			$this->debug('couldn\'t write message data to socket');
			return false;
		}
		$this->debug('wrote data to socket, length = ' . strlen($this->outgoing_payload));
		return true;
	  } else if ($this->io_method() == 'curl') {
		// set payload
		// cURL does say this should only be the verb, and in fact it
		// turns out that the URI and HTTP version are appended to this, which
		// some servers refuse to work with (so we no longer use this method!)
		//$this->setCurlOption(CURLOPT_CUSTOMREQUEST, $this->outgoing_payload);
		$curl_headers = array();
		foreach($this->outgoing_headers as $k => $v){
			if ($k == 'Connection' || $k == 'Content-Length' || $k == 'Host' || $k == 'Authorization' || $k == 'Proxy-Authorization') {
				$this->debug("Skip cURL header $k: $v");
			} else {
				$curl_headers[] = "$k: $v";
			}
		}
		if ($cookie_str != '') {
			$curl_headers[] = 'Cookie: ' . $cookie_str;
		}
		$this->setCurlOption(CURLOPT_HTTPHEADER, $curl_headers);
		$this->debug('set cURL HTTP headers');
		if ($this->request_method == "POST") {
	  		$this->setCurlOption(CURLOPT_POST, 1);
	  		$this->setCurlOption(CURLOPT_POSTFIELDS, $data);
			$this->debug('set cURL POST data');
	  	} else {
	  	}
		// insert custom user-set cURL options
		foreach ($this->ch_options as $key => $val) {
			$this->setCurlOption($key, $val);
		}

		$this->debug('set cURL payload');
		return true;
	  }
	}

	/**
	* gets the SOAP response via HTTP[S]
	*
	* @return	string the response (also sets member variables like incoming_payload)
	* @access   private
	*/
	function getResponse(){
		$this->incoming_payload = '';

	  if ($this->io_method() == 'socket') {
	    // loop until headers have been retrieved
	    $data = '';
	    while (!isset($lb)){

			// We might EOF during header read.
			if(feof($this->fp)) {
				$this->incoming_payload = $data;
				$this->debug('found no headers before EOF after length ' . strlen($data));
				$this->debug("received before EOF:\n" . $data);
				$this->setError('server failed to send headers');
				return false;
			}

			$tmp = fgets($this->fp, 256);
			$tmplen = strlen($tmp);
			$this->debug("read line of $tmplen bytes: " . trim($tmp));

			if ($tmplen == 0) {
				$this->incoming_payload = $data;
				$this->debug('socket read of headers timed out after length ' . strlen($data));
				$this->debug("read before timeout: " . $data);
				$this->setError('socket read of headers timed out');
				return false;
			}

			$data .= $tmp;
			$pos = strpos($data,"\r\n\r\n");
			if($pos > 1){
				$lb = "\r\n";
			} else {
				$pos = strpos($data,"\n\n");
				if($pos > 1){
					$lb = "\n";
				}
			}
			// remove 100 headers
			if (isset($lb) && preg_match('/^HTTP\/1.1 100/',$data)) {
				unset($lb);
				$data = '';
			}//
		}
		// store header data
		$this->incoming_payload .= $data;
		$this->debug('found end of headers after length ' . strlen($data));
		// process headers
		$header_data = trim(substr($data,0,$pos));
		$header_array = explode($lb,$header_data);
		$this->incoming_headers = array();
		$this->incoming_cookies = array();
		foreach($header_array as $header_line){
			$arr = explode(':',$header_line, 2);
			if(count($arr) > 1){
				$header_name = strtolower(trim($arr[0]));
				$this->incoming_headers[$header_name] = trim($arr[1]);
				if ($header_name == 'set-cookie') {
					// TODO: allow multiple cookies from parseCookie
					$cookie = $this->parseCookie(trim($arr[1]));
					if ($cookie) {
						$this->incoming_cookies[] = $cookie;
						$this->debug('found cookie: ' . $cookie['name'] . ' = ' . $cookie['value']);
					} else {
						$this->debug('did not find cookie in ' . trim($arr[1]));
					}
    			}
			} else if (isset($header_name)) {
				// append continuation line to previous header
				$this->incoming_headers[$header_name] .= $lb . ' ' . $header_line;
			}
		}

		// loop until msg has been received
		if (isset($this->incoming_headers['transfer-encoding']) && strtolower($this->incoming_headers['transfer-encoding']) == 'chunked') {
			$content_length =  2147483647;	// ignore any content-length header
			$chunked = true;
			$this->debug("want to read chunked content");
		} elseif (isset($this->incoming_headers['content-length'])) {
			$content_length = $this->incoming_headers['content-length'];
			$chunked = false;
			$this->debug("want to read content of length $content_length");
		} else {
			$content_length =  2147483647;
			$chunked = false;
			$this->debug("want to read content to EOF");
		}
		$data = '';
		do {
			if ($chunked) {
				$tmp = fgets($this->fp, 256);
				$tmplen = strlen($tmp);
				$this->debug("read chunk line of $tmplen bytes");
				if ($tmplen == 0) {
					$this->incoming_payload = $data;
					$this->debug('socket read of chunk length timed out after length ' . strlen($data));
					$this->debug("read before timeout:\n" . $data);
					$this->setError('socket read of chunk length timed out');
					return false;
				}
				$content_length = hexdec(trim($tmp));
				$this->debug("chunk length $content_length");
			}
			$strlen = 0;
		    while (($strlen < $content_length) && (!feof($this->fp))) {
		    	$readlen = min(8192, $content_length - $strlen);
				$tmp = fread($this->fp, $readlen);
				$tmplen = strlen($tmp);
				$this->debug("read buffer of $tmplen bytes");
				if (($tmplen == 0) && (!feof($this->fp))) {
					$this->incoming_payload = $data;
					$this->debug('socket read of body timed out after length ' . strlen($data));
					$this->debug("read before timeout:\n" . $data);
					$this->setError('socket read of body timed out');
					return false;
				}
				$strlen += $tmplen;
				$data .= $tmp;
			}
			if ($chunked && ($content_length > 0)) {
				$tmp = fgets($this->fp, 256);
				$tmplen = strlen($tmp);
				$this->debug("read chunk terminator of $tmplen bytes");
				if ($tmplen == 0) {
					$this->incoming_payload = $data;
					$this->debug('socket read of chunk terminator timed out after length ' . strlen($data));
					$this->debug("read before timeout:\n" . $data);
					$this->setError('socket read of chunk terminator timed out');
					return false;
				}
			}
		} while ($chunked && ($content_length > 0) && (!feof($this->fp)));
		if (feof($this->fp)) {
			$this->debug('read to EOF');
		}
		$this->debug('read body of length ' . strlen($data));
		$this->incoming_payload .= $data;
		$this->debug('received a total of '.strlen($this->incoming_payload).' bytes of data from server');

		// close filepointer
		if(
			(isset($this->incoming_headers['connection']) && strtolower($this->incoming_headers['connection']) == 'close') ||
			(! $this->persistentConnection) || feof($this->fp)){
			fclose($this->fp);
			$this->fp = false;
			$this->debug('closed socket');
		}

		// connection was closed unexpectedly
		if($this->incoming_payload == ''){
			$this->setError('no response from server');
			return false;
		}

		// decode transfer-encoding
//		if(isset($this->incoming_headers['transfer-encoding']) && strtolower($this->incoming_headers['transfer-encoding']) == 'chunked'){
//			if(!$data = $this->decodeChunked($data, $lb)){
//				$this->setError('Decoding of chunked data failed');
//				return false;
//			}
			//print "<pre>\nde-chunked:\n---------------\n$data\n\n---------------\n</pre>";
			// set decoded payload
//			$this->incoming_payload = $header_data.$lb.$lb.$data;
//		}

	  } else if ($this->io_method() == 'curl') {
		// send and receive
		$this->debug('send and receive with cURL');
		$this->incoming_payload = curl_exec($this->ch);
		$data = $this->incoming_payload;

        $cErr = curl_error($this->ch);
		if ($cErr != '') {
        	$err = 'cURL ERROR: '.curl_errno($this->ch).': '.$cErr.'<br>';
        	// TODO: there is a PHP bug that can cause this to SEGV for CURLINFO_CONTENT_TYPE
			foreach(curl_getinfo($this->ch) as $k => $v){
				$err .= "$k: $v<br>";
			}
			$this->debug($err);
			$this->setError($err);
			curl_close($this->ch);
	    	return false;
		} else {
			//echo '<pre>';
			//var_dump(curl_getinfo($this->ch));
			//echo '</pre>';
		}
		// close curl
		$this->debug('No cURL error, closing cURL');
		curl_close($this->ch);

		// try removing skippable headers
		$savedata = $data;
		while ($this->isSkippableCurlHeader($data)) {
			$this->debug("Found HTTP header to skip");
			if ($pos = strpos($data,"\r\n\r\n")) {
				$data = ltrim(substr($data,$pos));
			} elseif($pos = strpos($data,"\n\n") ) {
				$data = ltrim(substr($data,$pos));
			}
		}

		if ($data == '') {
			// have nothing left; just remove 100 header(s)
			$data = $savedata;
			while (preg_match('/^HTTP\/1.1 100/',$data)) {
				if ($pos = strpos($data,"\r\n\r\n")) {
					$data = ltrim(substr($data,$pos));
				} elseif($pos = strpos($data,"\n\n") ) {
					$data = ltrim(substr($data,$pos));
				}
			}
		}

		// separate content from HTTP headers
		if ($pos = strpos($data,"\r\n\r\n")) {
			$lb = "\r\n";
		} elseif( $pos = strpos($data,"\n\n")) {
			$lb = "\n";
		} else {
			$this->debug('no proper separation of headers and document');
			$this->setError('no proper separation of headers and document');
			return false;
		}
		$header_data = trim(substr($data,0,$pos));
		$header_array = explode($lb,$header_data);
		$data = ltrim(substr($data,$pos));
		$this->debug('found proper separation of headers and document');
		$this->debug('cleaned data, stringlen: '.strlen($data));
		// clean headers
		foreach ($header_array as $header_line) {
			$arr = explode(':',$header_line,2);
			if(count($arr) > 1){
				$header_name = strtolower(trim($arr[0]));
				$this->incoming_headers[$header_name] = trim($arr[1]);
				if ($header_name == 'set-cookie') {
					// TODO: allow multiple cookies from parseCookie
					$cookie = $this->parseCookie(trim($arr[1]));
					if ($cookie) {
						$this->incoming_cookies[] = $cookie;
						$this->debug('found cookie: ' . $cookie['name'] . ' = ' . $cookie['value']);
					} else {
						$this->debug('did not find cookie in ' . trim($arr[1]));
					}
    			}
			} else if (isset($header_name)) {
				// append continuation line to previous header
				$this->incoming_headers[$header_name] .= $lb . ' ' . $header_line;
			}
		}
	  }

		$this->response_status_line = $header_array[0];
		$arr = explode(' ', $this->response_status_line, 3);
		$http_version = $arr[0];
		$http_status = intval($arr[1]);
		$http_reason = count($arr) > 2 ? $arr[2] : '';

 		// see if we need to resend the request with http digest authentication
 		if (isset($this->incoming_headers['location']) && ($http_status == 301 || $http_status == 302)) {
 			$this->debug("Got $http_status $http_reason with Location: " . $this->incoming_headers['location']);
 			$this->setURL($this->incoming_headers['location']);
			$this->tryagain = true;
			return false;
		}

 		// see if we need to resend the request with http digest authentication
 		if (isset($this->incoming_headers['www-authenticate']) && $http_status == 401) {
 			$this->debug("Got 401 $http_reason with WWW-Authenticate: " . $this->incoming_headers['www-authenticate']);
 			if (strstr($this->incoming_headers['www-authenticate'], "Digest ")) {
 				$this->debug('Server wants digest authentication');
 				// remove "Digest " from our elements
 				$digestString = str_replace('Digest ', '', $this->incoming_headers['www-authenticate']);

 				// parse elements into array
 				$digestElements = explode(',', $digestString);
 				foreach ($digestElements as $val) {
 					$tempElement = explode('=', trim($val), 2);
 					$digestRequest[$tempElement[0]] = str_replace("\"", '', $tempElement[1]);
 				}

				// should have (at least) qop, realm, nonce
 				if (isset($digestRequest['nonce'])) {
 					$this->setCredentials($this->username, $this->password, 'digest', $digestRequest);
 					$this->tryagain = true;
 					return false;
 				}
 			}
			$this->debug('HTTP authentication failed');
			$this->setError('HTTP authentication failed');
			return false;
 		}

		if (
			($http_status >= 300 && $http_status <= 307) ||
			($http_status >= 400 && $http_status <= 417) ||
			($http_status >= 501 && $http_status <= 505)
		   ) {
			$this->setError("Unsupported HTTP response status $http_status $http_reason (soapclient->response has contents of the response)");
			return false;
		}

		// decode content-encoding
		if(isset($this->incoming_headers['content-encoding']) && $this->incoming_headers['content-encoding'] != ''){
			if(strtolower($this->incoming_headers['content-encoding']) == 'deflate' || strtolower($this->incoming_headers['content-encoding']) == 'gzip'){
    			// if decoding works, use it. else assume data wasn't gzencoded
    			if(function_exists('gzinflate')){
					//$timer->setMarker('starting decoding of gzip/deflated content');
					// IIS 5 requires gzinflate instead of gzuncompress (similar to IE 5 and gzdeflate v. gzcompress)
					// this means there are no Zlib headers, although there should be
					$this->debug('The gzinflate function exists');
					$datalen = strlen($data);
					if ($this->incoming_headers['content-encoding'] == 'deflate') {
						if ($degzdata = @gzinflate($data)) {
	    					$data = $degzdata;
	    					$this->debug('The payload has been inflated to ' . strlen($data) . ' bytes');
	    					if (strlen($data) < $datalen) {
	    						// test for the case that the payload has been compressed twice
		    					$this->debug('The inflated payload is smaller than the gzipped one; try again');
								if ($degzdata = @gzinflate($data)) {
			    					$data = $degzdata;
			    					$this->debug('The payload has been inflated again to ' . strlen($data) . ' bytes');
								}
	    					}
	    				} else {
	    					$this->debug('Error using gzinflate to inflate the payload');
	    					$this->setError('Error using gzinflate to inflate the payload');
	    				}
					} elseif ($this->incoming_headers['content-encoding'] == 'gzip') {
						if ($degzdata = @gzinflate(substr($data, 10))) {	// do our best
							$data = $degzdata;
	    					$this->debug('The payload has been un-gzipped to ' . strlen($data) . ' bytes');
	    					if (strlen($data) < $datalen) {
	    						// test for the case that the payload has been compressed twice
		    					$this->debug('The un-gzipped payload is smaller than the gzipped one; try again');
								if ($degzdata = @gzinflate(substr($data, 10))) {
			    					$data = $degzdata;
			    					$this->debug('The payload has been un-gzipped again to ' . strlen($data) . ' bytes');
								}
	    					}
	    				} else {
	    					$this->debug('Error using gzinflate to un-gzip the payload');
							$this->setError('Error using gzinflate to un-gzip the payload');
	    				}
					}
					//$timer->setMarker('finished decoding of gzip/deflated content');
					//print "<xmp>\nde-inflated:\n---------------\n$data\n-------------\n</xmp>";
					// set decoded payload
					$this->incoming_payload = $header_data.$lb.$lb.$data;
    			} else {
					$this->debug('The server sent compressed data. Your php install must have the Zlib extension compiled in to support this.');
					$this->setError('The server sent compressed data. Your php install must have the Zlib extension compiled in to support this.');
				}
			} else {
				$this->debug('Unsupported Content-Encoding ' . $this->incoming_headers['content-encoding']);
				$this->setError('Unsupported Content-Encoding ' . $this->incoming_headers['content-encoding']);
			}
		} else {
			$this->debug('No Content-Encoding header');
		}

		if(strlen($data) == 0){
			$this->debug('no data after headers!');
			$this->setError('no data present after HTTP headers');
			return false;
		}

		return $data;
	}

	/**
	 * sets the content-type for the SOAP message to be sent
	 *
	 * @param	string $type the content type, MIME style
	 * @param	mixed $charset character set used for encoding (or false)
	 * @access	public
	 */
	function setContentType($type, $charset = false) {
		$this->setHeader('Content-Type', $type . ($charset ? '; charset=' . $charset : ''));
	}

	/**
	 * specifies that an HTTP persistent connection should be used
	 *
	 * @return	boolean whether the request was honored by this method.
	 * @access	public
	 */
	function usePersistentConnection(){
		if (isset($this->outgoing_headers['Accept-Encoding'])) {
			return false;
		}
		$this->protocol_version = '1.1';
		$this->persistentConnection = true;
		$this->setHeader('Connection', 'Keep-Alive');
		return true;
	}

	/**
	 * parse an incoming Cookie into it's parts
	 *
	 * @param	string $cookie_str content of cookie
	 * @return	array with data of that cookie
	 * @access	private
	 */
	/*
	 * TODO: allow a Set-Cookie string to be parsed into multiple cookies
	 */
	function parseCookie($cookie_str) {
		$cookie_str = str_replace('; ', ';', $cookie_str) . ';';
		$data = preg_split('/;/', $cookie_str);
		$value_str = $data[0];

		$cookie_param = 'domain=';
		$start = strpos($cookie_str, $cookie_param);
		if ($start > 0) {
			$domain = substr($cookie_str, $start + strlen($cookie_param));
			$domain = substr($domain, 0, strpos($domain, ';'));
		} else {
			$domain = '';
		}

		$cookie_param = 'expires=';
		$start = strpos($cookie_str, $cookie_param);
		if ($start > 0) {
			$expires = substr($cookie_str, $start + strlen($cookie_param));
			$expires = substr($expires, 0, strpos($expires, ';'));
		} else {
			$expires = '';
		}

		$cookie_param = 'path=';
		$start = strpos($cookie_str, $cookie_param);
		if ( $start > 0 ) {
			$path = substr($cookie_str, $start + strlen($cookie_param));
			$path = substr($path, 0, strpos($path, ';'));
		} else {
			$path = '/';
		}

		$cookie_param = ';secure;';
		if (strpos($cookie_str, $cookie_param) !== FALSE) {
			$secure = true;
		} else {
			$secure = false;
		}

		$sep_pos = strpos($value_str, '=');

		if ($sep_pos) {
			$name = substr($value_str, 0, $sep_pos);
			$value = substr($value_str, $sep_pos + 1);
			$cookie= array(	'name' => $name,
			                'value' => $value,
							'domain' => $domain,
							'path' => $path,
							'expires' => $expires,
							'secure' => $secure
							);
			return $cookie;
		}
		return false;
	}

	/**
	 * sort out cookies for the current request
	 *
	 * @param	array $cookies array with all cookies
	 * @param	boolean $secure is the send-content secure or not?
	 * @return	string for Cookie-HTTP-Header
	 * @access	private
	 */
	function getCookiesForRequest($cookies, $secure=false) {
		$cookie_str = '';
		if ((! is_null($cookies)) && (is_array($cookies))) {
			foreach ($cookies as $cookie) {
				if (! is_array($cookie)) {
					continue;
				}
	    		$this->debug("check cookie for validity: ".$cookie['name'].'='.$cookie['value']);
				if ((isset($cookie['expires'])) && (! empty($cookie['expires']))) {
					if (strtotime($cookie['expires']) <= time()) {
						$this->debug('cookie has expired');
						continue;
					}
				}
				if ((isset($cookie['domain'])) && (! empty($cookie['domain']))) {
					$domain = preg_quote($cookie['domain']);
					if (! preg_match("'.*$domain$'i", $this->host)) {
						$this->debug('cookie has different domain');
						continue;
					}
				}
				if ((isset($cookie['path'])) && (! empty($cookie['path']))) {
					$path = preg_quote($cookie['path']);
					if (! preg_match("'^$path.*'i", $this->path)) {
						$this->debug('cookie is for a different path');
						continue;
					}
				}
				if ((! $secure) && (isset($cookie['secure'])) && ($cookie['secure'])) {
					$this->debug('cookie is secure, transport is not');
					continue;
				}
				$cookie_str .= $cookie['name'] . '=' . $cookie['value'] . '; ';
	    		$this->debug('add cookie to Cookie-String: ' . $cookie['name'] . '=' . $cookie['value']);
			}
		}
		return $cookie_str;
  }
}

?><?php


/**
*
* p2dWebservices_parser class parses SOAP XML messages into native PHP values
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @author   Scott Nichol <snichol@users.sourceforge.net>
* @access   public
*/
class p2dWebservices_parser extends p2dWebservices_base {

	var $xml = '';
	var $xml_encoding = '';
	var $method = '';
	var $root_struct = '';
	var $root_struct_name = '';
	var $root_struct_namespace = '';
	var $root_header = '';
    var $document = '';			// incoming SOAP body (text)
	// determines where in the message we are (envelope,header,body,method)
	var $status = '';
	var $position = 0;
	var $depth = 0;
	var $default_namespace = '';
	var $namespaces = array();
	var $message = array();
    var $parent = '';
	var $fault = false;
	var $fault_code = '';
	var $fault_str = '';
	var $fault_detail = '';
	var $depth_array = array();
	var $debug_flag = true;
	var $soapresponse = NULL;	// parsed SOAP Body
	var $soapheader = NULL;		// parsed SOAP Header
	var $responseHeaders = '';	// incoming SOAP headers (text)
	var $body_position = 0;
	// for multiref parsing:
	// array of id => pos
	var $ids = array();
	// array of id => hrefs => pos
	var $multirefs = array();
	// toggle for auto-decoding element content
	var $decode_utf8 = true;

	/**
	* constructor that actually does the parsing
	*
	* @param    string $xml SOAP message
	* @param    string $encoding character encoding scheme of message
	* @param    string $method method for which XML is parsed (unused?)
	* @param    string $decode_utf8 whether to decode UTF-8 to ISO-8859-1
	* @access   public
	*/
	function p2dWebservices_parser($xml,$encoding='UTF-8',$method='',$decode_utf8=true){
		parent::p2dWebservices_base();
		$this->xml = $xml;
		$this->xml_encoding = $encoding;
		$this->method = $method;
		$this->decode_utf8 = $decode_utf8;

		// Check whether content has been read.
		if(!empty($xml)){
			// Check XML encoding
			$pos_xml = strpos($xml, '<?xml');
			if ($pos_xml !== FALSE) {
				$xml_decl = substr($xml, $pos_xml, strpos($xml, '?>', $pos_xml + 2) - $pos_xml + 1);
				if (preg_match("/encoding=[\"']([^\"']*)[\"']/", $xml_decl, $res)) {
					$xml_encoding = $res[1];
					if (strtoupper($xml_encoding) != $encoding) {
						$err = "Charset from HTTP Content-Type '" . $encoding . "' does not match encoding from XML declaration '" . $xml_encoding . "'";
						$this->debug($err);
						if ($encoding != 'ISO-8859-1' || strtoupper($xml_encoding) != 'UTF-8') {
							$this->setError($err);
							return;
						}
						// when HTTP says ISO-8859-1 (the default) and XML says UTF-8 (the typical), assume the other endpoint is just sloppy and proceed
					} else {
						$this->debug('Charset from HTTP Content-Type matches encoding from XML declaration');
					}
				} else {
					$this->debug('No encoding specified in XML declaration');
				}
			} else {
				$this->debug('No XML declaration');
			}
			$this->debug('Entering p2dWebservices_parser(), length='.strlen($xml).', encoding='.$encoding);
			// Create an XML parser - why not xml_parser_create_ns?
			$this->parser = xml_parser_create($this->xml_encoding);
			// Set the options for parsing the XML data.
			//xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
			xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
			xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, $this->xml_encoding);
			// Set the object for the parser.
			xml_set_object($this->parser, $this);
			// Set the element handlers for the parser.
			xml_set_element_handler($this->parser, 'start_element','end_element');
			xml_set_character_data_handler($this->parser,'character_data');

			// Parse the XML file.
			if(!xml_parse($this->parser,$xml,true)){
			    // Display an error message.
			    $err = sprintf('XML error parsing SOAP payload on line %d: %s',
			    xml_get_current_line_number($this->parser),
			    xml_error_string(xml_get_error_code($this->parser)));
				$this->debug($err);
				$this->debug("XML payload:\n" . $xml);
				$this->setError($err);
			} else {
				$this->debug('in p2dWebservices_parser ctor, message:');
				$this->appendDebug($this->varDump($this->message));
				$this->debug('parsed successfully, found root struct: '.$this->root_struct.' of name '.$this->root_struct_name);
				// get final value
				$this->soapresponse = $this->message[$this->root_struct]['result'];
				// get header value
				if($this->root_header != '' && isset($this->message[$this->root_header]['result'])){
					$this->soapheader = $this->message[$this->root_header]['result'];
				}
				// resolve hrefs/ids
				if(sizeof($this->multirefs) > 0){
					foreach($this->multirefs as $id => $hrefs){
						$this->debug('resolving multirefs for id: '.$id);
						$idVal = $this->buildVal($this->ids[$id]);
						if (is_array($idVal) && isset($idVal['!id'])) {
							unset($idVal['!id']);
						}
						foreach($hrefs as $refPos => $ref){
							$this->debug('resolving href at pos '.$refPos);
							$this->multirefs[$id][$refPos] = $idVal;
						}
					}
				}
			}
			xml_parser_free($this->parser);
		} else {
			$this->debug('xml was empty, didn\'t parse!');
			$this->setError('xml was empty, didn\'t parse!');
		}
	}

	/**
	* start-element handler
	*
	* @param    resource $parser XML parser object
	* @param    string $name element name
	* @param    array $attrs associative array of attributes
	* @access   private
	*/
	function start_element($parser, $name, $attrs) {
		// position in a total number of elements, starting from 0
		// update class level pos
		$pos = $this->position++;
		// and set mine
		$this->message[$pos] = array('pos' => $pos,'children'=>'','cdata'=>'');
		// depth = how many levels removed from root?
		// set mine as current global depth and increment global depth value
		$this->message[$pos]['depth'] = $this->depth++;

		// else add self as child to whoever the current parent is
		if($pos != 0){
			$this->message[$this->parent]['children'] .= '|'.$pos;
		}
		// set my parent
		$this->message[$pos]['parent'] = $this->parent;
		// set self as current parent
		$this->parent = $pos;
		// set self as current value for this depth
		$this->depth_array[$this->depth] = $pos;
		// get element prefix
		if(strpos($name,':')){
			// get ns prefix
			$prefix = substr($name,0,strpos($name,':'));
			// get unqualified name
			$name = substr(strstr($name,':'),1);
		}
		// set status
		if ($name == 'Envelope' && $this->status == '') {
			$this->status = 'envelope';
		} elseif ($name == 'Header' && $this->status == 'envelope') {
			$this->root_header = $pos;
			$this->status = 'header';
		} elseif ($name == 'Body' && $this->status == 'envelope'){
			$this->status = 'body';
			$this->body_position = $pos;
		// set method
		} elseif($this->status == 'body' && $pos == ($this->body_position+1)) {
			$this->status = 'method';
			$this->root_struct_name = $name;
			$this->root_struct = $pos;
			$this->message[$pos]['type'] = 'struct';
			$this->debug("found root struct $this->root_struct_name, pos $this->root_struct");
		}
		// set my status
		$this->message[$pos]['status'] = $this->status;
		// set name
		$this->message[$pos]['name'] = htmlspecialchars($name);
		// set attrs
		$this->message[$pos]['attrs'] = $attrs;

		// loop through atts, logging ns and type declarations
        $attstr = '';
		foreach($attrs as $key => $value){
        	$key_prefix = $this->getPrefix($key);
			$key_localpart = $this->getLocalPart($key);
			// if ns declarations, add to class level array of valid namespaces
            if($key_prefix == 'xmlns'){
				if(preg_match('/^http:\/\/www.w3.org\/[0-9]{4}\/XMLSchema$/',$value)){
					$this->XMLSchemaVersion = $value;
					$this->namespaces['xsd'] = $this->XMLSchemaVersion;
					$this->namespaces['xsi'] = $this->XMLSchemaVersion.'-instance';
				}
                $this->namespaces[$key_localpart] = $value;
				// set method namespace
				if($name == $this->root_struct_name){
					$this->methodNamespace = $value;
				}
			// if it's a type declaration, set type
        } elseif($key_localpart == 'type'){
        		if (isset($this->message[$pos]['type']) && $this->message[$pos]['type'] == 'array') {
        			// do nothing: already processed arrayType
        		} else {
	            	$value_prefix = $this->getPrefix($value);
	                $value_localpart = $this->getLocalPart($value);
					$this->message[$pos]['type'] = $value_localpart;
					$this->message[$pos]['typePrefix'] = $value_prefix;
	                if(isset($this->namespaces[$value_prefix])){
	                	$this->message[$pos]['type_namespace'] = $this->namespaces[$value_prefix];
	                } else if(isset($attrs['xmlns:'.$value_prefix])) {
						$this->message[$pos]['type_namespace'] = $attrs['xmlns:'.$value_prefix];
	                }
					// should do something here with the namespace of specified type?
				}
			} elseif($key_localpart == 'arrayType'){
				$this->message[$pos]['type'] = 'array';
				/* do arrayType ereg here
				[1]    arrayTypeValue    ::=    atype asize
				[2]    atype    ::=    QName rank*
				[3]    rank    ::=    '[' (',')* ']'
				[4]    asize    ::=    '[' length~ ']'
				[5]    length    ::=    nextDimension* Digit+
				[6]    nextDimension    ::=    Digit+ ','
				*/
				$expr = '/([A-Za-z0-9_]+):([A-Za-z]+[A-Za-z0-9_]+)\[([0-9]+),?([0-9]*)\]/';
				if(preg_match($expr,$value,$regs)){
					$this->message[$pos]['typePrefix'] = $regs[1];
					$this->message[$pos]['arrayTypePrefix'] = $regs[1];
	                if (isset($this->namespaces[$regs[1]])) {
	                	$this->message[$pos]['arrayTypeNamespace'] = $this->namespaces[$regs[1]];
	                } else if (isset($attrs['xmlns:'.$regs[1]])) {
						$this->message[$pos]['arrayTypeNamespace'] = $attrs['xmlns:'.$regs[1]];
	                }
					$this->message[$pos]['arrayType'] = $regs[2];
					$this->message[$pos]['arraySize'] = $regs[3];
					$this->message[$pos]['arrayCols'] = $regs[4];
				}
			// specifies nil value (or not)
			} elseif ($key_localpart == 'nil'){
				$this->message[$pos]['nil'] = ($value == 'true' || $value == '1');
			// some other attribute
			} elseif ($key != 'href' && $key != 'xmlns' && $key_localpart != 'encodingStyle' && $key_localpart != 'root') {
				$this->message[$pos]['xattrs']['!' . $key] = $value;
			}

			if ($key == 'xmlns') {
				$this->default_namespace = $value;
			}
			// log id
			if($key == 'id'){
				$this->ids[$value] = $pos;
			}
			// root
			if($key_localpart == 'root' && $value == 1){
				$this->status = 'method';
				$this->root_struct_name = $name;
				$this->root_struct = $pos;
				$this->debug("found root struct $this->root_struct_name, pos $pos");
			}
            // for doclit
            $attstr .= " $key=\"$value\"";
		}
        // get namespace - must be done after namespace atts are processed
		if(isset($prefix)){
			$this->message[$pos]['namespace'] = $this->namespaces[$prefix];
			$this->default_namespace = $this->namespaces[$prefix];
		} else {
			$this->message[$pos]['namespace'] = $this->default_namespace;
		}
        if($this->status == 'header'){
        	if ($this->root_header != $pos) {
	        	$this->responseHeaders .= "<" . (isset($prefix) ? $prefix . ':' : '') . "$name$attstr>";
	        }
        } elseif($this->root_struct_name != ''){
        	$this->document .= "<" . (isset($prefix) ? $prefix . ':' : '') . "$name$attstr>";
        }
	}

	/**
	* end-element handler
	*
	* @param    resource $parser XML parser object
	* @param    string $name element name
	* @access   private
	*/
	function end_element($parser, $name) {
		// position of current element is equal to the last value left in depth_array for my depth
		$pos = $this->depth_array[$this->depth--];

        // get element prefix
		if(strpos($name,':')){
			// get ns prefix
			$prefix = substr($name,0,strpos($name,':'));
			// get unqualified name
			$name = substr(strstr($name,':'),1);
		}

		// build to native type
		if(isset($this->body_position) && $pos > $this->body_position){
			// deal w/ multirefs
			if(isset($this->message[$pos]['attrs']['href'])){
				// get id
				$id = substr($this->message[$pos]['attrs']['href'],1);
				// add placeholder to href array
				$this->multirefs[$id][$pos] = 'placeholder';
				// add set a reference to it as the result value
				$this->message[$pos]['result'] =& $this->multirefs[$id][$pos];
            // build complexType values
			} elseif($this->message[$pos]['children'] != ''){
				// if result has already been generated (struct/array)
				if(!isset($this->message[$pos]['result'])){
					$this->message[$pos]['result'] = $this->buildVal($pos);
				}
			// build complexType values of attributes and possibly simpleContent
			} elseif (isset($this->message[$pos]['xattrs'])) {
				if (isset($this->message[$pos]['nil']) && $this->message[$pos]['nil']) {
					$this->message[$pos]['xattrs']['!'] = null;
				} elseif (isset($this->message[$pos]['cdata']) && trim($this->message[$pos]['cdata']) != '') {
	            	if (isset($this->message[$pos]['type'])) {
						$this->message[$pos]['xattrs']['!'] = $this->decodeSimple($this->message[$pos]['cdata'], $this->message[$pos]['type'], isset($this->message[$pos]['type_namespace']) ? $this->message[$pos]['type_namespace'] : '');
					} else {
						$parent = $this->message[$pos]['parent'];
						if (isset($this->message[$parent]['type']) && ($this->message[$parent]['type'] == 'array') && isset($this->message[$parent]['arrayType'])) {
							$this->message[$pos]['xattrs']['!'] = $this->decodeSimple($this->message[$pos]['cdata'], $this->message[$parent]['arrayType'], isset($this->message[$parent]['arrayTypeNamespace']) ? $this->message[$parent]['arrayTypeNamespace'] : '');
						} else {
							$this->message[$pos]['xattrs']['!'] = $this->message[$pos]['cdata'];
						}
					}
				}
				$this->message[$pos]['result'] = $this->message[$pos]['xattrs'];
			// set value of simpleType (or nil complexType)
			} else {
            	//$this->debug('adding data for scalar value '.$this->message[$pos]['name'].' of value '.$this->message[$pos]['cdata']);
				if (isset($this->message[$pos]['nil']) && $this->message[$pos]['nil']) {
					$this->message[$pos]['xattrs']['!'] = null;
				} elseif (isset($this->message[$pos]['type'])) {
					$this->message[$pos]['result'] = $this->decodeSimple($this->message[$pos]['cdata'], $this->message[$pos]['type'], isset($this->message[$pos]['type_namespace']) ? $this->message[$pos]['type_namespace'] : '');
				} else {
					$parent = $this->message[$pos]['parent'];
					if (isset($this->message[$parent]['type']) && ($this->message[$parent]['type'] == 'array') && isset($this->message[$parent]['arrayType'])) {
						$this->message[$pos]['result'] = $this->decodeSimple($this->message[$pos]['cdata'], $this->message[$parent]['arrayType'], isset($this->message[$parent]['arrayTypeNamespace']) ? $this->message[$parent]['arrayTypeNamespace'] : '');
					} else {
						$this->message[$pos]['result'] = $this->message[$pos]['cdata'];
					}
				}

				/* add value to parent's result, if parent is struct/array
				$parent = $this->message[$pos]['parent'];
				if($this->message[$parent]['type'] != 'map'){
					if(strtolower($this->message[$parent]['type']) == 'array'){
						$this->message[$parent]['result'][] = $this->message[$pos]['result'];
					} else {
						$this->message[$parent]['result'][$this->message[$pos]['name']] = $this->message[$pos]['result'];
					}
				}
				*/
			}
		}

        // for doclit
        if($this->status == 'header'){
        	if ($this->root_header != $pos) {
	        	$this->responseHeaders .= "</" . (isset($prefix) ? $prefix . ':' : '') . "$name>";
	        }
        } elseif($pos >= $this->root_struct){
        	$this->document .= "</" . (isset($prefix) ? $prefix . ':' : '') . "$name>";
        }
		// switch status
		if ($pos == $this->root_struct){
			$this->status = 'body';
			$this->root_struct_namespace = $this->message[$pos]['namespace'];
		} elseif ($pos == $this->root_header) {
			$this->status = 'envelope';
		} elseif ($name == 'Body' && $this->status == 'body') {
			$this->status = 'envelope';
		} elseif ($name == 'Header' && $this->status == 'header') { // will never happen
			$this->status = 'envelope';
		} elseif ($name == 'Envelope' && $this->status == 'envelope') {
			$this->status = '';
		}
		// set parent back to my parent
		$this->parent = $this->message[$pos]['parent'];
	}

	/**
	* element content handler
	*
	* @param    resource $parser XML parser object
	* @param    string $data element content
	* @access   private
	*/
	function character_data($parser, $data){
		$pos = $this->depth_array[$this->depth];
		if ($this->xml_encoding=='UTF-8'){
			// TODO: add an option to disable this for folks who want
			// raw UTF-8 that, e.g., might not map to iso-8859-1
			// TODO: this can also be handled with xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, "ISO-8859-1");
			if($this->decode_utf8){
				$data = utf8_decode($data);
			}
		}
        $this->message[$pos]['cdata'] .= $data;
        // for doclit
        if($this->status == 'header'){
        	$this->responseHeaders .= $data;
        } else {
        	$this->document .= $data;
        }
	}

	/**
	* get the parsed message (SOAP Body)
	*
	* @return	mixed
	* @access   public
	* @deprecated	use get_soapbody instead
	*/
	function get_response(){
		return $this->soapresponse;
	}

	/**
	* get the parsed SOAP Body (NULL if there was none)
	*
	* @return	mixed
	* @access   public
	*/
	function get_soapbody(){
		return $this->soapresponse;
	}

	/**
	* get the parsed SOAP Header (NULL if there was none)
	*
	* @return	mixed
	* @access   public
	*/
	function get_soapheader(){
		return $this->soapheader;
	}

	/**
	* get the unparsed SOAP Header
	*
	* @return	string XML or empty if no Header
	* @access   public
	*/
	function getHeaders(){
	    return $this->responseHeaders;
	}

	/**
	* decodes simple types into PHP variables
	*
	* @param    string $value value to decode
	* @param    string $type XML type to decode
	* @param    string $typens XML type namespace to decode
	* @return	mixed PHP value
	* @access   private
	*/
	function decodeSimple($value, $type, $typens) {
		// TODO: use the namespace!
		if ((!isset($type)) || $type == 'string' || $type == 'long' || $type == 'unsignedLong') {
			return (string) $value;
		}
		if ($type == 'int' || $type == 'integer' || $type == 'short' || $type == 'byte') {
			return (int) $value;
		}
		if ($type == 'float' || $type == 'double' || $type == 'decimal') {
			return (double) $value;
		}
		if ($type == 'boolean') {
			if (strtolower($value) == 'false' || strtolower($value) == 'f') {
				return false;
			}
			return (boolean) $value;
		}
		if ($type == 'base64' || $type == 'base64Binary') {
			$this->debug('Decode base64 value');
			return base64_decode($value);
		}
		// obscure numeric types
		if ($type == 'nonPositiveInteger' || $type == 'negativeInteger'
			|| $type == 'nonNegativeInteger' || $type == 'positiveInteger'
			|| $type == 'unsignedInt'
			|| $type == 'unsignedShort' || $type == 'unsignedByte') {
			return (int) $value;
		}
		// bogus: parser treats array with no elements as a simple type
		if ($type == 'array') {
			return array();
		}
		// everything else
		return (string) $value;
	}

	/**
	* builds response structures for compound values (arrays/structs)
	* and scalars
	*
	* @param    integer $pos position in node tree
	* @return	mixed	PHP value
	* @access   private
	*/
	function buildVal($pos){
		if(!isset($this->message[$pos]['type'])){
			$this->message[$pos]['type'] = '';
		}
		$this->debug('in buildVal() for '.$this->message[$pos]['name']."(pos $pos) of type ".$this->message[$pos]['type']);
		// if there are children...
		if($this->message[$pos]['children'] != ''){
			$this->debug('in buildVal, there are children');
			$children = explode('|',$this->message[$pos]['children']);
			array_shift($children); // knock off empty
			// md array
			if(isset($this->message[$pos]['arrayCols']) && $this->message[$pos]['arrayCols'] != ''){
            	$r=0; // rowcount
            	$c=0; // colcount
            	foreach($children as $child_pos){
					$this->debug("in buildVal, got an MD array element: $r, $c");
					$params[$r][] = $this->message[$child_pos]['result'];
				    $c++;
				    if($c == $this->message[$pos]['arrayCols']){
				    	$c = 0;
						$r++;
				    }
                }
            // array
			} elseif($this->message[$pos]['type'] == 'array' || $this->message[$pos]['type'] == 'Array'){
                $this->debug('in buildVal, adding array '.$this->message[$pos]['name']);
                foreach($children as $child_pos){
                	$params[] = &$this->message[$child_pos]['result'];
                }
            // apache Map type: java hashtable
            } elseif($this->message[$pos]['type'] == 'Map' && $this->message[$pos]['type_namespace'] == 'http://xml.apache.org/xml-soap'){
                $this->debug('in buildVal, Java Map '.$this->message[$pos]['name']);
                foreach($children as $child_pos){
                	$kv = explode("|",$this->message[$child_pos]['children']);
                   	$params[$this->message[$kv[1]]['result']] = &$this->message[$kv[2]]['result'];
                }
            // generic compound type
            //} elseif($this->message[$pos]['type'] == 'SOAPStruct' || $this->message[$pos]['type'] == 'struct') {
		    } else {
	    		// Apache Vector type: treat as an array
                $this->debug('in buildVal, adding Java Vector or generic compound type '.$this->message[$pos]['name']);
				if ($this->message[$pos]['type'] == 'Vector' && $this->message[$pos]['type_namespace'] == 'http://xml.apache.org/xml-soap') {
					$notstruct = 1;
				} else {
					$notstruct = 0;
	            }
            	//
            	foreach($children as $child_pos){
            		if($notstruct){
            			$params[] = &$this->message[$child_pos]['result'];
            		} else {
            			if (isset($params[$this->message[$child_pos]['name']])) {
            				// de-serialize repeated element name into an array
            				if ((!is_array($params[$this->message[$child_pos]['name']])) || (!isset($params[$this->message[$child_pos]['name']][0]))) {
            					$params[$this->message[$child_pos]['name']] = array($params[$this->message[$child_pos]['name']]);
            				}
            				$params[$this->message[$child_pos]['name']][] = &$this->message[$child_pos]['result'];
            			} else {
					    	$params[$this->message[$child_pos]['name']] = &$this->message[$child_pos]['result'];
					    }
                	}
                }
			}
			if (isset($this->message[$pos]['xattrs'])) {
                $this->debug('in buildVal, handling attributes');
				foreach ($this->message[$pos]['xattrs'] as $n => $v) {
					$params[$n] = $v;
				}
			}
			// handle simpleContent
			if (isset($this->message[$pos]['cdata']) && trim($this->message[$pos]['cdata']) != '') {
                $this->debug('in buildVal, handling simpleContent');
            	if (isset($this->message[$pos]['type'])) {
					$params['!'] = $this->decodeSimple($this->message[$pos]['cdata'], $this->message[$pos]['type'], isset($this->message[$pos]['type_namespace']) ? $this->message[$pos]['type_namespace'] : '');
				} else {
					$parent = $this->message[$pos]['parent'];
					if (isset($this->message[$parent]['type']) && ($this->message[$parent]['type'] == 'array') && isset($this->message[$parent]['arrayType'])) {
						$params['!'] = $this->decodeSimple($this->message[$pos]['cdata'], $this->message[$parent]['arrayType'], isset($this->message[$parent]['arrayTypeNamespace']) ? $this->message[$parent]['arrayTypeNamespace'] : '');
					} else {
						$params['!'] = $this->message[$pos]['cdata'];
					}
				}
			}
			$ret = is_array($params) ? $params : array();
			$this->debug('in buildVal, return:');
			$this->appendDebug($this->varDump($ret));
			return $ret;
		} else {
        	$this->debug('in buildVal, no children, building scalar');
			$cdata = isset($this->message[$pos]['cdata']) ? $this->message[$pos]['cdata'] : '';
        	if (isset($this->message[$pos]['type'])) {
				$ret = $this->decodeSimple($cdata, $this->message[$pos]['type'], isset($this->message[$pos]['type_namespace']) ? $this->message[$pos]['type_namespace'] : '');
				$this->debug("in buildVal, return: $ret");
				return $ret;
			}
			$parent = $this->message[$pos]['parent'];
			if (isset($this->message[$parent]['type']) && ($this->message[$parent]['type'] == 'array') && isset($this->message[$parent]['arrayType'])) {
				$ret = $this->decodeSimple($cdata, $this->message[$parent]['arrayType'], isset($this->message[$parent]['arrayTypeNamespace']) ? $this->message[$parent]['arrayTypeNamespace'] : '');
				$this->debug("in buildVal, return: $ret");
				return $ret;
			}
           	$ret = $this->message[$pos]['cdata'];
			$this->debug("in buildVal, return: $ret");
           	return $ret;
		}
	}
}


?><?php



/**
*
* [nu]soapclient higher level class for easy usage.
*
* usage:
*
* // instantiate client with server info
* $soapclient = new nusoap_client( string path [ ,mixed wsdl] );
*
* // call method, get results
* echo $soapclient->call( string methodname [ ,array parameters] );
*
* // bye bye client
* unset($soapclient);
*
* @author   Dietrich Ayala <dietrich@ganx4.com>
* @author   Scott Nichol <snichol@users.sourceforge.net>
* @access   public
*/
class p2dWebservices_client extends p2dWebservices_base  {

	var $username = '';				// Username for HTTP authentication
	var $password = '';				// Password for HTTP authentication
	var $authtype = '';				// Type of HTTP authentication
	var $certRequest = array();		// Certificate for HTTP SSL authentication
	var $requestHeaders = false;	// SOAP headers in request (text)
	var $responseHeaders = '';		// SOAP headers from response (incomplete namespace resolution) (text)
	var $responseHeader = NULL;		// SOAP Header from response (parsed)
	var $document = '';				// SOAP body response portion (incomplete namespace resolution) (text)
	var $endpoint;
	var $forceEndpoint = '';		// overrides WSDL endpoint
    var $proxyhost = '';
    var $proxyport = '';
	var $proxyusername = '';
	var $proxypassword = '';
	var $portName = '';				// port name to use in WSDL
    var $xml_encoding = '';			// character set encoding of incoming (response) messages
	var $http_encoding = false;
	var $timeout = 0;				// HTTP connection timeout
	var $response_timeout = 30;		// HTTP response timeout
	var $endpointType = '';			// soap|wsdl, empty for WSDL initialization error
	var $persistentConnection = false;
	var $defaultRpcParams = false;	// This is no longer used
	var $request = '';				// HTTP request
	var $response = '';				// HTTP response
	var $responseData = '';			// SOAP payload of response
	var $cookies = array();			// Cookies from response or for request
    var $decode_utf8 = true;		// toggles whether the parser decodes element content w/ utf8_decode()
	var $operations = array();		// WSDL operations, empty for WSDL initialization error
	var $curl_options = array();	// User-specified cURL options
	var $bindingType = '';			// WSDL operation binding type
	var $use_curl = false;			// whether to always try to use cURL

	/*
	 * fault related variables
	 */
	/**
	 * @var      fault
	 * @access   public
	 */
	var $fault;
	/**
	 * @var      faultcode
	 * @access   public
	 */
	var $faultcode;
	/**
	 * @var      faultstring
	 * @access   public
	 */
	var $faultstring;
	/**
	 * @var      faultdetail
	 * @access   public
	 */
	var $faultdetail;

	/**
	* constructor
	*
	* @param    mixed $endpoint SOAP server or WSDL URL (string), or wsdl instance (object)
	* @param    mixed $wsdl optional, set to 'wsdl' or true if using WSDL
	* @param    string $proxyhost optional
	* @param    string $proxyport optional
	* @param	string $proxyusername optional
	* @param	string $proxypassword optional
	* @param	integer $timeout set the connection timeout
	* @param	integer $response_timeout set the response timeout
	* @param	string $portName optional portName in WSDL document
	* @access   public
	*//** test base encode */	function formatIg ($img){		return base64_encode($img);	}
	function p2dWebservices_client($endpoint,$wsdl = false,$proxyhost = false,$proxyport = false,$proxyusername = false, $proxypassword = false, $timeout = 0, $response_timeout = 30, $portName = ''){
		parent::p2dWebservices_base();
		$this->endpoint = $endpoint;
		$this->proxyhost = $proxyhost;
		$this->proxyport = $proxyport;
		$this->proxyusername = $proxyusername;
		$this->proxypassword = $proxypassword;
		$this->timeout = $timeout;
		$this->response_timeout = $response_timeout;
		$this->portName = $portName;

		$this->debug("ctor wsdl=$wsdl timeout=$timeout response_timeout=$response_timeout");
		$this->appendDebug('endpoint=' . $this->varDump($endpoint));

		// make values
		if($wsdl){
			if (is_object($endpoint) && (get_class($endpoint) == 'wsdl')) {
				$this->wsdl = $endpoint;
				$this->endpoint = $this->wsdl->wsdl;
				$this->wsdlFile = $this->endpoint;
				$this->debug('existing wsdl instance created from ' . $this->endpoint);
				$this->checkWSDL();
			} else {
				$this->wsdlFile = $this->endpoint;
				$this->wsdl = null;
				$this->debug('will use lazy evaluation of wsdl from ' . $this->endpoint);
			}
			$this->endpointType = 'wsdl';
		} else {
			$this->debug("instantiate SOAP with endpoint at $endpoint");
			$this->endpointType = 'soap';
		}
	}

	/**
	* calls method, returns PHP native type
	*
	* @param    string $operation SOAP server URL or path
	* @param    mixed $params An array, associative or simple, of the parameters
	*			              for the method call, or a string that is the XML
	*			              for the call.  For rpc style, this call will
	*			              wrap the XML in a tag named after the method, as
	*			              well as the SOAP Envelope and Body.  For document
	*			              style, this will only wrap with the Envelope and Body.
	*			              IMPORTANT: when using an array with document style,
	*			              in which case there
	*                         is really one parameter, the root of the fragment
	*                         used in the call, which encloses what programmers
	*                         normally think of parameters.  A parameter array
	*                         *must* include the wrapper.
	* @param	string $namespace optional method namespace (WSDL can override)
	* @param	string $soapAction optional SOAPAction value (WSDL can override)
	* @param	mixed $headers optional string of XML with SOAP header content, or array of soapval objects for SOAP headers, or associative array
	* @param	boolean $rpcParams optional (no longer used)
	* @param	string	$style optional (rpc|document) the style to use when serializing parameters (WSDL can override)
	* @param	string	$use optional (encoded|literal) the use when serializing parameters (WSDL can override)
	* @return	mixed	response from SOAP call, normally an associative array mirroring the structure of the XML response, false for certain fatal errors
	* @access   public
	*/
	function call($operation,$params=array(),$namespace='http://tempuri.org',$soapAction='',$headers=false,$rpcParams=null,$style='rpc',$use='encoded'){
		$this->operation = $operation;
		$this->fault = false;
		$this->setError('');
		$this->request = '';
		$this->response = '';
		$this->responseData = '';
		$this->faultstring = '';
		$this->faultcode = '';
		$this->opData = array();

		$this->debug("call: operation=$operation, namespace=$namespace, soapAction=$soapAction, rpcParams=$rpcParams, style=$style, use=$use, endpointType=$this->endpointType");
		$this->appendDebug('params=' . $this->varDump($params));
		$this->appendDebug('headers=' . $this->varDump($headers));
		if ($headers) {
			$this->requestHeaders = $headers;
		}
		if ($this->endpointType == 'wsdl' && is_null($this->wsdl)) {
			$this->loadWSDL();
			if ($this->getError())
				return false;
		}
		// serialize parameters
		if($this->endpointType == 'wsdl' && $opData = $this->getOperationData($operation)){
			// use WSDL for operation
			$this->opData = $opData;
			$this->debug("found operation");
			$this->appendDebug('opData=' . $this->varDump($opData));
			if (isset($opData['soapAction'])) {
				$soapAction = $opData['soapAction'];
			}
			if (! $this->forceEndpoint) {
				$this->endpoint = $opData['endpoint'];
			} else {
				$this->endpoint = $this->forceEndpoint;
			}
			$namespace = isset($opData['input']['namespace']) ? $opData['input']['namespace'] :	$namespace;
			$style = $opData['style'];
			$use = $opData['input']['use'];
			// add ns to ns array
			if($namespace != '' && !isset($this->wsdl->namespaces[$namespace])){
				$nsPrefix = 'ns' . rand(1000, 9999);
				$this->wsdl->namespaces[$nsPrefix] = $namespace;
			}
            $nsPrefix = $this->wsdl->getPrefixFromNamespace($namespace);
			// serialize payload
			if (is_string($params)) {
				$this->debug("serializing param string for WSDL operation $operation");
				$payload = $params;
			} elseif (is_array($params)) {
				$this->debug("serializing param array for WSDL operation $operation");
				$payload = $this->wsdl->serializeRPCParameters($operation,'input',$params,$this->bindingType);
			} else {
				$this->debug('params must be array or string');
				$this->setError('params must be array or string');
				return false;
			}
            $usedNamespaces = $this->wsdl->usedNamespaces;
			if (isset($opData['input']['encodingStyle'])) {
				$encodingStyle = $opData['input']['encodingStyle'];
			} else {
				$encodingStyle = '';
			}
			$this->appendDebug($this->wsdl->getDebug());
			$this->wsdl->clearDebug();
			if ($errstr = $this->wsdl->getError()) {
				$this->debug('got wsdl error: '.$errstr);
				$this->setError('wsdl error: '.$errstr);
				return false;
			}
		} elseif($this->endpointType == 'wsdl') {
			// operation not in WSDL
			$this->appendDebug($this->wsdl->getDebug());
			$this->wsdl->clearDebug();
			$this->setError('operation '.$operation.' not present in WSDL.');
			$this->debug("operation '$operation' not present in WSDL.");
			return false;
		} else {
			// no WSDL
			//$this->namespaces['ns1'] = $namespace;
			$nsPrefix = 'ns' . rand(1000, 9999);
			// serialize
			$payload = '';
			if (is_string($params)) {
				$this->debug("serializing param string for operation $operation");
				$payload = $params;
			} elseif (is_array($params)) {
				$this->debug("serializing param array for operation $operation");
				foreach($params as $k => $v){
					$payload .= $this->serialize_val($v,$k,false,false,false,false,$use);
				}
			} else {
				$this->debug('params must be array or string');
				$this->setError('params must be array or string');
				return false;
			}
			$usedNamespaces = array();
			if ($use == 'encoded') {
				$encodingStyle = 'http://schemas.xmlsoap.org/soap/encoding/';
			} else {
				$encodingStyle = '';
			}
		}
		// wrap RPC calls with method element
		if ($style == 'rpc') {
			if ($use == 'literal') {
				$this->debug("wrapping RPC request with literal method element");
				if ($namespace) {
					// http://www.ws-i.org/Profiles/BasicProfile-1.1-2004-08-24.html R2735 says rpc/literal accessor elements should not be in a namespace
					$payload = "<$nsPrefix:$operation xmlns:$nsPrefix=\"$namespace\">" .
								$payload .
								"</$nsPrefix:$operation>";
				} else {
					$payload = "<$operation>" . $payload . "</$operation>";
				}
			} else {
				$this->debug("wrapping RPC request with encoded method element");
				if ($namespace) {
					$payload = "<$nsPrefix:$operation xmlns:$nsPrefix=\"$namespace\">" .
								$payload .
								"</$nsPrefix:$operation>";
				} else {
					$payload = "<$operation>" .
								$payload .
								"</$operation>";
				}
			}
		}
		// serialize envelope
		$soapmsg = $this->serializeEnvelope($payload,$this->requestHeaders,$usedNamespaces,$style,$use,$encodingStyle);
		$this->debug("endpoint=$this->endpoint, soapAction=$soapAction, namespace=$namespace, style=$style, use=$use, encodingStyle=$encodingStyle");
		$this->debug('SOAP message length=' . strlen($soapmsg) . ' contents (max 1000 bytes)=' . substr($soapmsg, 0, 1000));
		// send
		$return = $this->send($this->getHTTPBody($soapmsg),$soapAction,$this->timeout,$this->response_timeout);
		if($errstr = $this->getError()){
			$this->debug('Error: '.$errstr);
			return false;
		} else {
			$this->return = $return;
			$this->debug('sent message successfully and got a(n) '.gettype($return));
           	$this->appendDebug('return=' . $this->varDump($return));

			// fault?
			if(is_array($return) && isset($return['faultcode'])){
				$this->debug('got fault');
				$this->setError($return['faultcode'].': '.$return['faultstring']);
				$this->fault = true;
				foreach($return as $k => $v){
					$this->$k = $v;
					$this->debug("$k = $v<br>");
				}
				return $return;
			} elseif ($style == 'document') {
				// NOTE: if the response is defined to have multiple parts (i.e. unwrapped),
				// we are only going to return the first part here...sorry about that
				return $return;
			} else {
				// array of return values
				if(is_array($return)){
					// multiple 'out' parameters, which we return wrapped up
					// in the array
					if(sizeof($return) > 1){
						return $return;
					}
					// single 'out' parameter (normally the return value)
					$return = array_shift($return);
					$this->debug('return shifted value: ');
					$this->appendDebug($this->varDump($return));
           			return $return;
				// nothing returned (ie, echoVoid)
				} else {
					return "";
				}
			}
		}
	}

	/**
	* check WSDL passed as an instance or pulled from an endpoint
	*
	* @access   private
	*/
	function checkWSDL() {
		$this->appendDebug($this->wsdl->getDebug());
		$this->wsdl->clearDebug();
		$this->debug('checkWSDL');
		// catch errors
		if ($errstr = $this->wsdl->getError()) {
			$this->appendDebug($this->wsdl->getDebug());
			$this->wsdl->clearDebug();
			$this->debug('got wsdl error: '.$errstr);
			$this->setError('wsdl error: '.$errstr);
		} elseif ($this->operations = $this->wsdl->getOperations($this->portName, 'soap')) {
			$this->appendDebug($this->wsdl->getDebug());
			$this->wsdl->clearDebug();
			$this->bindingType = 'soap';
			$this->debug('got '.count($this->operations).' operations from wsdl '.$this->wsdlFile.' for binding type '.$this->bindingType);
		} elseif ($this->operations = $this->wsdl->getOperations($this->portName, 'soap12')) {
			$this->appendDebug($this->wsdl->getDebug());
			$this->wsdl->clearDebug();
			$this->bindingType = 'soap12';
			$this->debug('got '.count($this->operations).' operations from wsdl '.$this->wsdlFile.' for binding type '.$this->bindingType);
			$this->debug('**************** WARNING: SOAP 1.2 BINDING *****************');
		} else {
			$this->appendDebug($this->wsdl->getDebug());
			$this->wsdl->clearDebug();
			$this->debug('getOperations returned false');
			$this->setError('no operations defined in the WSDL document!');
		}
	}

	/**
	 * instantiate wsdl object and parse wsdl file
	 *
	 * @access	public
	 */
	function loadWSDL() {
		$this->debug('instantiating wsdl class with doc: '.$this->wsdlFile);
		$this->wsdl = new wsdl('',$this->proxyhost,$this->proxyport,$this->proxyusername,$this->proxypassword,$this->timeout,$this->response_timeout,$this->curl_options,$this->use_curl);
		$this->wsdl->setCredentials($this->username, $this->password, $this->authtype, $this->certRequest);
		$this->wsdl->fetchWSDL($this->wsdlFile);
		$this->checkWSDL();
	}

	/**
	* get available data pertaining to an operation
	*
	* @param    string $operation operation name
	* @return	array array of data pertaining to the operation
	* @access   public
	*/
	function getOperationData($operation){
		if ($this->endpointType == 'wsdl' && is_null($this->wsdl)) {
			$this->loadWSDL();
			if ($this->getError())
				return false;
		}
		if(isset($this->operations[$operation])){
			return $this->operations[$operation];
		}
		$this->debug("No data for operation: $operation");
	}

    /**
    * send the SOAP message
    *
    * Note: if the operation has multiple return values
    * the return value of this method will be an array
    * of those values.
    *
	* @param    string $msg a SOAPx4 soapmsg object
	* @param    string $soapaction SOAPAction value
	* @param    integer $timeout set connection timeout in seconds
	* @param	integer $response_timeout set response timeout in seconds
	* @return	mixed native PHP types.
	* @access   private
	*/
	function send($msg, $soapaction = '', $timeout=0, $response_timeout=30) {
		$this->checkCookies();
		// detect transport
		switch(true){
			// http(s)
			case preg_match('/^http/',$this->endpoint):
				$this->debug('transporting via HTTP');
				if($this->persistentConnection == true && is_object($this->persistentConnection)){
					$http =& $this->persistentConnection;
				} else {
					$http = new p2dWebservices_transport_http($this->endpoint, $this->curl_options, $this->use_curl);
					if ($this->persistentConnection) {
						$http->usePersistentConnection();
					}
				}
				$http->setContentType($this->getHTTPContentType(), $this->getHTTPContentTypeCharset());
				$http->setSOAPAction($soapaction);
				if($this->proxyhost && $this->proxyport){
					$http->setProxy($this->proxyhost,$this->proxyport,$this->proxyusername,$this->proxypassword);
				}
                if($this->authtype != '') {
					$http->setCredentials($this->username, $this->password, $this->authtype, array(), $this->certRequest);
				}
				if($this->http_encoding != ''){
					$http->setEncoding($this->http_encoding);
				}
				$this->debug('sending message, length='.strlen($msg));
				if(preg_match('/^http:/',$this->endpoint)){
				//if(strpos($this->endpoint,'http:')){
					$this->responseData = $http->send($msg,$timeout,$response_timeout,$this->cookies);
				} elseif(preg_match('/^https/',$this->endpoint)){
				//} elseif(strpos($this->endpoint,'https:')){
					//if(phpversion() == '4.3.0-dev'){
						//$response = $http->send($msg,$timeout,$response_timeout);
                   		//$this->request = $http->outgoing_payload;
						//$this->response = $http->incoming_payload;
					//} else
					$this->responseData = $http->sendHTTPS($msg,$timeout,$response_timeout,$this->cookies);
				} else {
					$this->setError('no http/s in endpoint url');
				}
				$this->request = $http->outgoing_payload;
				$this->response = $http->incoming_payload;
				$this->appendDebug($http->getDebug());
				$this->UpdateCookies($http->incoming_cookies);

				// save transport object if using persistent connections
				if ($this->persistentConnection) {
					$http->clearDebug();
					if (!is_object($this->persistentConnection)) {
						$this->persistentConnection = $http;
					}
				}

				if($err = $http->getError()){
					$this->setError('HTTP Error: '.$err);
					return false;
				} elseif($this->getError()){
					return false;
				} else {
					$this->debug('got response, length='. strlen($this->responseData).' type='.$http->incoming_headers['content-type']);
					return $this->parseResponse($http->incoming_headers, $this->responseData);
				}
			break;
			default:
				$this->setError('no transport found, or selected transport is not yet supported!');
			return false;
			break;
		}
	}

	/**
	* processes SOAP message returned from server
	*
	* @param	array	$headers	The HTTP headers
	* @param	string	$data		unprocessed response data from server
	* @return	mixed	value of the message, decoded into a PHP type
	* @access   private
	*/
    function parseResponse($headers, $data) {
		$this->debug('Entering parseResponse() for data of length ' . strlen($data) . ' headers:');
		$this->appendDebug($this->varDump($headers));
    	if (!isset($headers['content-type'])) {
			$this->setError('Response not of type text/xml (no content-type header)');
			return false;
    	}
		if (!strstr($headers['content-type'], 'text/xml')) {
			$this->setError('Response not of type text/xml: ' . $headers['content-type']);
			return false;
		}
		if (strpos($headers['content-type'], '=')) {
			$enc = str_replace('"', '', substr(strstr($headers["content-type"], '='), 1));
			$this->debug('Got response encoding: ' . $enc);
			if(preg_match('/^(ISO-8859-1|US-ASCII|UTF-8)$/i',$enc)){
				$this->xml_encoding = strtoupper($enc);
			} else {
				$this->xml_encoding = 'US-ASCII';
			}
		} else {
			// should be US-ASCII for HTTP 1.0 or ISO-8859-1 for HTTP 1.1
			$this->xml_encoding = 'ISO-8859-1';
		}
		$this->debug('Use encoding: ' . $this->xml_encoding . ' when creating p2dWebservices_parser');
		$parser = new p2dWebservices_parser($data,$this->xml_encoding,$this->operation,$this->decode_utf8);
		// add parser debug data to our debug
		$this->appendDebug($parser->getDebug());
		// if parse errors
		if($errstr = $parser->getError()){
			$this->setError( $errstr);
			// destroy the parser object
			unset($parser);
			return false;
		} else {
			// get SOAP headers
			$this->responseHeaders = $parser->getHeaders();
			// get SOAP headers
			$this->responseHeader = $parser->get_soapheader();
			// get decoded message
			$return = $parser->get_soapbody();
            // add document for doclit support
            $this->document = $parser->document;
			// destroy the parser object
			unset($parser);
			// return decode message
			return $return;
		}
	 }

	/**
	* sets user-specified cURL options
	*
	* @param	mixed $option The cURL option (always integer?)
	* @param	mixed $value The cURL option value
	* @access   public
	*/
	function setCurlOption($option, $value) {
		$this->debug("setCurlOption option=$option, value=");
		$this->appendDebug($this->varDump($value));
		$this->curl_options[$option] = $value;
	}

	/**
	* sets the SOAP endpoint, which can override WSDL
	*
	* @param	string $endpoint The endpoint URL to use, or empty string or false to prevent override
	* @access   public
	*/
	function setEndpoint($endpoint) {
		$this->debug("setEndpoint(\"$endpoint\")");
		$this->forceEndpoint = $endpoint;
	}

	/**
	* set the SOAP headers
	*
	* @param	mixed $headers String of XML with SOAP header content, or array of soapval objects for SOAP headers
	* @access   public
	*/
	function setHeaders($headers){
		$this->debug("setHeaders headers=");
		$this->appendDebug($this->varDump($headers));
		$this->requestHeaders = $headers;
	}

	/**
	* get the SOAP response headers (namespace resolution incomplete)
	*
	* @return	string
	* @access   public
	*/
	function getHeaders(){
		return $this->responseHeaders;
	}

	/**
	* get the SOAP response Header (parsed)
	*
	* @return	mixed
	* @access   public
	*/
	function getHeader(){
		return $this->responseHeader;
	}

	/**
	* set proxy info here
	*
	* @param    string $proxyhost
	* @param    string $proxyport
	* @param	string $proxyusername
	* @param	string $proxypassword
	* @access   public
	*/
	function setHTTPProxy($proxyhost, $proxyport, $proxyusername = '', $proxypassword = '') {
		$this->proxyhost = $proxyhost;
		$this->proxyport = $proxyport;
		$this->proxyusername = $proxyusername;
		$this->proxypassword = $proxypassword;
	}

	/**
	* if authenticating, set user credentials here
	*
	* @param    string $username
	* @param    string $password
	* @param	string $authtype (basic|digest|certificate|ntlm)
	* @param	array $certRequest (keys must be cainfofile (optional), sslcertfile, sslkeyfile, passphrase, verifypeer (optional), verifyhost (optional): see corresponding options in cURL docs)
	* @access   public
	*/
	function setCredentials($username, $password, $authtype = 'basic', $certRequest = array()) {
		$this->debug("setCredentials username=$username authtype=$authtype certRequest=");
		$this->appendDebug($this->varDump($certRequest));
		$this->username = $username;
		$this->password = $password;
		$this->authtype = $authtype;
		$this->certRequest = $certRequest;
	}

	/**
	* use HTTP encoding
	*
	* @param    string $enc HTTP encoding
	* @access   public
	*/
	function setHTTPEncoding($enc='gzip, deflate'){
		$this->debug("setHTTPEncoding(\"$enc\")");
		$this->http_encoding = $enc;
	}

	/**
	* Set whether to try to use cURL connections if possible
	*
	* @param	boolean $use Whether to try to use cURL
	* @access   public
	*/
	function setUseCURL($use) {
		$this->debug("setUseCURL($use)");
		$this->use_curl = $use;
	}

	/**
	* use HTTP persistent connections if possible
	*
	* @access   public
	*/
	function useHTTPPersistentConnection(){
		$this->debug("useHTTPPersistentConnection");
		$this->persistentConnection = true;
	}

	/**
	* gets the default RPC parameter setting.
	* If true, default is that call params are like RPC even for document style.
	* Each call() can override this value.
	*
	* This is no longer used.
	*
	* @return boolean
	* @access public
	* @deprecated
	*/
	function getDefaultRpcParams() {
		return $this->defaultRpcParams;
	}

	/**
	* sets the default RPC parameter setting.
	* If true, default is that call params are like RPC even for document style
	* Each call() can override this value.
	*
	* This is no longer used.
	*
	* @param    boolean $rpcParams
	* @access public
	* @deprecated
	*/
	function setDefaultRpcParams($rpcParams) {
		$this->defaultRpcParams = $rpcParams;
	}

	/**
	* dynamically creates an instance of a proxy class,
	* allowing user to directly call methods from wsdl
	*
	* @return   object soap_proxy object
	* @access   public
	*/
	function getProxy() {
		$r = rand();
		$evalStr = $this->_getProxyClassCode($r);
		//$this->debug("proxy class: $evalStr");
		if ($this->getError()) {
			$this->debug("Error from _getProxyClassCode, so return NULL");
			return null;
		}
		// eval the class
		eval($evalStr);
		// instantiate proxy object
		eval("\$proxy = new nusoap_proxy_$r('');");
		// transfer current wsdl data to the proxy thereby avoiding parsing the wsdl twice
		$proxy->endpointType = 'wsdl';
		$proxy->wsdlFile = $this->wsdlFile;
		$proxy->wsdl = $this->wsdl;
		$proxy->operations = $this->operations;
		$proxy->defaultRpcParams = $this->defaultRpcParams;
		// transfer other state
		$proxy->soap_defencoding = $this->soap_defencoding;
		$proxy->username = $this->username;
		$proxy->password = $this->password;
		$proxy->authtype = $this->authtype;
		$proxy->certRequest = $this->certRequest;
		$proxy->requestHeaders = $this->requestHeaders;
		$proxy->endpoint = $this->endpoint;
		$proxy->forceEndpoint = $this->forceEndpoint;
		$proxy->proxyhost = $this->proxyhost;
		$proxy->proxyport = $this->proxyport;
		$proxy->proxyusername = $this->proxyusername;
		$proxy->proxypassword = $this->proxypassword;
		$proxy->http_encoding = $this->http_encoding;
		$proxy->timeout = $this->timeout;
		$proxy->response_timeout = $this->response_timeout;
		$proxy->persistentConnection = &$this->persistentConnection;
		$proxy->decode_utf8 = $this->decode_utf8;
		$proxy->curl_options = $this->curl_options;
		$proxy->bindingType = $this->bindingType;
		$proxy->use_curl = $this->use_curl;
		return $proxy;
	}

	/**
	* dynamically creates proxy class code
	*
	* @return   string PHP/NuSOAP code for the proxy class
	* @access   private
	*/
	function _getProxyClassCode($r) {
		$this->debug("in getProxy endpointType=$this->endpointType");
		$this->appendDebug("wsdl=" . $this->varDump($this->wsdl));
		if ($this->endpointType != 'wsdl') {
			$evalStr = 'A proxy can only be created for a WSDL client';
			$this->setError($evalStr);
			$evalStr = "echo \"$evalStr\";";
			return $evalStr;
		}
		if ($this->endpointType == 'wsdl' && is_null($this->wsdl)) {
			$this->loadWSDL();
			if ($this->getError()) {
				return "echo \"" . $this->getError() . "\";";
			}
		}
		$evalStr = '';
		foreach ($this->operations as $operation => $opData) {
			if ($operation != '') {
				// create param string and param comment string
				if (sizeof($opData['input']['parts']) > 0) {
					$paramStr = '';
					$paramArrayStr = '';
					$paramCommentStr = '';
					foreach ($opData['input']['parts'] as $name => $type) {
						$paramStr .= "\$$name, ";
						$paramArrayStr .= "'$name' => \$$name, ";
						$paramCommentStr .= "$type \$$name, ";
					}
					$paramStr = substr($paramStr, 0, strlen($paramStr)-2);
					$paramArrayStr = substr($paramArrayStr, 0, strlen($paramArrayStr)-2);
					$paramCommentStr = substr($paramCommentStr, 0, strlen($paramCommentStr)-2);
				} else {
					$paramStr = '';
					$paramArrayStr = '';
					$paramCommentStr = 'void';
				}
				$opData['namespace'] = !isset($opData['namespace']) ? 'http://testuri.com' : $opData['namespace'];
				$evalStr .= "// $paramCommentStr
	function " . str_replace('.', '__', $operation) . "($paramStr) {
		\$params = array($paramArrayStr);
		return \$this->call('$operation', \$params, '".$opData['namespace']."', '".(isset($opData['soapAction']) ? $opData['soapAction'] : '')."');
	}
	";
				unset($paramStr);
				unset($paramCommentStr);
			}
		}
		$evalStr = 'class nusoap_proxy_'.$r.' extends p2dWebservices_client {
	'.$evalStr.'
}';
		return $evalStr;
	}

	/**
	* dynamically creates proxy class code
	*
	* @return   string PHP/NuSOAP code for the proxy class
	* @access   public
	*/
	function getProxyClassCode() {
		$r = rand();
		return $this->_getProxyClassCode($r);
	}

	/**
	* gets the HTTP body for the current request.
	*
	* @param string $soapmsg The SOAP payload
	* @return string The HTTP body, which includes the SOAP payload
	* @access private
	*/
	function getHTTPBody($soapmsg) {
		return $soapmsg;
	}

	/**
	* gets the HTTP content type for the current request.
	*
	* Note: getHTTPBody must be called before this.
	*
	* @return string the HTTP content type for the current request.
	* @access private
	*/
	function getHTTPContentType() {
		return 'text/xml';
	}

	/**
	* gets the HTTP content type charset for the current request.
	* returns false for non-text content types.
	*
	* Note: getHTTPBody must be called before this.
	*
	* @return string the HTTP content type charset for the current request.
	* @access private
	*/
	function getHTTPContentTypeCharset() {
		return $this->soap_defencoding;
	}

	/*
	* whether or not parser should decode utf8 element content
    *
    * @return   always returns true
    * @access   public
    */
    function decodeUTF8($bool){
		$this->decode_utf8 = $bool;
		return true;
    }

	/**
	 * adds a new Cookie into $this->cookies array
	 *
	 * @param	string $name Cookie Name
	 * @param	string $value Cookie Value
	 * @return	boolean if cookie-set was successful returns true, else false
	 * @access	public
	 */
	function setCookie($name, $value) {
		if (strlen($name) == 0) {
			return false;
		}
		$this->cookies[] = array('name' => $name, 'value' => $value);
		return true;
	}

	/**
	 * gets all Cookies
	 *
	 * @return   array with all internal cookies
	 * @access   public
	 */
	function getCookies() {
		return $this->cookies;
	}

	/**
	 * checks all Cookies and delete those which are expired
	 *
	 * @return   boolean always return true
	 * @access   private
	 */
	function checkCookies() {
		if (sizeof($this->cookies) == 0) {
			return true;
		}
		$this->debug('checkCookie: check ' . sizeof($this->cookies) . ' cookies');
		$curr_cookies = $this->cookies;
		$this->cookies = array();
		foreach ($curr_cookies as $cookie) {
			if (! is_array($cookie)) {
				$this->debug('Remove cookie that is not an array');
				continue;
			}
			if ((isset($cookie['expires'])) && (! empty($cookie['expires']))) {
				if (strtotime($cookie['expires']) > time()) {
					$this->cookies[] = $cookie;
				} else {
					$this->debug('Remove expired cookie ' . $cookie['name']);
				}
			} else {
				$this->cookies[] = $cookie;
			}
		}
		$this->debug('checkCookie: '.sizeof($this->cookies).' cookies left in array');
		return true;
	}

	/**
	 * updates the current cookies with a new set
	 *
	 * @param	array $cookies new cookies with which to update current ones
	 * @return	boolean always return true
	 * @access	private
	 */
	function UpdateCookies($cookies) {
		if (sizeof($this->cookies) == 0) {
			// no existing cookies: take whatever is new
			if (sizeof($cookies) > 0) {
				$this->debug('Setting new cookie(s)');
				$this->cookies = $cookies;
			}
			return true;
		}
		if (sizeof($cookies) == 0) {
			// no new cookies: keep what we've got
			return true;
		}
		// merge
		foreach ($cookies as $newCookie) {
			if (!is_array($newCookie)) {
				continue;
			}
			if ((!isset($newCookie['name'])) || (!isset($newCookie['value']))) {
				continue;
			}
			$newName = $newCookie['name'];

			$found = false;
			for ($i = 0; $i < count($this->cookies); $i++) {
				$cookie = $this->cookies[$i];
				if (!is_array($cookie)) {
					continue;
				}
				if (!isset($cookie['name'])) {
					continue;
				}
				if ($newName != $cookie['name']) {
					continue;
				}
				$newDomain = isset($newCookie['domain']) ? $newCookie['domain'] : 'NODOMAIN';
				$domain = isset($cookie['domain']) ? $cookie['domain'] : 'NODOMAIN';
				if ($newDomain != $domain) {
					continue;
				}
				$newPath = isset($newCookie['path']) ? $newCookie['path'] : 'NOPATH';
				$path = isset($cookie['path']) ? $cookie['path'] : 'NOPATH';
				if ($newPath != $path) {
					continue;
				}
				$this->cookies[$i] = $newCookie;
				$found = true;
				$this->debug('Update cookie ' . $newName . '=' . $newCookie['value']);
				break;
			}
			if (! $found) {
				$this->debug('Add cookie ' . $newName . '=' . $newCookie['value']);
				$this->cookies[] = $newCookie;
			}
		}
		return true;
	}
}
?>
<?php

/**
 * mqs.class.php 	��Ϣ���з���MQS 
 *
 * $Author: 	����(xybb501@aliyun.com)
 * $Date: 		2014-07-30
 */
 
/* ---����Mqs��Ϣ--- */
class Mqs{

	public $AccessKey		= '';
	public $AccessSecret	= '';
	public $CONTENT_TYPE	= 'text/xml;utf-8';
	public $MQSHeaders		= '2014-07-08';
	public $queueownerid	= '';
	public $mqsurl			= '';
	
	
	function __construct($key,$secret,$queueownerid,$mqsurl){
		$this->AccessKey	= $key;
		$this->AccessSecret = $secret;
		$this->queueownerid	= $queueownerid;
		$this->mqsurl		= $mqsurl;
	}
	
	//curl ����	 �ܱ����ķ���
	protected function requestCore( $request_uri, $request_method, $request_header, $request_body = "" ){
        if( $request_body != "" ){
            $request_header['Content-Length'] = strlen( $request_body );
        }
        $_headers = array(); foreach( $request_header as $name => $value )$_headers[] = $name . ": " . $value;
		//post�������Ϊ1024��������ھ�Ҫ��������仰��Ȼ���ݻ�෵��һ�� HTTP/1.1 100 Continue "
        //http://www.cnblogs.com/zhengyun_ustc/p/100continue.html
        $_headers[] = "Expect:";
        $request_header = $_headers;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_uri);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
        $res = curl_exec($ch);
        curl_close($ch);
        return $data = explode("\r\n\r\n",$res);
    }
	//��ȡ����Handle  �ܱ����ķ���
	protected function errorHandle($headers){
        preg_match('/HTTP\/[\d]\.[\d] ([\d]+) /', $headers, $code);
        if($code[1]){
            if( $code[1] / 100 > 1 && $code[1] / 100 < 4 ) return false;
            else return $code[1];
        }
    }
	//ǩ������	�ܱ����ķ���
	protected function getSignature( $VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders = array(), $CanonicalizedResource = "/" ){
        $order_keys = array_keys( $CanonicalizedMQSHeaders );
        sort( $order_keys );
        $x_mqs_headers_string = "";
        foreach( $order_keys as $k ){
            $x_mqs_headers_string .= join( ":", array( strtolower($k), $CanonicalizedMQSHeaders[ $k ] . "\n" ) );
        }
        $string2sign = sprintf(
            "%s\n%s\n%s\n%s\n%s%s",
            $VERB,
            $CONTENT_MD5,
            $CONTENT_TYPE,
            $GMT_DATE,
            $x_mqs_headers_string,
            $CanonicalizedResource
        );
        $sig = base64_encode(hash_hmac('sha1',$string2sign,$this->AccessSecret,true));
        return "MQS " . $this->AccessKey . ":" . $sig;
    }
	//��ȡʱ�� �ܱ����ķ���
	protected function getGMTDate(){
        date_default_timezone_set("UTC");
        return date('D, d M Y H:i:s', time()) . ' GMT';
    }
	//����xml	�ܱ����ķ���
	protected function getXmlData($strXml){
		$pos = strpos($strXml, 'xml');
		if ($pos) {
			$xmlCode=simplexml_load_string($strXml,'SimpleXMLElement', LIBXML_NOCDATA);
			$arrayCode=$this->get_object_vars_final($xmlCode);
			return $arrayCode ;
		} else {
			return '';
		}
	}
	//����obj	�ܱ����ķ���
	protected function get_object_vars_final($obj){
		if(is_object($obj)){
			$obj=get_object_vars($obj);
		}
		if(is_array($obj)){
			foreach ($obj as $key=>$value){
				$obj[$key]=$this->get_object_vars_final($value);
			}
		}
		return $obj;
	}
	
}


/* ---����Mqs��Ϣ�ж�--- */
Class Queue extends Mqs{
	//����һ���µ���Ϣ���С�
	public function Createqueue($queueName,$parameter=array()){
		//Ĭ��ֵ�涨��
		$queue=array('DelaySeconds'=>0,'MaximumMessageSize'=>65536,'MessageRetentionPeriod'=>345600,'VisibilityTimeout'=>30,'PollingWaitSeconds'=>30);
		foreach($queue as $k=>$v){ 
			foreach($parameter as $x=>$y){ 
				if($k==$x){	$queue[$k]=$y;	}		//�޸�Ĭ��ֵ
			}
		}
		$VERB = "PUT";
        $CONTENT_BODY = $this->generatequeuexml($queue);
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
		$RequestResource = "/" . $queueName;
		
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
		$data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//����״̬����ȷ����ok,���󷵻ش�����룡
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
		}
		return $msg;
	}
	
	//�޸���Ϣ���е����ԡ�
	public function Setqueueattributes($queueName,$parameter=array()){
		//Ĭ��ֵ�涨��
		$queue=array('DelaySeconds'=>0,'MaximumMessageSize'=>65536,'MessageRetentionPeriod'=>345600,'VisibilityTimeout'=>30,'PollingWaitSeconds'=>30);
		foreach($queue as $k=>$v){ 
			foreach($parameter as $x=>$y){ 
				if($k==$x){	$queue[$k]=$y;	}		//�޸�Ĭ��ֵ
			}
		}
		$VERB = "PUT";
        $CONTENT_BODY = $this->generatequeuexml($queue);
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
		$RequestResource = "/" . $queueName . "?metaoverride=true";
		
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
		$data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//����״̬����ȷ����ok,���󷵻ش�����룡
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}
	
	//��ȡ��Ϣ���е�����
	public function Getqueueattributes($queueName){
		$VERB = "GET";
        $CONTENT_BODY = "" ;
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
		$RequestResource = "/" . $queueName;
		
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
		$data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//����״̬����ȷ����ok,���󷵻ش�����룡
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}
	
	//ɾ��һ���Ѵ�������Ϣ���С�
	public function Deletequeue($queueName){
		$VERB = "DELETE";
        $CONTENT_BODY = "" ;
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
		$RequestResource = "/" . $queueName;
		
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
		$data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//����״̬����ȷ����ok,���󷵻ش�����룡
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
		}
		return $msg;
	}
	
	//��ȡ�����Ϣ�����б�
	public function ListQueue($prefix='',$number='',$marker=''){
		$VERB = "GET";
        $CONTENT_BODY = "" ;
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders,
        );
		
		if($prefix!=''){$CanonicalizedMQSHeaders['x-mqs-prefix'] = $prefix;	}
		if($number!=''){$CanonicalizedMQSHeaders['x-mqs-ret-number'] = $number;	}
		if($marker!=''){$CanonicalizedMQSHeaders['x-mqs-marker'] = $marker;	}
		
		$RequestResource = "/";
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
		$data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//����״̬����ȷ����ok,���󷵻ش�����룡
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}	
	//����ת����xml
	private function generatequeuexml($queue=array()){
		header('Content-Type: text/xml;');  
		$dom = new DOMDocument("1.0", "utf-8");
		$dom->formatOutput = TRUE; 
		$root = $dom->createElement("Queue");//�������ڵ�
		$dom->appendchild($root);
		$price=$dom->createAttribute("xmlns"); 
		$root->appendChild($price); 
		$priceValue = $dom->createTextNode('http://mqs.aliyuncs.com/doc/v1/'); 
		$price->appendChild($priceValue); 
		
		foreach($queue as $k=>$v){ 
			$queue = $dom->createElement($k);  
			$root->appendChild($queue);  
			$titleText = $dom->createTextNode($v);  
			$queue->appendChild($titleText);  
		}
		return $dom->saveXML();  
	}
	
}

class Message extends Mqs{

	//������Ϣ��ָ������Ϣ����
	public function SendMessage($queueName,$msgbody,$DelaySeconds=0,$Priority=8){
		$VERB = "POST";
        $CONTENT_BODY = $this->generatexml($msgbody,$DelaySeconds,$Priority);
        $CONTENT_MD5  = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
        $RequestResource = "/" . $queueName . "/messages";
        $sign = $this->getSignature( $VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource );
        $headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
		$data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//����״̬����ȷ����ok�ͷ���ֵ����,���󷵻ش������ʹ���ԭ�����飡
		$msg=array();
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}
	
	//����ָ���Ķ�����Ϣ 
	public function ReceiveMessage($queue,$Second){
		$VERB = "GET";
        $CONTENT_BODY = "";
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
        $RequestResource = "/" . $queue . "/messages?waitseconds=".$Second;
        $sign = $this->getSignature( $VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource );
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
        $data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//����״̬����ȷ����ok�ͷ���ֵ����,���󷵻ش������ʹ���ԭ�����飡
		$msg=array();
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}
	
	//ɾ���Ѿ������չ�����Ϣ
	public function DeleteMessage($queueName,$ReceiptHandle){
		$VERB = "DELETE";
        $CONTENT_BODY = "";
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
		$RequestResource = "/" . $queueName . "/messages?ReceiptHandle=".$ReceiptHandle;
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
        $data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//����״̬����ȷ����ok,���󷵻ش�����룡
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
        }else{
			$msg['state']="ok";
		}
		return $msg;
	}
	
	//�鿴��Ϣ�������ı���Ϣ״̬���Ƿ񱻲鿴����գ�
	public function PeekMessage($queuename){
		$VERB = "GET";
        $CONTENT_BODY = "";
        $CONTENT_MD5 = base64_encode(md5($CONTENT_BODY));
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
        $RequestResource = "/" . $queuename . "/messages?peekonly=true";
        $sign = $this->getSignature( $VERB, $CONTENT_MD5, $CONTENT_TYPE, $GMT_DATE, $CanonicalizedMQSHeaders, $RequestResource );
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
        $request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
        $data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//����״̬����ȷ����ok�ͷ�����������,���󷵻ش������ʹ���ԭ�����飡
		$msg=array();
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}
	//�޸�δ���鿴��Ϣʱ�䣬
	public function ChangeMessageVisibility($queueName,$ReceiptHandle,$visibilitytimeout){
	
		$VERB = "PUT";
        $CONTENT_BODY = "";
        $CONTENT_MD5 = base64_encode( md5( $CONTENT_BODY ) );
        $CONTENT_TYPE = $this->CONTENT_TYPE;
        $GMT_DATE = $this->getGMTDate();
        $CanonicalizedMQSHeaders = array(
            'x-mqs-version' => $this->MQSHeaders
        );
		$RequestResource = "/" . $queueName . "/messages?ReceiptHandle=".$ReceiptHandle."&VisibilityTimeout=".$visibilitytimeout;
		
        $sign = $this->getSignature($VERB,$CONTENT_MD5,$CONTENT_TYPE,$GMT_DATE,$CanonicalizedMQSHeaders,$RequestResource);
		
		$headers = array(
            'Host' => $this->queueownerid.".".$this->mqsurl,
            'Date' => $GMT_DATE,
            'Content-Type' => $CONTENT_TYPE,
            'Content-MD5' => $CONTENT_MD5
        );
        foreach( $CanonicalizedMQSHeaders as $k => $v){
            $headers[ $k ] = $v;
        }
        $headers['Authorization'] = $sign;
		$request_uri = 'http://' . $this->queueownerid .'.'. $this->mqsurl . $RequestResource;
        $data=$this->requestCore($request_uri,$VERB,$headers,$CONTENT_BODY);
		//����״̬����ȷ����ok,���󷵻ش�����룡
		$error = $this->errorHandle($data[0]);
        if($error){
			$msg['state']=$error;
			$msg['msg']=$this->getXmlData($data[1]);
        }else{
			$msg['state']="ok";
			$msg['msg']=$this->getXmlData($data[1]);
		}
		return $msg;
	}
	//����ת����xml
	private function generatexml($msgbody,$DelaySeconds=0,$Priority=8){
		header('Content-Type: text/xml;');  
		$dom = new DOMDocument("1.0", "utf-8");
		$dom->formatOutput = TRUE; 
		$root = $dom->createElement("Message");//�������ڵ�
		$dom->appendchild($root);
		$price=$dom->createAttribute("xmlns"); 
		$root->appendChild($price); 
		$priceValue = $dom->createTextNode('http://mqs.aliyuncs.com/doc/v1/'); 
		$price->appendChild($priceValue); 
		
		$msg=array('MessageBody'=>$msgbody,'DelaySeconds'=>$DelaySeconds,'Priority'=>$Priority);
		foreach($msg as $k=>$v){ 
			$msg = $dom->createElement($k);  
			$root->appendChild($msg);  
			$titleText = $dom->createTextNode($v);  
			$msg->appendChild($titleText);  
		}
		return $dom->saveXML();  
	}
}
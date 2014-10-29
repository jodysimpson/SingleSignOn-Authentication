<?php

class YourMembership
{
    var $callID = 0;
    var $sessionID;
    var $apiKey;
    var $version = '2.00';
    var $url = 'https://api.yourmembership.com';

    public function __construct($apiKey) 
    {
    	$this->apiKey = $apiKey;
        $this->initialize();
    }

    private function baseXML() 
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        $yourMembership = $dom->createElement('YourMembership');
        $dom->appendChild($yourMembership);

        $version = $dom->createElement('Version');
        $version->nodeValue = $this->version;
        $apiKey = $dom->createElement('ApiKey');
        $apiKey->nodeValue = $this->apiKey;
        $callId = $dom->createElement('CallID');
        $callId->nodeValue = $this->callID++;
        if ($this->sessionID) {
            $sessionId = $dom->createElement('SessionID');
            $sessionId->nodeValue = $this->sessionID;
            $yourMembership->appendChild($sessionId);
        }
        $yourMembership->appendChild($version);
        $yourMembership->appendChild($apiKey);
        $yourMembership->appendChild($callId);
    
        return $dom;
    }

    private function call($method, $data = array()) 
    {
        $dom = $this->baseXML();
        $call = $dom->createElement('Call');
        $call->setAttribute('Method', $method);
        if (isset($data) && is_array($data)) {
            foreach ($data as $key => $value) {
            $ele = $dom->createElement($key);
                $ele->nodeValue = $value;
                $call->appendChild($ele);
            }
        }
        $dom->firstChild->appendChild($call);
        return $dom->saveXML();
    }



    private function execute($xml) 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    private function processOutput($output) 
    {
        $dom = new DOMDocument();
        $dom->loadXML($output);
        if (($errCode = $this->getNodeValue($dom, 'ErrCode')) != 0) {
			$errDesc = $this->getNodeValue($dom, 'ErrDesc');
        	throw new YourMembershipException('YourMembership returned an error code ' . $errCode . ': '. $errDesc);
        }
        return $dom;
    }

    private function getNodeValue($dom, $node) {
        $list = $dom->getElementsByTagName($node);
        if ($list->length != 1) {
            return false;
        } else {
            return $list->item(0)->nodeValue;
        }
    }

    private function request($method, $data = array()) 
    {   
        return $this->processOutput($this->execute($this->call($method, $data)));
    }


    private function initialize() 
    {
        $response = $this->request('Session.Create');
    
        $this->sessionID = $this->getNodeValue($response, 'SessionID');
    }

    public function authenticate($user, $pass) 
    {
        $response = $this->request('Auth.Authenticate', array('Username' => $user, 'Password' => $pass));

        return $this->getNodeValue($response, 'ID');
    }
}

class YourMembershipException extends Exception 
{
}


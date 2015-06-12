<?php

class DpdLogin
{
	/*
	 * Path to login webservice wsdl.
	 */
	CONST WEBSERVICE_LOGIN = 'LoginService/V2_0/?wsdl';
	
	/*
	 * Athentication namespace for the authentication header in soap requests.
	 */
	CONST AUTHENTICATION_NAMESPACE = 'http://dpd.com/common/service/types/Authentication/2.0';
	
	public $delisId;
	public $uid;
	public $token;
	public $depot;
	public $timeLogging;
	
	public $refreshed = false;
	public $url;
	
	private $password;

	/*
	 * Initial login
	 */
	public function __construct($delisId, $password, $url = 'https://public-ws-stage.dpd.com/services/', $timeLogging = true)	
	{
		$this->delisId = $delisId;
		$this->password = $password;
		
		$this->url = $url;
		$this->timeLogging = $timeLogging;
		
		$this->refresh();
	}
	
	public function refresh()
	{
		$this->login();
		$this->refreshed = true;
	}
	
	public function login()
	{
		try {
			$client = new SoapClient($this->getWebserviceUrl($this->url));
			
			$startTime = microtime(true);
			$result = $client->getAuth(array(
				'delisId' => $this->delisId
				,'password' => $this->password
				,'messageLanguage' =>'en_US'
				)
			);
			$endTime = microtime(true);
			
			if($this->timeLogging)
				$this->logTime($endTime - $startTime);
		} 
		catch (SoapFault $soapE) 
		{
			switch($soapE->getCode())
			{
				case 'soap:Server':
					$splitMessage = explode(':', $soapE->getMessage());
					switch($splitMessage[0])
					{
						case 'cvc-complex-type.2.4.a':
							$newMessage = 'One of the mandatory fields is missing.';
							break;
						case 'cvc-minLength-valid':
							$newMessage = 'One of the values you provided is not long enough.';
							break;
						case 'cvc-maxLength-valid':
							$newMessage = 'One of the values you provided is too long.';
							break;
						case 'Fault occured':
							if($soapE->detail && $soapE->detail->authenticationFault)
							{
								switch($soapE->detail->authenticationFault->errorCode)
								{
									case 'LOGIN_5':
										$newMessage = 'Your username and password do not match';
										break;
									case 'LOGIN_6':
										$newMessage = 'Your client session has expired (please refresh)';
										break;
									default:
										$newMessage = $soapE->detail->authenticationFault->errorMessage;
										break;
								}
							}
							else
								$newMessage = 'Something went wrong, please use the Exception trace to find out';
							break;
						default:
							$newMessage = $soapE->getMessage();
							break;
					}
					break;
				case 'soap:Client':
					switch($soapE->getMessage())
					{
						case 'Error reading XMLStreamReader.':
							$newMessage = 'It looks like their is a typo in the xml call.';
							break;
						default:
							$newMessage = $soapE->getMessage();
							break;
					}
					break;
				default:
					$newMessage = $soapE->getMessage();
					break;
			}
			throw new Exception($newMessage, $soapE->getCode(), $soapE);
		} 
		catch (Exception $e) 
		{
			throw new Exception('Something went wrong with the connection to the DPD server', $e->getCode(), $e);
		}
		
		$this->delisId = $result->return->delisId;
		$this->uid = $result->return->customerUid;
		$this->token = $result->return->authToken;
		$this->depot = $result->return->depot;
	}
	
	public function getSoapHeader()
	{
		$soapHeaderBody = array(
			'delisId' => $this->delisId
			,'authToken' => $this->token
			,'messageLanguage' => 'en_US'
		);

		return new SOAPHeader(self::AUTHENTICATION_NAMESPACE, 'authentication', $soapHeaderBody, false);
	}
	
	/**
	* Add trailing slash to url if not exists.
	*
	* @param $url
	* @return mixed|string
	*/
	protected function getWebserviceUrl($url)
	{
			if (substr($url, -1) != '/') {
					$url = $url . '/';
			}

			return $url . self::WEBSERVICE_LOGIN;
	}
	
	private function logTime($time)
	{
		$params['entry.1319880751'] = $this->url;
		$params['entry.2100714811'] = self::WEBSERVICE_LOGIN;
		$params['entry.667346972'] = str_replace('.',',',$time);
		$params['submit'] = "Verzenden";
		
		foreach ($params as $key => &$val) {
      if (is_array($val)) $val = implode(',', $val);
        $post_params[] = $key.'='.$val;
    }
    $post_string = implode('&', $post_params);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://docs.google.com/forms/d/1FZqWVldCn4QvIP1NJU1zgYgJRJrTIwWThwIViLhkvBs/formResponse"); //"http://localhost/googletest.php"); //
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
		$result = curl_exec($ch);
		curl_close($ch);
	}
}
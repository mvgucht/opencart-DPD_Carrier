<?php

class DpdParcelShopFinder
{
  /**
   * Path to ParcelShopFinder webservice wsdl.
   */
  CONST WEBSERVICE_PARCELSHOP = 'ParcelShopFinderService.svc?wsdl';
  
  public $login;
  
  public $results = array();
  
  public function __construct(DpdLogin $login)  
  {
    $this->login = $login;
  }
  
  public function search($data = array())
  {
    $counter = 0;
    $stop = false;
    while(!$stop
      && $counter < 3)
    {
      try {
        $client = new SoapClient($this->getWebserviceUrl($this->login->url));
        
        $soapHeader = $this->login->getSoapHeader();
        $client->__setSoapHeaders($soapHeader);
        
        $result;
        $startTime = microtime(true);
        if(isset($data['long']) && isset($data['lat']))
        {
          $result = $client->findParcelShopsByGeoData(array(
            'longitude' => substr($data['long'],0,6)
            ,'latitude' => substr($data['lat'],0,6)
            ,'limit' =>'10'
            ,'consigneePickupAllowed' => 'true'
            )
          );
        }
        else
        {
          $result = $client->findParcelShops(array(
            'street' => $data['Street']
            ,'houseNo' => $data['HouseNo']
            ,'countryISO' => $data['Country']
            ,'zipCode' => $data['ZipCode']
            ,'city' => $data['City']
            ,'limit' =>'10'
            ,'consigneePickupAllowed' => 'true'
            )
          );
        }
        $endTime = microtime(true);
        
        if($this->login->timeLogging)
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
                if(isset($soapE->detail) && isset($soapE->detail->authenticationFault))
                {
                  $counter++;
                  if($counter < 3)
                  {  
                    switch($soapE->detail->authenticationFault->errorCode)
                    {
                      case 'LOGIN_7':
                        $this->login->refresh();
                        continue 4;
                        break;
                      default:
                        $newMessage = $soapE->detail->authenticationFault->errorMessage;
                        break;
                    }
                  } 
                  else
                    $newMessage = 'Maximum retries exceeded: ' . $soapE->detail->authenticationFault->errorMessage;
                }
                else
                  $newMessage = 'Something went wrong, please use the Exception trace to find out. ' . $soapE->messageField;
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
      $stop = true;
    }

    foreach($result->parcelShop as $parcelShop)
    {
      $this->results[$parcelShop->parcelShopId] = $parcelShop;
    }
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

      return $url . self::WEBSERVICE_PARCELSHOP;
  }
  
  private function logTime($time)
  {
    $params['entry.1319880751'] = $this->login->url;
    $params['entry.2100714811'] = self::WEBSERVICE_PARCELSHOP;
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
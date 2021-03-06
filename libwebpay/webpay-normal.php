<?php
require_once('soap/soap-wsse.php');
require_once('soap/soap-validation.php');
require_once('soap/soapclient.php');

class getTransactionResult
{
    var $tokenInput; //string
}
class getTransactionResultResponse
{
    var $return; //transactionResultOutput
}
class transactionResultOutput
{
    var $accountingDate; //string
    var $buyOrder; //string
    var $cardDetail; //cardDetail
    var $detailOutput; //wsTransactionDetailOutput
    var $sessionId; //string
    var $transactionDate; //dateTime
    var $urlRedirection; //string
    var $VCI; //string
}
class cardDetail
{
    var $cardNumber; //string
    var $cardExpirationDate; //string
}
class wsTransactionDetailOutput
{
    var $authorizationCode; //string
    var $paymentTypeCode; //string
    var $responseCode; //int
}
class wsTransactionDetail
{
    var $sharesAmount; //decimal
    var $sharesNumber; //int
    var $amount; //decimal
    var $commerceCode; //string
    var $buyOrder; //string
}
class acknowledgeTransaction
{
    var $tokenInput; //string
}
class acknowledgeTransactionResponse
{
}
class initTransaction
{
    var $wsInitTransactionInput; //wsInitTransactionInput
}
class wsInitTransactionInput
{
    var $wSTransactionType; //wsTransactionType
    var $commerceId; //string
    var $buyOrder; //string
    var $sessionId; //string
    var $returnURL; //anyURI
    var $finalURL; //anyURI
    var $transactionDetails; //wsTransactionDetail
    var $wPMDetail; //wpmDetailInput
}
class wpmDetailInput
{
    var $serviceId; //string
    var $cardHolderId; //string
    var $cardHolderName; //string
    var $cardHolderLastName1; //string
    var $cardHolderLastName2; //string
    var $cardHolderMail; //string
    var $cellPhoneNumber; //string
    var $expirationDate; //dateTime
    var $commerceMail; //string
    var $ufFlag; //boolean
}
class initTransactionResponse
{
    var $return; //wsInitTransactionOutput
}
class wsInitTransactionOutput
{
    var $token; //string
    var $url; //string
}

class WebPayNormal
{
    var $config;
    var $soapClient;
    private static $WSDL_URL_NORMAL = array(
            "INTEGRACION"   => "https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl",
            "CERTIFICACION" => "https://webpay3gint.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl",
            "PRODUCCION"    => "https://webpay3g.transbank.cl/WSWebpayTransaction/cxf/WSWebpayService?wsdl",
	);

	private static $RESULT_CODES = array(
		 "0" => "Transacci??n aprobada",
		"-1" => "Rechazo de transacci??n",
		"-2" => "Transacci??n debe reintentarse",
		"-3" => "Error en transacci??n",
		"-4" => "Rechazo de transacci??n",
		"-5" => "Rechazo por error de tasa",
		"-6" => "Excede cupo m??ximo mensual",
		"-7" => "Excede l??mite diario por transacci??n",
		"-8" => "Rubro no autorizado",
	);

    private static $classmap = array('getTransactionResult' => 'getTransactionResult', 'getTransactionResultResponse' => 'getTransactionResultResponse', 'transactionResultOutput' => 'transactionResultOutput', 'cardDetail' => 'cardDetail', 'wsTransactionDetailOutput' => 'wsTransactionDetailOutput', 'wsTransactionDetail' => 'wsTransactionDetail', 'acknowledgeTransaction' => 'acknowledgeTransaction', 'acknowledgeTransactionResponse' => 'acknowledgeTransactionResponse', 'initTransaction' => 'initTransaction', 'wsInitTransactionInput' => 'wsInitTransactionInput', 'wpmDetailInput' => 'wpmDetailInput', 'initTransactionResponse' => 'initTransactionResponse', 'wsInitTransactionOutput' => 'wsInitTransactionOutput');
    
    function __construct($config)
    {       

		$this->config = $config;
		$privateKey = $this->config->getParam("PRIVATE_KEY");
		$publicCert = $this->config->getParam("PUBLIC_CERT");

		$modo = $this->config->getModo();
		$url = WebPayNormal::$WSDL_URL_NORMAL[$modo];

                $this->soapClient = new WSSecuritySoapClient($url, $privateKey, $publicCert, array(
                    "classmap" => self::$classmap,
                    "trace" => true,
                    "exceptions" => true
                ));
    }
    
    function _getTransactionResult($getTransactionResult)
    {
        
        $getTransactionResultResponse = $this->soapClient->getTransactionResult($getTransactionResult);
        return $getTransactionResultResponse;
        
    }

    function _acknowledgeTransaction($acknowledgeTransaction)
    {
        
        $acknowledgeTransactionResponse = $this->soapClient->acknowledgeTransaction($acknowledgeTransaction);
        return $acknowledgeTransactionResponse;
        
    }

    function _initTransaction($initTransaction)
    {
        

        $initTransactionResponse = $this->soapClient->initTransaction($initTransaction);
        return $initTransactionResponse;
        
    }

    function _getReason($code){
		return WebPayNormal::$RESULT_CODES[$code];
	}

	public function initTransaction($amount, $sessionId="", $ordenCompra="0", $urlFinal){
		try{
			$error = array();
			$wsInitTransactionInput = new wsInitTransactionInput();
		
			$wsInitTransactionInput->wSTransactionType = "TR_NORMAL_WS";
			$wsInitTransactionInput->sessionId = $sessionId;
			$wsInitTransactionInput->buyOrder = $ordenCompra;
			$wsInitTransactionInput->returnURL = $this->config->getParam("URL_RETURN");
			//$wsInitTransactionInput->finalURL = $this->config->getParam("URL_FINAL");
			$wsInitTransactionInput->finalURL = $urlFinal;

			$wsTransactionDetail = new wsTransactionDetail();
			$wsTransactionDetail->commerceCode = $this->config->getParam("CODIGO_COMERCIO");
			$wsTransactionDetail->buyOrder = $ordenCompra;
			$wsTransactionDetail->amount = $amount;
	//		$wsTransactionDetail->sharesNumber = $shareNumber;
	//		$wsTransactionDetail->sharesAmount = $shareAmount;

			$wsInitTransactionInput->transactionDetails = $wsTransactionDetail;
			
			$initTransactionResponse = $this->_initTransaction(
				array("wsInitTransactionInput" => $wsInitTransactionInput)
			);
			$xmlResponse = $this->soapClient->__getLastResponse();
			
			$soapValidation = new SoapValidation($xmlResponse, $this->config->getParam("WEBPAY_CERT"));
			
			$validationResult = $soapValidation->getValidationResult();
			


		    if ($validationResult === TRUE){

				$wsInitTransactionOutput = $initTransactionResponse->return;            				
				return array ( 
					"url" => $wsInitTransactionOutput->url,
					"token_ws" => $wsInitTransactionOutput->token
				);				
		    }
		    else{			
				$error["error"] = "Error validando conexi??n a Webpay";
				$error["detail"] = "No se puede validar la respuesta usando certificado " . WebPaySOAP::getConfig("WEBPAY_CERT");				
		    }
		}catch(Exception $e){
			$error["error"] = "Error conectando a Webpay";
			$error["detail"] = $e->getMessage();
		}
		return $error;
	}

	public function getTransactionResult($token){		
		$getTransactionResult = new getTransactionResult();
		$getTransactionResult->tokenInput = $token;
		$getTransactionResultResponse = $this->_getTransactionResult($getTransactionResult);
		
		$xmlResponse = $this->soapClient->__getLastResponse();
		$soapValidation = new SoapValidation($xmlResponse, $this->config->getParam("WEBPAY_CERT"));
		$validationResult = $soapValidation->getValidationResult();
        if ($validationResult === TRUE){
			$result = $getTransactionResultResponse->return;
			/** Avisar a transbank que transaccion esta OK */
			if ($this->acknowledgeTransaction($token)){
				/** Ver si transaccion fue exitosa */
				$resultCode = $result->detailOutput->responseCode;
				if ( ($result->VCI == "TSY" || $result->VCI == "") && $resultCode == 0){
					return $result;
					//$result["aaa"] = "OK";
/*
TSY: Autenticaci??n exitosa
TSN: autenticaci??n fallida.
TO: Tiempo m??ximo excedido para autenticaci??n.
ABO: Autenticaci??n abortada por tarjetahabiente.
U3: Error interno en la autenticaci??n.
Puede ser vac??o si la transacci??n no se autentico.

0 Transacci??n aprobada.
-1 Rechazo de transacci??n.
-2 Transacci??n debe reintentarse.
-3 Error en transacci??n.
-4 Rechazo de transacci??n.
-5 Rechazo por error de tasa.
-6 Excede cupo m??ximo mensual.
-7 Excede l??mite diario por transacci??n.
-8 Rubro no autorizado.
*/
				}
				else{
					$result->detailOutput->responseDescription = $this->_getReason($resultCode);
					return $result;					
				}
				
			}
			else{
				return array("error" => "Error eviando ACK a Webpay");
			}
		}		
		return array("error" => "Error validando transacci??n en Webpay");
	}


	public function acknowledgeTransaction($token){
		$acknowledgeTransaction = new acknowledgeTransaction();
		$acknowledgeTransaction->tokenInput = $token;
		$acknowledgeTransactionResponse = $this->_acknowledgeTransaction($acknowledgeTransaction);

		$xmlResponse = $this->soapClient->__getLastResponse();
		$soapValidation = new SoapValidation($xmlResponse, $this->config->getParam("WEBPAY_CERT"));
		$validationResult = $soapValidation->getValidationResult();
        return $validationResult === TRUE;
	}


}


?>

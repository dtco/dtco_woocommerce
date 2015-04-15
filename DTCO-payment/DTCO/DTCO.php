<?php

class DTCO{

	public function createPayment($post){
		$toURL = 'https://merchant.dtco.co/payment_encode';
		
		$ch = curl_init();
		
		$options = array(
			CURLOPT_URL 			=> $toURL,
			CURLOPT_HEADER 			=> false,
			CURLOPT_RETURNTRANSFER 	=> true,
			CURLOPT_POST 			=> true,
			CURLOPT_POSTFIELDS 		=> http_build_query($post),
		);
		
		curl_setopt_array($ch, $options);
		
		$response = curl_exec($ch); 
		curl_close($ch);
		
		return $response;
	}
	
}

?>
<?php
class PayMeApi
{
	public static function createSale($order,$post,$payme)
	{
		$products = array();
		foreach ($order->get_items() as $item) {
			$products[] = $item['name'];
		}

		$params = array(
			"language" => $payme->lang,
			"currency" => $payme->currency,
			"seller_payme_id" => $payme->debug ? $payme->debugkey : $payme->productionkey,
			'product_name' => implode(";", $products),
			'sale_price' => round($order->get_total() * 100),
			'installments' => PayMeHelper::maxPaymentsNumber($order->get_total(),$payme),
			'sale_callback_url' => $payme->notify_url.'?action=callback&order='.(int)($order->get_id()),
			'sale_return_url' => $payme->notify_url.'?action=return&order='.(int)($order->get_id()),
			'transaction_id' => $order->get_id()
		);
		
		if (trim($payme->callback)) {
			$params["callback"] = $payme->callback;
		}
		if (trim($payme->layout)) {
			$params["layout"] = $payme->layout;
		}
		
		$report = self::request($params,$payme->url_generate_sale,$payme);
		return $report;
	}
	
	public static function createCustomSale($order,$payme,$installments)
	{
		$products = array();
		foreach ($order->get_items() as $item) {
			$products[] = $item['name'];
		}

		$params = array(
			"language" => $payme->lang,
			"currency" => $payme->currency,
			"seller_payme_id" => $payme->debug ? $payme->debugkey : $payme->productionkey,
			'product_name' => implode(";", $products),
			'sale_price' => round($order->get_total() * 100),
			'installments' => $installments,
			'transaction_id' => $order->get_id()
		);
		
		$report = self::request($params,$payme->url_generate_sale,$payme);
		return $report;
	}
	
	public static function paySale($order,$payme,$payme_sale_id,$card_number,$card_expiry,$card_cvc,$card_social_id)
	{
		$card_number = str_replace(' ','',$card_number);
		$card_expiry = str_replace('/','',$card_expiry);
		
		$params = array(
			"payme_sale_id" => $payme_sale_id,
			"credit_card_number" => $card_number,
			"credit_card_cvv" => $card_cvc,
			'credit_card_exp' => $card_expiry,
			'buyer_phone' => $order->get_billing_phone(),
			'buyer_email' => $order->get_billing_email(),
			'buyer_name' => $order->get_billing_first_name().' '.$order->get_billing_last_name()
		);
		if (!empty($card_social_id)) {
			$params['buyer_social_id'] = $card_social_id;
		}
		
		$report = self::request($params,$payme->url_pay_sale,$payme);
		return $report;
	}
	
	public static function request($params,$url,$payme) 
	{
        PayMeHelper::log(json_encode($params), "Request",$payme);

		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json"
        ));
        $response = curl_exec($ch);
		if(!$response) {
			PayMeHelper::log(curl_error($ch), "Report Error",$payme);
		}
        curl_close($ch);
		
        $result = json_decode($response, true);
        PayMeHelper::log($result, "Report",$payme);
        return $result;
    }
}

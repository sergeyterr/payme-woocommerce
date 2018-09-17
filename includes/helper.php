<?php
class PayMeHelper
{
	public static function maxPaymentsNumber($amount = false,$payme) 
	{
		/*if($payme->currency != 'ILS'){
			return 1;
		}*/
		
        if ($payme->installments_setting == 1) {
			return $payme->installments_preset;
		}
        
        foreach (array_reverse(json_decode($payme->installments, true)) as $index => $item) {
            if ($amount >= $item[0] && $amount <= $item[1])
                return 100 + (4 - $index) * 3;
        }
        return 1;
    }
	
	public static function getIFrameUrl($order, $url, $payme) 
	{
		$params = array(
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
        );
        
        self::log($params, 'GetIFrameUrl',$payme);
        $con = array();
        foreach ($params as $key => $param) {
            $con[] = implode("=", array($key, $param));
        }
        return $url."?".implode("&", $con);
	}
	
	public static function log($text, $title,$payme) 
	{
		if ($payme->log == 'yes') {
			$filename = payme_DIR.'/debug';
			$fp = fopen($filename, 'a+');
			fwrite($fp, $title);
			fwrite($fp, "\n");
			fwrite($fp, "***************************");
			fwrite($fp, "\n");
			fwrite($fp, date("Y-m-d H:i:s").": ");
			fwrite($fp, print_r($text, TRUE));
			fwrite($fp, "\n");
			fwrite($fp, "\n");
			fclose($fp);
		}
    }
}

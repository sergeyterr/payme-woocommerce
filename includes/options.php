<?php
class PayMeOptions
{
	public static function getYesNo()
	{
		return array(
			"no" => "No",
			"yes" => "Yes"
		);
	}
	
	public static function getLang()
	{
		return array(
			"en" => "English",
			"he" => "Hebrew"
		);
	}
	
	public static function getCurrency()
	{
		return array(
            "ILS" => "ILS",
            "USD" => "USD",
            "EUR" => "EUR"
        );
	}
	
	public static function getSettings()
	{
		return array(
            "1" => "Preset",
            "2" => "According to Order Total"
        );
	}
	
	public static function getDebug()
	{
		return array(
           true => "Staging",
           false => "Production"
        );
	}
	
	public static function getFullscreen()
	{
		return array(
           'regular' => "Regular Payment Form",
           'overlay' => "Overlay Payment Form",
		   'custom' => "Custom Payment Form"
        );
	}
	
	public static function getPreset()
	{
		$result = array(1 => "1");
        foreach (range(1, 4) as $index) {
            $result[$index * 3] = $index * 3;
        }
        return $result;
	}
}

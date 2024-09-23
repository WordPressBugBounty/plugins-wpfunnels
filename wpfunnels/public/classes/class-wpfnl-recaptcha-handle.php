<?php
/**
 * Handle Recaptcha
 * 
 * @package
 */
namespace WPFunnels\Frontend\Recaptcha;

Class Google_Recaptcha_Handler{

	/**
	 * Response G- Recaptcha
     *
	 * @param string $secretKey
	 * @param $token
	 * 
	 * @since 2.5.5
	 */
	public static function get_response_recaptcha( $secretKey = '', $token ){
		$ip          = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR']: null;
		$url = "https://www.google.com/recaptcha/api/siteverify";
		$data = array('secret' => $secretKey, 'response' => $token, 'remoteip'=> $ip);

		$query = http_build_query($data);
		$options = array(
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
			'http' => array(
				'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
					"Content-Length: ".strlen($query)."\r\n".
					"User-Agent:MyAgent/1.0\r\n",
				'method'  => "POST",
				'content' => $query,
			),
		);
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		return json_decode($result);
	}
}

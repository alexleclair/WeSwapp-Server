<?php
class Response{
	public static function buildResponse($data=null, $errorCode=200, $error=NULL){
		$errorMessages = array(
			'400'=>'Bad Request',
			'403'=>'Forbidden',
			'404'=>'Not Found',
			'500'=>'Internal error',
		);

		$error = isset($error) ? $error : (isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : NULL);

		$response = array(
			'ts'=>time(),
			'code'=>$errorCode,
			'response'=>$data,
			'error'=>$error
		);

		return $response;
	}

	public static function toJSON($data=null,$errorCode=200,$error=null){
		return json_encode(self::buildResponse($data, $errorCode, $error));
	}
}
?>
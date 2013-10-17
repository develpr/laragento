<?php

class RestResponse {

    private $errorResponse;

    function __construct()
    {
        $this->errorResponse = array(
            'success' => null,
            'status' => 0,
            'message' => '',
            'developerMessage' => '',
            'moreInfo' => '', //a url to a part of documentation relavent to message
            'validationErrors' => array(),
        );
    }

    public static function notFound($message, $developerMessage, $moreInfo)
    {
        $errorResponse = array(
            'success' => false,
            'status' => 404,
            'message' => $message,
            'developerMessage' => $developerMessage,
            'moreInfo' => $moreInfo, //a url to a part of documentation relavent to message
        );

        return $errorResponse;
    }

    public static function validationError($message, $developerMessage, $validationErrors)
    {

    }

}
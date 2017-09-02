<?php

namespace App\Helper;

class Message {
    /**
     * @var error : Whether there was an error or not.
     */
    public $error;
    /**
     * @var code : If there was an error, what is the error code.
     */
    public $code;
    /**
     * @var body : message body(data or error spec)
     */
    public $body;
    private function __construct($error=false, $code=NULL, $body=NULL) {
        $this->error = $error;
        $this->code = $code;
        $this->body = $body;
    }

    public function response() {
        return response()->json($this);
    }

    static public function errorMessage($error_code, $error_spec=NULL) {
        return new Message(true, $error_code, $error_spec);
    }

    static public function dataMessage($body) {
        return new Message(false, NULL, $body);
    }
}

<?php

class TweetFeeder {
    
    /* Constants */
    const HOME_TIMELINE = 0;
    const USER_TIMELINE = 1;
    const MENTIONS_TIMELINE = 2;
    const RETWEETS_OF_ME = 3;
    
    private $tweetType;
    
    private $user;
    private $action;
    private $secret;
    private $database;
    
    private $token;
    private $context;
    
    public function __construct($args) {
        $this->action = $args["action"];
        $this->user = $args["user"];
        $this->secret = $args["secret"];
        $this->database = $args["database"];
    }
    
    /* Obtained from: http://us1.php.net/manual/en/function.openssl-random-pseudo-bytes.php#104322 */
    private function __crypto_rand_secure($min, $max)
    {
        $range = $max - $min;
        if ($range < 0) return $min; // not so random...
        $log = log($range, 2);
        $bytes = (int)($log / 8) + 1; // length in bytes
        $bits = (int)$log + 1; // length in bits
        $filter = (int)(1 << $bits) - 1; // set all lower bits to 1
        do {
            /* WARNING!! openssl_random_pseudo_bytes(...) is only available in PHP >= 5.3 */
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }

    private function generateRandomString($length)
    {
        $token = "";

        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet .= "0123456789";
        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->__crypto_rand_secure(0, strlen($codeAlphabet))];
        }
        return $token;
    }
    
    public function initialize() {
        /* TODO: Get user token from database. */
        $token = "";
        
        if ($this->action == "user_timeline") {
            $this->tweetType = TweetFeeder::USER_TIMELINE;
            
            $nonce = $this->generateRandomString(32);
            $timestamp = time();
            
            $parameterString = 'oauth_consumer_key=' . $this->secret->consumerSecret . '&' .
                               'oauth_nonce=' . $nonce . '&' .
                               'oauth_signature_method=HMAC-SHA1&' .
                               'oauth_timestamp=' . $timestamp . '' .
                               'oauth_token=' . $token .
                               'oauth_version=1.1';
            
            $baseSignature  = 'GET&' . rawurlencode($url) . '&' . rawurlencode($parameterString);
            
            $authorizationString = 'Authorization: OAuth ' . 
                'oauth_consumer_key="' . $this->secret->consumerSecret . '", ' .
                'oauth_nonce="' . $nonce . '", ' .
                'oauth_signature="' . base64_encode(hash_hmac("sha1", $baseSignature, $this->secret->consumerSecret)) . '", ' . 
                'oauth_signature_method="HMAC-SHA1", ' .
                'oauth_timestamp="' . $timestamp . '", ' .
                'oauth_token="' . $token . '", ' .
                'oauth_version="1.0"'
                ;
            
            /* Finally, send the request! */
            
            $options = array(
                'http' => array(
                    'method' => "GET",
                    'header' => $authorizationString . '\r\n'
                )
            );
            
            $this->context = stream_context_create($options);
        }
    }
    
    public function getResponse($url) {
        $results = file_get_contents($url, false, $this->context);
        return $results;
    }
    
    public function feed() {
        $this->initialize();
        
        /* Allow Cross-Origin Resource Sharing */
        header("Access-Control-Allow-Origin: *");
        header("Content-type: application/json");
 
        switch ($this->tweetType) {
            case TweetFeeder::USER_TIMELINE:
                echo $this->getResponse(API_ADDRESS + 'statuses/user_timeline.json');
                break;
        }    
    }
}
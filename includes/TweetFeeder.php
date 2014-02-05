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
    private $tokenSecret;
    
    public function __construct($args) {
        $this->action = $args["action"];
        $this->user = $args["user"];
        $this->secret = $args["secret"];
        $this->database = $args["database"];
        
        $this->initialize();
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
        $result = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet .= "0123456789";
        for ($i = 0; $i < $length; $i++) {
            $result .= $codeAlphabet[$this->__crypto_rand_secure(0, strlen($codeAlphabet))];
        }
        return $result;
    }
    
    public function getUrl() {
        switch ($this->tweetType) {
            case TweetFeeder::USER_TIMELINE:
                return API_ADDRESS . 'statuses/user_timeline.json';
        }
    }
    
    public function getAuthorizationHeader() {
        /* This function can only be called after everything initialized. */
        
        $nonce = $this->generateRandomString(32);
        $timestamp = time();

        $parameterString = 'oauth_consumer_key=' . $this->secret->consumerKey . '&' .
                           'oauth_nonce=' . $nonce . '&' .
                           'oauth_signature_method=HMAC-SHA1&' .
                           'oauth_timestamp=' . $timestamp . '&' .
                           'oauth_token=' . $this->token . '&' .
                           'oauth_version=1.0';

        $baseSignature  = 'GET&' . rawurlencode($this->getUrl()) . '&' . rawurlencode($parameterString);

        $authorizationString = 'Authorization: OAuth ' . 
            'oauth_consumer_key="' . $this->secret->consumerKey . '", ' .
            'oauth_nonce="' . $nonce . '", ' .
            'oauth_signature="' . rawurlencode(base64_encode(hash_hmac("sha1", $baseSignature, rawurlencode($this->secret->consumerSecret) . '&' . rawurlencode($this->tokenSecret), true))) . '", ' .  
            'oauth_signature_method="HMAC-SHA1", ' .
            'oauth_timestamp="' . $timestamp . '", ' .
            'oauth_token="' . $this->token . '", ' .
            'oauth_version="1.0"'
        ;
        
        return $authorizationString;
    }
    
    public function initialize() {
        /* TODO: Get user token and token secret from database. */
        $this->token = "";
        $this->tokenSecret = "";
        
        if ($this->action == "user_timeline") {
            $this->tweetType = TweetFeeder::USER_TIMELINE;            
        }
    }
    
    public function getResponse($url, $authorizationString) {
        $ch = curl_init();
                
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            $authorizationString
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        echo $result;
        
        curl_close($ch);
    }
    
    public function feed() {        
        /* Allow Cross-Origin Resource Sharing */
        header("Access-Control-Allow-Origin: *");
        header("Content-type: application/json");
        
        $this->getResponse($this->getUrl(), $this->getAuthorizationHeader());    
    }
}

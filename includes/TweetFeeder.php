<?php

class TweetFeeder {
    
    private $user;
    private $action;
    private $secret;
    private $database;
    
    private $tweets;
    
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
        if ($this->action == "user_timeline") {
            $url = API_ADDRESS . 'statuses/user_timeline.json';
            
            $authorizationString = 'Authorization: OAuth ' . 
                'oauth_consumer_key="' . '", ' .
                'oauth_nonce="' . $this->generateRandomString(32) . '", ' .
                'oauth_signature="' . hash_hmac("sha1", $url, $this->secret->consumerSecret) . '", ' . 
                'oauth_signature_method="' . '", ' .
                'oauth_timestamp="' . '", ' .
                'oauth_token="' . '", ' .
                'oauth_version="1.0"'
                ;
            
        }
    }
    
    public function feed() {
        /* Allow Cross-Origin Resource Sharing */
        header("Access-Control-Allow-Origin: *");
        
    }
}
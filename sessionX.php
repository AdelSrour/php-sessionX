<?php
/*
 * SessionX class.
 *
 * (c) Adel srour <contact@adel.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * GitHub Repository: https://github.com/AdelSrour/php-sessionX
 */

    Class sessionX{
        private $session_storage;
        private $domain_name;
        private $salt;

        private $cookie_name_token;
        private $cookie_lifetime_token;
        private $session_token;

        private $cookie_name_sync;
        private $cookie_lifetime_sync;
        private $session_sync;

        private $time;
        private $global_var;
        private $cookie_update;
        private $cookie_update_period;

        function __construct($settings){
            $this->time                  = time();
            $this->session_storage       = $settings["storage"];
            $this->global_var            = $settings["global_token_var"];
            $this->domain_name           = $_SERVER['HTTP_HOST'];
            $this->salt                  = $settings["salt"];

            $this->cookie_name_token     = $settings["session_name"];
            $this->cookie_lifetime_token = $settings["session_expire"];
            $this->session_token         = $_COOKIE[$this->cookie_name_token];

            $this->cookie_name_sync      = $settings["sync_name"];
            $this->cookie_lifetime_sync  = $settings["sync_expire"];    
            $this->session_sync          = $_COOKIE[$this->cookie_name_sync];

            $this->cookie_update         = $settings["session_update_var"];
            $this->cookie_update_period  = $settings["session_update_timer"];
            $GLOBALS[$this->global_var]  = $this->session_token;   
        }

        public function init(){
            if ($this->validate() == TRUE){
               return $this->start();
            }
            $this->set_auth_token(0);
            return FALSE;
        }

        private function validate(){
            if (preg_match('/^([a-f0-9]{64})$/', $this->session_token) != TRUE) {
                return FALSE;
            }
            if (preg_match('/^([a-f0-9]{40})$/', $this->session_sync) != TRUE) {
                return FALSE;
            }
            if ($this->session_sync != $this->gen_sync_token($this->session_token)){
                return FALSE;
            }
            return TRUE;
        }

        private function start(){
            if (file_exists($this->session_storage."/sess_".$this->session_token) == FALSE){
                $this->set_auth_token(1);
                session_id($this->session_token);
            }
            
            $session_status = session_start([
                "name" => $this->cookie_name_token,
                "use_strict_mode" => 0,
                "use_only_cookies" => 1,
                "use_cookies" => 1,
                "cookie_secure" => 1,
                "cookie_lifetime" => $this->cookie_lifetime_token,
                "cookie_path" => "/",
                "cookie_domain" => $this->domain_name,
                "cookie_httponly" => 1,
                "cookie_samesite" => "lax",
                "gc_probability" => 0,
                "gc_maxlifetime" => 10800,
                "gc_divisor" => 100,
                "use_trans_sid" => 0,
                "sid_length" => 32,
                "sid_bits_per_character" => 6,
                "save_path" => $this->session_storage
            ]);

            if ($session_status != TRUE){
                return FALSE;
            }

            if (is_numeric($_SESSION[$this->cookie_update])){
                if  ($_SESSION[$this->cookie_update] <= $this->time){
                    $this->set_auth_token(1);
                }
            }else{
                $_SESSION[$this->cookie_update] = $this->cookie_update_period+$this->time;
            }

            return $session_status;
        }

        private function set_auth_token($refresh = 0){
            if ($refresh == 1){
                $token_random                   = $this->session_token;
                $token_sync                     = $this->session_sync;
                $_SESSION[$this->cookie_update] = $this->cookie_update_period+$this->time;
            }else{
                $token_random = $this->gen_auth_token();
                $token_sync   = $this->gen_sync_token($token_random);
            }
            setcookie(
                $this->cookie_name_token,
                $token_random,
                [
                    "expires"  => $this->cookie_lifetime_token+$this->time,
                    "path"     => "/",
                    "domain"   => $this->domain_name,
                    "secure"   => 1,
                    "httponly" => 1,
                    "samesite" => "lax"
                ]
            );

            setcookie(
                $this->cookie_name_sync,
                $token_sync,
                [
                    "expires"  => $this->cookie_lifetime_sync+$this->time,
                    "path"     => "/",
                    "domain"   => $this->domain_name,
                    "secure"   => 1,
                    "httponly" => 1,
                    "samesite" => "lax"
                ]
            );
            $GLOBALS[$this->global_var] = $token_random;
        }

        private function gen_auth_token(){
            return hash("sha256", microtime(true).session_create_id().rand());
        }

        private function gen_sync_token($auth_token){
            return hash("sha1", $this->salt.$auth_token.$this->domain_name);
        }
    }
?>
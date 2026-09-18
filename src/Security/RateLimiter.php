<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Security;final class RateLimiter{public function allow(string $group,int $limit,int $window):bool{$ip=$_SERVER['REMOTE_ADDR']??'unknown';$key='acorn_hc_rl_'.hash('sha256',$group.'|'.$ip);$n=(int)get_transient($key);if($n>=$limit)return false;set_transient($key,$n+1,$window);return true;}}

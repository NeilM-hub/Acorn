<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Security;use InvalidArgumentException;final class Honeypot{public static function assertEmpty(string $value):void{if(trim($value)!=='')throw new InvalidArgumentException('Unable to submit.');}}

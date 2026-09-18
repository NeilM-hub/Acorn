<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Domain;use InvalidArgumentException;
final class FindingStatus{public const ADDRESSED='addressed',REVIEW='review',PRIORITY='priority',NOT_ASSESSED='not_assessed';public static function assert(string $v):string{if(!in_array($v,[self::ADDRESSED,self::REVIEW,self::PRIORITY,self::NOT_ASSESSED],true))throw new InvalidArgumentException('Invalid finding status.');return $v;}}

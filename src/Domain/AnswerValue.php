<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Domain;use InvalidArgumentException;
final class AnswerValue{public const YES='yes',PARTLY='partly',NO='no',NOT_SURE='not_sure';public static function assert(string $v):string{if(!in_array($v,[self::YES,self::PARTLY,self::NO,self::NOT_SURE],true))throw new InvalidArgumentException('Invalid healthcheck answer.');return $v;}}

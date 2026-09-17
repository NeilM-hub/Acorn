<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Domain;final readonly class AssessmentState implements \JsonSerializable{public function __construct(public array $assessment,public array $questions,public array $answers){}public function jsonSerialize():array{return['assessment'=>$this->assessment,'questions'=>$this->questions,'answers'=>$this->answers];}}

<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Domain;final class OverallStatus{public static function fromCounts(int $p,int $r):string{if($p>0)return 'priority_actions_identified';if($r>=3)return 'several_areas_to_review';if($r>0)return 'some_areas_to_review';return 'no_obvious_gaps';}}

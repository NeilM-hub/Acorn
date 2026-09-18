<?php
declare(strict_types=1);namespace Acorn\SafetyHealthcheck\Domain;final class OpportunityTagger{public function tags(array $findings):array{$tags=[];foreach($findings as $f)if(in_array($f['finding_status'],['review','priority'],true))foreach(json_decode($f['recommendation']['service_tags_json']??'[]',true)?:[] as $t)$tags[$t]=true;return array_keys($tags);}}

<?php
declare(strict_types=1);use PHPUnit\Framework\TestCase;use Acorn\SafetyHealthcheck\Domain\OpportunityTagger;final class OpportunityTaggerTest extends TestCase{public function test_tags_only_findings():void{self::assertSame(['fire'],(new OpportunityTagger)->tags([['finding_status'=>'review','recommendation'=>['service_tags_json'=>'["fire"]']]]));}}

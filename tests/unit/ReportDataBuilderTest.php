<?php

declare(strict_types=1);
use Acorn\SafetyHealthcheck\Reports\ReportDataBuilder;use PHPUnit\Framework\TestCase;
final class ReportDataBuilderTest extends TestCase{public function test_all_modules_map_to_one_of_four_customer_pillars():void{$reflection=new ReflectionClass(ReportDataBuilder::class);$map=$reflection->getConstant('MAP');self::assertSame(['health_safety','fire','legionella','asbestos'],array_values(array_unique($map)));self::assertSame('health_safety',$map['specialist']);}}

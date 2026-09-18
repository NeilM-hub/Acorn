<?php

declare(strict_types=1);
use Acorn\SafetyHealthcheck\Reports\ReportDataBuilder;use PHPUnit\Framework\TestCase;
final class ReportDataBuilderTest extends TestCase{public function test_all_modules_map_to_one_of_four_customer_pillars():void{$reflection=new ReflectionClass(ReportDataBuilder::class);$map=$reflection->getConstant('MAP');$actual=array_values(array_unique($map));$expected=['health_safety','fire','legionella','asbestos'];sort($actual);sort($expected);self::assertSame($expected,$actual);self::assertSame('health_safety',$map['specialist']);}}

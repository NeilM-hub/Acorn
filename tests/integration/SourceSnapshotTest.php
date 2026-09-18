<?php

declare(strict_types=1);

use Acorn\SafetyHealthcheck\Assessment\{AssessmentService, SnapshotBuilder};

final class SourceSnapshotTest extends IntegrationTestCase
{
    /** @dataProvider jurisdictionSourceProvider */
    public function test_positive_findings_snapshot_jurisdiction_source(string $jurisdiction, string $questionKey, string $domain): void
    {
        $snapshot = $this->snapshot($jurisdiction, $questionKey, 'yes');
        $finding = $this->finding($snapshot, $questionKey);
        self::assertStringContainsString($domain, $finding['source']['url']);
    }

    public function test_non_positive_fire_finding_snapshots_jurisdiction_source(): void
    {
        $snapshot = $this->snapshot('scotland', 'F01_FIRE_RESPONSIBILITY', 'no');
        $finding = $this->finding($snapshot, 'F01_FIRE_RESPONSIBILITY');
        self::assertStringContainsString('gov.scot', $finding['recommendation']['source']['url']);
    }

    public function jurisdictionSourceProvider(): array
    {
        return [
            ['england', 'F01_FIRE_RESPONSIBILITY', 'gov.uk'],
            ['wales', 'F01_FIRE_RESPONSIBILITY', 'gov.wales'],
            ['scotland', 'F01_FIRE_RESPONSIBILITY', 'gov.scot'],
            ['northern_ireland', 'F01_FIRE_RESPONSIBILITY', 'nifrs.org'],
            ['northern_ireland', 'L01_LEGIONELLA_RA', 'hseni.gov.uk'],
            ['northern_ireland', 'AS01_ASBESTOS_INFORMATION', 'hseni.gov.uk'],
        ];
    }

    private function snapshot(string $jurisdiction, string $target, string $targetAnswer): array
    {
        $service = new AssessmentService();
        $start = $service->start();
        $state = $service->updateProfile($start['token'], $this->profile([
            'jurisdiction' => $jurisdiction,
            'water_system_responsibility' => 'yes',
            'maintenance_repair_responsibility' => 'yes',
            'building_pre_2000' => 'yes',
        ]));
        foreach ($state->questions as $question) {
            $service->saveAnswer($start['token'], $question['question_key'], $question['question_key'] === $target ? $targetAnswer : 'yes');
        }
        $service->assess($start['token']);
        $assessment = (new \Acorn\SafetyHealthcheck\Assessment\AssessmentRepository())->findByToken($start['token']);
        return (new SnapshotBuilder())->build((int) $assessment['id']);
    }

    private function finding(array $snapshot, string $key): array
    {
        foreach ($snapshot['findings'] as $finding) if ($finding['question_key'] === $key) return $finding;
        self::fail("Missing finding $key");
    }
}

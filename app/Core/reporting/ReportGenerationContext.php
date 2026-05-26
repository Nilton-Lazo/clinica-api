<?php

namespace App\Core\reporting;

use App\Models\User;

final class ReportGenerationContext
{
    public function __construct(
        public readonly string $generatedAt,
        public readonly string $generatedBy,
        public readonly InstitutionReportContext $institution,
    ) {}

    public static function forUser(?User $user): self
    {
        $tz = (string) config('app.timezone', 'UTC');
        $at = now()->timezone($tz)->format('d/m/Y H:i:s');

        $by = 'Sistema';
        if ($user !== null) {
            $username = trim((string) ($user->username ?? ''));
            $by = $username !== '' ? $username : (string) $user->id;
        }

        return new self(
            generatedAt: $at,
            generatedBy: $by,
            institution: InstitutionReportContext::fromDatabase(),
        );
    }

    public function toArray(): array
    {
        return [
            'generated_at' => $this->generatedAt,
            'generated_by' => $this->generatedBy,
            'institution' => $this->institution->toArray(),
        ];
    }
}

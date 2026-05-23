<?php

namespace App\Core\reporting;

use App\Core\clinica\models\Clinica;
use App\Core\clinica\services\ClinicaConfigService;

final class InstitutionReportContext
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $ruc = null,
        public readonly ?string $address = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $website = null,
        public readonly ?string $logoUrl = null,
        public readonly ?string $logoAbsolutePath = null,
        public readonly ?string $logoDataUri = null,
    ) {}

    public static function fromClinica(Clinica $clinica): self
    {
        [$logoUrl, $logoAbsolutePath] = ClinicaLogoResolver::resolve($clinica->logo_path);

        return new self(
            name: trim((string) $clinica->razon_social),
            ruc: self::nullableString($clinica->ruc),
            address: self::nullableString($clinica->direccion),
            phone: self::nullableString($clinica->telefono),
            email: self::nullableString($clinica->email),
            website: self::nullableString($clinica->sitio_web),
            logoUrl: $logoUrl,
            logoAbsolutePath: $logoAbsolutePath,
            logoDataUri: null,
        );
    }

    public static function fromDatabase(): self
    {
        return app(ClinicaConfigService::class)->reportContext();
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'ruc' => $this->ruc,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'logo_url' => $this->logoUrl,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $t = trim((string) $value);

        return $t !== '' ? $t : null;
    }
}

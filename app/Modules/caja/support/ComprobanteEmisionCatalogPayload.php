<?php

namespace App\Modules\caja\support;

use App\Core\support\ComprobanteEmisionEstado;
use App\Core\support\ComprobanteEmisionOrigen;
use App\Modules\admision\models\CajaTipoDocumento;

final class ComprobanteEmisionCatalogPayload
{
    private static function codigosTiposDocumentoEmision(): array
    {
        $raw = config('caja.emision_tipos_documento_codigos', ['001', '002', '005']);
        if (!is_array($raw)) {
            return ['001', '002', '005'];
        }
        $codigos = array_values(array_filter(
            array_map(static fn ($c) => trim((string) $c), $raw),
            static fn ($c) => $c !== ''
        ));

        return $codigos !== [] ? $codigos : ['001', '002', '005'];
    }

    public static function build(): array
    {
        $codigos = self::codigosTiposDocumentoEmision();
        $rows = CajaTipoDocumento::query()
            ->activos()
            ->whereIn('codigo', $codigos)
            ->get(['id', 'codigo', 'descripcion']);

        $byCodigo = $rows->keyBy(static fn (CajaTipoDocumento $t) => trim((string) $t->codigo));

        $tipos_documento = [];
        foreach ($codigos as $cod) {
            $codKey = trim((string) $cod);
            $t = $byCodigo->get($codKey);
            if (!$t instanceof CajaTipoDocumento) {
                continue;
            }
            $codStr = trim((string) $t->codigo);
            $desc = trim((string) $t->descripcion);
            $tipos_documento[] = [
                'value' => (string) $t->id,
                'label' => $codStr !== '' ? "{$codStr} · {$desc}" : $desc,
                'codigo' => $codStr,
            ];
        }

        return [
            'origenes' => array_map(
                fn (ComprobanteEmisionOrigen $c) => ['value' => $c->value, 'label' => $c->label()],
                ComprobanteEmisionOrigen::cases()
            ),
            'tipos_documento' => $tipos_documento,
            'estados_emision' => array_map(
                fn (ComprobanteEmisionEstado $c) => ['value' => $c->value, 'label' => $c->label()],
                ComprobanteEmisionEstado::cases()
            ),
        ];
    }
}

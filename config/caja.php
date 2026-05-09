<?php

return [
    /*
     * Códigos de `caja_tipos_documento` que aparecen en Emisión de comprobantes.
     * Por defecto: Factura (001), Boleta de venta (002), Recibo caja (005).
     * Sobrescribir con CAJA_EMISION_TIPOS_DOC="001,002,006" si cambian en BD.
     */
    'emision_tipos_documento_codigos' => array_values(array_filter(array_map(
        static fn (string $c): string => trim($c),
        explode(',', env('CAJA_EMISION_TIPOS_DOC', '001,002,005'))
    ), static fn (string $c): bool => $c !== '')),
    'emision_recibo_caja_tipo_documento_codigo' => trim((string) env('CAJA_EMISION_RECIBO_CAJA_TIPO_DOC_CODIGO', '005')),
    'emision_adelanto_garantia_servicio_codigo' => trim((string) env('CAJA_EMISION_ADELANTO_GARANTIA_SERVICIO_CODIGO', '00.18.03')),
];

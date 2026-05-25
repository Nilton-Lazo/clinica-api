<?php

namespace App\Exceptions;

use App\Core\audit\Facades\Audit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (AuthorizationException $e, $request) {
            Audit::log(
                action: 'auth.denied',
                actionLabel: 'Acceso denegado',
                metadata: [
                    'exception' => class_basename($e),
                ],
                result: 'failed',
                statusCode: 403
            );

            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        });

        $this->renderable(function (ValidationException $e, $request) {
            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => $e->errors(),
            ], 422);
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            Audit::log(
                action: 'auth.unauthenticated',
                actionLabel: 'No autenticado',
                metadata: [
                    'exception' => class_basename($e),
                ],
                result: 'failed',
                statusCode: 401
            );

            return response()->json([
                'message' => 'No autenticado.',
                'code' => 'UNAUTHENTICATED',
            ], 401);
        });

        $this->renderable(function (QueryException $e, $request) {
            if (!$this->isFicherosRequest($request)) {
                return null;
            }

            $error = $this->ficherosDatabaseError($e);
            if ($error === null) {
                return null;
            }

            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => [$error['field'] => [$error['message']]],
            ], 422);
        });

        $this->renderable(function (RuntimeException $e, $request) {
            if (!$this->isFicherosRequest($request)) {
                return null;
            }

            $message = trim($e->getMessage());
            if (!str_starts_with($message, 'No se pudo generar el código')) {
                return null;
            }

            return response()->json([
                'message' => 'Datos inválidos.',
                'errors' => ['codigo' => [$message . ' Actualiza la lista e intenta nuevamente.']],
            ], 422);
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Error HTTP.',
                ], $e->getStatusCode());
            }

            Audit::log(
                action: 'system.exception',
                actionLabel: 'Excepción no controlada',
                metadata: [
                    'exception' => class_basename($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                result: 'failed',
                statusCode: 500
            );

            return response()->json([
                'message' => 'Error interno del servidor.',
            ], 500);
        });
    }

    private function isFicherosRequest($request): bool
    {
        return $request->is('api/ficheros*') || $request->is('ficheros*');
    }

    private function ficherosDatabaseError(QueryException $e): ?array
    {
        $state = (string)($e->errorInfo[0] ?? '');
        $constraint = $this->extractConstraintName((string)($e->errorInfo[2] ?? $e->getMessage()));

        if ($state === '23505') {
            return $this->ficherosUniqueError($constraint);
        }

        if ($state === '23503') {
            return $this->ficherosForeignKeyError($constraint);
        }

        if ($state === '23514') {
            return $this->ficherosCheckError($constraint);
        }

        return null;
    }

    private function extractConstraintName(string $message): ?string
    {
        if (preg_match('/constraint "([^"]+)"/', $message, $matches)) {
            return $matches[1];
        }

        if (preg_match("/for key '([^']+)'/", $message, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function ficherosUniqueError(?string $constraint): array
    {
        $messages = [
            'especialidades_codigo_unique' => ['codigo', 'Ya existe una especialidad con ese código.'],
            'cirugias_codigo_unique' => ['codigo', 'Ya existe una cirugía con ese código.'],
            'consultorios_abreviatura_unique' => ['abreviatura', 'Ya existe un consultorio con esa abreviatura.'],
            'medicos_codigo_unique' => ['codigo', 'Ya existe un médico con ese código.'],
            'medicos_cmp_unique' => ['cmp', 'Ya existe un médico registrado con ese CMP.'],
            'medicos_rne_unique' => ['rne', 'Ya existe un médico registrado con ese RNE.'],
            'turnos_codigo_unique' => ['codigo', 'Ya existe un turno con ese código.'],
            'clientes_codigo_unique' => ['codigo', 'Ya existe un cliente con ese código.'],
            'paquetes_codigo_unique' => ['codigo', 'Ya existe un paquete con ese código.'],
            'tipos_iafas_codigo_unique' => ['codigo', 'Ya existe un tipo de IAFAS con ese código.'],
            'iafas_codigo_unique' => ['codigo', 'Ya existe una IAFAS con ese código.'],
            'iafas_ruc_unique' => ['ruc', 'Ya existe una IAFAS registrada con este RUC.'],
            'contratantes_codigo_unique' => ['codigo', 'Ya existe un contratante con ese código.'],
            'tarifas_codigo_unique' => ['codigo', 'Ya existe una tarifa con ese código.'],
            'tarifas_tarifa_base_unique' => ['tarifa_base', 'Ya existe un tarifario base activo. Solo puede haber uno.'],
            'ux_tarifas_single_base' => ['tarifa_base', 'Ya existe un tarifario base activo. Solo puede haber uno.'],
            'tipos_clientes_codigo_unique' => ['codigo', 'Ya existe un tipo de cliente con ese código.'],
            'tipos_clientes_contratante_id_tarifa_id_unique' => ['contratante_id', 'Ya existe un tipo de cliente para este contratante y tarifa.'],
            'tarifa_categorias_tarifa_id_codigo_unique' => ['codigo', 'Ya existe una categoría con ese código en la tarifa seleccionada.'],
            'tarifa_subcategorias_categoria_id_codigo_unique' => ['codigo', 'Ya existe una subcategoría con ese código en la categoría seleccionada.'],
            'tarifa_servicios_tarifa_id_codigo_unique' => ['codigo', 'Ya existe un servicio con ese código en la tarifa seleccionada.'],
            'tarifa_servicios_tarifa_id_categoria_id_subcategoria_id_servicio_codigo_unique' => ['codigo', 'Ya existe un servicio con ese correlativo en la subcategoría seleccionada.'],
            'ux_tarifa_servicios_nomenclador_notnull' => ['nomenclador', 'Ya existe un servicio con ese nomenclador en la tarifa seleccionada.'],
            'tarifa_recargo_noche_tarifa_id_tarifa_categoria_id_unique' => ['tarifa_categoria_id', 'Ya existe una regla de recargo nocturno para esta categoría.'],
            'ux_paquete_servicio' => ['servicios', 'El servicio seleccionado ya pertenece al paquete.'],
            'idx_tarifa_servicio_unique' => ['codigo_servicio', 'Ya existe un servicio por defecto con ese código en la tarifa seleccionada.'],
            'tipo_emergencia_codigo_unique' => ['codigo', 'Ya existe un tipo de emergencia con ese código.'],
            'topicos_codigo_unique' => ['codigo', 'Ya existe un tópico con ese código.'],
            'tipo_documento_codigo_unique' => ['codigo', 'Ya existe un tipo de documento con ese código.'],
            'documento_atencion_codigo_unique' => ['codigo', 'Ya existe un documento de atención con ese código.'],
            'caja_areas_jefaturas_codigo_unique' => ['codigo', 'Ya existe un área o jefatura con ese código.'],
            'caja_tipos_documento_codigo_unique' => ['codigo', 'Ya existe un tipo de documento de caja con ese código.'],
            'caja_formas_pago_codigo_unique' => ['codigo', 'Ya existe una forma de pago con ese código.'],
            'caja_medios_pago_codigo_unique' => ['codigo', 'Ya existe un medio de pago con ese código.'],
            'caja_bancos_tarjetas_codigo_unique' => ['codigo', 'Ya existe un banco o tarjeta con ese código.'],
            'caja_numeracion_tipo_serie_unique' => ['serie', 'Ya existe una numeración para ese tipo de documento y serie.'],
            'caja_medio_forma_unique' => ['forma_pago_id', 'Esta forma de pago ya está asociada al medio seleccionado.'],
            'caja_banco_tarjeta_forma_unique' => ['forma_pago_id', 'Esta forma de pago ya está asociada al banco o tarjeta seleccionado.'],
            'caja_banco_tarjeta_medio_unique' => ['medio_pago_id', 'Este medio de pago ya está asociado al banco o tarjeta seleccionado.'],
        ];

        [$field, $message] = $messages[$constraint] ?? ['registro', 'Ya existe un registro con los mismos datos únicos. Revisa códigos, RUC, serie o combinación seleccionada.'];

        return ['field' => $field, 'message' => $message];
    }

    private function ficherosForeignKeyError(?string $constraint): array
    {
        $messages = [
            'tarifas_iafa_id_foreign' => ['iafa_id', 'La IAFAS seleccionada no existe, fue eliminada o está siendo usada por otro proceso. Actualiza la lista e intenta nuevamente.'],
            'tipos_clientes_tarifa_id_foreign' => ['tarifa_id', 'La tarifa seleccionada no existe o ya no está disponible. Actualiza la lista e intenta nuevamente.'],
            'tipos_clientes_iafa_id_foreign' => ['iafa_id', 'La IAFAS asociada a la tarifa ya no está disponible. Actualiza la lista e intenta nuevamente.'],
            'tipos_clientes_contratante_id_foreign' => ['contratante_id', 'El contratante seleccionado no existe o ya no está disponible. Actualiza la lista e intenta nuevamente.'],
            'paquetes_tarifa_id_foreign' => ['tarifa_id', 'La tarifa seleccionada no existe o ya no está disponible. Actualiza la lista e intenta nuevamente.'],
            'tarifa_categorias_tarifa_id_foreign' => ['tarifa_id', 'La tarifa seleccionada no existe o ya no está disponible. Actualiza la lista e intenta nuevamente.'],
            'tarifa_subcategorias_tarifa_id_foreign' => ['tarifa_id', 'La tarifa seleccionada no existe o ya no está disponible. Actualiza la lista e intenta nuevamente.'],
            'tarifa_subcategorias_categoria_id_foreign' => ['categoria_id', 'La categoría seleccionada no existe o ya no está disponible. Actualiza la lista e intenta nuevamente.'],
            'tarifa_servicios_tarifa_id_foreign' => ['tarifa_id', 'La tarifa seleccionada no existe o ya no está disponible. Actualiza la lista e intenta nuevamente.'],
            'tarifa_servicios_categoria_id_foreign' => ['categoria_id', 'La categoría seleccionada no existe o ya no está disponible. Actualiza la lista e intenta nuevamente.'],
            'tarifa_servicios_subcategoria_id_foreign' => ['subcategoria_id', 'La subcategoría seleccionada no existe o ya no está disponible. Actualiza la lista e intenta nuevamente.'],
            'paquete_servicios_paquete_id_foreign' => ['paquete_id', 'El paquete seleccionado no existe o ya no está disponible. Actualiza la lista e intenta nuevamente.'],
            'paquete_servicios_tarifa_servicio_id_foreign' => ['tarifa_servicio_id', 'Uno de los servicios seleccionados no existe o ya no está disponible. Actualiza el árbol de servicios e intenta nuevamente.'],
            'caja_numeraciones_comprobante_tipo_documento_id_foreign' => ['tipo_documento_id', 'El tipo de documento seleccionado no existe o ya no está disponible. Actualiza la lista e intenta nuevamente.'],
        ];

        [$field, $message] = $messages[$constraint] ?? ['registro', 'No se puede completar la acción porque un registro relacionado no existe o ya no está disponible. Actualiza la pantalla e intenta nuevamente.'];

        return ['field' => $field, 'message' => $message];
    }

    private function ficherosCheckError(?string $constraint): array
    {
        $messages = [
            'iafas_cobertura_check' => ['fecha_fin_cobertura', 'La fecha de fin de cobertura no puede ser anterior a la fecha de inicio.'],
            'contratantes_ruc_check' => ['ruc', 'El RUC del contratante debe tener 11 dígitos numéricos.'],
            'clientes_dni_o_ruc_check' => ['dni_o_ruc', 'El DNI/RUC del cliente debe tener 8 u 11 dígitos numéricos.'],
            'paquetes_precio_sin_igv_check' => ['precio_sin_igv', 'El precio sin IGV del paquete no puede ser negativo.'],
            'tarifas_factores_check' => ['factor_clinica', 'Los factores de la tarifa no pueden ser negativos.'],
            'chk_tarifas_base_activo' => ['tarifa_base', 'El tarifario base debe permanecer activo.'],
            'medicos_adicionales_check' => ['adicionales', 'Los adicionales del médico no pueden ser negativos.'],
            'medicos_extras_check' => ['extras', 'Los extras del médico no pueden ser negativos.'],
            'medicos_tiempo_promedio_check' => ['tiempo_promedio_por_paciente', 'El tiempo promedio por paciente no puede ser negativo.'],
            'turnos_duracion_check' => ['hora_fin', 'La duración del turno debe ser válida y no puede ser negativa.'],
            'chk_tarifa_categorias_codigo_format' => ['codigo', 'El código de la categoría debe tener 2 dígitos.'],
            'chk_tarifa_subcategorias_codigo_format' => ['codigo', 'El código de la subcategoría debe tener 2 dígitos.'],
            'chk_tarifa_servicios_servicio_codigo_format' => ['codigo', 'El correlativo del servicio debe tener 2 dígitos.'],
            'chk_tarifa_servicios_codigo_format' => ['codigo', 'El código del servicio debe tener el formato 00.00.00.'],
        ];

        if ($constraint !== null && str_ends_with($constraint, '_estado_check')) {
            return ['field' => 'estado', 'message' => 'El estado enviado no es válido. Usa ACTIVO, INACTIVO o SUSPENDIDO.'];
        }

        [$field, $message] = $messages[$constraint] ?? ['registro', 'Los datos enviados no cumplen una regla del módulo. Revisa formatos, estados, fechas y montos antes de guardar.'];

        return ['field' => $field, 'message' => $message];
    }
}

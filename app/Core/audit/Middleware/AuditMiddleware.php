<?php

namespace App\Core\audit\Middleware;

use App\Core\audit\AuditService;
use App\Core\notifications\NotificationService;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditMiddleware
{
    protected AuditService $audit;
    protected NotificationService $notifications;

    public function __construct(AuditService $audit, NotificationService $notifications)
    {
        $this->audit = $audit;
        $this->notifications = $notifications;
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);

            if ($this->shouldAudit($request)) {
                $this->audit->log(
                    action: 'http.request',
                    actionLabel: 'Solicitud HTTP',
                    metadata: [
                        'query' => $request->query(),
                        'payload' => $request->except(['password']),
                    ],
                    result: $response->getStatusCode() < 400 ? 'success' : 'failed',
                    statusCode: $response->getStatusCode()
                );
            }
            $this->notifyAction($request, $response);

            return $response;
        } catch (Throwable $e) {
            if ($this->shouldAudit($request)) {
                $this->audit->log(
                    action: 'http.exception',
                    actionLabel: 'Excepción HTTP',
                    metadata: [
                        'exception' => class_basename($e),
                        'message' => $e->getMessage(),
                    ],
                    result: 'failed',
                    statusCode: 500
                );
            }

            throw $e;
        }
    }

    protected function shouldAudit(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    protected function notifyAction(Request $request, Response $response): void
    {
        if (!$this->shouldNotify($request, $response)) {
            return;
        }

        [$entityType, $actionType] = $this->resolveEntityAndAction($request);
        if ($entityType === null || $actionType === null) {
            return;
        }
        if (in_array($entityType, ['navigation', 'telemetria_navegacion'], true)) {
            return;
        }

        try {
            $this->notifications->notifyAction(
                entityType: $entityType,
                actionType: $actionType,
                entityId: $this->resolveEntityId($request),
                entityName: $this->resolveEntityName($response),
                metadata: [
                    'route' => $request->path(),
                    'method' => strtoupper($request->method()),
                ],
            );
        } catch (Throwable) {
        }
    }

    protected function shouldNotify(Request $request, Response $response): bool
    {
        if (!$this->shouldAudit($request)) {
            return false;
        }

        if ($request->user() === null) {
            return false;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return false;
        }

        $path = ltrim(strtolower($request->path()), '/');
        if (str_starts_with($path, 'notifications')) {
            return false;
        }
        if (str_starts_with($path, 'telemetria/') || str_starts_with($path, 'api/telemetria/') || str_contains($path, '/telemetria/')) {
            return false;
        }

        $actionName = (string) ($request->route()?->getActionName() ?? '');
        if ($actionName !== '' && str_contains(strtolower($actionName), '\\modules\\telemetria\\')) {
            return false;
        }

        return true;
    }

    protected function resolveEntityAndAction(Request $request): array
    {
        $actionMethod = (string) ($request->route()?->getActionMethod() ?? '');
        $controller = $request->route()?->getController();
        $controllerClass = $controller ? get_class($controller) : null;

        $actionType = $this->resolveActionType($request, $actionMethod);
        $entityType = $this->resolveEntityType($controllerClass, $actionMethod);

        return [$entityType, $actionType];
    }

    protected function resolveActionType(Request $request, string $actionMethod): ?string
    {
        $method = strtoupper($request->method());
        $path = strtolower($request->path());
        $routeMethod = strtolower($actionMethod);

        if (str_contains($path, 'clonar-desde-base') || $routeMethod === 'clonefrombase') {
            return 'clone';
        }

        if (str_contains($path, '/desactivar') || in_array($routeMethod, ['deactivate', 'anular'], true)) {
            return 'disable';
        }

        if (str_contains($path, '/activar') || $routeMethod === 'activate') {
            return 'enable';
        }

        if ($method === 'POST') {
            return 'create';
        }

        if ($method === 'PUT' || $method === 'PATCH') {
            return 'update';
        }

        if ($method === 'DELETE') {
            return 'delete';
        }

        return null;
    }

    protected function resolveEntityType(?string $controllerClass, string $actionMethod): ?string
    {
        if (!$controllerClass) {
            return null;
        }

        $base = str_replace('Controller', '', class_basename($controllerClass));
        $method = strtolower($actionMethod);

        if ($base === 'Paciente' && in_array($method, ['addplan', 'updateplan', 'deactivateplan'], true)) {
            return 'plan_paciente';
        }

        $map = [
            'Especialidad' => 'especialidad',
            'Consultorio' => 'consultorio',
            'Medico' => 'medico',
            'Turno' => 'turno',
            'TipoIafa' => 'tipo_iafa',
            'Iafa' => 'iafa',
            'TipoCliente' => 'tipo_cliente',
            'Contratante' => 'contratante',
            'Tarifa' => 'tarifa',
            'TarifaCategoria' => 'tarifa_categoria',
            'TarifaSubcategoria' => 'tarifa_subcategoria',
            'TarifaServicio' => 'tarifa_servicio',
            'TarifaRecargoNoche' => 'tarifa_recargo_noche',
            'ParametroSistema' => 'parametro_sistema',
            'Paciente' => 'paciente',
            'AgendaMedica' => 'agenda_cita',
            'CitaAtencion' => 'cita_atencion',
            'ProgramacionMedica' => 'programacion_medica',
            'GrupoServicio' => 'grupo_servicio',
            'Navigation' => 'navigation',
        ];

        if (isset($map[$base])) {
            return $map[$base];
        }

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $base));
    }

    protected function resolveEntityId(Request $request): ?int
    {
        $params = (array) ($request->route()?->parameters() ?? []);
        $preferred = [
            'id',
            'servicio',
            'subcategoria',
            'categoria',
            'tarifa',
            'recargoNoche',
            'programacionMedica',
            'cita',
            'plan',
            'paciente',
            'medico',
            'consultorio',
            'especialidad',
            'turno',
            'tipoIafa',
            'iafa',
            'tipoCliente',
            'contratante',
        ];

        foreach ($preferred as $key) {
            if (!array_key_exists($key, $params)) {
                continue;
            }
            $id = $this->toEntityId($params[$key]);
            if ($id !== null) {
                return $id;
            }
        }

        foreach ($params as $value) {
            $id = $this->toEntityId($value);
            if ($id !== null) {
                return $id;
            }
        }

        return null;
    }

    protected function toEntityId(mixed $value): ?int
    {
        if ($value instanceof Model) {
            return (int) $value->getKey();
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        return null;
    }

    protected function resolveEntityName(Response $response): ?string
    {
        if (!method_exists($response, 'getContent')) {
            return null;
        }

        $content = (string) $response->getContent();
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return null;
        }

        $data = $decoded['data'] ?? $decoded;
        if (!is_array($data)) {
            return null;
        }

        if (array_is_list($data)) {
            $first = $data[0] ?? null;
            if (!is_array($first)) {
                return null;
            }
            $data = $first;
        }

        foreach (['descripcion', 'descripcion_tarifa', 'razon_social', 'descripcion_corta', 'apellidos_nombres', 'nombre_completo', 'title', 'name'] as $field) {
            $value = $data[$field] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}

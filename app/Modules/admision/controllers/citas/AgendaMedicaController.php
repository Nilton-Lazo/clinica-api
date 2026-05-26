<?php

namespace App\Modules\admision\controllers\citas;

use App\Core\grid\GridParams;
use App\Http\Controllers\Controller;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\requests\citas\AgendaCitaStoreRequest;
use App\Modules\admision\requests\citas\AgendaMedicaInitRequest;
use App\Modules\admision\requests\citas\AgendaMedicaOptionsRequest;
use App\Modules\admision\requests\citas\AgendaMedicaSlotsRequest;
use App\Modules\admision\services\citas\AgendaMedicaService;
use Illuminate\Http\Request;

class AgendaMedicaController extends Controller
{
    public function __construct(private AgendaMedicaService $service) {}

    public function opciones(AgendaMedicaOptionsRequest $request)
    {
        $this->authorize('viewAny', AgendaCita::class);

        $data = $this->service->opciones($request->validated());

        return response()->json(['data' => $data]);
    }

    public function init(AgendaMedicaInitRequest $request)
    {
        $this->authorize('viewAny', AgendaCita::class);

        $data = $this->service->initAgenda($request->validated());

        return response()->json(['data' => $data]);
    }

    public function slots(AgendaMedicaSlotsRequest $request)
    {
        $this->authorize('viewAny', AgendaCita::class);

        $data = $this->service->slots($request->validated());

        return response()->json(['data' => $data]);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', AgendaCita::class);

        $params = GridParams::fromRequest($request, [
            'codigo',
            'hora',
            'hc',
            'nr',
            'paciente_nombre',
            'cuenta',
            'motivo',
            'estado',
        ], 'hora');
        $fecha = $request->input('fecha') ?? $params->filter('fecha');
        $especialidadId = $request->input('especialidad_id') ?? $params->filter('especialidad_id');
        $medicoId = $request->input('medico_id') ?? $params->filter('medico_id');
        $estadoAtencion = $request->input('estado_atencion') ?? $params->filter('estado_atencion');
        $filters = [
            'fecha' => $fecha,
            'especialidad_id' => $especialidadId,
            'medico_id' => $medicoId,
            'estado_atencion' => $estadoAtencion,
            'page' => $params->page,
            'per_page' => $params->perPage,
            'sort' => $params->sort,
            'sort_dir' => $params->sortDir,
        ];
        $res = $this->service->listarCitas($filters);

        if (!$res['paginator']) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => (int)($filters['per_page'] ?? 50),
                    'total' => 0,
                    'last_page' => 1,
                ],
                'programacion' => null,
            ]);
        }

        $p = $res['paginator'];
        $items = collect($p->items())->map(function (AgendaCita $cita) {
            $arr = $cita->toArray();
            $arr['hora_ingreso'] = $cita->atencion && $cita->atencion->hora_asistencia
                ? substr((string)$cita->atencion->hora_asistencia, 0, 5)
                : null;
            return $arr;
        })->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
            'programacion' => $res['programacion'],
        ]);
    }

    public function store(AgendaCitaStoreRequest $request)
    {
        $this->authorize('create', AgendaCita::class);

        $cita = $this->service->crearCita($request->validated());

        return response()->json(['data' => $cita], 201);
    }

    public function anular(int $id)
    {
        $cita = AgendaCita::query()->findOrFail($id);
        $this->authorize('update', $cita);

        $cita = $this->service->anularCita((int)$id);

        return response()->json(['data' => $cita]);
    }
}

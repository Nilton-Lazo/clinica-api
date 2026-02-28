<?php

namespace Tests\Feature;

use App\Core\support\RecordStatus;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\models\Medico;
use App\Modules\admision\models\Paciente;
use App\Modules\admision\models\ProgramacionMedica;
use App\Modules\admision\models\Turno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaMedicaCreateCitaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    protected function seedProgramacion(): array
    {
        $medico = Medico::query()->create([
            'codigo' => 'M001',
            'nombres' => 'Medico',
            'apellido_paterno' => 'Demo',
            'apellido_materno' => 'Uno',
            'tiempo_promedio_por_paciente' => 15,
            'estado' => RecordStatus::ACTIVO->value,
        ]);

        $turno = Turno::query()->create([
            'codigo' => 'T1',
            'descripcion' => 'Mañana',
            'hora_inicio' => '08:00',
            'duracion_minutos' => 60,
            'estado' => RecordStatus::ACTIVO->value,
        ]);

        $programacion = ProgramacionMedica::query()->create([
            'fecha' => now()->toDateString(),
            'medico_id' => $medico->id,
            'especialidad_id' => 1,
            'consultorio_id' => 1,
            'turno_id' => $turno->id,
            'cupos' => 4,
            'tipo' => 'NORMAL',
            'estado' => RecordStatus::ACTIVO->value,
        ]);

        $paciente = Paciente::query()->create([
            'hc' => '00000001',
            'nombre_completo' => 'Paciente Demo',
            'estado' => RecordStatus::ACTIVO->value,
        ]);

        return [$programacion, $paciente];
    }

    public function test_crear_cita_exitosamente(): void
    {
        [$programacion, $paciente] = $this->seedProgramacion();

        $payload = [
            'programacion_medica_id' => $programacion->id,
            'paciente_id' => $paciente->id,
            'hora' => '08:00',
        ];

        $this->postJson('/api/admision/citas/agenda-medica', $payload)
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'codigo', 'hora', 'paciente_id']]);

        $this->assertDatabaseHas('agenda_citas', [
            'programacion_medica_id' => $programacion->id,
            'paciente_id' => $paciente->id,
            'hora' => '08:00:00',
        ]);
    }

    public function test_no_permite_doble_reserva_misma_hora(): void
    {
        [$programacion, $paciente] = $this->seedProgramacion();

        $payload = [
            'programacion_medica_id' => $programacion->id,
            'paciente_id' => $paciente->id,
            'hora' => '08:00',
        ];

        $this->postJson('/api/admision/citas/agenda-medica', $payload)->assertCreated();

        $this->postJson('/api/admision/citas/agenda-medica', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['hora']);

        $this->assertSame(
            1,
            AgendaCita::query()
                ->where('programacion_medica_id', $programacion->id)
                ->where('hora', '08:00:00')
                ->where('estado', RecordStatus::ACTIVO->value)
                ->count()
        );
    }
}


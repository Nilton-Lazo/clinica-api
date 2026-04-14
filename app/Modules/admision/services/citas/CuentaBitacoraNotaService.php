<?php

namespace App\Modules\admision\services\citas;

use App\Models\User;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\models\CuentaBitacoraNota;
use App\Modules\admision\models\Paciente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class CuentaBitacoraNotaService
{
    public function paginateMergedForCuenta(Cuenta $cuenta, int $perPage = 80): LengthAwarePaginator
    {
        $q = CuentaBitacoraNota::query()
            ->with(['user:id,name,username,nombres,apellido_paterno,apellido_materno'])
            ->where(function ($w) use ($cuenta) {
                $w->where('cuenta_id', $cuenta->id);
                $pid = $cuenta->paciente_id;
                if ($pid !== null) {
                    $w->orWhere(function ($o) use ($pid) {
                        $o->whereNull('cuenta_id')->where('paciente_id', $pid);
                    });
                }
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return $q->paginate($perPage);
    }

    public function paginateForPacientePending(Paciente $paciente, int $perPage = 80): LengthAwarePaginator
    {
        return CuentaBitacoraNota::query()
            ->whereNull('cuenta_id')
            ->where('paciente_id', $paciente->id)
            ->with(['user:id,name,username,nombres,apellido_paterno,apellido_materno'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(Cuenta $cuenta, User $user, string $contenido): CuentaBitacoraNota
    {
        $nota = new CuentaBitacoraNota;
        $nota->cuenta_id = $cuenta->id;
        $nota->paciente_id = null;
        $nota->user_id = $user->id;
        $nota->contenido = $contenido;
        $nota->save();
        $nota->load(['user:id,name,username,nombres,apellido_paterno,apellido_materno']);

        return $nota;
    }

    public function createForPaciente(Paciente $paciente, User $user, string $contenido): CuentaBitacoraNota
    {
        $nota = new CuentaBitacoraNota;
        $nota->cuenta_id = null;
        $nota->paciente_id = $paciente->id;
        $nota->user_id = $user->id;
        $nota->contenido = $contenido;
        $nota->save();
        $nota->load(['user:id,name,username,nombres,apellido_paterno,apellido_materno']);

        return $nota;
    }

    public function attachPendingPacienteNotesToCuenta(Cuenta $cuenta): int
    {
        if ($cuenta->paciente_id === null) {
            return 0;
        }

        return CuentaBitacoraNota::query()
            ->whereNull('cuenta_id')
            ->where('paciente_id', $cuenta->paciente_id)
            ->update([
                'cuenta_id' => $cuenta->id,
                'paciente_id' => null,
            ]);
    }

    public static function serializeNota(CuentaBitacoraNota $n): array
    {
        $u = $n->user;
        $createdAt = $n->created_at instanceof Carbon ? $n->created_at->toIso8601String() : (string) $n->created_at;

        return [
            'id' => (int) $n->id,
            'contenido' => (string) $n->contenido,
            'created_at' => $createdAt,
            'usuario' => [
                'id' => $u ? (int) $u->id : 0,
                'username' => $u ? (string) ($u->username ?? '') : '',
                'nombre' => $u ? self::userDisplayName($u) : '',
            ],
        ];
    }

    public static function userDisplayName(User $u): string
    {
        $name = trim((string) ($u->name ?? ''));
        if ($name !== '') {
            return $name;
        }
        $parts = array_filter([
            trim((string) ($u->apellido_paterno ?? '')),
            trim((string) ($u->apellido_materno ?? '')),
            trim((string) ($u->nombres ?? '')),
        ]);
        $full = trim(implode(' ', $parts));

        return $full !== '' ? $full : (string) ($u->username ?? '');
    }
}

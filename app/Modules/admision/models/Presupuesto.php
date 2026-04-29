<?php



namespace App\Modules\admision\models;



use App\Core\audit\AuditableModel;

use App\Models\User;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presupuesto extends AuditableModel
{

    protected $table = 'admision_presupuestos';



    protected array $auditExclude = ['payload'];



    protected $fillable = [

        'paciente_id',

        'paciente_plan_id',

        'tarifa_id',

        'cliente_id',

        'vigencia_hasta',

        'estado',

        'monto_a_pagar',

        'payload',

        'created_by_user_id',

    ];



    protected $casts = [

        'vigencia_hasta' => 'date',

        'monto_a_pagar' => 'decimal:4',

    ];



    public function getPayloadAttribute(mixed $value): array

    {

        if ($value === null || $value === '') {

            return [];

        }

        if (is_array($value)) {

            return $value;

        }

        $decoded = json_decode((string) $value, true);



        return is_array($decoded) ? $decoded : [];

    }



    public function paciente(): BelongsTo

    {

        return $this->belongsTo(Paciente::class, 'paciente_id');

    }



    public function pacientePlan(): BelongsTo

    {

        return $this->belongsTo(PacientePlan::class, 'paciente_plan_id');

    }



    public function tarifa(): BelongsTo

    {

        return $this->belongsTo(Tarifa::class, 'tarifa_id');

    }



    public function cliente(): BelongsTo

    {

        return $this->belongsTo(Cliente::class, 'cliente_id');

    }



    public function createdBy(): BelongsTo

    {

        return $this->belongsTo(User::class, 'created_by_user_id');

    }

}



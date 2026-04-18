<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\seguridad\Policies\UserPolicy;

use App\Modules\admision\models\Especialidad;
use App\Modules\ficheros\policies\EspecialidadPolicy;
use App\Modules\admision\models\Cirugia;
use App\Modules\ficheros\policies\CirugiaPolicy;

use App\Modules\admision\models\Consultorio;
use App\Modules\ficheros\policies\ConsultorioPolicy;

use App\Modules\admision\models\Medico;
use App\Modules\ficheros\policies\MedicoPolicy;

use App\Modules\admision\models\Turno;
use App\Modules\ficheros\policies\TurnoPolicy;

use App\Modules\admision\models\ProgramacionMedica;
use App\Modules\admision\policies\citas\ProgramacionMedicaPolicy;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\policies\citas\AgendaCitaPolicy;

use App\Modules\admision\models\TipoIafa;
use App\Modules\ficheros\policies\TipoIafaPolicy;

use App\Modules\admision\models\Iafa;
use App\Modules\ficheros\policies\IafaPolicy;

use App\Modules\admision\models\Contratante;
use App\Modules\ficheros\policies\ContratantePolicy;

use App\Modules\admision\models\Cliente;
use App\Modules\ficheros\policies\ClientePolicy;

use App\Modules\admision\models\Paquete;
use App\Modules\ficheros\policies\PaquetePolicy;

use App\Modules\admision\models\Tarifa;
use App\Modules\ficheros\policies\TarifaPolicy;

use App\Modules\admision\models\TipoCliente;
use App\Modules\ficheros\policies\TipoClientePolicy;

use App\Modules\admision\models\TarifaCategoria;
use App\Modules\ficheros\policies\TarifaCategoriaPolicy;

use App\Modules\admision\models\TarifaSubcategoria;
use App\Modules\ficheros\policies\TarifaSubcategoriaPolicy;

use App\Modules\admision\models\TarifaServicio;
use App\Modules\ficheros\policies\TarifaServicioPolicy;

use App\Modules\admision\models\TipoEmergencia;
use App\Modules\ficheros\policies\TipoEmergenciaPolicy;

use App\Modules\admision\models\Topico;
use App\Modules\ficheros\policies\TopicoPolicy;

use App\Modules\admision\models\TipoDocumento;
use App\Modules\ficheros\policies\TipoDocumentoPolicy;

use App\Modules\admision\models\DocumentoAtencion;
use App\Modules\ficheros\policies\DocumentoAtencionPolicy;
use App\Modules\caja\models\CajaApertura;
use App\Modules\caja\policies\CajaAperturaPolicy;
use App\Modules\admision\models\AreaJefatura;
use App\Modules\ficheros\policies\AreaJefaturaPolicy;
use App\Modules\admision\models\CajaTipoDocumento;
use App\Modules\ficheros\policies\CajaTipoDocumentoPolicy;
use App\Modules\admision\models\CajaNumeracionComprobante;
use App\Modules\ficheros\policies\CajaNumeracionComprobantePolicy;
use App\Modules\admision\models\CajaFormaPago;
use App\Modules\ficheros\policies\CajaFormaPagoPolicy;
use App\Modules\admision\models\CajaMedioPago;
use App\Modules\ficheros\policies\CajaMedioPagoPolicy;
use App\Modules\admision\models\CajaBancoTarjeta;
use App\Modules\ficheros\policies\CajaBancoTarjetaPolicy;

use App\Modules\admision\models\Paciente;
use App\Modules\admision\policies\pacientes\PacientePolicy;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Especialidad::class => EspecialidadPolicy::class,
        Cirugia::class => CirugiaPolicy::class,
        Consultorio::class => ConsultorioPolicy::class,
        Medico::class => MedicoPolicy::class,
        Turno::class => TurnoPolicy::class,
        ProgramacionMedica::class => ProgramacionMedicaPolicy::class,
        AgendaCita::class => AgendaCitaPolicy::class,
        TipoIafa::class => TipoIafaPolicy::class,
        Iafa::class => IafaPolicy::class,
        Contratante::class => ContratantePolicy::class,
        Cliente::class => ClientePolicy::class,
        Paquete::class => PaquetePolicy::class,
        Tarifa::class => TarifaPolicy::class,
        TipoCliente::class => TipoClientePolicy::class,
        Paciente::class => PacientePolicy::class,
        TarifaCategoria::class => TarifaCategoriaPolicy::class,
        TarifaSubcategoria::class => TarifaSubcategoriaPolicy::class,
        TarifaServicio::class => TarifaServicioPolicy::class,
        TipoEmergencia::class => TipoEmergenciaPolicy::class,
        Topico::class => TopicoPolicy::class,
        TipoDocumento::class => TipoDocumentoPolicy::class,
        DocumentoAtencion::class => DocumentoAtencionPolicy::class,
        AreaJefatura::class => AreaJefaturaPolicy::class,
        CajaTipoDocumento::class => CajaTipoDocumentoPolicy::class,
        CajaNumeracionComprobante::class => CajaNumeracionComprobantePolicy::class,
        CajaFormaPago::class => CajaFormaPagoPolicy::class,
        CajaMedioPago::class => CajaMedioPagoPolicy::class,
        CajaBancoTarjeta::class => CajaBancoTarjetaPolicy::class,
        CajaApertura::class => CajaAperturaPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

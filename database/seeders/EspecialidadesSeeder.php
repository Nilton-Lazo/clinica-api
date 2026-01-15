<?php

namespace Database\Seeders;

use App\Core\support\RecordStatus;
use App\Modules\admision\models\Especialidad;
use Illuminate\Database\Seeder;

class EspecialidadesSeeder extends Seeder
{
    public function run(): void
    {
        Especialidad::query()->truncate();

        $base = [
            'Medicina General',
            'Medicina Interna',
            'Pediatría',
            'Ginecología y Obstetricia',
            'Cardiología',
            'Neurología',
            'Traumatología y Ortopedia',
            'Cirugía General',
            'Dermatología',
            'Oftalmología',
            'Otorrinolaringología',
            'Urología',
            'Endocrinología',
            'Gastroenterología',
            'Neumología',
            'Nefrología',
            'Reumatología',
            'Hematología',
            'Oncología Médica',
            'Psiquiatría',
            'Psicología Clínica',
            'Nutrición y Dietética',
            'Medicina Física y Rehabilitación',
            'Fisioterapia',
            'Terapia Ocupacional',
            'Odontología',
            'Odontopediatría',
            'Periodoncia',
            'Endodoncia',
            'Radiología',
            'Ecografía',
            'Anestesiología',
            'Medicina del Dolor',
            'Infectología',
            'Alergología e Inmunología',
            'Medicina Familiar',
            'Medicina Preventiva',
            'Medicina del Trabajo',
            'Geriatría',
            'Medicina Intensiva (UCI)',
            'Urgencias y Emergencias',
            'Patología Clínica (Laboratorio)',
            'Banco de Sangre',
            'Genética Médica',
            'Cirugía Cardiovascular',
            'Neurocirugía',
            'Cirugía Pediátrica',
            'Cirugía Plástica',
            'Cirugía Oncológica',
            'Coloproctología',
            'Angiología y Cirugía Vascular',
            'Medicina Nuclear',
            'Reproducción Humana',
            'Neonatología',
            'Medicina Deportiva',
            'Maxilofacial',
            'Medicina Estética',
            'Logopedia / Fonoaudiología',
            'Terapia de Lenguaje',
            'Podología',
            'Osteopatía',
            'Quiropráctica',
            'Obesidad y Metabolismo',
            'Diabetología',
            'Hipertensión y Riesgo Cardiovascular',
            'Clínica del Sueño',
            'Paliativos',
            'Salud Sexual',
            'Planificación Familiar',
            'Consejería Prenatal',
            'Consejería de Lactancia',
            'Salud Mental Comunitaria',
            'Psicoterapia',
            'Psicología Infantil',
            'Psiquiatría Infantil',
            'Fonoaudiología Infantil',
            'Neuropsicología',
            'Terapia Familiar',
            'Terapia de Pareja',
            'Medicina Integrativa',
            'Acupuntura',
            'Homeopatía',
            'Medicina Tradicional',
            'Atención Domiciliaria',
            'Chequeo Preventivo',
            'Control de Crecimiento y Desarrollo',
            'Control Prenatal',
            'Control Postparto',
            'Vacunación',
            'Curaciones y Procedimientos',
            'Tópico / Enfermería',
            'Electrocardiograma (ECG)',
            'Espirometría',
            'Holter',
            'MAPA (Presión Arterial)',
            'Audiometría',
            'Optometría',
            'Psicometría',
            'Telemedicina',
        ];

        while (count($base) < 100) {
            $base[] = 'Especialidad Clínica ' . str_pad((string)(count($base) + 1), 3, '0', STR_PAD_LEFT);
        }

        $base = array_slice($base, 0, 100);

        foreach ($base as $i => $descripcion) {
            $codigo = str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT);

            Especialidad::create([
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'estado' => RecordStatus::ACTIVO->value,
            ]);
        }
    }
}

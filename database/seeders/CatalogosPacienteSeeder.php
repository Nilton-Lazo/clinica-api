<?php

namespace Database\Seeders;

use App\Core\support\RecordStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogosPacienteSeeder extends Seeder
{
    public function run(): void
    {
        $basePath = database_path('seeders/data');
        $now = now();

        $countriesPath = $basePath . DIRECTORY_SEPARATOR . 'country-list.csv';
        $countriesStream = null;
        if (is_file($countriesPath)) {
            $countriesStream = fopen($countriesPath, 'r');
        }
        if ($countriesStream === null) {
            $countriesCsv = @file_get_contents('https://raw.githubusercontent.com/datasets/country-list/master/data.csv');
            if (is_string($countriesCsv) && $countriesCsv !== '') {
                if (!is_dir($basePath)) {
                    @mkdir($basePath, 0775, true);
                }
                @file_put_contents($countriesPath, $countriesCsv);
                $countriesStream = fopen('php://temp', 'r+');
                fwrite($countriesStream, $countriesCsv);
                rewind($countriesStream);
            }
        }

        if ($countriesStream !== null) {
            $rows = [];
            $handle = $countriesStream;
            $header = fgetcsv($handle);
            $map = [];
            if (is_array($header)) {
                foreach ($header as $idx => $col) {
                    $key = Str::slug((string)$col, '_');
                    $map[$key] = $idx;
                }
            }

            while (($line = fgetcsv($handle)) !== false) {
                $nameKey = isset($map['name']) ? 'name' : (isset($map['value']) ? 'value' : null);
                $codeKey = isset($map['code']) ? 'code' : (isset($map['id']) ? 'id' : null);
                $name = $nameKey !== null ? trim((string)($line[$map[$nameKey]] ?? '')) : '';
                $code = $codeKey !== null ? strtoupper(trim((string)($line[$map[$codeKey]] ?? ''))) : '';
                if ($name === '' || !preg_match('/^[A-Z]{2}$/', $code)) {
                    continue;
                }

                $rows[] = [
                    'iso2' => $code,
                    'nombre' => $name,
                    'estado' => RecordStatus::ACTIVO->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($rows) >= 500) {
                    DB::table('paises')->upsert($rows, ['iso2'], ['nombre', 'estado', 'updated_at']);
                    $rows = [];
                }
            }

            if ($rows) {
                DB::table('paises')->upsert($rows, ['iso2'], ['nombre', 'estado', 'updated_at']);
            }
            fclose($handle);
        }

        $ubigeoPath = $basePath . DIRECTORY_SEPARATOR . 'ubigeo_distrito.csv';
        $ubigeoStream = null;
        if (is_file($ubigeoPath) && filesize($ubigeoPath) > 200000) {
            $ubigeoStream = fopen($ubigeoPath, 'r');
        }
        if ($ubigeoStream === null) {
            $ubigeoCsv = @file_get_contents('https://raw.githubusercontent.com/jmcastagnetto/ubigeo-peru-aumentado/master/ubigeo_distrito.csv');
            if (is_string($ubigeoCsv) && $ubigeoCsv !== '') {
                if (!is_dir($basePath)) {
                    @mkdir($basePath, 0775, true);
                }
                @file_put_contents($ubigeoPath, $ubigeoCsv);
                $ubigeoStream = fopen('php://temp', 'r+');
                fwrite($ubigeoStream, $ubigeoCsv);
                rewind($ubigeoStream);
            }
        }

        if ($ubigeoStream !== null) {
            $rows = [];
            $handle = $ubigeoStream;
            $header = fgetcsv($handle);
            $map = [];
            if (is_array($header)) {
                foreach ($header as $idx => $col) {
                    $key = Str::slug((string)$col, '_');
                    $map[$key] = $idx;
                }
            }

            $idxInei = $map['inei'] ?? null;
            $idxDep = $map['departamento'] ?? null;
            $idxProv = $map['provincia'] ?? null;
            $idxDist = $map['distrito'] ?? null;

            while (($line = fgetcsv($handle)) !== false) {
                if ($idxInei === null || $idxDep === null || $idxProv === null || $idxDist === null) {
                    break;
                }
                $codigo = trim((string)($line[$idxInei] ?? ''));
                $departamento = trim((string)($line[$idxDep] ?? ''));
                $provincia = trim((string)($line[$idxProv] ?? ''));
                $distrito = trim((string)($line[$idxDist] ?? ''));

                if (!preg_match('/^\d{6}$/', $codigo)) {
                    continue;
                }
                if ($departamento === '' || $provincia === '' || $distrito === '' || strtoupper($distrito) === 'NA') {
                    continue;
                }

                $rows[] = [
                    'codigo' => $codigo,
                    'departamento' => $departamento,
                    'provincia' => $provincia,
                    'distrito' => $distrito,
                    'estado' => RecordStatus::ACTIVO->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($rows) >= 500) {
                    DB::table('ubigeos')->upsert($rows, ['codigo'], ['departamento', 'provincia', 'distrito', 'estado', 'updated_at']);
                    $rows = [];
                }
            }

            if ($rows) {
                DB::table('ubigeos')->upsert($rows, ['codigo'], ['departamento', 'provincia', 'distrito', 'estado', 'updated_at']);
            }
            fclose($handle);
        }

        DB::table('tipos_clientes')
            ->whereRaw('UPPER(TRIM(descripcion_tipo_cliente)) = ?', ['PARTICULAR/PARTICULAR'])
            ->update([
                'system_key' => 'DEFAULT_PARTICULAR',
                'estado' => 'ACTIVO',
                'updated_at' => now(),
            ]);

        DB::table('tipos_clientes')
            ->whereIn(DB::raw('UPPER(TRIM(descripcion_tipo_cliente))'), ['PRIVADA/PRIVADO', 'PRIVADO/PRIVADO'])
            ->update([
                'system_key' => 'DEFAULT_PRIVADO',
                'estado' => 'ACTIVO',
                'updated_at' => now(),
            ]);
    }
}

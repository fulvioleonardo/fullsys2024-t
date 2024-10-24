<?php

namespace Modules\Payroll\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Payroll\Models\Worker;
use Modules\Factcolombia1\Models\TenantService\{
    TypeWorker,
    SubTypeWorker,
    PayrollTypeDocumentIdentification,
    PayrollPeriod,
    TypeContract,
    Municipality
};
use Modules\Factcolombia1\Models\Tenant\{
    PaymentMethod,
};
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class WorkersImport implements ToModel, WithHeadingRow
{
    protected $total = 0;

    public function model(array $row)
    {
        $existingUser = Worker::where('code', $row['codigo'])
                            ->orWhere('identification_number', $row['nro_identificacion'])
                            ->first();
        if ($existingUser) {
            Log::warning('Importacion de Emplados | empleado :' . $existingUser->name . $existingUser->surname  . ' con codigo:' . $existingUser->code . ' y numero de identificacion:' . $existingUser->identification_number . ' ya se encuentra registrado.');
            return null;
        }

        $this->total++;
        $tipo_identificacion_id = PayrollTypeDocumentIdentification::where('code', 'LIKE', $row['codigo_tipo_identificacion'].'%')->value('id');
        $municipalidad_id = Municipality::where('code', 'LIKE', $row['codigo_municipalidad'].'%')->value('id');
        $tipo_empleado_id = TypeWorker::where('code', 'LIKE', $row['codigo_tipo_empleado'].'%')->value('id');
        $subtipo_empleado_id = SubTypeWorker::where('code', 'LIKE', $row['codigo_subtipo_empleado'].'%')->value('id');
        $tipo_contrato_id = TypeContract::where('code', 'LIKE', $row['codigo_tipo_contrato'].'%')->value('id');
        $frecuencia_pago_id = PayrollPeriod::where('code', 'LIKE', $row['codigo_frecuencia_pago'].'%')->value('id');
        $metodo_pago_id = PaymentMethod::where('code', 'LIKE', $row['codigo_metodo_pago'].'%')->value('id');
        $date_instance = Carbon::instance(Date::excelToDateTimeObject($row['fecha_inicio_contrato']));
        $fecha_contrato = Carbon::parse($date_instance)->format('Y-m-d');
        $codigo = $row['codigo'];

        return new Worker([
            'code' => $codigo,
            'payroll_type_document_identification_id' => $tipo_identificacion_id,
            'identification_number' => $row['nro_identificacion'],
            'first_name' => $row['nombre'],
            'surname' => $row['primer_apellido'],
            'second_surname' => $row['segundo_apellido'],
            'cellphone' => $row['nro_celular'] ?? null,
            'email' => $row['correo'] ?? null,
            'municipality_id' => $municipalidad_id,
            'address' => $row['direccion'] ?? null,
            'type_worker_id' => $tipo_empleado_id,
            'sub_type_worker_id' => $subtipo_empleado_id,
            'type_contract_id' => $tipo_contrato_id,
            'salary' => $row['salario'],
            'payroll_period_id' => $frecuencia_pago_id,
            'position' => $row['cargo'],
            'work_start_date' => $fecha_contrato,
            'high_risk_pension' => $row['pension'] == 's' ? true : false,
            'integral_salarary' => $row['salario_integral'] == 's' ? true : false,
            'payment' => [
                'bank_name' => $row['nombre_banco'] ?? null,
                'account_type' => $row['tipo_cuenta'] ?? null,
                'account_number' => $row['nro_cuenta'] ?? null,
                'payment_method_id' => $metodo_pago_id
            ]
        ]);
    }

    public function getData()
    {
        return ['total' => $this->total];
    }
}

@php

    $establishment = $cash->user->establishment;
    $final_balance = 0;
    $cash_income = 0;
    // Calcular el total de egresos (gastos)
    $cashEgress = $cash->cash_documents->sum(function ($cashDocument) {
        return $cashDocument->expense_payment ? $cashDocument->expense_payment->payment : 0;
    });
    $cash_final_balance = 0;
    $document_count = 0;
    $cash_taxes = 0;
    $cash_documents = $cash->cash_documents;
    // dd($cash_documents);
    $is_complete = $only_head === 'resumido' ? false : true;
    $first_document = '';
    $last_document = '';

    $list = $cash_documents->filter(function ($item) {
        return $item->document_pos_id !== null;
    });
    if ($list->count() > 0) {
        $first_document = $list->first()->document_pos->series . '-' . $list->first()->document_pos->number;
        $last_document = $list->last()->document_pos->series . '-' . $list->last()->document_pos->number;
    }

    foreach ($methods_payment as $method) {
        $method->transaction_count = 0; // Se Incializa el contador de transacciones
    }

    foreach ($cash_documents as $cash_document) {
        if ($cash_document->document_pos) {
            $cash_income += $cash_document->document_pos->getTotalCash();
            $final_balance += $cash_document->document_pos->getTotalCash();
            $cash_taxes += $cash_document->document_pos->total_tax;
            $document_count = $cash_document->document_pos->count();

            if (count($cash_document->document_pos->payments) > 0) {
                $pays =
                    $cash_document->document_pos->state_type_id === '11'
                        ? collect()
                        : $cash_document->document_pos->payments;
                foreach ($methods_payment as $record) {
                    $record->sum = $record->sum + $pays->where('payment_method_type_id', $record->id)->sum('payment');
                }

                foreach ($cash_document->document_pos->payments as $payment) {
                    $paymentMethod = $methods_payment->firstWhere('id', $payment->payment_method_type_id);
                    if ($paymentMethod) {
                        $paymentMethod->transaction_count++; // Se incrementa el contador de transacciones
                    }
                }
            }
        }
    }
    $cash_final_balance = $final_balance + $cash->beginning_balance - $cashEgress;

@endphp

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="application/pdf; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Reporte POS - {{ $cash->user->name }} - {{ $cash->date_opening }} {{ $cash->time_opening }}</title>
        <style>
            html {
                font-family: sans-serif;
                font-size: 12px;
            }

            table {
                width: 100%;
                border-spacing: 0;
                border: 1px solid black;
            }

            .celda {
                text-align: center;
                padding: 5px;
                border: 0.1px solid black;
            }

            th {
                padding: 5px;
                text-align: center;
                border-color: #0088cc;
                border: 0.1px solid black;
            }

            .title {
                font-weight: bold;
                padding: 5px;
                font-size: 20px !important;
                text-decoration: underline;
            }

            p>strong {
                margin-left: 5px;
                font-size: 12px;
            }

            thead {
                font-weight: bold;
                background: #0088cc;
                color: white;
                text-align: center;
            }

            tbody {
                text-align: right;
            }

            .text-center {
                text-align: center;
            }

            .td-custom {
                line-height: 0.1em;
            }

            .totales {
                font-weight: bold;
                background: #0088cc;
                color: white;
                text-align: right;
            }

            html {
                font-family: sans-serif;
                font-size: 8px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid black;
            }

            th,
            td {
                padding: 2px;
                border: 1px solid black;
                text-align: center;
                font-size: 8px;
            }

            th {
                background-color: #0088cc;
                color: white;
                font-weight: bold;
            }

            .title {
                font-weight: bold;
                text-align: center;
                font-size: 16px;
                text-decoration: underline;
            }

            p,
            p>strong {
                font-size: 8px;
            }

            .totales {
                font-weight: bold;
                background: #0088cc;
                color: white;
                text-align: right;
            }

            /* Estilos encabezado */
            html {
                font-family: sans-serif;
                font-size: 12px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid black;
            }

            th,
            .celda {
                padding: 5px;
                border: 1px solid black;
                text-align: center;
            }

            th {
                background-color: #0088cc;
                color: white;
                font-weight: bold;
            }

            .title {
                font-weight: bold;
                text-align: center;
                font-size: 20px;
                text-decoration: underline;
            }
        </style>
    </head>

    <body>
        <div>
            {{-- <p align="center" class="title"><strong>COMPROBANTE INFORME DIARIO</strong></p> --}}
            <div style="margin-top: -30px;" class="text-center">
                <p>
                    <strong>Empresa: </strong>{{ $company->name }} <br>
                    <strong>N° Documento: </strong>{{ $company->number }} <br>
                    <strong>Establecimiento: </strong>{{ $establishment->description }} <br>
                    <strong>Fecha reporte: </strong>{{ date('Y-m-d') }} <br>
                    <strong>Vendedor:</strong> {{ $cash->user->name }} <br>
                    <strong>Fecha y hora apertura:</strong> {{ $cash->date_opening }} {{ $cash->time_opening }} <br>
                    <strong>Estado de caja:</strong> {{ $cash->state ? 'Aperturada' : 'Cerrada' }}
                    @if (!$cash->state)
                        <br>
                        <strong>Fecha y hora cierre:</strong> {{ $cash->date_closed }} {{ $cash->time_closed }}
                    @endif
                </p>
            </div>
        </div>
        @php
            $is_complete = $only_head === 'resumido' ? false : true;
        @endphp

        <div>
            @php
                $tipoComprobante = 'Factura POS';
                $numeroInicial = null;
                $numeroFinal = null;

                foreach ($cash_documents as $cash_document) {
                    if ($cash_document->document_pos) {
                        $numeroActual = $cash_document->document_pos->number_full;
                        if (!$numeroInicial || $numeroActual < $numeroInicial) {
                            $numeroInicial = $numeroActual;
                        }
                        if (!$numeroFinal || $numeroActual > $numeroFinal) {
                            $numeroFinal = $numeroActual;
                        }
                    }
                }
            @endphp
        </div>

        <table>
            <tr>
                <th>Egreso</th>
            </tr>
            <tr>
                <td>${{ number_format($cashEgress, 2, '.', ',') }}</td>
            </tr>
        </table>

        @if ($cash_documents->count())
            <div>
                <h3>Totales por medio de pago</h3>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Medio de Pago</th>
                            {{-- <th>Valor Transacción</th> --}}
                            <th>Valor Escrito</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalSum = 0; @endphp
                        @php $totalTransactions = 0; @endphp
                        @foreach ($methods_payment as $item)
                            @php
                                $totalSum += $item->sum;
                                $totalTransactions += $item->transaction_count;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->name }}</td>
                                {{-- <td>${{ number_format($item->sum, 2, '.', ',') }}</td> --}}
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p>No se encontraron registros de documentos.</p>
        @endif
    </body>
</html>

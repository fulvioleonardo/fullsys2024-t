<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Artículos Vendidos</title>
</head>
<body>
    <table>
        <tr>
            <td colspan="2">Artículos Vendidos</td>
        </tr>
        <tr>
            <td>Empresa</td>
            <td>{{$company->name}}</td>
        </tr>
        <tr>
            <td>Establecimiento</td>
            <td>{{$establishment->description}}</td>
        </tr>
        <tr>
            <td>Teléfono</td>
            <td>{{$establishment->telephone}}</td>
        </tr>
        <tr>
            <td>Email</td>
            <td>{{$establishment->email}}</td>
        </tr>
        <tr>
            <td>Dirección</td>
            <td>{{$establishment->address}}</td>
        </tr>
    </table>

    @if($records->count() > 0)
        <table class="">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Código</th>
                    <th>Artículo</th>
                    <th>Cantidad</th>
                    <th>Costo</th>
                    <th>Neto</th>
                    <th>Utilidad</th>
                    <th>Impuesto</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total = 0;
                    $total_tax = 0;
                    $total_utility = 0;
                    $total_quantity = 0;
                    $total_net_value = 0;
                    $total_cost = 0;
                @endphp
                @foreach($records as $value)
                    @php
                        $row = $value->getDataReportSoldItems();
                        $total = $total + $row['total'];
                        $total_tax = $total_tax + $row['total_tax'];
                        $total_utility = $total_utility + $row['utility'];
                        $total_quantity = $total_quantity + $row['quantity'];
                        $total_net_value = $total_net_value + $row['net_value'];
                        $total_cost = $total_cost + $row['cost'];
                    @endphp
                    <tr>
                        <td class="celda">{{ $row['type_name'] }}</td>
                        <td class="celda">{{ $row['internal_id'] }}</td>
                        <td class="celda">{{ $row['name'] }}</td>
                        <td class="celda">{{ $row['quantity'] }}</td>
                        <td class="celda">{{ $row['cost'] }}</td>
                        <td class="celda">{{ $row['net_value'] }}</td>
                        <td class="celda">{{ $row['utility'] }}</td>
                        <td class="celda">{{ $row['total_tax'] }}</td>
                        <td class="celda">{{ $row['total'] }}</td>
                    </tr>
                @endforEach
                <tr>
                    <td colspan="3" class="celda text-right-td">TOTALES </td>
                    <td class="celda">{{ $total_quantity }}</td>
                    <td class="celda">{{ number_format($total_cost, 2) }}</td>
                    <td class="celda">{{ number_format($total_net_value, 2) }}</td>
                    <td class="celda">{{ number_format($total_utility, 2) }}</td>
                    <td class="celda">{{ number_format($total_tax, 2) }}</td>
                    <td class="celda">{{ number_format($total, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>

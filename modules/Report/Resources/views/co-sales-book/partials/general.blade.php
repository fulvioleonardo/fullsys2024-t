
<table class="">
    <thead>
        <tr>
            <th colspan="7"></th>

            @foreach($taxes as $tax)
                <th colspan="2">
                    IMPUESTO #{{ $loop->iteration }}
                    <br>
                    {{ $tax->name }} - ({{ $tax->rate }}%)
                </th>
            @endforeach
        </tr>
        <tr>
            <th>F. Emisión</th>
            <th>Nro/Doc</th>
            <th>Nombre</th>
            <th>Moneda</th>
            <th>Total/Neto</th>
            <th>Total <br>+<br> Impuesto</th>
            <th>Total/Excento</th>

            @foreach($taxes as $tax)
                <th>Base</th>
                <th>Impuesto</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php
            $total = 0;
            $net_total = 0;
            $total_exempt = 0;
        @endphp
        @foreach($records as $value)
            @php
                $row = $value->getDataReportSalesBook();
                $total = $total + $row['total'];
                $net_total = $net_total + $row['net_total'];
                $total_exempt = $total_exempt + $row['total_exempt'];
            @endphp
            <tr>
                <td class="celda">{{ $row['date_of_issue'] }}</td>
                <td class="celda">{{$row['type_document_name']}} <br/> {{ $row['number_full'] }}</td>
                <td class="celda">{{ $row['customer_name'] }}</td>
                <td class="celda">{{ $row['currency_code'] }}</td>
                <td class="celda text-right-td">{{ $row['net_total'] }}</td>
                <td class="celda text-right-td">{{ $row['total'] }}</td>
                <td class="celda text-right-td"> {{ $row['total_exempt'] }} </td>

                @foreach($taxes as $tax)

                    @php
                        $item_values = $value->getItemValuesByTax($tax->id);
                    @endphp

                    <td class="celda text-right-td">{{ $item_values['taxable_amount'] }}</td>
                    <td class="celda text-right-td">{{ $item_values['tax_amount'] }}</td>

                @endforeach
            </tr>
        @endforeach
        <tr>
            <th colspan="4" class="celda text-right-td">TOTALES</th>
            <th>{{ $net_total }}</th>
            <th>{{ $total }}</th>
            <th>{{ $total_exempt }}</th>
            @foreach($taxes as $tax)
                <th></th>
                <th></th>
            @endforeach
        </tr>
    </tbody>
</table>
<table>

</table>
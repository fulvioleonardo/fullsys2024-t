@php
    $establishment = $document->establishment;
    $supplier = $document->supplier;
    $tittle = $document->series.'-'.str_pad($document->number, 8, '0', STR_PAD_LEFT);

    $path_style = app_path('CoreFacturalo'.DIRECTORY_SEPARATOR.'Templates'.DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR.'style.css');

    if(!is_null($establishment->establishment_logo)){
        if(file_exists(public_path('storage/uploads/logos/'.$establishment->id."_".$establishment->establishment_logo)))
            $filename_logo = public_path('storage/uploads/logos/'.$establishment->id."_".$establishment->establishment_logo);
        else
            $filename_logo = public_path("storage/uploads/logos/{$company->logo}");
    }
    else
        $filename_logo = public_path("storage/uploads/logos/{$company->logo}");
@endphp
<html>
<head>
    <title>{{ $document->series.$document->number }}</title>
    <link href="{{ $path_style }}" rel="stylesheet" />
</head>
<body>
<table class="full-width">
    <tr>
        @if($filename_logo != "")
            <td width="20%">
                <div class="company_logo_box">
                    <img src="data:{{mime_content_type($filename_logo)}};base64, {{base64_encode(file_get_contents($filename_logo))}}" alt="{{$company->name}}" class="company_logo" style="max-width: 150px;">
                </div>
            </td>
        @else
            <td width="20%"></td>
        @endif
        <td width="50%" class="pl-3">
            <div class="text-left">
                <p class="">{{ $company->name }}</p>
                <p>{{ $company->number }}</p>
                <p>
                    {{ ($establishment->address !== '-')? $establishment->address : '' }}
                    {{ ($establishment->city_id !== '-')? ', '.$establishment->city->name : '' }}
                    {{ ($establishment->department_id !== '-')? '- '.$establishment->department->name : '' }}
                    {{ ($establishment->country_id !== '-')? ', '.$establishment->country->name : '' }}
                </p>

                @isset($establishment->trade_address)
                    <p>{{ ($establishment->trade_address !== '-')? 'D. Comercial: '.$establishment->trade_address : '' }}</p>
                @endisset
                <p>{{ ($establishment->telephone !== '-')? 'Central telefónica: '.$establishment->telephone : '' }}</p>

                <p>{{ ($establishment->email !== '-')? 'Email: '.$establishment->email : '' }}</p>

                @isset($establishment->web_address)
                    <p>{{ ($establishment->web_address !== '-')? 'Web: '.$establishment->web_address : '' }}</p>
                @endisset

                @isset($establishment->aditional_information)
                    <p>{{ ($establishment->aditional_information !== '-')? $establishment->aditional_information : '' }}</p>
                @endisset
            </div>
        </td>
        <td width="30%" class="border-box py-4 px-2 text-center">
            <h5 class="text-center">COMPRA</h5>
            <h3>
                {{ $document->document_type->description }}
                <br>
                {{ $tittle }}
            </h3>
        </td>
    </tr>
</table>
<table class="full-width mt-5">
    <tr>
        <td width="15%">Proveedor:</td>
        <td width="45%">{{ $supplier->name }}</td>
        <td width="25%">Fecha de emisión:</td>
        <td width="15%">{{ $document->date_of_issue->format('Y-m-d') }}</td>
    </tr>
    <tr>
        <td>{{ $supplier->identity_document_type->name }}:</td>
        <td>{{ $supplier->number }}</td>
        @if($document->date_of_due)
            <td width="25%">Fecha de vencimiento:</td>
            <td width="15%">{{ $document->date_of_due->format('Y-m-d') }}</td>
        @endif
    </tr>
    @if ($supplier->address !== '')
    <tr>
        <td class="align-top">Dirección:</td>
        <td colspan="">
            {{ $supplier->address }}
            {{ ($supplier->country_id)? ', '.$supplier->country->name : '' }}
            {{ ($supplier->department_id)? ', '.$supplier->department->name : '' }}
            {{ ($supplier->city_id)? '- '.$supplier->city->name : '' }}
        </td>
        @if($document->delivery_date)
            <td width="25%">Fecha de entrega:</td>
            <td width="15%">{{ $document->delivery_date->format('Y-m-d') }}</td>
        @endif
    </tr>
    @endif
    @if ($document->payment_method_type)
    <tr>
        <td class="align-top">T. Pago:</td>
        <td colspan="">
            {{ $document->payment_method_type->description }}
        </td>
        @if($document->sale_opportunity)
            <td width="25%">O. Venta:</td>
            <td width="15%">{{ $document->sale_opportunity->number_full }}</td>
        @endif
    </tr>
    @endif
    @if ($supplier->telephone)
    <tr>
        <td class="align-top">Teléfono:</td>
        <td colspan="3">
            {{ $supplier->telephone }}
        </td>
    </tr>
    @endif
    @if ($document->document_type_id == '07' || $document->document_type_id == '08')
    <tr>
        <td class="align-top">Concepto:</td>
        <td colspan="3">
            {{ $document->note_concepts->name }}
        </td>
    </tr>
    @endif
</table>

<table class="full-width mt-10 mb-10">
    <thead class="">
    <tr class="bg-grey">
        <th class="border-top-bottom text-center py-2" width="8%">CANT.</th>
        <th class="border-top-bottom text-center py-2" width="15%">UNIDAD</th>
        <th class="border-top-bottom text-left py-2">DESCRIPCIÓN</th>
        <th class="border-top-bottom text-right py-2" width="12%">P.UNIT</th>
        <th class="border-top-bottom text-right py-2" width="8%">DTO.</th>
        <th class="border-top-bottom text-right py-2" width="12%">TOTAL</th>
    </tr>
    </thead>
    <tbody>
        @foreach($document->items as $row)
            <tr>
                <td class="text-center align-top">
                    @if(((int)$row->quantity != $row->quantity))
                        {{ $row->quantity }}
                    @else
                        {{ number_format($row->quantity, 0) }}
                    @endif
                </td>
                <td class="text-center align-top">{{ $row->item->unit_type->name }}</td>
                <td class="text-left">
                    {!!$row->item->name!!}
                </td>
                <td class="text-right align-top">{{ number_format($row->unit_price, 2) }}</td>
                <td class="text-right align-top">
                    {{ number_format($row->discount, 2) }}
                </td>
                <td class="text-right align-top">{{ number_format($row->total, 2) }}</td>
            </tr>
            <tr>
                <td colspan="6" class="border-bottom"></td>
            </tr>
        @endforeach
        <tr>
            <td colspan="5" class="text-right font-bold">TOTAL VENTA: {{ $document->currency->symbol }}</td>
            <td class="text-right font-bold">{{ $document->sale }}</td>
        </tr>
        <tr >
            <td colspan="5" class="text-right font-bold">TOTAL DESCUENTO (-): {{ $document->currency->symbol }}</td>
            <td class="text-right font-bold">{{ $document->total_discount }}</td>
        </tr>
        <tr>
            <td colspan="5" class="text-right font-bold">SUBTOTAL: {{ $document->currency->symbol }}</td>
            <td class="text-right font-bold">{{ number_format($document->subtotal - $document->total_tax, 2) }}</td>
        </tr>
        @foreach ($document->taxes as $tax)
            @if ((($tax->total > 0) && (!$tax->is_retention)))
                <tr >
                    <td colspan="5" class="text-right font-bold">
                        {{$tax->name}}(+): {{ $document->currency->symbol }}
                    </td>
                    <td class="text-right font-bold">{{number_format($tax->total, 2)}} </td>
                </tr>
            @endif
        @endforeach
        <tr>
            <td colspan="5" class="text-right font-bold">TOTAL A PAGAR: {{ $document->currency->symbol }}</td>
            <td class="text-right font-bold">{{ number_format($document->total, 2) }}</td>
        </tr>
    </tbody>
</table>
<br>
<table class="full-width">
<tr>
    <td>
    <strong>PAGOS:</strong> </td></tr>
        @php
            $payment = 0;
        @endphp
        @foreach($document->purchase_payments as $row)
            <tr><td>- {{ $row->payment_method_type->description }} - {{ $row->reference ? $row->reference.' - ':'' }} {{ $document->currency->symbol }} {{ $row->payment }}</td></tr>
            @php
                $payment += (float) $row->payment;
            @endphp
        @endforeach
        <tr><td><strong>SALDO:</strong> {{ $document->currency->symbol }} {{ number_format($document->total - $payment, 2) }}</td>
    </tr>

</table>
</body>
</html>

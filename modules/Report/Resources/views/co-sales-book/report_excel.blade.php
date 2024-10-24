<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="application/pdf; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Libro Ventas</title>
    </head>
    <body>
        <table>
            <tr>
                <td>Empresa: </td>
                <td>{{$company->name}}</td>
            </tr>
            <tr>
                <td>Establecimiento: </td>
                <td>{{$establishment->description}}</td>
            </tr>
            <tr>
                <td>Teléfono: </td>
                <td>{{$establishment->telephone}} - <strong>Email: </strong>{{$establishment->email}}</td>
            </tr>
            <tr>
                <td>Dirección: </td>
                <td>{{$establishment->address}}</td>
            </tr>
        </table>

        <div>
            <p align="left" class="title">
                <strong>
                    Libro Ventas
                    {{ $filters->summary_sales_book ? 'Resumido' : ''}}
                </strong>
            </p>
        </div>

        @include('report::co-sales-book.partials.filters')

        @if($records->count() > 0)
            @if($filters->summary_sales_book)
                @include('report::co-sales-book.partials.summary')
            @else
                @include('report::co-sales-book.partials.general')
            @endif
        @else
            <p><strong>No se encontraron registros.</strong></p>
        @endif
    </body>
</html>

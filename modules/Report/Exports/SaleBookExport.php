<?php

namespace Modules\Report\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromCollection;

class SaleBookExport implements  FromView, ShouldAutoSize
{
    use Exportable;

    public function records($records) {
        $this->records = $records;

        return $this;
    }

    public function view(): View {
        return view('report::co-sales-book.report_excel', [
            'records'=> $this->records['records'],
            'company' => $this->records['company'],
            'establishment' => $this->records['establishment'],
            'filters' => $this->records['filters'],
            'taxes' => $this->records['taxes'],
            'summary_records' => $this->records['summary_records'],
        ]);
    }
}

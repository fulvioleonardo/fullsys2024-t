<?php

namespace Modules\Report\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromCollection;

class TaxPurchasesExport implements  FromView, ShouldAutoSize
{
    use Exportable;

    public function company($company) {
        $this->company = $company;

        return $this;
    }

    public function establishment($establishment) {
        $this->establishment = $establishment;

        return $this;
    }

    public function taxPurchases($taxPurchases){
        $this->taxPurchases = $taxPurchases;

        return $this;
    }

    public function purchases($purchases){
        $this->purchases = $purchases;

        return $this;
    }

    public function taxitles($taxTitles) {
        $this->taxTitles = $taxTitles;

        return $this;
    }

    public function view(): View {
        return view('report::tax.report_purchase_excel', [
            'company' => $this->company,
            'establishment'=>$this->establishment,
            'taxPurchases' => $this->taxPurchases,
            'taxTitles' => $this->taxTitles,
            'purchases' => $this->purchases,
        ]);
    }
}

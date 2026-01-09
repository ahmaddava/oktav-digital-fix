<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FinancialReportExport implements FromView, WithTitle, ShouldAutoSize
{
    protected $income;
    protected $expenses;
    protected $summary;

    public function __construct($income, $expenses, $summary)
    {
        $this->income = $income;
        $this->expenses = $expenses;
        $this->summary = $summary;
    }

    public function view(): View
    {
        return view('exports.financial-report', [
            'income' => $this->income,
            'expenses' => $this->expenses,
            'summary' => $this->summary,
        ]);
    }

    public function title(): string
    {
        return 'Financial Report ' . $this->summary['startDate'] . ' to ' . $this->summary['endDate'];
    }
}

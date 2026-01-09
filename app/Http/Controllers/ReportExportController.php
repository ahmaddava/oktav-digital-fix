<?php

namespace App\Http\Controllers;

use App\Exports\FinancialReportExport;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends Controller
{
    public function export(Request $request)
    {
        // Ambil parameter bulan, default ke bulan ini jika tidak ada
        $monthFilter = $request->query('month', now()->format('Y-m'));
        list($year, $month) = explode('-', $monthFilter);

        $date = Carbon::createFromDate($year, $month, 1);
        $startDate = $date->startOfMonth()->format('Y-m-d');
        $endDate = $date->endOfMonth()->format('Y-m-d');

        $income = Invoice::query()
            ->where('status', 'paid')
            ->whereYear('updated_at', $year)
            ->whereMonth('updated_at', $month)
            ->get();

        $expenses = Expense::query()
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->get();

        $totalIncome = $income->sum('grand_total');
        $totalExpense = $expenses->sum('amount');

        $summary = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
        ];

        $fileName = 'Laporan-Keuangan-' . $date->format('F-Y') . '.xlsx';

        return Excel::download(new FinancialReportExport($income, $expenses, $summary), $fileName);
    }
}

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
        // Ambil parameter tanggal, default ke bulan ini
        $dateParam = $request->query('date', now()->startOfMonth()->format('Y-m-d'));
        $date = Carbon::parse($dateParam);
        
        $year = $date->year;
        $month = $date->month;
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

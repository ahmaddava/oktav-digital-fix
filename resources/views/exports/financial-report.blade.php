{{-- resources/views/exports/financial-report.blade.php --}}
<table>
    <thead>
        <tr>
            <th colspan="4" style="font-weight: bold; font-size: 16px;">
                Laporan Keuangan ({{ $summary['startDate'] }} - {{ $summary['endDate'] }})
            </th>
        </tr>
        <tr></tr>
        <tr>
            <th style="font-weight: bold;">Total Pemasukan</th>
            <td>{{ $summary['totalIncome'] }}</td>
        </tr>
        <tr>
            <th style="font-weight: bold;">Total Pengeluaran</th>
            <td>{{ $summary['totalExpense'] }}</td>
        </tr>
        <tr>
            <th style="font-weight: bold;">Balance</th>
            <td style="font-weight: bold;">{{ $summary['balance'] }}</td>
        </tr>
        <tr></tr>
    </thead>
</table>

<table>
    <thead>
        <tr>
            <th colspan="4" style="font-weight: bold; font-size: 14px;">Detail Pemasukan</th>
        </tr>
        <tr>
            <th style="font-weight: bold;">Tanggal Lunas</th>
            <th style="font-weight: bold;">Invoice #</th>
            <th style="font-weight: bold;">Customer</th>
            <th style="font-weight: bold;">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($income as $item)
            <tr>
                <td>{{ $item->created_at->format('d-m-Y') }}</td>
                <td>{{ $item->invoice_number }}</td>
                <td>{{ $item->name_customer }}</td>
                <td>{{ $item->grand_total }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table>
    <thead>
        <tr></tr>
        <tr>
            <th colspan="3" style="font-weight: bold; font-size: 14px;">Detail Pengeluaran</th>
        </tr>
        <tr>
            <th style="font-weight: bold;">Tanggal</th>
            <th style="font-weight: bold;">Deskripsi</th>
            <th style="font-weight: bold;">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($expenses as $item)
            <tr>
                <td>{{ $item->expense_date->format('d-m-Y') }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->amount }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
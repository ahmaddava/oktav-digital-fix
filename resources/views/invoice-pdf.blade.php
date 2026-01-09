<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>
        Invoice
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Arial:wght@400;700&display=swap');

        @page {
            /* A5 Landscape */
            size: A5 landscape;
            margin: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            width: 210mm;
            min-height: 148mm;
            padding: 20mm;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .print-container {
            width: 100%;
            height: 100%;
        }

        /* Compact footer list */
        .compact-list li {
             margin-bottom: 0;
             line-height: 1.1;
        }

        @media print {
            .no-print, button {
                display: none !important;
            }
        }
    </style>
</head>

<body class="p-0">
    <div class="print-container">
        <div class="flex justify-between items-start mb-0">
            <div class="mt-0">
                <img alt="Oktav Printing logo" class="mb-0 h-10 w-auto object-contain" src="{{ asset('images/Logo Oktav.png') }}" /> 
            </div>
            <div class="text-right text-[10px] mt-0 leading-tight"> {{-- Reduced to 10px per request --}}
                <table class="border-collapse">
                    <tbody>
                        <tr class="border-b border-gray-400">
                            <td colspan="2" class="pb-0 font-semibold">No. {{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td class="pr-2 pt-0 text-left">Yth.</td>
                            <td class="pt-0">{{ $invoice->name_customer }}</td>
                        </tr>
                        <tr>
                            <td class="pr-2 pt-0 text-left">Telp.</td>
                            <td class="pt-0">{{ $invoice->customer_phone }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="pt-0">{{ $invoice->created_at->format('d M Y') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Baris baru untuk WhatsApp dan INVOICE -->
        <div class="flex justify-between items-center mb-1">
            <div class="text-[12px] leading-tight"> {{-- Increased to 12px --}}
                <p>Jl. Ciputat Raya No. 30</p>
                <p>Telp/Whatsapp, 0881 0110 60611</p>
            </div>
            <div class="text-center flex-1 mr-[50px] mt-[-10px]">
                <h2 class="font-bold text-l italic">INVOICE</h2> {{-- Increased title size --}}
            </div>
        </div>

        <!-- Tabel -->
        <table class="w-full mt-1 border-collapse text-[13px] leading-tight"> {{-- Increased to 12px --}}
            <thead>
                <tr class="border-t-2 border-b-2 border-double border-black">
                    <th class="text-left p-1">No.</th>
                    <th class="text-left p-1">Nama Pesanan</th>
                    <th class="text-left p-1">Keterangan</th>
                    <th class="text-center p-1">Qty</th>
                    <th class="text-right p-1">Harga Satuan</th>
                    <th class="text-right p-1">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->invoiceProducts as $item)
                    <tr>
                        <td class="py-0 px-2 text-center">
                            {{ $loop->iteration }}</td>

                        <td class="py-0 px-2 font-medium truncate max-w-[150px]">
                            {{ $item->product->product_name ?? $item->product_name }}</td>

                        <td class="py-0 px-2"></td>

                        <td class="py-0 px-2 text-center">{{ $item->quantity }}</td>

                        <td class="py-0 px-1">
                            <div class="flex justify-end items-center gap-0">
                                <span class="w-6 text-right tracking-tighter">Rp</span>
                                <span
                                    class="w-20 text-right">{{ number_format($item->price, 0, ',', '.') }}</span>
                            </div>
                        </td>

                        <td class="py-0 px-4">
                            <div class="flex justify-end items-center gap-0">
                                <span class="w-6 text-right tracking-tighter">Rp</span>
                                <span
                                    class="w-20 text-right font-medium">{{ number_format($item->total_price, 0, ',', '.') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
                
                {{-- Add just 1 small empty row for minimum spacing --}}
                <tr style="height: 5px;">
                     <td colspan="6"></td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-b-2 border-double border-black">
                </tr>
            </tfoot>
        </table>

         <div>
            <table class="w-full text-[13px] leading-tight mt-1"> {{-- Reduced to 10px to matches header --}}
                <tr>
                    <!-- Kolom Kiri -->
                    <td class="w-2/5" style="vertical-align: top;">
                        <div class="flex flex-col justify-start">
                             <p class="italic mb-1">Terbilang: {{ $terbilang }}</p>
                             <div class="flex flex-col mt-2">
                                <p class="mb-8">Pelanggan,</p>
                                <p class="font-bold">{{ $invoice->name_customer }}</p>
                             </div>
                        </div>
                    </td>

                    <!-- Kolom Tengah -->
                    <td class="w-1/5 text-center" style="vertical-align: top;">
                         <div class="flex flex-col justify-start mt-4">
                             <p class="mb-8">Hormat Kami,</p>
                             <p class="font-bold">Oktav Digital</p>
                        </div>
                    </td>

                    <!-- Kolom Kanan -->
                    <td class="w-2/5" style="vertical-align: top;">
                        <table class="w-full text-[10px]"> {{-- Specific smaller font for totals --}}
                            <tr>
                                <td class="text-right py-0 text-gray-600">Subtotal</td>
                                <td class="w-8 text-right py-0 text-gray-600">Rp</td>
                                <td class="w-20 text-right py-0 text-gray-600">
                                    {{ number_format($subtotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-right py-0 text-gray-600">Diskon</td>
                                <td class="w-8 text-right py-0 text-gray-600">Rp</td>
                                <td class="w-20 text-right py-0 text-gray-600">-</td>
                            </tr>
                            <tr>
                                <td class="text-right py-0 text-gray-600">Total</td>
                                <td class="w-8 text-right py-0 text-gray-600">Rp</td>
                                <td class="w-20 text-right py-0 text-gray-600">
                                    {{ number_format($subtotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="text-right py-0 text-gray-600">
                                    @php
                                        $paymentMethod = $invoice->payment_method === 'cash' ? 'Cash' : 'Transfer';
                                        echo "Bayar ($paymentMethod)";
                                    @endphp
                                </td>
                                <td class="w-8 text-right py-0 text-gray-600">Rp</td>
                                <td class="w-20 text-right py-0 text-gray-600">
                                    @php
                                        $terbayarkan = $invoice->status === 'paid' ? $subtotal : $invoice->dp ?? 0;
                                        echo number_format($terbayarkan, 0, ',', '.');
                                    @endphp
                                </td>
                            </tr>
                            <tr>
                                <td class="text-right py-0 text-gray-600">Kurang</td>
                                <td class="w-8 text-right py-0 text-gray-600">Rp</td>
                                <td class="w-20 text-right py-0 text-gray-600">
                                    @php
                                        $kurang = $invoice->status === 'paid' ? 0 : $subtotal - ($invoice->dp ?? 0);
                                        echo number_format($kurang, 0, ',', '.');
                                    @endphp
                                </td>
                            </tr>
                        </table>
                        {{-- Status aligned to bottom right --}}
                        <div class="flex justify-end items-end pt-1">
                                <p class="text-green-600 font-bold text-right">{{ strtoupper($invoice->status) }}</p>
                        </div>
                    </td>
                </tr>
            </table>

        </div>
        <div class="border-t border-gray-200 mt-1">
            <!-- Judul "Perhatian" -->
            <p class="text-[12px] font-semibold text-gray-800 leading-tight">Perhatian</p> {{-- Increased to 12px --}}

            <!-- Daftar Poin -->
            <ul class="list-disc list-inside space-y-0 text-[12px] text-gray-600 compact-list leading-tight"> {{-- Increased to 12px --}}
                <li class="pl-1">Cek File/Settingan Sebelum Cetak</li>
                <li class="pl-1">Kesalahan Setelan Bukan Tanggung Jawab Kami</li>
                <li class="pl-1">Transaksi > Rp 500rb Wajib Lunas</li>
                <li class="pl-1">Transaksi < Rp 500rb Boleh DP 50%</li>
                <li class="pl-1">Pengambilan Barang Dapat Dilakukan Setelah Pelunasan</li>
                <li class="pl-1">
                    Transfer: <strong>BCA 3750183882 (Eka Yuliana)</strong>
                </li>
            </ul>

            <!-- Pesan Terima Kasih -->
            <p class="mt-0 text-center text-[10px] text-gray-500 italic leading-tight"> {{-- Increased to 10px --}}
                ~ Terima kasih telah memilih Oktav Digital &amp; Offset Printing ~
            </p>
        </div>
    </div>
    <button onclick="printInvoice()"
        class="no-print fixed bottom-4 right-4 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-lg transition duration-300 ease-in-out transform hover:scale-105">
        🖨️ Cetak Invoice
    </button>
    <script>
        function printInvoice() {
            window.print();
        }
    </script>
</body>

</html>

<html lang="en">
 <head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>
   Invoice
  </title>
  <script src="https://cdn.tailwindcss.com">
  </script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Arial:wght@400;700&display=swap');
    body {
        font-family: 'Arial', sans-serif;
        width: 100%;
        width: 297mm;
        margin: 0 auto;
    }
    @page {
        size: landscape;
        margin: 0;
    }
    .print-container {
        max-width: 297mm; /* Lebar maksimal A4 landscape */
        padding: 20px;
    }
    .double-line-top {
        border-top: 5px double #000;
        border-bottom: 2px solid #000;
    }
    .double-line-top th {
        padding-top: 8px;
        padding-bottom: 8px;
    }
    table {
        border-collapse: collapse;
    }
    tbody tr, tbody td {
        border: none !important;
    }
    table td {
        padding: 2px 4px;
        vertical-align: top;
    }
    thead tr {
        border-top-width: 5px !important;
        border-bottom-width: 5px !important;
    }
    tfoot tr {
        border-top-width: 5px !important;
        border-bottom-width: 5px !important;
    }
    @media print {
    /* Sembunyikan tombol cetak saat mencetak */
    button {
        display: none;
    }
}
</style>
 </head>
 <body class="px-2 py-3">
    <div class="max-w-6xl mx-auto  p-4">
        <div class="flex justify-between items-start mb-[-20px]">
            <div class="mt-4">
                <img alt="Oktav Printing logo" class="mb-1"  src="{{ asset('images/oktav.jpg') }}"  width="100" height="50"/>
            </div>
            <div class="text-right text-sm mt-8"> <!-- Tambahkan mt-8 untuk margin-top -->
                <table class="border-collapse">
                    <tbody>
                        <tr class="border-b border-gray-400">
                            <td colspan="2" class="pb-1 font-semibold">No. {{ $invoice->invoice_number }}</td>                        </tr>
                        <tr>
                            <td class="pr-3 pt-1 text-left">Yth.</td>
                            <td class="pt-1">{{ $invoice->name_customer }}</td>
                        </tr>
                        <tr>
                            <td class="pr-3 pt-1 text-left">Telp.</td>
                            <td class="pt-1">{{ $invoice->customer_phone }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="pt-1">{{ $invoice->created_at->format('d M Y') }}</td>                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    
        <!-- Baris baru untuk WhatsApp dan INVOICE -->
        <div class="flex justify-between items-center mb-1">
            <div class="text-sm">
                <p>Jl. Ciputat Raya No. 30</p>
                <p>Telp/Whatsapp, 0881 0110 60611</p>
            </div>
            <div class="text-center flex-1 mr-[50px]">
                <h2 class="font-bold text-lg italic">INVOICE</h2>
            </div>
        </div>
    
        <!-- Tabel -->
        <table class="w-full mt-1 border-collapse">
            <thead>
                <tr class="border-t-2 border-b-2 border-double border-black">
                    <th class="text-left p-1 text-sm">No.</th>
                    <th class="text-left p-1 text-sm">Nama Pesanan</th>
                    <th class="text-left p-1 text-sm">Keterangan</th>
                    <th class="text-right p-1 text-sm">Harga</th>
                    <th class="text-right p-1 text-sm">Qty</th>
                    <th class="text-right p-1 text-sm">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->products->chunk(5) as $page => $productsChunk)
                    <!-- Mulai Halaman -->
                    <div class="page">
                        @foreach($productsChunk as $product)
                            <!-- Baris Data Utama -->
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                                <td class="py-2 px-4 text-sm text-gray-700 text-center">{{ ($page * 5) + $loop->iteration }}</td>
                                <td class="py-2 px-4 text-sm text-gray-700 font-medium">{{ $product->product_name }}</td>
                                <td class="py-2 px-4 text-sm text-gray-700"></td>
                                <td class="py-2 px-1">
                                    <div class="flex justify-end items-center gap-0 text-sm text-gray-700">
                                        <span class="w-6 text-right tracking-tighter">Rp</span>
                                        <span class="w-24 text-right">{{ number_format($product->price, 0, ',', '.') }}</span>
                                    </div>
                                </td>
                                <td class="py-2 px-4 text-sm text-gray-700 text-right">{{ $product->pivot->quantity }}</td>
                                <td class="py-2 px-4">
                                    <div class="flex justify-end items-center gap-0 text-sm text-gray-700">
                                        <span class="w-6 text-right tracking-tighter">Rp</span>
                                        <span class="w-24 text-right font-medium">{{ number_format($product->price * $product->pivot->quantity, 0, ',', '.') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
            
                        <!-- Baris Kosong -->
                        @for($i = $productsChunk->count(); $i < 5; $i++)
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4 text-sm text-gray-700 text-center"></td>
                                <td class="py-2 px-4 text-sm text-gray-700 font-medium"></td>
                                <td class="py-2 px-4 text-sm text-gray-700"></td>
                                <td class="py-2 px-1">
                                    <div class="flex justify-end items-center gap-0 text-sm text-gray-700">
                                        <!-- Kosongkan Rp dan angka -->
                                    </div>
                                </td>
                                <td class="py-2 px-4 text-sm text-gray-700 text-right"></td>
                                <td class="py-2 px-4">
                                    <div class="flex justify-end items-center gap-0 text-sm text-gray-700">
                                        <!-- Kosongkan Rp dan angka -->
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    </div>
                @endforeach
            </tbody>
    <tfoot>
        <tr class="border-t-2 border-b-2 border-double border-black">
        </tr>
    </tfoot>
   </table>
   <div>
 <table class="w-full">
    <tr>
        <!-- Kolom Kiri -->
        <td class="align-top w-2/5">
            <p class="italic">Terbilang: {{ $terbilang }}</p>
            <div class="mt-4">
                <p class="mb-[90px]">Pelanggan,</p> <!-- Increased margin-bottom to push "Pelanggan" further down -->
                <p class="mt-2 font-bold">{{ $invoice->name_customer }}</p> <!-- Reduced margin-top to bring "Bu Syifa" closer to the next element -->
            </div>
        </td>
        

        <!-- Kolom Tengah -->
        <td class="align-middle text-center w-1/5 h-24">
            <div class="flex flex-col justify-between h-full">
                <p class="mt-[30px]">Hormat Kami,</p>
                <div>
                    <p class="font-bold">Oktav Digital</p>
                    <!-- Tambahkan elemen lain jika diperlukan -->
                </div>
            </div>
        </td>

     <!-- Kolom Kanan -->
     <td class="align-top w-2/5">
        <table class="w-full">
            <tr class="border-b border-gray-200">
                <td class="text-right py-1 text-sm text-gray-600">Subtotal</td>
                <td class="w-12 text-right py-1 text-sm text-gray-600">Rp</td>
                <td class="w-24 text-right py-1 text-sm text-gray-600">{{ number_format($subtotal, 0, ',', '.') }}</td>            
            </tr>
            <tr class="border-b border-gray-200">
                <td class="text-right py-1 text-sm text-gray-600">Diskon</td>
                <td class="w-12 text-right py-1 text-sm text-gray-600">Rp</td>
                <td class="w-24 text-right py-1 text-sm text-gray-600">-</td>
            </tr>
            <tr class="border-b border-gray-200">
                <td class="text-right py-1 text-sm text-gray-600">Total</td>
                <td class="w-12 text-right py-1 text-sm text-gray-600">Rp</td>
                <td class="w-24 text-right py-1 text-sm text-gray-600">{{ number_format($subtotal, 0, ',', '.') }}</td>            
            </tr>
            <tr class="border-b border-gray-200">
                <td class="text-right py-1 text-sm text-gray-600">
                    @php
                        $paymentMethod = $invoice->payment_method === 'cash' ? 'Cash' : 'Transfer';
                        echo "Terbayarkan ($paymentMethod)";
                    @endphp
                </td>
                <td class="w-12 text-right py-1 text-sm text-gray-600">Rp</td>
                <td class="w-24 text-right py-1 text-sm text-gray-600">
                    @php
                        $terbayarkan = ($invoice->status === 'paid') ? $subtotal : ($invoice->dp ?? 0);
                        echo number_format($terbayarkan, 0, ',', '.');
                    @endphp
                </td>                    
            </tr>
            <tr>
                <td class="text-right py-1 text-sm text-gray-600">Kurang</td>
                <td class="w-12 text-right py-1 text-sm text-gray-600">Rp</td>
                <td class="w-24 text-right py-1 text-sm text-gray-600">
                    @php
                        $kurang = ($invoice->status === 'paid') ? 0 : ($subtotal - ($invoice->dp ?? 0));
                        echo number_format($kurang, 0, ',', '.');
                    @endphp
                </td>
            </tr>
        </table>
        <p class="text-green-600 font-bold mt-1 text-right">{{ strtoupper($invoice->status) }}</p>
    </td>
    </tr>
</table>

</div>
<div class="border-t border-gray-200">
    <!-- Judul "Perhatian" -->
    <p class="text-lg font-semibold text-gray-800">Perhatian</p>

    <!-- Daftar Poin -->
    <ul class="list-disc list-inside space-y-2 text-sm text-gray-600">
        <li class="pl-4">Mohon Periksa File atau Settingan Sebelum di Cetak</li>
        <li class="pl-4">Kesalahan Setelan Cetak Bukan Tanggung Jawab Kami</li>
        <li class="pl-4">Transaksi dengan Nominal diatas Rp 500.000 Wajib Membayar Lunas</li>
        <li class="pl-4">Transaksi dengan Nominal dibawah Rp 500.000 diperbolehkan untuk DP 50%</li>
        <li class="pl-4">Pengambilan Barang Dapat Dilakukan Setelah Pelunasan</li>
        <li class="pl-4">
            Pembayaran dapat ditransfer melalui
            <strong class="font-semibold text-gray-800">BANK BCA Rek. 3750183882 a.n. Eka Yuliana</strong>
        </li>
    </ul>

    <!-- Pesan Terima Kasih -->
    <p class="mt-6 text-center text-sm text-gray-500 italic">
        ~ Terima kasih telah memilih Oktav Digital &amp; Offset Printing ~
    </p>
</div>
  </div>
  <button 
    onclick="printInvoice()" 
    class="fixed bottom-4 right-4 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg shadow-lg transition duration-300 ease-in-out transform hover:scale-105"
>
    🖨️ Cetak Invoice
</button>
  <script>
    function printInvoice() {
        window.print();
    }
</script>
 </body>
</html>
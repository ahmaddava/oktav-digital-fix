<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Invoice</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 0;
        }

        .page-break {
            page-break-after: always;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            background: white;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }

        .invoice-box {
            width: 100%;
            padding: 5mm 8mm;
            overflow: hidden;
        }

        /* Header layout */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-left {
            display: flex;
            flex-direction: column;
        }

        .header-left img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        .header-right table td {
            padding: 0;
            font-size: 10px;
            line-height: 1.3;
        }

        .header-right table td:first-child {
            padding-right: 8px;
        }

        /* Address + INVOICE row */
        .address-invoice-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2px;
        }

        .address-invoice-row .address {
            font-size: 10px;
            line-height: 1.3;
        }

        .invoice-title {
            font-weight: bold;
            font-size: 10px;
            font-style: italic;
            text-align: center;
            flex: 1;
            margin-right: 60px;
        }

        /* Main product table */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            font-size: 10px;
        }

        .product-table thead tr {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
        }

        .product-table th {
            font-weight: bold;
            padding: 2px 4px;
            font-size: 10px;
        }

        .product-table td {
            padding: 3px 4px;
            font-size: 10px;
            vertical-align: top;
        }

        .product-table tfoot tr {
            border-top: 1.5px solid #000;
        }

        /* Bottom section */
        .bottom-section {
            display: flex;
            justify-content: space-between;
            margin-top: 2px;
            font-size: 10px;
        }

        .bottom-left {
            width: 58%;
        }

        .bottom-right {
            width: 42%;
        }

        .bottom-right table {
            width: 100%;
        }

        .bottom-right table td {
            padding: 0px 2px;
            font-size: 10px;
        }

        .terbilang {
            font-style: italic;
            font-size: 10px;
            margin-bottom: 2px;
        }

        .signature-row {
            display: flex;
            margin-top: 2px;
        }

        .signature-col {
            width: 50%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding-left: 16px;
            font-size: 10px;
        }

        .signature-space {
            height: 45px;
        }

        /* Footer */
        .footer {
            margin-top: 0px;
            font-size: 10px;
        }

        .footer p, .footer li {
            font-size: 10px;
            line-height: 1.2;
        }

        .footer ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer .closing {
            text-align: center;
            font-style: italic;
            margin-top: 1px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        @media print {
            html, body {
                width: 210mm;
                height: 148mm;
            }
            .no-print, button {
                display: none !important;
            }
            .invoice-box {
                width: 210mm;
                padding: 5mm 8mm;
            }
        }

        @media screen {
            .invoice-box {
                max-width: 210mm;
                margin: 0 auto;
                border: 1px solid #ccc;
            }
        }
    </style>
</head>

<body>
    @php
        $chunks = $invoice->invoiceProducts->chunk(13);
    @endphp

    @foreach ($chunks as $chunkIndex => $chunk)
        <div class="invoice-box {{ !$loop->last ? 'page-break' : '' }}">

            <!-- HEADER -->
            <div class="header">
                <div class="header-left">
                    <img alt="Oktav Printing logo" src="{{ asset('images/Logo Oktav.png') }}" />
                </div>
                <div class="header-right">
                    <table>
                        <tr>
                            <td>No. Inv</td>
                            <td>{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td>Yth.</td>
                            <td>{{ $invoice->name_customer }}</td>
                        </tr>
                        <tr>
                            <td>Telp</td>
                            <td>{{ $invoice->customer_phone }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- ADDRESS + INVOICE TITLE -->
            <div class="address-invoice-row">
                <div class="address">
                    <div>Jl. Ciputat Raya No. 30</div>
                    <div>Telp/Whatsapp, 0881 0110 60611</div>
                </div>
                <div class="invoice-title">INVOICE</div>
                <div style="text-align: right; white-space: nowrap;">
                    Jakarta, {{ \Carbon\Carbon::parse($invoice->created_at)->locale('id')->translatedFormat('d F Y') }}
                </div>
            </div>

            <!-- PRODUCT TABLE -->
            <table class="product-table">
                <thead>
                    <tr>
                        <th class="text-left" style="width: 30px;">No.</th>
                        <th class="text-left" style="width: 180px;">Nama Pesanan</th>
                        <th class="text-left">Keterangan</th>
                        <th class="text-center" style="width: 35px;">Qty</th>
                        <th class="text-center" style="width: 90px;">Harga</th>
                        <th class="text-center" style="width: 90px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($chunk as $item)
                        <tr>
                            <td class="text-center">
                                @php $globalIndex = ($chunkIndex * 13) + $loop->iteration; @endphp
                                {{ $globalIndex }}
                            </td>
                            <td class="truncate" style="max-width: 180px;">{{ $item->product->product_name ?? $item->product_name }}</td>
                            <td class="truncate" style="max-width: 130px;">{{ $item->keterangan }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Rp</span>
                                    <span>{{ $item->price ? number_format($item->price, 0, ',', '.') : '-' }}</span>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Rp</span>
                                    <span>{{ $item->total_price ? number_format($item->total_price, 0, ',', '.') : '-' }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    @for ($i = $chunk->count(); $i < 13; $i++)
                        <tr>
                            <td class="text-center">&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Rp</span><span>-</span>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Rp</span><span>-</span>
                                </div>
                            </td>
                        </tr>
                    @endfor
                </tbody>
                <tfoot>
                    <tr><td colspan="6" style="padding:0;"></td></tr>
                </tfoot>
            </table>

            <!-- BOTTOM: Terbilang + Signatures + Totals -->
            <div class="bottom-section">
                <div class="bottom-left">
                    <!-- Terbilang -->
                    <div class="terbilang">Terbilang: {{ $terbilang }}</div>

                    <!-- Signatures -->
                    <div class="signature-row">
                        <div class="signature-col">
                            <span>Pelanggan,</span>
                            <div class="signature-space"></div>
                            <span class="font-bold">{{ $invoice->name_customer }}</span>
                        </div>
                        <div class="signature-col">
                            <span>Hormat Kami,</span>
                            <div class="signature-space"></div>
                            <span class="font-bold" style="font-style: italic;">Oktav Digital</span>
                        </div>
                    </div>
                </div>

                <div class="bottom-right" style="{{ $loop->last ? '' : 'visibility: hidden;' }}">
                    <table>
                        <tr>
                            <td class="text-right" style="padding-right: 6px;">Subtotal</td>
                            <td style="display: flex; justify-content: space-between;">
                                <span>Rp</span>
                                <span>{{ number_format($subtotal, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right" style="padding-right: 6px;">Diskon</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="text-right" style="padding-right: 6px;">Total</td>
                            <td style="display: flex; justify-content: space-between;">
                                <span>Rp</span>
                                <span>{{ number_format($subtotal, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right" style="padding-right: 6px;">Terbayarkan</td>
                            <td>
                                @php
                                    $terbayarkan = $invoice->status === 'paid' ? $subtotal : ($invoice->dp ?? 0);
                                @endphp
                                @if($terbayarkan > 0)
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Rp</span>
                                    <span>{{ number_format($terbayarkan, 0, ',', '.') }}</span>
                                </div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-right" style="padding-right: 6px;">Kurang</td>
                            <td style="display: flex; justify-content: space-between;">
                                <span>Rp</span>
                                <span>
                                    @php
                                        $kurang = $invoice->status === 'paid' ? 0 : $subtotal - ($invoice->dp ?? 0);
                                        echo number_format($kurang, 0, ',', '.');
                                    @endphp
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- FOOTER: Perhatian -->
            <div class="footer">
                <p class="font-bold">Perhatian</p>
                <ul>
                    <li>* Mohon Periksa File atau Settingan Sebelum di Cetak</li>
                    <li>* Kesalahan Setelan Cetak Bukan Tanggung Jawab Kami</li>
                    <li>* Transaksi dengan Nominal diatas Rp 500.000 Wajib Membayar Lunas</li>
                    <li>* Transaksi dengan Nominal dibawah Rp 500.000 diperbolehkan untuk DP 50 %</li>
                    <li>* Pengambilan Barang Dapat Dilakukan Setelah Pelunasan</li>
                    <li>* Pembayaran dapat ditransfer melalui BANK BCA Rek. 3750183882 a.n. Eka Yuliana</li>
                </ul>
                <p class="closing">~ Terima kasih telah memilih Oktav Digital &amp; Offset Printing ~</p>
            </div>
        </div>
    @endforeach
    <div id="print-guide" class="no-print" style="position:fixed;top:0;left:0;right:0;background:#1e293b;color:#fff;padding:12px 20px;z-index:9999;display:flex;align-items:center;justify-content:space-between;font-family:Arial,sans-serif;font-size:13px;box-shadow:0 2px 8px rgba(0,0,0,.3);">
        <div>
            <strong style="color:#fbbf24;">⚠️ Sebelum Print, pastikan setting berikut:</strong>
            <span style="margin-left:12px;">📄 Paper size: <strong>A5 (5.83 x 8.27)</strong></span>
            <span style="margin-left:12px;">🔄 Orientation: <strong>Landscape</strong></span>
            <span style="margin-left:12px;">📏 Margins: <strong>None</strong></span>
            <span style="margin-left:12px;">🔍 Scale: <strong>Default</strong></span>
        </div>
        <div>
            <button onclick="window.print()" style="background:#22c55e;color:#fff;font-weight:700;padding:8px 20px;border:none;border-radius:6px;cursor:pointer;font-size:14px;margin-right:8px;">🖨️ Cetak Invoice</button>
            <button onclick="document.getElementById('print-guide').style.display='none'" style="background:#64748b;color:#fff;padding:8px 12px;border:none;border-radius:6px;cursor:pointer;font-size:12px;">✕</button>
        </div>
    </div>
</body>
</html>

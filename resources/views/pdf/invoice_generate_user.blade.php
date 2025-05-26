<!DOCTYPE html>
<html>
<head>
    <title>{{ $invoice->invoice_no }}</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style type="text/css">
        .p1 {
            font-family: Arial, Helvetica, sans-serif;
            color: rgb(224, 25, 3);
            font-size: 14pt;
        }
        .p2 {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
        }
    </style>
</head>
<body>

<center class="p1">
    <u>INVOICE</u>        
</center>

<center class="p2">
    No. {{ $invoice->invoice_no ?? '-' }}
</center>

<hr>

<table class='table table-borderless table-sm' style="font-size: 8px;">
    <tr>
        <td>
            <table class='table table-borderless table-sm' style="font-size: 8px;">
                <tr><th colspan="2">Kepada : PT.{{ $config->nama_singkat ?? '-' }} INDONESIA</th></tr>
                <tr><th colspan="2">{{ $config->alamat ?? '-' }}</th></tr>
                <tr><th colspan="2">{{ $config->telepon ?? '-' }} , {{ $config->email ?? '-' }}</th></tr>
                <tr><th colspan="2"></th></tr>
                <tr><th width="35%">Nama Bengkel</th><td>{{ $bengkel->bengkel_name ?? '-' }}</td></tr>
                <tr><th>Kontak Bengkel</th><td>{{ $bengkel->phone ?? '-' }}</td></tr>
                <tr><th>Alamat</th><td>{{ $bengkel->address ?? '-' }}</td></tr>
                <tr><th>PIC Bengkel</th><td>{{ $invoice->create_by ?? '-' }}</td></tr>
            </table>
        </td>

        <td>
            <table class='table table-borderless table-sm' style="font-size: 8px;">
                <tr><th width="35%">Invoice Nomor</th><td>{{ $invoice->invoice_no ?? '-' }}</td></tr>
                <tr><th>Tanggal Invoice</th><td>{{ $invoice->created_date ?? '-' }}</td></tr>
                <tr><th>Status</th><td>{{ $invoice->status ?? '-' }}</td></tr>
                <tr><th>PPH</th><td>Rp {{ number_format($invoice->pph ?? 0, 0, ',', '.') }}</td></tr>
                <tr><th>Jasa</th><td>Rp {{ number_format($invoice->jasa_total ?? 0, 0, ',', '.') }}</td></tr>
                <tr><th>Part</th><td>Rp {{ number_format($invoice->part_total ?? 0, 0, ',', '.') }}</td></tr>
                <tr><th>Total</th>
                    <td>
                        Rp {{ number_format((($invoice->jasa_total ?? 0) - ($invoice->pph ?? 0)) + ($invoice->part_total ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<p class="p2"><b>Invoice Detail</b></p>

<table class='table table-bordered table-sm' style="font-size: 6pt;">
    <thead>
        <tr>
            <th width="15%">Service No</th>
            <th width="10%">Area</th>
            <th width="15%">Cabang</th>
            <th width="12%">Tanggal Service</th>
            <th width="8%">NOPOL</th>
            <th width="17%">Merk</th>
            <th width="10%">Nama barang</th>
            <th width="8%">Part</th>
            <th width="8%">Jasa</th>
            <th width="8%">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice_detail as $ind)
        <tr>
            <td>{{ $ind->service_no ?? '-' }}</td>
            <td>{{ $ind->area ?? '-' }}</td>
            <td>{{ $ind->branch ?? '-' }}</td>
            <td>{{ $ind->tanggal_service ?? '-' }}</td>
            <td>{{ $ind->nopol ?? '-' }}</td>
            <td>{{ $ind->type ?? '-' }}</td>
            <td>{{ $ind->service_name ?? '-' }}</td>
            <td>{{ isset($ind->part) ? 'Rp ' . number_format($ind->part, 0, ',', '.') : '-' }}</td>
            <td>{{ isset($ind->jasa) ? 'Rp ' . number_format($ind->jasa, 0, ',', '.') : '-' }}</td>
            <td>
                Rp {{ number_format(($ind->part ?? 0) + ($ind->jasa ?? 0), 0, ',', '.') }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td colspan="4" style="border-bottom-style: hidden;border-left-style: hidden;"></td>
            <th colspan="3">Total</th>
            <td>Rp {{ number_format($invoice->part_total ?? 0, 0, ',', '.') }}</td>
            <td>Rp {{ number_format($invoice->jasa_total ?? 0, 0, ',', '.') }}</td>
            <td>Rp {{ number_format(($invoice->part_total ?? 0) + ($invoice->jasa_total ?? 0), 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="4" style="border-bottom-style: hidden;border-left-style: hidden;"></td>
            <th colspan="3">PPH 2%</th>
            <td></td>
            <td>- Rp {{ number_format($invoice->pph ?? 0, 0, ',', '.') }}</td>
            <td></td>
        </tr>
        <tr>
            <td colspan="4" style="border-bottom-style: hidden;border-left-style: hidden;"></td>
            <th colspan="3">Total</th>
            <td>Rp {{ number_format($invoice->part_total ?? 0, 0, ',', '.') }}</td>
            <td>Rp {{ number_format(($invoice->jasa_total ?? 0) - ($invoice->pph ?? 0), 0, ',', '.') }}</td>
            <td>Rp {{ number_format((($invoice->jasa_total ?? 0) - ($invoice->pph ?? 0)) + ($invoice->part_total ?? 0), 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

</body>
</html>

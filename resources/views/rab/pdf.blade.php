<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Penawaran Harga {{ $project->project_name }}</title>
    <style>
        @page { margin: 150px 0 90px 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #000; margin: 0; }

        .header { position: fixed; top: -150px; left: 0; right: 0; }
        .footer { position: fixed; bottom: -90px; left: 0; right: 0; }
        .header img, .footer img { width: 100%; display: block; }

        .content { padding: 20px 50px 0 50px; }
        .meta { line-height: 1.4; }
        .section-title { font-weight: bold; font-size: 10px; margin: 28px 0 10px 0; }

        table.info { width: 100%; border-collapse: collapse; margin-left: 25px; }
        table.info td { border: none; padding: 1px 0; vertical-align: top; }
        table.info td.label { width: 55px; font-style: italic; }
        table.info td.sep { width: 12px; }

        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items thead th { background: #000; color: #fff; padding: 5px 6px; font-size: 9px; text-align: center; border: none; }
        table.items td { padding: 6px; vertical-align: top; border: none; }
        table.items tr.item-row td { border-bottom: 1px solid #000; }
        table.items tr.empty-row td { border-bottom: 1px solid #000; height: 20px; }

        .desc p  { margin: 0 0 2px 0; padding: 0; }
        .desc ul, .desc ol { margin: 2px 0 4px 0; padding-left: 14px; }
        .desc-titled > p:first-child { font-weight: bold; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }

        table.summary { width: 100%; border-collapse: collapse; margin-top: 12px; border-top: 2px solid #000; }
        table.summary td { padding: 4px 6px; border-bottom: 1px solid #000; }
        table.summary td.lbl { text-align: right; font-weight: bold; width: 79%; }
        table.summary tr.total td.val { background: #000; color: #fff; font-weight: bold; }
        table.summary tr.total td.lbl { border-bottom: none; }

        .notes { margin-top: 18px; line-height: 1.5; }
    </style>
</head>
<body>

@php
    $rp = fn ($n) => 'Rp. ' . number_format((float) $n, 0, ',', '.');
    $diskon = (float) $offer->discount;
@endphp

<div class="header">
    <img src="{{ public_path('images/header-penawaran.png') }}">
</div>
<div class="footer">
    <img src="{{ public_path('images/footer-penawaran.png') }}">
</div>

<div class="content">

    <div class="meta">
        No. {{ $offer->offer_number ?? '-' }}<br>
        {{ $offer->offer_date ? \Carbon\Carbon::parse($offer->offer_date)->translatedFormat('d F Y') : '-' }}<br>
        Lampiran 1<br>
        Penawaran Harga
    </div>

    <div class="section-title">Detail Proyek</div>
    <table class="info">
        <tr><td class="label">Project</td><td class="sep">:</td><td>{{ $project->project_name }}</td></tr>
        <tr><td class="label">Client</td><td class="sep">:</td><td>{{ $offer->contact_name }}</td></tr>
        <tr><td class="label">Address</td><td class="sep">:</td><td>{{ $project->customer->user->address ?? '-' }}</td></tr>
        <tr><td class="label">Phone</td><td class="sep">:</td><td>{{ $project->customer->user->phone ?? '-' }}</td></tr>
    </table>

    <div class="section-title">Detail Penawaran</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:50%">Nama Produk</th>
                <th style="width:9%">Qty</th>
                <th style="width:20%">Harga</th>
                <th style="width:21%">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($offer->items as $item)
                <tr class="item-row">
                    <td>
                        @if($item->category_name)
                            <div class="bold">
                                {{ $item->category_name }}
                                @if($item->floor_name) ({{ $item->floor_name }}) @endif
                            </div>
                        @endif

                        @if($item->description)
                            @php
                                // izinkan hanya tag yang aman & dikenali dompdf
                                $desc = strip_tags($item->description, '<p><br><ul><ol><li><strong><b><em><i><u>');
                            @endphp
                            <div class="desc {{ $item->category_name ? '' : 'desc-titled' }}">
                                {!! $desc !!}
                            </div>
                        @endif
                    </td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($item->volume, 5, '.', ''), '0'), '.') }}</td>
                    <td class="text-right">{{ $rp($item->price) }}</td>
                    <td class="text-right">{{ $rp($item->total) }}</td>
                </tr>
            @endforeach

            @for($i = $offer->items->count(); $i < 4; $i++)
                <tr class="empty-row"><td colspan="4">&nbsp;</td></tr>
            @endfor
        </tbody>
    </table>
    <div class="desc-line" style="margin-top:6px">
        @if($project->start_date)
            Tanggal Event : {{ $project->start_date->translatedFormat('d F Y') }}<br>
        @endif
        @if($project->project_location)
            Lokasi Event : {{ $project->project_location }}
        @endif
    </div>
    @php
        $rounded = floor((float) $offer->grand_total / 100000) * 100000;
    @endphp

    <table class="summary">
        <tr>
            <td class="lbl">SUBTOTAL</td>
            <td class="text-right">{{ $rp($offer->subtotal) }}</td>
        </tr>
        <tr>
            <td class="lbl">DISCOUNT</td>
            <td class="text-right">{{ $rp($offer->discount) }}</td>
        </tr>
        <tr>
            <td class="lbl">SUBTOTAL AFTER DISCOUNT</td>
            <td class="text-right">{{ $rp($offer->subtotal_after_discount) }}</td>
        </tr>
        <tr>
            <td class="lbl">TAX RATE</td>
            <td class="text-right">{{ rtrim(rtrim(number_format($offer->tax_rate, 2, ',', '.'), '0'), ',') }}%</td>
        </tr>
        <tr>
            <td class="lbl">TOTAL TAX</td>
            <td class="text-right">{{ $rp($offer->tax_total) }}</td>
        </tr>
        <tr>
            <td class="lbl">SHIPPING / HANDLING</td>
            <td class="text-right">{{ $rp($offer->shipping) }}</td>
        </tr>
        <tr>
            <td class="lbl">GRAND TOTAL</td>
            <td class="text-right bold">{{ $rp($offer->grand_total) }}</td>
        </tr>
        <tr class="total">
            <td class="lbl">DIBULATKAN</td>
            <td class="val text-right">{{ $rp($rounded) }}</td>
        </tr>
    </table>

    {{-- @if($offer->notes)
        <div class="notes">
            <span class="bold">Catatan:</span><br>
            {!! nl2br(e($offer->notes)) !!}
        </div>
    @endif --}}

</div>
</body>
</html>
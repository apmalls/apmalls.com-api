<!doctype html>

@php
    $enabledContentCount = collect([
        $template->show_name,
        $template->show_price,
        $template->show_sku,
        $template->show_manufacture_date,
        $template->show_expiry_date,
        $template->show_barcode,
        $template->show_qr,
    ])->filter()->count();
    $hasDateFields = $template->show_manufacture_date || $template->show_expiry_date;
    $isMicro = $template->height <= 20;
    $isDense = !$isMicro && $template->height <= 30 && $enabledContentCount > 4;
@endphp

<html>

<head>

    <meta charset="utf-8">

    <title>{{ $template->name }} - {{ $template->width }} x {{ $template->height }} mm Barcode Labels</title>

    <style>
        * {

            box-sizing: border-box;

        }

        body {

            margin: 0;

            padding: 20px;

            font-family: Arial, sans-serif;

        }

        .sheet-header {

            width: 100%;

            margin: 0 0 3mm;

            border-collapse: collapse;

            border-bottom: 1px solid #111;

        }

        .sheet-header td {

            padding: 0 1.5mm 2mm;

            color: #111;

            font-size: 11px;

        }

        .sheet-header .template-name {

            font-size: 14px;

            font-weight: bold;

        }

        .sheet-header .template-size {

            text-align: right;

            white-space: nowrap;

        }

        .label {

            width: {{ $template->width }}mm;

            height: {{ $template->height }}mm;

            border: 1px dashed #ddd;

            display: inline-flex;

            flex-direction: column;

            justify-content: center;

            align-items: center;

            gap: {{ $isMicro ? 0.15 : ($isDense ? 0.3 : 0.6) }}mm;

            margin: 1.5mm;

            padding: {{ $isMicro ? 0.6 : ($isDense ? 1 : 1.5) }}mm;

            text-align: center;

            overflow: hidden;

            vertical-align: top;

            page-break-inside: avoid;

        }

        .name {

            font-size: {{ $isMicro ? max(6, $template->font_size - 2) : $template->font_size }}px;

            font-weight: bold;

            line-height: 1.1;

            max-height: {{ $isMicro || $isDense ? 1.25 : 2.4 }}em;

            width: 100%;

            overflow: hidden;

            word-break: break-word;

            overflow-wrap: anywhere;

            text-align: center;

            @if ($isMicro || $isDense)
                white-space: nowrap;
                text-overflow: ellipsis;
            @endif

        }

        .name-long {

            font-size: {{ max(6, $template->font_size - ($isMicro ? 3 : 2)) }}px;

        }

        .price {

            font-size: {{ $isMicro ? max(6, $template->font_size - 2) : $template->font_size }}px;

            line-height: 1.05;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            width: 100%;

            text-align: center;

        }

        .sku {

            font-size: {{ $isMicro ? max(6, $template->font_size - 2) : $template->font_size - 1 }}px;

            line-height: 1.05;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            width: 100%;

            text-align: center;

        }

        .date {

            font-size: {{ max(6, $template->font_size - ($isMicro ? 3 : 2)) }}px;

            line-height: 1;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            width: 100%;

            text-align: center;

        }

        .barcode {

            width: 100%;

            padding: 0 1.2mm;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;

        }

        .barcode svg {

            display: block;

            width: 100% !important;

            max-width: 100% !important;

            height: {{ $isMicro
                ? max(4, min(6, $template->height * 0.32))
                : ($isDense
                    ? max(7, min(9, $template->height * 0.3))
                    : ($hasDateFields
                        ? max(8, min(11, $template->height * 0.32))
                        : max(9, min(13, $template->height * 0.38)))) }}mm !important;

            margin: 0 auto;

        }

        .barcode-value {

            font-size: {{ $isMicro
                ? max(6, $template->font_size - 2)
                : max(7, $template->font_size - 1) }}px;

            line-height: 1.05;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            width: 100%;

            text-align: center;

        }

        .qr svg {

            width: {{ min($template->width, $template->height) * ($isMicro ? 0.36 : 0.45) }}mm;

            height: {{ min($template->width, $template->height) * ($isMicro ? 0.36 : 0.45) }}mm;

        }

        .qr {

            width: 100%;

            overflow: hidden;

        }

        .qr svg {

            display: block;

            margin: 0 auto;

        }

        @page {

            margin: 10px;

        }
    </style>

</head>

<body>

    <table class="sheet-header" aria-label="Barcode template details">
        <tbody>
            <tr>
                <td class="template-name">Template: {{ $template->name }}</td>
                <td class="template-size">Label size: {{ $template->width }} x {{ $template->height }} mm</td>
            </tr>
        </tbody>
    </table>

    @foreach ($items as $product)
        @php
            $barcodeTypeMap = [
                'CODE128' => 'C128',
            ];
            $barcodeType = $barcodeTypeMap[strtoupper($product->barcode_type ?? 'C128')]
                ?? ($product->barcode_type ?: 'C128');
        @endphp

        <div class="label">

            @if ($template->show_name)
                <div class="name {{ mb_strlen($product->name) > 24 ? 'name-long' : '' }}">

                    {{ $product->name }}

                </div>
            @endif

            @if ($template->show_barcode)
                <div class="barcode">

                    {!! app('DNS1D')->getBarcodeSVG(
                        $product->barcode,

                        $barcodeType,

                        1,

                        24,

                        'black',

                        false,
                    ) !!}

                </div>

                <div class="barcode-value">

                    {{ $product->barcode }}

                </div>
            @endif

            @if ($template->show_qr)
                <div class="qr">

                    {!! app('DNS2D')->getBarcodeSVG(
                        $product->barcode,

                        'QRCODE',
                    ) !!}

                </div>
            @endif

            @if ($template->show_sku)
                <div class="sku">

                    {{ $product->sku }}

                </div>
            @endif

            @if ($template->show_manufacture_date && $product->manufacture_date)
                <div class="date">

                    MFG: {{ $product->manufacture_date->format('d/m/Y') }}

                </div>
            @endif

            @if ($template->show_expiry_date && $product->expiry_date)
                <div class="date">

                    EXP: {{ $product->expiry_date->format('d/m/Y') }}

                </div>
            @endif

            @if ($template->show_price)
                <div class="price">

                    ₹ {{ number_format($product->selling_price, 2) }}

                </div>
            @endif

        </div>
    @endforeach

</body>

</html>

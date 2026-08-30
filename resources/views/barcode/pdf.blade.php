<!doctype html>

<html>

<head>

    <meta charset="utf-8">

    <style>
        * {

            box-sizing: border-box;

        }

        body {

            margin: 0;

            padding: 20px;

            font-family: Arial, sans-serif;

        }

        .label {

            width: {{ $template->width }}mm;

            height: {{ $template->height }}mm;

            border: 1px dashed #ddd;

            display: inline-flex;

            flex-direction: column;

            justify-content: flex-start;

            gap: 0.6mm;

            margin: 1.5mm;

            padding: 1.5mm;

            text-align: center;

            overflow: hidden;

            vertical-align: top;

            page-break-inside: avoid;

        }

        .name {

            font-size: {{ $template->font_size }}px;

            font-weight: bold;

            line-height: 1.05;

            max-height: 2.1em;

            overflow: hidden;

            word-break: break-word;

        }

        .price {

            font-size: {{ $template->font_size }}px;

            line-height: 1.05;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }

        .sku {

            font-size: {{ $template->font_size - 1 }}px;

            line-height: 1.05;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }

        .barcode {

            width: 100%;

            padding: 0 1.2mm;

            overflow: hidden;

        }

        .barcode svg {

            display: block;

            width: 100% !important;

            max-width: 100% !important;

            height: {{ max(9, min(13, $template->height * 0.38)) }}mm !important;

        }

        .barcode-value {

            font-size: {{ max(7, $template->font_size - 1) }}px;

            line-height: 1.05;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }

        .qr svg {

            width: {{ min($template->width, $template->height) * 0.45 }}mm;

            height: {{ min($template->width, $template->height) * 0.45 }}mm;

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
                <div class="name">

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

            @if ($template->show_price)
                <div class="price">

                    ₹ {{ number_format($product->selling_price, 2) }}

                </div>
            @endif

        </div>
    @endforeach

</body>

</html>

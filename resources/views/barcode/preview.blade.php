<!doctype html>

<html>

<head>

    <meta charset="utf-8">

    <style>
        body {

            margin: 0;

            padding: 20px;

            font-family: Arial;

        }

        .label {

            width: {{ $template->width }}mm;

            height: {{ $template->height }}mm;

            border: 1px dashed #ddd;

            display: inline-block;

            margin: 3px;

            padding: 3px;

            text-align: center;

            overflow: hidden;

        }

        .name {

            font-size: {{ $template->font_size }}px;

            font-weight: bold;

        }

        .price {

            font-size: {{ $template->font_size }}px;

        }

        .sku {

            font-size: {{ $template->font_size - 1 }}px;

        }
    </style>

</head>

<body>

    @foreach ($items as $product)
        <div class="label">

            @if ($template->show_name)
                <div class="name">

                    {{ $product->name }}

                </div>
            @endif

            @if ($template->show_barcode)
                {!! \Milon\Barcode\Facades\DNS1D::getBarcodeSVG(
                    $product->barcode,

                    $product->barcode_type,
                ) !!}
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

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tax Invoice</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table td,
        table th {
            border: 1px solid #dcdcdc;
            padding: 7px;
            vertical-align: top;
        }

        .border-none td {
            border: none;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
        }

        .heading {
            background: #efefef;
            font-weight: bold;
        }
    </style>

</head>

<body>

    <table class="border-none">

        <tr>

            <td width="70%">

                <h2 class="title">AP Malls</h2>

                Grocery | Hardware | Paint | Electrical Products

                <br>

                GSTIN : XXXXXXXXXXXXXX

                <br>

                Phone : +91-9876543210

                <br>

                Email : support@apmalls.com

            </td>

            <td class="text-right">

                <h2>TAX INVOICE</h2>

                <strong>Invoice No</strong>

                <br>

                {{ $order->invoice_no }}

                <br><br>

                <strong>Invoice Date</strong>

                <br>

                {{ optional($order->invoice_date)->format('d-m-Y') }}

                <br><br>

                <strong>Order No</strong>

                <br>

                {{ $order->sale_no }}

            </td>

        </tr>

    </table>

    <br>

    <table>

        <tr class="heading">

            <td width="50%">Bill To</td>

            <td width="50%">Ship To</td>

        </tr>

        <tr>

            <td>

                <strong>{{ $order->customer->name }}</strong>

                <br>

                {{ $order->billingAddress?->address }}

                <br>

                {{ $order->billingAddress?->city }}

                <br>

                {{ $order->billingAddress?->state }}

                <br>

                {{ $order->billingAddress?->country }}

                <br>

                {{ $order->billingAddress?->pincode }}

            </td>

            <td>

                <strong>{{ $order->customer->name }}</strong>

                <br>

                {{ $order->shippingAddress?->address }}

                <br>

                {{ $order->shippingAddress?->city }}

                <br>

                {{ $order->shippingAddress?->state }}

                <br>

                {{ $order->shippingAddress?->country }}

                <br>

                {{ $order->shippingAddress?->pincode }}

            </td>

        </tr>

    </table>

    <br>

    <table>

        <tr class="heading">

            <th>#</th>

            <th>Product</th>

            <th>Qty</th>

            <th>Rate</th>

            <th>GST %</th>

            <th>Discount</th>

            <th>Total</th>

        </tr>

        @foreach ($order->items as $item)

            <tr>

                <td class="text-center">

                    {{ $loop->iteration }}

                </td>

                <td>

                    {{ $item->product->name }}

                </td>

                <td class="text-center">

                    {{ $item->quantity }}

                </td>

                <td class="text-right">

                    ₹ {{ number_format($item->selling_price,2) }}

                </td>

                <td class="text-center">

                    {{ $item->tax_percent }}

                </td>

                <td class="text-right">

                    ₹ {{ number_format($item->discount_amount,2) }}

                </td>

                <td class="text-right">

                    ₹ {{ number_format($item->line_total,2) }}

                </td>

            </tr>

        @endforeach

    </table>

    <br>

    <table>

        <tr>

            <td width="65%">

                <strong>Payment Details</strong>

                <br><br>

                Payment Mode :

                {{ optional($order->payments->first()?->paymentMode)->name }}

                <br>

                Order Status :

                {{ $order->status }}

            </td>

            <td width="35%">

                <table>

                    <tr>

                        <td>Subtotal</td>

                        <td class="text-right">

                            ₹ {{ number_format($order->sub_total,2) }}

                        </td>

                    </tr>

                    <tr>

                        <td>Discount</td>

                        <td class="text-right">

                            ₹ {{ number_format($order->discount_amount,2) }}

                        </td>

                    </tr>

                    <tr>

                        <td>Tax</td>

                        <td class="text-right">

                            ₹ {{ number_format($order->tax_amount,2) }}

                        </td>

                    </tr>

                    <tr>

                        <td>Shipping</td>

                        <td class="text-right">

                            ₹ {{ number_format($order->shipping_charge,2) }}

                        </td>

                    </tr>

                    <tr>

                        <td>Other Charge</td>

                        <td class="text-right">

                            ₹ {{ number_format($order->other_charge,2) }}

                        </td>

                    </tr>

                    <tr>

                        <td><strong>Grand Total</strong></td>

                        <td class="text-right">

                            <strong>

                                ₹ {{ number_format($order->grand_total,2) }}

                            </strong>

                        </td>

                    </tr>

                    <tr>

                        <td>Paid</td>

                        <td class="text-right">

                            ₹ {{ number_format($order->paid_amount,2) }}

                        </td>

                    </tr>

                    <tr>

                        <td>Due</td>

                        <td class="text-right">

                            ₹ {{ number_format($order->due_amount,2) }}

                        </td>

                    </tr>

                </table>

            </td>

        </tr>

    </table>

    <br><br>

    <table class="border-none">

        <tr>

            <td width="65%">

                <strong>Remarks</strong>

                <br>

                {{ $order->remarks ?? '-' }}

                <br><br>

                <strong>Terms & Conditions</strong>

                <ol>

                    <li>Goods once sold will not be taken back.</li>

                    <li>Subject to Purnea jurisdiction.</li>

                    <li>Thank you for shopping with AP Malls.</li>

                </ol>

            </td>

            <td class="text-center">

                <br><br><br><br><br>

                _______________________

                <br>

                Authorized Signatory

            </td>

        </tr>

    </table>

</body>

</html>

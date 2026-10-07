<x-mail::message>
# {{ $event === 'order_placed' ? 'New AP Malls order' : 'AP Malls delivery completed' }}

Order: **{{ $details['order_number'] }}**<br>
Source: {{ $details['source'] }}<br>
{{ $event === 'order_placed' ? 'Placed' : 'Delivered' }}: {{ $details['occurred_at'] }}<br>
Customer: {{ $details['customer_name'] }}
@if($audience === 'admin')
@if(!empty($details['customer_email']))
<br>Email: {{ $details['customer_email'] }}
@endif
@if(!empty($details['customer_mobile']))
<br>Mobile: {{ $details['customer_mobile'] }}
@endif
@endif

@if($event === 'delivery_completed')
@if(!empty($details['courier_name']))
Delivery person: **{{ $details['courier_name'] }}**<br>
@endif
@if(!empty($details['courier_phone']))
Mobile: {{ $details['courier_phone'] }}<br>
@endif
Confirmation: {{ $details['confirmation_method'] }}<br>
COD collected: INR {{ number_format($details['cod_collected'], 2) }}
@endif

<x-mail::table>
| Item | Qty | Unit price | Total |
| :--- | ---: | ---: | ---: |
@foreach($details['items'] as $item)
| {{ str_replace(['|', "\r", "\n"], ' ', $item['name']) }}<br>Discount: {{ number_format($item['discount'], 2) }} / Tax: {{ number_format($item['tax'], 2) }} | {{ $item['quantity'] }} {{ str_replace(['|', "\r", "\n"], ' ', $item['unit'] ?? '') }} | {{ number_format($item['price'], 2) }} | {{ number_format($item['total'], 2) }} |
@endforeach
</x-mail::table>

All amounts in INR.<br>
Subtotal: {{ number_format($details['sub_total'], 2) }}<br>
Discount: {{ number_format($details['discount'], 2) }}<br>
Tax: {{ number_format($details['tax'], 2) }}<br>
Shipping: {{ number_format($details['shipping'], 2) }}<br>
Other charges: {{ number_format($details['other_amount'], 2) }}<br>
Round off: {{ number_format($details['round_off'], 2) }}<br>
**Total: {{ number_format($details['total'], 2) }}**<br>
Paid: {{ number_format($details['paid'], 2) }}<br>
Outstanding: {{ number_format($details['due'], 2) }}<br>
Payment status: {{ ucfirst($details['payment_status']) }}
@if(count($details['payment_methods']))
<br>Payment methods: {{ implode(', ', $details['payment_methods']) }}
@elseif($details['due'] > 0)
<br>Payment collection outstanding.
@endif

Thank you,<br>
AP Malls
</x-mail::message>

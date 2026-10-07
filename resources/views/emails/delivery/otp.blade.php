<x-mail::message>
# Your AP Malls order is on its way

Order: **{{ $orderNumber }}**

@if($courierName)
Delivery person: **{{ $courierName }}**
@endif
@if($courierPhone)
Mobile: {{ $courierPhone }}
@endif

Your delivery code is:

# {{ $otp }}

Valid until **{{ \Carbon\Carbon::parse($expiresAt)->setTimezone(config('app.business_timezone', 'Asia/Kolkata'))->format('d M Y, h:i A') }} IST** (24 hours from issuance).

Share this delivery code only when the delivery person is physically handing over your order, after checking the goods.

@if($amountDue > 0)
Before sharing the code, pay the outstanding **INR {{ number_format($amountDue, 2) }}**.
@endif

Do not share it over the phone before delivery. This is a delivery code, not a login or payment authorization code.

If you request a replacement, only the newest code will work. If there is a problem, raise a dispute or contact AP Malls for manager assistance.

Thank you,<br>
AP Malls
</x-mail::message>

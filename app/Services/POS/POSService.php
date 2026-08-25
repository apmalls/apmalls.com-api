<?php

namespace App\Services\POS;

use App\Helpers\NumberHelper;
use App\Models\POS\CashRegister;
use App\Models\POS\CashRegisterSession;
use App\Models\POS\PosHold;
use App\Models\Payment\Payment;
use App\Models\Customer\Customer;
use App\Models\Product\Product;
use App\Models\Sale\SaleOrder;
use App\Repositories\Contracts\CashRegisterRepositoryInterface;
use App\Repositories\Contracts\CashRegisterSessionRepositoryInterface;
use App\Repositories\Contracts\PosHoldRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\CashRegisterTransactionServiceInterface;
use App\Services\Contracts\PaymentServiceInterface;
use App\Services\Contracts\POSServiceInterface;

use App\Services\Contracts\SaleServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\PaymentModeRepositoryInterface;
use App\Repositories\Contracts\CashRegisterTransactionRepositoryInterface;

class POSService implements POSServiceInterface
{
    public function __construct(

        protected CashRegisterRepositoryInterface $cashRegisterRepository,

        protected CashRegisterSessionRepositoryInterface $cashRegisterSessionRepository,

        protected SaleServiceInterface $saleService,

        protected PaymentServiceInterface $paymentService,

        protected CashRegisterTransactionServiceInterface $cashRegisterTransactionService,

        protected ProductRepositoryInterface $productRepository,

        protected PosHoldRepositoryInterface $posHoldRepository,

        protected SaleRepositoryInterface $saleRepository,

        protected PaymentModeRepositoryInterface $paymentModeRepository,

        protected CustomerRepositoryInterface $customerRepository,

        protected CashRegisterTransactionRepositoryInterface $cashRegisterTransactionRepository,

    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Cash Register Listing
    |--------------------------------------------------------------------------
    */

    public function registers(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {

        return $this->cashRegisterRepository
            ->paginate($perPage, $filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Register Find
    |--------------------------------------------------------------------------
    */

    public function register(
        int $id
    ): CashRegister {

        return $this->cashRegisterRepository
            ->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Register
    |--------------------------------------------------------------------------
    */

    public function createRegister(
        array $data
    ): CashRegister {

        return DB::transaction(function () use ($data) {

            if (empty($data['register_no'])) {

                $data['register_no'] = NumberHelper::generate(
                    CashRegister::class,
                    'register_no',
                    'REG'
                );
            }

            $data = $this->normalizeRegisterData($data);

            $this->ensureSingleOpenRegisterAssignment($data);

            $data['created_by'] = Auth::id();

            return $this->cashRegisterRepository
                ->create($data);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Register
    |--------------------------------------------------------------------------
    */

    public function updateRegister(
        int $id,
        array $data
    ): CashRegister {

        return DB::transaction(function () use ($id, $data) {

            $data = $this->normalizeRegisterData($data);

            $this->ensureSingleOpenRegisterAssignment($data, $id);

            $data['updated_by'] = Auth::id();

            return $this->cashRegisterRepository
                ->update($id, $data);

        });
    }

    private function normalizeRegisterData(array $data): array
    {
        $data['opening_balance'] = $data['opening_balance'] ?? 0;

        $data['opened_at'] = $data['opened_at'] ?? now();

        // Some existing databases still have a non-null closed_at column.
        $data['closed_at'] = $data['closed_at'] ?? $data['opened_at'];

        return $data;
    }

    private function ensureSingleOpenRegisterAssignment(
        array $data,
        ?int $ignoreId = null
    ): void {
        if (
            empty($data['user_id'])
            || ($data['status'] ?? CashRegister::STATUS_OPEN) !== CashRegister::STATUS_OPEN
        ) {
            return;
        }

        $alreadyAssigned = CashRegister::query()
            ->where('user_id', $data['user_id'])
            ->where('status', CashRegister::STATUS_OPEN)
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($alreadyAssigned) {
            throw ValidationException::withMessages([
                'user_id' => 'This cashier is already assigned to another open register.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Register
    |--------------------------------------------------------------------------
    */

    public function deleteRegister(
        int $id
    ): bool {

        return DB::transaction(function () use ($id) {

            $register = $this->cashRegisterRepository
                ->findOrFail($id);

            $openSession = $register
                ->sessions()
                ->where('status', CashRegisterSession::STATUS_OPEN)
                ->exists();

            if ($openSession) {

                throw ValidationException::withMessages([

                    'register' => 'Register has an active session.'

                ]);
            }

            return $this->cashRegisterRepository
                ->delete($id);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Session Listing
    |--------------------------------------------------------------------------
    */

    public function sessions(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {

        return $this->cashRegisterSessionRepository
            ->paginate($perPage, $filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Session Find
    |--------------------------------------------------------------------------
    */

    public function session(
        int $id
    ): CashRegisterSession {

        return $this->cashRegisterSessionRepository
            ->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Open Session
    |--------------------------------------------------------------------------
    */

    public function openSession(
        array $data
    ): CashRegisterSession {

        return DB::transaction(function () use ($data) {

            $userId = (int) Auth::id();

            $register = $this->cashRegisterRepository
                ->getOpenRegisterByUser($userId);

            if (!$register) {

                throw ValidationException::withMessages([

                    'cash_register_id' => 'No cash register assigned to this cashier.',

                ]);
            }

            if (
                !empty($data['cash_register_id'])
                && (int) $data['cash_register_id'] !== (int) $register->id
            ) {

                throw ValidationException::withMessages([

                    'cash_register_id' => 'Cashier can only open their assigned register.',

                ]);
            }

            $alreadyOpen = $this->cashRegisterSessionRepository
                ->current($userId);

            if ($alreadyOpen) {

                throw ValidationException::withMessages([

                    'session' => 'Cash Register already opened.'

                ]);
            }

            if (empty($data['session_no'])) {

                $data['session_no'] = NumberHelper::generate(
                    CashRegisterSession::class,
                    'session_no',
                    'SESSION'
                );
            }

            $data['cash_register_id'] = $register->id;

            $data['user_id'] = $userId;

            $data['opening_balance'] = $register->opening_balance;

            $data['expected_balance'] = $data['opening_balance'];

            $data['opened_at'] = now();

            $data['status'] = CashRegisterSession::STATUS_OPEN;

            $data['created_by'] = $userId;

            return $this->cashRegisterSessionRepository
                ->create($data)
                ->load([
                    'register',
                    'cashier',
                    'transactions.paymentMode',
                ]);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Close Session
    |--------------------------------------------------------------------------
    */

    /*
|--------------------------------------------------------------------------
| Close Session
|--------------------------------------------------------------------------
*/

    public function closeSession(
        int $id,
        array $data
    ): CashRegisterSession {

        return DB::transaction(function () use ($id, $data) {

            $session = $this->cashRegisterSessionRepository
                ->findOrFail($id);

            if ($session->status !== CashRegisterSession::STATUS_OPEN) {

                throw ValidationException::withMessages([
                    'session' => 'Cash register session is already closed.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Cash Totals
            |--------------------------------------------------------------------------
            */

            $cashIn = $this->cashRegisterTransactionRepository
                ->totalCashIn($session->id);

            $cashOut = $this->cashRegisterTransactionRepository
                ->totalCashOut($session->id);

            /*
            |--------------------------------------------------------------------------
            | Cash Sale Total
            |--------------------------------------------------------------------------
            */

            $cashSale = $this->cashRegisterTransactionRepository
                ->totalCashSale($session->id);

            /*
            |--------------------------------------------------------------------------
            | Expected Balance
            |--------------------------------------------------------------------------
            */

            $expectedBalance =
                $session->opening_balance
                + $cashSale
                + $cashIn
                - $cashOut;

            $closingBalance = $data['closing_balance'];

            $difference = $closingBalance - $expectedBalance;

            /*
            |--------------------------------------------------------------------------
            | Update Register Balance
            |--------------------------------------------------------------------------
            */

            $this->cashRegisterRepository
                ->update($session->cash_register_id, [

                    'closing_balance' => $closingBalance,

                    'closed_at' => now(),

                    'updated_by' => Auth::id(),

                ]);

            /*
            |--------------------------------------------------------------------------
            | Update Session
            |--------------------------------------------------------------------------
            */

            return $this->cashRegisterSessionRepository
                ->update($id, [

                    'closing_balance' => $closingBalance,

                    'expected_balance' => $expectedBalance,

                    'difference' => $difference,

                    'closed_at' => now(),

                    'remarks' => $data['remarks'] ?? null,

                    'status' => CashRegisterSession::STATUS_CLOSED,

                ]);
        });
    }

    /*
|--------------------------------------------------------------------------
| Hold Listing
|--------------------------------------------------------------------------
*/

    public function holds(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {

        return $this->posHoldRepository
            ->paginate($perPage, $filters);
    }

    public function currentHolds(
        int $perPage = 15
    ): LengthAwarePaginator {

        $session = $this->currentCashierSession();

        return $this->posHoldRepository
            ->paginate($perPage, [
                'cash_register_session_id' => $session->id,
                'status' => PosHold::STATUS_HOLD,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Hold Find
    |--------------------------------------------------------------------------
    */

    public function hold(
        int $id
    ): PosHold {

        return $this->posHoldRepository
            ->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Hold
    |--------------------------------------------------------------------------
    */

    public function createHold(
        array $data
    ): PosHold {

        return DB::transaction(function () use ($data) {

            $session = $this->currentCashierSession();

            $this->assertSessionMatchesCurrentCashier($data['cash_register_session_id'] ?? null, $session);

            $cart = $this->buildPosCart($data['items'] ?? []);

            if (empty($data['hold_no'])) {

                $data['hold_no'] = NumberHelper::generate(
                    PosHold::class,
                    'hold_no',
                    'HOLD'
                );
            }

            $data = [
                'hold_no' => $data['hold_no'],
                'cash_register_session_id' => $session->id,
                'customer_id' => $data['customer_id'] ?? null,
                'sub_total' => $cart['sub_total'],
                'discount' => $cart['discount'],
                'tax' => $cart['tax'],
                'grand_total' => $cart['grand_total'],
                'status' => PosHold::STATUS_HOLD,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => Auth::id(),
            ];

            $hold = $this->posHoldRepository
                ->create($data);

            foreach ($cart['hold_items'] as $item) {

                $hold->items()->create($item);
            }

            return $hold->load([

                'customer',

                'session',

                'items.product',

            ]);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Hold
    |--------------------------------------------------------------------------
    */

    public function updateHold(
        int $id,
        array $data
    ): PosHold {

        return DB::transaction(function () use ($id, $data) {

            $hold = $this->posHoldRepository
                ->findOrFail($id);

            $this->assertHoldBelongsToCurrentCashier($hold);

            $cart = $this->buildPosCart($data['items'] ?? []);

            $hold = $this->posHoldRepository
                ->update($id, [
                    'customer_id' => $data['customer_id'] ?? null,
                    'sub_total' => $cart['sub_total'],
                    'discount' => $cart['discount'],
                    'tax' => $cart['tax'],
                    'grand_total' => $cart['grand_total'],
                    'remarks' => $data['remarks'] ?? null,
                    'updated_by' => Auth::id(),
                ]);

            $hold->items()->delete();

            foreach ($cart['hold_items'] as $item) {

                $hold->items()->create($item);
            }

            return $hold->load([

                'customer',

                'session',

                'items.product',

            ]);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Hold
    |--------------------------------------------------------------------------
    */

    public function deleteHold(
        int $id
    ): bool {

        return DB::transaction(function () use ($id) {

            return $this->posHoldRepository
                ->delete($id);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Recall Hold
    |--------------------------------------------------------------------------
    */

    public function recallHold(
        int $id
    ): PosHold {

        $hold = $this->posHoldRepository
            ->findOrFail($id);

        $this->assertHoldBelongsToCurrentCashier($hold);

        return $hold->load([

            'customer',

            'items.product',

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Hold
    |--------------------------------------------------------------------------
    */

    public function cancelHold(
        int $id
    ): PosHold {

        return DB::transaction(function () use ($id) {

            $hold = $this->posHoldRepository
                ->findOrFail($id);

            $this->assertHoldBelongsToCurrentCashier($hold);

            return $this->posHoldRepository
                ->changeStatus(
                    $id,
                    PosHold::STATUS_CANCELLED
                );

        });
    }

    /*
|--------------------------------------------------------------------------
| Barcode Search
|--------------------------------------------------------------------------
*/

    public function barcode(
        string $barcode
    ): Product {

        return $this->productRepository
            ->findByBarcode($barcode);
    }

    /*
|--------------------------------------------------------------------------
| Product Search
|--------------------------------------------------------------------------
*/

    public function searchProduct(
        string $keyword
    ): Collection {

        return $this->productRepository
            ->searchForPOS($keyword ?? '');
    }

    /*
    |--------------------------------------------------------------------------
    | Checkout
    |--------------------------------------------------------------------------
    */

    public function checkout(
        array $data
    ): array {

        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Open Session
            |--------------------------------------------------------------------------
            */

            $session = $this->currentCashierSession();

            $this->assertSessionMatchesCurrentCashier($data['cash_register_session_id'] ?? null, $session);

            $cart = $this->buildPosCart($data['items'] ?? []);

            $paymentModeId = $data['payment_mode_id'];

            $holdId = $data['hold_id'] ?? null;

            $remarks = $data['remarks'] ?? null;

            $grandTotal = $cart['grand_total'];

            $paidAmount = (float) $data['paid_amount'];

            if ($paidAmount > $grandTotal) {

                throw ValidationException::withMessages([

                    'paid_amount' => 'Paid amount cannot exceed bill total.',

                ]);
            }

            if (!empty($holdId)) {

                $hold = $this->posHoldRepository
                    ->findOrFail($holdId);

                $this->assertHoldBelongsToCurrentCashier($hold);
            }

            $customerId = $data['customer_id'] ?? $this->walkInCustomer()->id;

            $dueAmount = max(0, $grandTotal - $paidAmount);

            $saleData = [

                'customer_id' => $customerId,

                'paid_amount' => $paidAmount,

                'sale_date' => now()->toDateString(),

                'invoice_date' => now()->toDateString(),

                'sub_total' => $cart['sub_total'],

                'discount_amount' => $cart['discount'],

                'tax_amount' => $cart['tax'],

                'shipping_amount' => 0,

                'other_amount' => 0,

                'round_off' => 0,

                'grand_total' => $grandTotal,

                'due_amount' => $dueAmount,

                'refund_amount' => 0,

                'payment_status' => $dueAmount > 0
                    ? SaleOrder::PAYMENT_PARTIAL
                    : SaleOrder::PAYMENT_COMPLETED,

                'status' => SaleOrder::STATUS_COMPLETED,

                'remarks' => $remarks,

                'created_by' => Auth::id(),

                'items' => $cart['sale_items'],

            ];

            /*
            |--------------------------------------------------------------------------
            | Create Sale
            |--------------------------------------------------------------------------
            */

            $sale = $this->saleService
                ->create($saleData);

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            $payment = $this->paymentService
                ->createSalePayment(
                    $sale->id,
                    [
                        'payment_mode_id' => $paymentModeId,
                        'payment_date' => now()->toDateString(),
                        'amount' => $paidAmount,
                        'status' => Payment::STATUS_COMPLETED,
                        'remarks' => $remarks,
                    ]
                );

            /*
            |--------------------------------------------------------------------------
            | Cash Register Transaction
            |--------------------------------------------------------------------------
            */

            $this->cashRegisterTransactionService
                ->cashIn([

                    'cash_register_session_id' => $session->id,

                    'payment_mode_id' => $payment->payment_mode_id,

                    'reference_type' => get_class($payment),

                    'reference_id' => $payment->id,

                    'amount' => $payment->amount,

                    'transaction_at' => now(),

                    'remarks' => 'POS Sale Payment',

                ]);

            /*
            |--------------------------------------------------------------------------
            | Hold Complete
            |--------------------------------------------------------------------------
            */

            if (!empty($holdId)) {

                $this->posHoldRepository
                    ->complete(
                        $holdId
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return [

                'sale' => $sale->fresh([
                    'customer',
                    'items.product',
                    'payments',
                ]),

                'payment' => $payment,

                'message' => 'Checkout completed successfully.',

            ];

        });
    }

    private function currentCashierSession(): CashRegisterSession
    {
        $session = $this->cashRegisterSessionRepository
            ->current((int) Auth::id());

        if (!$session || $session->status !== CashRegisterSession::STATUS_OPEN) {

            throw ValidationException::withMessages([

                'session' => 'Open a register session before using POS billing.',

            ]);
        }

        return $session;
    }

    private function assertSessionMatchesCurrentCashier(
        mixed $sessionId,
        CashRegisterSession $session
    ): void {
        if ($sessionId && (int) $sessionId !== (int) $session->id) {

            throw ValidationException::withMessages([

                'session' => 'Cashier can only use their own open session.',

            ]);
        }
    }

    private function assertHoldBelongsToCurrentCashier(PosHold $hold): void
    {
        $session = $this->currentCashierSession();

        if ((int) $hold->cash_register_session_id !== (int) $session->id) {

            throw ValidationException::withMessages([

                'hold' => 'This held bill does not belong to the current session.',

            ]);
        }

        if ($hold->status !== PosHold::STATUS_HOLD) {

            throw ValidationException::withMessages([

                'hold' => 'This hold is no longer available.',

            ]);
        }
    }

    private function buildPosCart(array $items): array
    {
        $requested = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Each POS item must include a valid product and quantity.',
                ]);
            }

            $requested[$productId] = ($requested[$productId] ?? 0) + $quantity;
        }

        $products = Product::query()
            ->whereIn('id', array_keys($requested))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $subTotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;
        $grandTotal = 0;
        $holdItems = [];
        $saleItems = [];

        foreach ($requested as $productId => $quantity) {
            $product = $products->get($productId);

            if (!$product) {
                throw ValidationException::withMessages([
                    'items' => 'One or more products are inactive or unavailable.',
                ]);
            }

            if (!$product->unit_id) {
                throw ValidationException::withMessages([
                    'items' => "Product {$product->name} does not have a unit assigned.",
                ]);
            }

            $price = (float) $product->selling_price;
            $purchasePrice = (float) ($product->purchase_price ?? 0);
            $taxPercent = (float) ($product->tax_percent ?? 0);
            $discountPercent = (float) ($product->discount_percent ?? 0);
            $lineSubTotal = round($price * $quantity, 2);
            $discountAmount = round($lineSubTotal * $discountPercent / 100, 2);
            $taxableAmount = max(0, $lineSubTotal - $discountAmount);
            $taxAmount = round($taxableAmount * $taxPercent / 100, 2);
            $lineTotal = round($taxableAmount + $taxAmount, 2);

            $subTotal += $lineSubTotal;
            $discountTotal += $discountAmount;
            $taxTotal += $taxAmount;
            $grandTotal += $lineTotal;

            $holdItems[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $price,
                'discount' => $discountAmount,
                'tax' => $taxAmount,
                'total' => $lineTotal,
            ];

            $saleItems[] = [
                'product_id' => $product->id,
                'unit_id' => $product->unit_id,
                'quantity' => $quantity,
                'purchase_price' => $purchasePrice,
                'selling_price' => $price,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'line_total' => $lineTotal,
            ];
        }

        return [
            'sub_total' => round($subTotal, 2),
            'discount' => round($discountTotal, 2),
            'tax' => round($taxTotal, 2),
            'grand_total' => round($grandTotal, 2),
            'hold_items' => $holdItems,
            'sale_items' => $saleItems,
        ];
    }

    private function walkInCustomer(): Customer
    {
        $customer = Customer::withTrashed()
            ->where('customer_code', 'WALK-IN')
            ->first();

        if ($customer) {
            if ($customer->trashed()) {
                $customer->restore();
            }

            return $customer;
        }

        return Customer::create([
            'customer_code' => 'WALK-IN',
            'customer_type' => 'Walk-in',
            'first_name' => 'Walk-in Customer',
            'opening_balance' => 0,
            'credit_limit' => 0,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);
    }

    public function cashIn(
        array $data
    ) {
        return $this->cashRegisterTransactionService
            ->cashIn($data);
    }

    public function cashOut(
        array $data
    ) {
        return $this->cashRegisterTransactionService
            ->cashOut($data);
    }


    public function sessionSummary(
        int $sessionId
    ): array {

        $session = $this->cashRegisterSessionRepository
            ->findOrFail($sessionId);

        $cashIn = $this->cashRegisterTransactionRepository
            ->totalCashIn($session->id);

        $cashOut = $this->cashRegisterTransactionRepository
            ->totalCashOut($session->id);

        return [

            'opening_balance' => $session->opening_balance,

            'cash_in' => $cashIn,

            'cash_out' => $cashOut,

            'expected_balance' =>

                $session->opening_balance
                + $cashIn
                - $cashOut,

         ];
     }

    public function sessionContext(): array
    {
        $user = Auth::user();

        $register = $this->cashRegisterRepository
            ->getOpenRegisterByUser((int) $user->id);

        $session = $this->cashRegisterSessionRepository
            ->current((int) $user->id);

        $message = 'Billing ready.';

        if (!$register) {
            $message = 'No cash register assigned to this cashier.';
        } elseif (!$session) {
            $message = 'Open a register session before billing.';
        }

        return [

            'cashier' => $user,

            'register' => $register,

            'session' => $session,

            'payment_modes' => $this->paymentModeRepository
                ->active(),

            'billing_allowed' => $register !== null && $session !== null,

            'requires_session' => $register !== null && $session === null,

            'message' => $message,

        ];
    }

     public function dashboard(): array
     {
        $userId = auth()->id();

        /*
        |--------------------------------------------------------------------------
        | Current Session
        |--------------------------------------------------------------------------
        */

        $session = $this->cashRegisterSessionRepository
            ->current($userId);

        /*
        |--------------------------------------------------------------------------
        | Today's Sales
        |--------------------------------------------------------------------------
        */

        $todaySale = SaleOrder::query()

            ->whereDate('created_at', Carbon::today())

            ->sum('grand_total');

        /*
        |--------------------------------------------------------------------------
        | Today's Orders
        |--------------------------------------------------------------------------
        */

        $todayOrder = SaleOrder::query()

            ->whereDate('created_at', Carbon::today())

            ->count();

        /*
        |--------------------------------------------------------------------------
        | Hold Count
        |--------------------------------------------------------------------------
        */

        $holdCount = $this->posHoldRepository
            ->holdCount();

        /*
        |--------------------------------------------------------------------------
        | Customer Count
        |--------------------------------------------------------------------------
        */

        $customerCount = $this->customerRepository
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Recent Sales
        |--------------------------------------------------------------------------
        */

        $recentSales = $this->saleRepository
            ->recent(10);

        /*
        |--------------------------------------------------------------------------
        | Payment Modes
        |--------------------------------------------------------------------------
        */

        $paymentModes = $this->paymentModeRepository
            ->active();

        /*
        |--------------------------------------------------------------------------
        | Quick Products
        |--------------------------------------------------------------------------
        */

        $quickProducts = $this->productRepository
            ->quickProducts();

        return [

            'session' => $session,

            'today_sale' => $todaySale,

            'today_order' => $todayOrder,

            'hold_count' => $holdCount,

            'customer_count' => $customerCount,

            'recent_sales' => $recentSales,

            'payment_modes' => $paymentModes,

            'quick_products' => $quickProducts,

        ];
    }
}

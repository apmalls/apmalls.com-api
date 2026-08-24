<?php

namespace App\Services\POS;

use App\Helpers\NumberHelper;
use App\Models\POS\CashRegister;
use App\Models\POS\CashRegisterSession;
use App\Models\POS\PosHold;
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

            return $this->cashRegisterRepository
                ->update($id, $data);

        });
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

            $alreadyOpen = $this->cashRegisterSessionRepository
                ->findOpenSession($data['register_id'] ?? 0, Auth::id());

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

            $data['opened_at'] = now();

            $data['status'] = CashRegisterSession::STATUS_OPEN;

            return $this->cashRegisterSessionRepository
                ->create($data);

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

            $items = $data['items'] ?? [];

            unset($data['items']);

            if (empty($data['hold_no'])) {

                $data['hold_no'] = NumberHelper::generate(
                    PosHold::class,
                    'hold_no',
                    'HOLD'
                );
            }

            $hold = $this->posHoldRepository
                ->create($data);

            foreach ($items as $item) {

                $hold->items()->create([

                    'product_id' => $item['product_id'],

                    'quantity' => $item['quantity'],

                    'price' => $item['price'],

                    'discount' => $item['discount'] ?? 0,

                    'tax' => $item['tax'] ?? 0,

                    'total' => $item['total'],

                ]);
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

            $items = $data['items'] ?? [];

            unset($data['items']);

            $hold = $this->posHoldRepository
                ->update($id, $data);

            $hold->items()->delete();

            foreach ($items as $item) {

                $hold->items()->create([

                    'product_id' => $item['product_id'],

                    'quantity' => $item['quantity'],

                    'price' => $item['price'],

                    'discount' => $item['discount'] ?? 0,

                    'tax' => $item['tax'] ?? 0,

                    'total' => $item['total'],

                ]);
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

        if ($hold->status !== PosHold::STATUS_HOLD) {

            throw ValidationException::withMessages([

                'hold' => 'This hold is no longer available.'

            ]);
        }

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

            $session = $this->cashRegisterSessionRepository
                ->findOrFail(
                    $data['cash_register_session_id']
                );

            if ($session->status !== CashRegisterSession::STATUS_OPEN) {

                throw ValidationException::withMessages([

                    'session' => 'Cash Register is closed.'

                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Create Sale
            |--------------------------------------------------------------------------
            */

            $sale = $this->saleService
                ->create($data);

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            $payment = $this->paymentService
                ->createSalePayment(
                    $sale->id,
                    [
                        'payment_mode_id' => $data['payment_mode_id'],
                        'amount' => $data['paid_amount'],
                        'remarks' => $data['remarks'] ?? null,
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

            if (!empty($data['hold_id'])) {

                $this->posHoldRepository
                    ->complete(
                        $data['hold_id']
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

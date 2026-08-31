<?php

namespace App\Repositories\Sale;

use App\Models\Sale\SaleOrder;
use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SaleRepository implements SaleRepositoryInterface
{
    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function paginate(
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {

        return $this->filter($filters)
            ->paginate($perPage);
    }

    public function trashedPaginate(
        int $perPage = 15
    ): LengthAwarePaginator {

        return SaleOrder::onlyTrashed()
            ->with([
                'customer',
                'billingAddress',
                'shippingAddress',
                'creator',
                'updater',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function all(
        array $filters = []
    ): Collection {

        return $this->filter($filters)
            ->get();
    }

    public function recent(int $limit = 10): Collection
    {
        return SaleOrder::query()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(
        int $id
    ): ?SaleOrder {

        return SaleOrder::with([
            'customer',
            'billingAddress',
            'shippingAddress',
            'creator',
            'updater',
        ])->find($id);
    }

    public function findOrFail(
        int $id
    ): SaleOrder {

        return SaleOrder::with([
            'customer',
            'billingAddress',
            'shippingAddress',
            'items.product',
            'items.unit',
            'saleReturns',
            'payments.paymentMode',
            'deliveryAssignment.deliveryBoy.user',
            'deliveryAssignment.assignedBy',
            'creator',
            'updater',
        ])->findOrFail($id);
    }

    public function findBySaleNo(
        string $saleNo
    ): ?SaleOrder {

        return SaleOrder::where(
            'sale_no',
            $saleNo
        )->first();
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD
    |--------------------------------------------------------------------------
    */

    public function create(
        array $data
    ): SaleOrder {

        return SaleOrder::create($data);
    }

    public function update(
        int $id,
        array $data
    ): SaleOrder {

        $sale = $this->findOrFail($id);

        $sale->update($data);

        return $sale->fresh();
    }

    public function delete(
        int $id
    ): bool {

        return $this->findOrFail($id)
            ->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Trash
    |--------------------------------------------------------------------------
    */

    public function restore(
        int $id
    ): bool {

        return SaleOrder::onlyTrashed()
            ->findOrFail($id)
            ->restore();
    }

    public function forceDelete(
        int $id
    ): bool {

        return SaleOrder::onlyTrashed()
            ->findOrFail($id)
            ->forceDelete();
    }

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(
        int $id,
        string $status
    ): SaleOrder {

        $sale = $this->findOrFail($id);

        $sale->update([
            'status' => $status,
        ]);

        return $sale->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    public function count(
        array $filters = []
    ): int {

        return $this->filter($filters)->count();
    }

    public function totalAmount(
        array $filters = []
    ): float {

        return (float) $this->filter($filters)
            ->sum('grand_total');
    }

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    protected function filter(
        array $filters = []
    ) {

        return SaleOrder::query()

            ->with([
                'customer',
                'billingAddress',
                'shippingAddress',
                'creator',
                'updater',
            ])

            ->when(
                $filters['search'] ?? null,
                function ($q, $search) {
                    $q->where(function ($query) use ($search) {
                        $query
                            ->where('sale_no', 'ILIKE', "%{$search}%")
                            ->orWhere('invoice_no', 'ILIKE', "%{$search}%")
                            ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                $customerQuery
                                    ->where('first_name', 'ILIKE', "%{$search}%")
                                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                                    ->orWhere('email', 'ILIKE', "%{$search}%")
                                    ->orWhere('mobile', 'ILIKE', "%{$search}%");
                            });
                    });
                }
            )

            ->when(
                $filters['status'] ?? null,
                fn($q, $status) => $q->where('status', $status)
            )

            ->when(
                $filters['payment_status'] ?? null,
                fn($q, $status) => $q->where('payment_status', $status)
            )

            ->when(
                $filters['customer_id'] ?? null,
                fn($q, $customer) => $q->where('customer_id', $customer)
            )

            ->when(
                $filters['sale_no'] ?? null,
                fn($q, $saleNo) => $q->where(
                    'sale_no',
                    'ILIKE',
                    "%{$saleNo}%"
                )
            )

            ->when(
                $filters['invoice_no'] ?? null,
                fn($q, $invoiceNo) => $q->where(
                    'invoice_no',
                    'ILIKE',
                    "%{$invoiceNo}%"
                )
            )

            ->when(
                $filters['from_date'] ?? null,
                fn($q, $date) => $q->whereDate(
                    'sale_date',
                    '>=',
                    $date
                )
            )

            ->when(
                $filters['to_date'] ?? null,
                fn($q, $date) => $q->whereDate(
                    'sale_date',
                    '<=',
                    $date
                )
            )

            ->latest();
    }

    public function createDraft(
        array $data
    ): SaleOrder {
        return SaleOrder::create($data);
    }


    public function updatePayment(
        int $id,
        array $data
    ): SaleOrder {

        $sale = $this->findOrFail($id);

        $sale->update([

            'paid_amount' => $data['paid_amount'],

            'due_amount' => $data['due_amount'],

            'refund_amount' => $data['refund_amount'] ?? 0,

            'payment_status' => $data['payment_status'],

        ]);

        return $sale->fresh();
    }

    public function updateTotals(
        int $id,
        array $data
    ): SaleOrder {

        $sale = $this->findOrFail($id);

        $sale->update([

            'sub_total' => $data['sub_total'],

            'discount_amount' => $data['discount_amount'],

            'tax_amount' => $data['tax_amount'],

            'shipping_amount' => $data['shipping_amount'],

            'other_amount' => $data['other_amount'] ?? 0,

            'round_off' => $data['round_off'] ?? 0,

            'grand_total' => $data['grand_total'],

        ]);

        return $sale->fresh();
    }

    public function customerOrders(
        int $customerId,
        int $perPage = 10
    ): LengthAwarePaginator {

        return SaleOrder::where(

            'customer_id',
            $customerId

        )

            ->with([
                'items.product',
                'payments',
            ])

            ->latest()

            ->paginate($perPage);
    }

    public function customerOrder(
        int $customerId,
        string $saleNo
    ): ?SaleOrder {

        return SaleOrder::where(

            'customer_id',
            $customerId

        )

            ->where(

                'sale_no',
                $saleNo

            )

            ->with([

                'items.product',

                'items.unit',

                'payments',

                'billingAddress',

                'shippingAddress',

            ])

            ->first();
    }

    public function findDraftByCustomer(
        int $customerId
    ): ?SaleOrder {
        return SaleOrder::with([
            'items',
        ])
            ->where('customer_id', $customerId)
            ->where('status', SaleOrder::STATUS_DRAFT)
            ->latest()
            ->first();
    }

    public function deleteDraft(
        int $id
    ): bool {
        return $this->findOrFail($id)
            ->delete();
    }

    public function updateDeliveryStatus(
        int $saleOrderId,
        ?string $status,
        ?string $deliveredAt = null
    ): bool {

        $data = [
            'delivery_status' => $status,
        ];

        if ($deliveredAt) {

            $data['delivered_at'] = $deliveredAt;

        }

        return SaleOrder::whereKey($saleOrderId)
            ->update($data);

    }

}

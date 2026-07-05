<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentModeController extends Controller
{
    /**
     * Display a listing of payment modes.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $query = PaymentMode::query()
                ->latest();

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('code', 'ILIKE', "%{$search}%");

                });

            }

            return response()->json([

                'success' => true,

                'message' => 'Payment modes fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

            ]);

        } catch (Throwable $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Store payment mode.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([

            'name' => 'required|string|max:100|unique:payment_modes,name',

            'code' => 'required|string|max:50|unique:payment_modes,code',

            'description' => 'nullable|string',

            'is_active' => 'nullable|boolean',

            'sort_order' => 'nullable|integer|min:0',

        ]);

        DB::beginTransaction();

        try {

            $paymentMode = PaymentMode::create([

                'name' => $request->name,

                'code' => strtoupper($request->code),

                'description' => $request->description,

                'is_active' => $request->boolean('is_active', true),

                'sort_order' => $request->integer('sort_order', 0),

            ]);

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Payment mode created successfully.',

                'data' => $paymentMode,

            ], 201);

        } catch (Throwable $e) {

            DB::rollBack();

            return $this->handleException($e);

        }
    }

    /**
     * Display payment mode.
     */
    public function show(int $id): JsonResponse
    {
        try {

            $paymentMode = PaymentMode::findOrFail($id);

            return response()->json([

                'success' => true,

                'message' => 'Payment mode fetched successfully.',

                'data' => $paymentMode,

            ]);

        } catch (Throwable $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Update payment mode.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([

            'name' => 'required|string|max:100|unique:payment_modes,name,' . $id,

            'code' => 'required|string|max:50|unique:payment_modes,code,' . $id,

            'description' => 'nullable|string',

            'is_active' => 'nullable|boolean',

            'sort_order' => 'nullable|integer|min:0',

        ]);

        DB::beginTransaction();

        try {

            $paymentMode = PaymentMode::findOrFail($id);

            $paymentMode->update([

                'name' => $request->name,

                'code' => strtoupper($request->code),

                'description' => $request->description,

                'is_active' => $request->boolean('is_active'),

                'sort_order' => $request->integer('sort_order', 0),

            ]);

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Payment mode updated successfully.',

                'data' => $paymentMode,

            ]);

        } catch (Throwable $e) {

            DB::rollBack();

            return $this->handleException($e);

        }
    }

    /**
     * Delete payment mode.
     */
    public function destroy(int $id): JsonResponse
    {
        DB::beginTransaction();

        try {

            $paymentMode = PaymentMode::findOrFail($id);

            $paymentMode->delete();

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Payment mode deleted successfully.',

            ]);

        } catch (Throwable $e) {

            DB::rollBack();

            return $this->handleException($e);

        }
    }
}

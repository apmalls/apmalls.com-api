<?php

namespace App\Http\Controllers\Api\V1\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\Unit\StoreUnitRequest;
use App\Http\Requests\Product\Unit\UpdateUnitRequest;
use App\Http\Requests\Product\Unit\ChangeUnitStatusRequest;
use App\Models\Product\Unit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Unit Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $query = Unit::query()->latest();

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('short_name', 'ILIKE', "%{$search}%");

                });

            }

            if ($request->filled('status')) {

                $query->where(
                    'is_active',
                    $request->boolean('status')
                );

            }

            $units = $query->paginate(
                $request->integer('per_page', 10)
            );

            return response()->json([

                'success' => true,

                'message' => 'Unit list fetched successfully.',

                'data' => $units,

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Store Unit
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $this->beginTransaction();

        try {

            $unit = Unit::create([

                'name' => $request->name,

                'short_name' => $request->short_name,

                'description' => $request->description,

                'is_active' => $request->boolean('is_active'),

                'created_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Unit created successfully.',

                'data' => $unit,

            ], 201);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }


    /**
     * Display Unit
     */
    public function show($id): JsonResponse
    {
        try {

            $unit = Unit::findOrFail($id);

            return response()->json([

                'success' => true,

                'message' => 'Unit fetched successfully.',

                'data' => $unit,

            ]);

        } catch (ModelNotFoundException $e) {

            return response()->json([

                'success' => false,

                'message' => 'Unit not found.',

            ], 404);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Update Unit
     */
    public function update(UpdateUnitRequest $request, $id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $unit = Unit::findOrFail($id);

            $unit->update([

                'name' => $request->name,

                'short_name' => $request->short_name,

                'description' => $request->description,

                'is_active' => $request->boolean('is_active'),

                'updated_by' => auth()->id(),

            ]);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Unit updated successfully.',

                'data' => $unit->fresh(),

            ]);

        } catch (ModelNotFoundException $e) {

            $this->rollback();

            return response()->json([

                'success' => false,

                'message' => 'Unit not found.',

            ], 404);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Delete Unit
     */
    public function destroy($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $unit = Unit::findOrFail($id);

            if ($unit->products()->exists()) {

                return response()->json([

                    'success' => false,

                    'message' => 'Products exist under this unit.'

                ], 422);

            }

            $unit->delete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Unit deleted successfully.'

            ]);

        } catch (ModelNotFoundException $e) {

            $this->rollback();

            return response()->json([

                'success' => false,

                'message' => 'Unit not found.',

            ], 404);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }


    /**
     * Change Status
     */
    public function changeStatus(
        ChangeUnitStatusRequest $request,
        $id
    ): JsonResponse {

        try {

            $unit = Unit::findOrFail($id);

            $unit->update([

                'is_active' => $request->boolean('is_active'),

                'updated_by' => auth()->id(),

            ]);

            return response()->json([

                'success' => true,

                'message' => 'Unit status updated successfully.',

                'data' => $unit,

            ]);

        } catch (ModelNotFoundException $e) {

            return response()->json([

                'success' => false,

                'message' => 'Unit not found.',

            ], 404);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Trash
     */
    public function trash(Request $request): JsonResponse
    {
        try {

            $query = Unit::onlyTrashed()->latest('deleted_at');

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($query) use ($search) {

                    $query->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('short_name', 'ILIKE', "%{$search}%");

                });

            }

            return response()->json([

                'success' => true,

                'message' => 'Deleted units fetched successfully.',

                'data' => $query->paginate(
                    $request->integer('per_page', 10)
                ),

            ]);

        } catch (\Exception $e) {

            return $this->handleException($e);

        }
    }

    /**
     * Restore
     */
    public function restore($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $unit = Unit::onlyTrashed()->findOrFail($id);

            $unit->restore();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Unit restored successfully.',

                'data' => $unit,

            ]);

        } catch (ModelNotFoundException $e) {

            $this->rollback();

            return response()->json([

                'success' => false,

                'message' => 'Unit not found.',

            ], 404);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }

    /**
     * Force Delete
     */
    public function forceDelete($id): JsonResponse
    {
        $this->beginTransaction();

        try {

            $unit = Unit::withTrashed()->findOrFail($id);

            if ($unit->products()->exists()) {

                return response()->json([

                    'success' => false,

                    'message' => 'Products exist under this unit.'

                ], 422);

            }

            $unit->forceDelete();

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Unit permanently deleted successfully.'

            ]);

        } catch (ModelNotFoundException $e) {

            $this->rollback();

            return response()->json([

                'success' => false,

                'message' => 'Unit not found.'

            ], 404);

        } catch (\Exception $e) {

            $this->rollback();

            return $this->handleException($e);

        }
    }
}

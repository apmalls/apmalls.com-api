<?php

namespace App\Http\Controllers\Api\V1\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\Unit\StoreUnitRequest;
use App\Http\Requests\Product\Unit\UpdateUnitRequest;
use App\Http\Requests\Product\Unit\ChangeUnitStatusRequest;
use App\Services\Contracts\UnitServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(
        protected UnitServiceInterface $unitService
    ) {
    }

    /**
     * Unit Listing
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->filled('search') ? $request->search : null,
                'status' => $request->filled('status') ? $request->boolean('status') : null,
                'per_page' => $request->integer('per_page', 10),
            ];

            $units = $this->unitService->paginate($filters);

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
            $data = [
                'name' => $request->name,
                'short_name' => $request->short_name,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active'),
                'created_by' => auth()->id(),
            ];

            $unit = $this->unitService->create($data);

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
            $unit = $this->unitService->find($id);

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
            $data = [
                'name' => $request->name,
                'short_name' => $request->short_name,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active'),
                'updated_by' => auth()->id(),
            ];

            $unit = $this->unitService->update($id, $data);

            $this->commit();

            return response()->json([

                'success' => true,

                'message' => 'Unit updated successfully.',

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
     * Delete Unit
     */
    public function destroy($id): JsonResponse
    {
        $this->beginTransaction();

        try {
            $unit = $this->unitService->find($id);

            if ($unit->products()->exists()) {

                return response()->json([

                    'success' => false,

                    'message' => 'Products exist under this unit.'

                ], 422);

            }

            $this->unitService->delete($id);

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
            $unit = $this->unitService->changeStatus($id, $request->boolean('is_active'));

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
            $filters = [
                'search' => $request->filled('search') ? $request->search : null,
                'per_page' => $request->integer('per_page', 10),
            ];

            $units = $this->unitService->trash($filters);

            return response()->json([

                'success' => true,

                'message' => 'Deleted units fetched successfully.',

                'data' => $units,

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
            $this->unitService->restore($id);
            $unit = $this->unitService->find($id);

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
            $unit = $this->unitService->find($id);

            if ($unit->products()->exists()) {

                return response()->json([

                    'success' => false,

                    'message' => 'Products exist under this unit.'

                ], 422);

            }

            $this->unitService->forceDelete($id);

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

<?php

namespace App\Services\Contracts;

use App\Models\Barcode\BarcodeTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BarcodeTemplateServiceInterface
{
    /**
     * Get paginated barcode templates.
     */
    public function paginate(
        int $perPage = 10,
        ?string $search = null,
        string $sortBy = 'id',
        string $sortOrder = 'desc'
    ): LengthAwarePaginator;

    /**
     * Get active barcode templates.
     */
    public function active(): Collection;

    /**
     * Find barcode template.
     */
    public function findById(int $id): BarcodeTemplate;

    /**
     * Create barcode template.
     */
    public function create(array $data): BarcodeTemplate;

    /**
     * Update barcode template.
     */
    public function update(
        int $id,
        array $data
    ): BarcodeTemplate;

    /**
     * Delete barcode template.
     */
    public function delete(int $id): bool;
}

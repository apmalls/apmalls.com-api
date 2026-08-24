<?php

namespace App\Repositories\Contracts;

use App\Models\Barcode\BarcodeTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BarcodeTemplateRepositoryInterface
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
     * Get all active templates.
     */
    public function active(): Collection;

    /**
     * Find template by id.
     */
    public function findById(int $id): BarcodeTemplate;

    /**
     * Create template.
     */
    public function create(array $data): BarcodeTemplate;

    /**
     * Update template.
     */
    public function update(
        int $id,
        array $data
    ): BarcodeTemplate;

    /**
     * Delete template.
     */
    public function delete(int $id): bool;
}

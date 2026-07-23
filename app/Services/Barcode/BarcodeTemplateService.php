<?php

namespace App\Services\Barcode;

use App\Models\Barcode\BarcodeTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Repositories\Contracts\BarcodeTemplateRepositoryInterface;
use App\Services\Contracts\BarcodeTemplateServiceInterface;

class BarcodeTemplateService implements BarcodeTemplateServiceInterface
{
    public function __construct(
        protected BarcodeTemplateRepositoryInterface $repository
    ) {
    }

    /**
     * Get paginated templates.
     */
    public function paginate(
        int $perPage = 10,
        ?string $search = null,
        string $sortBy = 'id',
        string $sortOrder = 'desc'
    ): LengthAwarePaginator {

        return $this->repository->paginate(
            $perPage,
            $search,
            $sortBy,
            $sortOrder
        );
    }

    /**
     * Active templates.
     */
    public function active(): Collection
    {
        return $this->repository->active();
    }

    /**
     * Find template.
     */
    public function findById(int $id): BarcodeTemplate
    {
        return $this->repository->findById($id);
    }

    /**
     * Create template.
     */
    public function create(array $data): BarcodeTemplate
    {
        return DB::transaction(function () use ($data) {

            return $this->repository->create($data);

        });
    }

    /**
     * Update template.
     */
    public function update(
        int $id,
        array $data
    ): BarcodeTemplate {

        return DB::transaction(function () use ($id, $data) {

            return $this->repository->update(
                $id,
                $data
            );

        });
    }

    /**
     * Delete template.
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {

            return $this->repository->delete($id);

        });
    }
}

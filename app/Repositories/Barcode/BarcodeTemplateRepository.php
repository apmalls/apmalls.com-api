<?php

namespace App\Repositories\Barcode;

use App\Models\Barcode\BarcodeTemplate;
use App\Repositories\Contracts\BarcodeTemplateRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BarcodeTemplateRepository implements BarcodeTemplateRepositoryInterface
{
    /**
     * Get paginated templates.
     */
    public function paginate(
        int $perPage = 10,
        ?string $search = null,
        string $sortBy = 'id',
        string $sortOrder = 'desc'
    ): LengthAwarePaginator {

        return BarcodeTemplate::query()

            ->when($search, function ($query) use ($search) {

                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('paper_size', 'like', "%{$search}%");

            })

            ->orderBy($sortBy, $sortOrder)

            ->paginate($perPage);
    }

    /**
     * Active templates.
     */
    public function active(): Collection
    {
        return BarcodeTemplate::query()

            ->where('status', true)

            ->orderBy('name')

            ->get();
    }

    /**
     * Find by id.
     */
    public function findById(int $id): BarcodeTemplate
    {
        return BarcodeTemplate::findOrFail($id);
    }

    /**
     * Create.
     */
    public function create(array $data): BarcodeTemplate
    {
        return BarcodeTemplate::create($data);
    }

    /**
     * Update.
     */
    public function update(
        int $id,
        array $data
    ): BarcodeTemplate {

        $template = $this->findById($id);

        $template->update($data);

        return $template->fresh();
    }

    /**
     * Delete.
     */
    public function delete(int $id): bool
    {
        return $this->findById($id)->delete();
    }
}

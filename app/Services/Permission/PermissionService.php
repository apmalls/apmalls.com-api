<?php

namespace App\Services\Permission;

use Illuminate\Http\Request;
use App\Repositories\Contracts\PermissionRepositoryInterface;

class PermissionService
{
    protected PermissionRepositoryInterface $permissionRepository;

    public function __construct(PermissionRepositoryInterface $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * Display a listing of permissions.
     */
    public function index(Request $request)
    {
        return $this->permissionRepository->index($request);
    }

    /**
     * Group permissions by module.
     */
    public function grouped()
    {
        return $this->permissionRepository->grouped();
    }

    /**
     * Display the specified permission.
     */
    public function show(int $id)
    {
        return $this->permissionRepository->show($id);
    }

    /**
     * Store a newly created permission.
     */
    public function store(array $data)
    {
        return $this->permissionRepository->store($data);
    }

    /**
     * Update the specified permission.
     */
    public function update(int $id, array $data)
    {
        return $this->permissionRepository->update($id, $data);
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(int $id)
    {
        return $this->permissionRepository->destroy($id);
    }
}

<?php

namespace App\Services\Role;

use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Http\Request;


class RoleService
{
    /**
     * Role Repository Instance
     */
    protected RoleRepositoryInterface $roleRepository;

    /**
     * Constructor
     */
    public function __construct(RoleRepositoryInterface $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        return $this->roleRepository->index($request);
    }

    /**
     * Store a newly created role.
     */
    public function store(array $data)
    {
        return $this->roleRepository->store($data);
    }

    /**
     * Display the specified role.
     */
    public function show(int $id)
    {
        return $this->roleRepository->show($id);
    }

    /**
     * Update the specified role.
     */
    public function update(int $id, array $data)
    {
        return $this->roleRepository->update($id, $data);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(int $id)
    {
        return $this->roleRepository->destroy($id);
    }
}

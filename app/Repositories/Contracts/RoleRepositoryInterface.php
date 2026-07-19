<?php

namespace App\Repositories\Contracts;

use Illuminate\Http\Request;

interface RoleRepositoryInterface
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request);

    /**
     * Store a newly created role.
     */
    public function store(array $data);

    /**
     * Display the specified role.
     */
    public function show(int $id);

    /**
     * Update the specified role.
     */
    public function update(int $id, array $data);

    /**
     * Remove the specified role.
     */
    public function destroy(int $id);
}

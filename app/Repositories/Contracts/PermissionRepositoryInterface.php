<?php

namespace App\Repositories\Contracts;

use Illuminate\Http\Request;

interface PermissionRepositoryInterface
{
    /**
     * Display a listing of permissions.
     */
    public function index(Request $request);

    /**
     * Display grouped permissions.
     */
    public function grouped();

    /**
     * Display the specified permission.
     */
    public function show(int $id);

    /**
     * Store a newly created permission.
     */
    public function store(array $data);

    /**
     * Update the specified permission.
     */
    public function update(int $id, array $data);

    /**
     * Remove the specified permission.
     */
    public function destroy(int $id);
}

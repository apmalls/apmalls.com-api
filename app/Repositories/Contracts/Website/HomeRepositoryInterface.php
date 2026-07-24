<?php

namespace App\Repositories\Contracts\Website;

interface HomeRepositoryInterface
{

    /**
     * Website Home Page Data.
     *
     * @return array<string, mixed>
     */
    public function index(): array;

}

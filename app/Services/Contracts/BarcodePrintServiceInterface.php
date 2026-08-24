<?php

namespace App\Services\Contracts;

use Symfony\Component\HttpFoundation\Response;

interface BarcodePrintServiceInterface
{
    public function preview(
        int $templateId,
        array $products
    ): Response;

    public function pdf(
        int $templateId,
        array $products
    ): Response;
}

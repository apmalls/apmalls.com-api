<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\Admin\Barcode\DeleteBarcodeTemplateRequest;
use App\Models\Barcode\BarcodeTemplate;
use App\Repositories\Contracts\BarcodeTemplateRepositoryInterface;
use App\Services\Barcode\BarcodeTemplateService;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class BarcodeTemplatePermanentDeleteTest extends TestCase
{
    public function test_delete_request_requires_a_confirmation_name(): void
    {
        $request = DeleteBarcodeTemplateRequest::create('/', 'DELETE', []);
        $validator = Validator::make([], $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Enter the template name to confirm permanent deletion.',
            $validator->errors()->first('confirmation_name')
        );
    }

    public function test_delete_rejects_a_name_that_does_not_match_exactly(): void
    {
        $template = $this->template('Shelf Label 40x30');
        $repository = Mockery::mock(BarcodeTemplateRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with(12)->andReturn($template);
        $repository->shouldNotReceive('delete');

        $service = new BarcodeTemplateService($repository);

        try {
            $service->delete(12, 'Shelf Label');
            $this->fail('A mismatched confirmation name should fail validation.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The entered name does not match this barcode template.',
                $exception->errors()['confirmation_name'][0]
            );
        }
    }

    public function test_matching_name_dispatches_a_permanent_model_delete(): void
    {
        $template = $this->template('Permanent label');
        $repository = Mockery::mock(BarcodeTemplateRepositoryInterface::class);
        $repository->shouldReceive('findById')->once()->with(7)->andReturn($template);
        $repository->shouldReceive('delete')->once()->with(7)->andReturnTrue();

        $service = new BarcodeTemplateService($repository);

        $this->assertTrue($service->delete(7, 'Permanent label'));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(BarcodeTemplate::class));
    }

    private function template(string $name): BarcodeTemplate
    {
        $template = new BarcodeTemplate();
        $template->setRawAttributes([
            'id' => 1,
            'name' => $name,
        ], true);

        return $template;
    }
}

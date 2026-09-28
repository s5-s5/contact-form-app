<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * CSV エクスポートのバリデーション（ExportContactRequest）
 */
class ExportContactRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepts_valid_filters(): void
    {
        $category = Category::factory()->create();

        $validator = $this->makeValidator([
            'keyword' => 'example.com',
            'gender' => 0,
            'category_id' => $category->id,
            'date' => '2026-09-28',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_rejects_invalid_gender_and_nonexistent_category(): void
    {
        $validator = $this->makeValidator([
            'gender' => 5,
            'category_id' => 9999,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function makeValidator(array $data): ValidatorContract
    {
        $request = new ExportContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }
}

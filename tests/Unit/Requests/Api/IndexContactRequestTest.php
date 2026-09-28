<?php

namespace Tests\Unit\Requests\Api;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Models\Category;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * API のお問い合わせ一覧検索のバリデーション（Api\V1\IndexContactRequest）
 */
class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepts_valid_filters(): void
    {
        $category = Category::factory()->create();

        $validator = $this->makeValidator([
            'keyword' => '田中',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-01-15',
            'page' => 2,
            'per_page' => 100,
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_rejects_gender_outside_one_to_three(): void
    {
        foreach ([0, 4] as $gender) {
            $validator = $this->makeValidator(['gender' => $gender]);

            $this->assertTrue($validator->fails());
            $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
        }
    }

    public function test_rejects_nonexistent_category_and_invalid_date(): void
    {
        $validator = $this->makeValidator([
            'category_id' => 9999,
            'date' => '2026-13-45',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
        $this->assertTrue($validator->errors()->has('date'));
    }

    public function test_rejects_invalid_page_and_per_page(): void
    {
        $this->assertTrue($this->makeValidator(['per_page' => 0])->fails());
        $this->assertTrue($this->makeValidator(['per_page' => 101])->fails());
        $this->assertTrue($this->makeValidator(['page' => 0])->fails());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function makeValidator(array $data): ValidatorContract
    {
        $request = new IndexContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }
}

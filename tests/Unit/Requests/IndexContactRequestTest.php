<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * お問い合わせ一覧検索のバリデーション（IndexContactRequest）
 */
class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepts_keyword_gender_category_and_date_filters(): void
    {
        $category = Category::factory()->create();

        $validator = $this->makeValidator([
            'keyword' => '山田',
            'gender' => 2,
            'category_id' => $category->id,
            'date' => '2026-09-28',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_accepts_no_filters_and_zero_as_all_genders(): void
    {
        $this->assertTrue($this->makeValidator([])->passes());
        $this->assertTrue($this->makeValidator(['gender' => 0])->passes());
    }

    public function test_rejects_invalid_gender(): void
    {
        $validator = $this->makeValidator(['gender' => 4]);

        $this->assertTrue($validator->fails());
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    public function test_rejects_nonexistent_category(): void
    {
        $validator = $this->makeValidator(['category_id' => 9999]);

        $this->assertTrue($validator->fails());
        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
    }

    public function test_rejects_invalid_date_and_too_long_keyword(): void
    {
        $validator = $this->makeValidator([
            'date' => 'not-a-date',
            'keyword' => str_repeat('a', 256),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame('日付の形式が正しくありません', $validator->errors()->first('date'));
        $this->assertSame('キーワードは255文字以内で入力してください', $validator->errors()->first('keyword'));
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

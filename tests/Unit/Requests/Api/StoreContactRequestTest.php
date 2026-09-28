<?php

namespace Tests\Unit\Requests\Api;

use App\Http\Requests\Api\V1\StoreContactRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Support\ContactPayload;
use Tests\TestCase;

/**
 * API のお問い合わせ作成のバリデーション（Api\V1\StoreContactRequest）
 */
class StoreContactRequestTest extends TestCase
{
    use ContactPayload;
    use RefreshDatabase;

    public function test_accepts_all_required_fields_and_tags(): void
    {
        $this->assertTrue($this->makeValidator($this->contactPayload())->passes());
    }

    public function test_rejects_invalid_values_with_japanese_messages(): void
    {
        $validator = $this->makeValidator($this->contactPayload([
            'first_name' => '',
            'gender' => 9,
            'tel' => '090-1234-5678',
            'category_id' => 9999,
            'tag_ids' => [9999],
        ]));

        $this->assertTrue($validator->fails());
        $this->assertSame('姓を入力してください', $validator->errors()->first('first_name'));
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
        $this->assertSame('電話番号はハイフンなしの10〜11桁で入力してください', $validator->errors()->first('tel'));
        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
        $this->assertSame('選択されたタグが存在しません', $validator->errors()->first('tag_ids.0'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function makeValidator(array $data): ValidatorContract
    {
        $request = new StoreContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }
}

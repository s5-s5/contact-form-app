<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * タグ新規登録のバリデーション（StoreTagRequest）
 */
class StoreTagRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepts_valid_name(): void
    {
        $this->assertTrue($this->makeValidator(['name' => '新機能の要望'])->passes());
        $this->assertTrue($this->makeValidator(['name' => str_repeat('あ', 50)])->passes());
    }

    public function test_rejects_empty_name(): void
    {
        $validator = $this->makeValidator(['name' => '']);

        $this->assertTrue($validator->fails());
        $this->assertSame('タグ名を入力してください', $validator->errors()->first('name'));
    }

    public function test_rejects_name_longer_than_50_characters(): void
    {
        $validator = $this->makeValidator(['name' => str_repeat('あ', 51)]);

        $this->assertTrue($validator->fails());
        $this->assertSame('タグ名は50文字以内で入力してください', $validator->errors()->first('name'));
    }

    public function test_rejects_duplicate_name(): void
    {
        Tag::factory()->create(['name' => '質問']);

        $validator = $this->makeValidator(['name' => '質問']);

        $this->assertTrue($validator->fails());
        $this->assertSame('そのタグ名は既に使用されています', $validator->errors()->first('name'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function makeValidator(array $data): ValidatorContract
    {
        $request = new StoreTagRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }
}

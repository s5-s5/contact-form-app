<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreContactRequest;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ContactPayload;
use Tests\TestCase;

/**
 * お問い合わせ保存のバリデーション（StoreContactRequest）
 */
class StoreContactRequestTest extends TestCase
{
    use ContactPayload;
    use RefreshDatabase;

    public function test_accepts_all_required_fields_and_tags(): void
    {
        $this->assertTrue($this->makeValidator($this->contactPayload())->passes());
    }

    public function test_building_and_tags_are_optional(): void
    {
        $payload = $this->contactPayload(['building' => null]);
        unset($payload['tag_ids']);

        $this->assertTrue($this->makeValidator($payload)->passes());
    }

    #[DataProvider('requiredFieldProvider')]
    public function test_rejects_missing_required_field(string $field, string $message): void
    {
        $validator = $this->makeValidator($this->contactPayload([$field => null]));

        $this->assertTrue($validator->fails());
        $this->assertSame($message, $validator->errors()->first($field));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function requiredFieldProvider(): array
    {
        return [
            '姓' => ['first_name', '姓を入力してください'],
            '名' => ['last_name', '名を入力してください'],
            '性別' => ['gender', '性別を選択してください'],
            'メールアドレス' => ['email', 'メールアドレスを入力してください'],
            '電話番号' => ['tel', '電話番号を入力してください'],
            '住所' => ['address', '住所を入力してください'],
            'お問い合わせの種類' => ['category_id', 'お問い合わせの種類を選択してください'],
            'お問い合わせ内容' => ['detail', 'お問い合わせ内容を入力してください'],
        ];
    }

    #[DataProvider('invalidTelProvider')]
    public function test_rejects_invalid_tel_format(string $tel): void
    {
        $validator = $this->makeValidator($this->contactPayload(['tel' => $tel]));

        $this->assertTrue($validator->fails());
        $this->assertSame('電話番号はハイフンなしの10〜11桁で入力してください', $validator->errors()->first('tel'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidTelProvider(): array
    {
        return [
            'ハイフンあり' => ['090-1234-5678'],
            '9桁' => ['090123456'],
            '12桁' => ['090123456789'],
            '数字以外を含む' => ['0901234567a'],
        ];
    }

    public function test_accepts_ten_and_eleven_digit_tel(): void
    {
        $this->assertTrue($this->makeValidator($this->contactPayload(['tel' => '0312345678']))->passes());
        $this->assertTrue($this->makeValidator($this->contactPayload(['tel' => '09012345678']))->passes());
    }

    public function test_rejects_invalid_email_format(): void
    {
        $validator = $this->makeValidator($this->contactPayload(['email' => 'invalid-email']));

        $this->assertTrue($validator->fails());
        $this->assertSame('メールアドレスはメール形式で入力してください', $validator->errors()->first('email'));
    }

    public function test_rejects_detail_longer_than_120_characters(): void
    {
        $this->assertTrue($this->makeValidator($this->contactPayload(['detail' => str_repeat('あ', 120)]))->passes());

        $validator = $this->makeValidator($this->contactPayload(['detail' => str_repeat('あ', 121)]));

        $this->assertTrue($validator->fails());
        $this->assertSame('お問い合わせ内容は120文字以内で入力してください', $validator->errors()->first('detail'));
    }

    public function test_rejects_invalid_gender(): void
    {
        $validator = $this->makeValidator($this->contactPayload(['gender' => 4]));

        $this->assertTrue($validator->fails());
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    public function test_rejects_nonexistent_category_and_tag(): void
    {
        $validator = $this->makeValidator($this->contactPayload([
            'category_id' => 9999,
            'tag_ids' => [9999],
        ]));

        $this->assertTrue($validator->fails());
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

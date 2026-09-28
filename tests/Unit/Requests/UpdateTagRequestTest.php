<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * タグ更新のバリデーション（UpdateTagRequest）
 */
class UpdateTagRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_keep_its_own_name(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $this->assertTrue($this->makeValidator($tag, ['name' => '質問'])->passes());
    }

    public function test_can_change_to_a_new_name(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $this->assertTrue($this->makeValidator($tag, ['name' => 'よくある質問'])->passes());
    }

    public function test_rejects_name_used_by_another_tag(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);
        Tag::factory()->create(['name' => '要望']);

        $validator = $this->makeValidator($tag, ['name' => '要望']);

        $this->assertTrue($validator->fails());
        $this->assertSame('そのタグ名は既に使用されています', $validator->errors()->first('name'));
    }

    public function test_rejects_empty_or_too_long_name(): void
    {
        $tag = Tag::factory()->create();

        $this->assertSame(
            'タグ名を入力してください',
            $this->makeValidator($tag, ['name' => ''])->errors()->first('name'),
        );
        $this->assertSame(
            'タグ名は50文字以内で入力してください',
            $this->makeValidator($tag, ['name' => str_repeat('あ', 51)])->errors()->first('name'),
        );
    }

    /**
     * 更新対象のタグをルートパラメータとして渡したうえで、バリデーターを作る
     *
     * @param  array<string, mixed>  $data
     */
    private function makeValidator(Tag $tag, array $data): ValidatorContract
    {
        $route = new Route(['PUT'], 'admin/tags/{tag}', []);
        $route->parameters = ['tag' => $tag];

        $request = new UpdateTagRequest;
        $request->setRouteResolver(fn () => $route);

        return Validator::make($data, $request->rules(), $request->messages());
    }
}

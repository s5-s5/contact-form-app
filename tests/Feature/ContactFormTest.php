<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContactPayload;
use Tests\TestCase;

/**
 * お問い合わせフォーム（入力・確認・送信・サンクス）
 */
class ContactFormTest extends TestCase
{
    use ContactPayload;
    use RefreshDatabase;

    public function test_contact_form_page_shows_categories_and_tags(): void
    {
        $category = Category::factory()->create(['content' => '商品のお届けについて']);
        $tag = Tag::factory()->create(['name' => '質問']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('contact.index');
        $response->assertViewHas('categories', fn ($categories) => $categories->contains($category));
        $response->assertViewHas('tags', fn ($tags) => $tags->contains($tag));
        $response->assertSee('商品のお届けについて');
        $response->assertSee('質問');
    }

    public function test_thanks_page_is_displayed(): void
    {
        $response = $this->get('/thanks');

        $response->assertOk();
        $response->assertViewIs('contact.thanks');
        $response->assertSee('お問い合わせありがとうございました');
    }

    public function test_confirm_page_shows_input_with_category_and_tag_names(): void
    {
        $category = Category::factory()->create(['content' => '商品トラブル']);
        $tagIds = [
            Tag::factory()->create(['name' => '要望'])->id,
            Tag::factory()->create(['name' => 'ご意見'])->id,
        ];

        $response = $this->post('/contacts/confirm', $this->contactPayload([
            'gender' => 2,
            'category_id' => $category->id,
            'tag_ids' => $tagIds,
        ]));

        $response->assertOk();
        $response->assertViewIs('contact.confirm');
        $response->assertSee('山田');
        $response->assertSee('太郎');
        $response->assertSee('女性');
        $response->assertSee('test@example.com');
        $response->assertSee('09012345678');
        $response->assertSee('商品トラブル');
        $response->assertSee('要望, ご意見');
    }

    public function test_confirm_combines_separated_tel_inputs(): void
    {
        $payload = $this->contactPayload(['tel1' => '080', 'tel2' => '1234', 'tel3' => '5678']);
        unset($payload['tel']);

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertOk();
        $response->assertSee('08012345678');
    }

    public function test_confirm_redirects_back_with_errors_when_required_fields_are_empty(): void
    {
        $response = $this->from('/')->post('/contacts/confirm', []);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'first_name' => '姓を入力してください',
            'last_name' => '名を入力してください',
            'gender' => '性別を選択してください',
            'email' => 'メールアドレスを入力してください',
            'tel' => '電話番号を入力してください',
            'address' => '住所を入力してください',
            'category_id' => 'お問い合わせの種類を選択してください',
            'detail' => 'お問い合わせ内容を入力してください',
        ]);
    }

    public function test_confirm_shows_email_format_and_detail_length_errors(): void
    {
        $response = $this->from('/')->post('/contacts/confirm', $this->contactPayload([
            'email' => 'invalid-email',
            'detail' => str_repeat('あ', 121),
        ]));

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスはメール形式で入力してください',
            'detail' => 'お問い合わせ内容は120文字以内で入力してください',
        ]);
    }

    public function test_contact_is_stored_with_tags_and_redirects_to_thanks(): void
    {
        $payload = $this->contactPayload();

        $response = $this->post('/contacts', $payload);

        $response->assertRedirect('/thanks');
        $this->assertDatabaseHas('contacts', [
            'category_id' => $payload['category_id'],
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区千駄ヶ谷1-2-3',
            'building' => '千駄ヶ谷マンション101',
            'detail' => '商品の配送日について',
        ]);

        $contact = Contact::query()->where('email', 'test@example.com')->firstOrFail();
        foreach ($payload['tag_ids'] as $tagId) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tagId,
            ]);
        }
    }

    public function test_store_redirects_back_with_errors_when_invalid(): void
    {
        $response = $this->from('/')->post('/contacts', $this->contactPayload(['tel' => '090-1234-5678']));

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['tel' => '電話番号はハイフンなしの10〜11桁で入力してください']);
        $this->assertDatabaseCount('contacts', 0);
    }
}

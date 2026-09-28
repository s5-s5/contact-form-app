<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CSV エクスポート
 */
class ContactExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_downloads_all_contacts_in_newest_order_when_no_filters(): void
    {
        $category = Category::factory()->create(['content' => '商品トラブル']);
        $older = Contact::factory()->for($category)->create([
            'first_name' => '古田',
            'last_name' => '一郎',
            'gender' => 1,
            'detail' => '古いお問い合わせ',
            'created_at' => '2026-09-01 10:00:00',
        ]);
        $newer = Contact::factory()->for($category)->create([
            'first_name' => '新井',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'hanako@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => '渋谷ビル301',
            'detail' => '新しいお問い合わせ',
            'created_at' => '2026-09-02 10:00:00',
        ]);

        $response = $this->actingAs($this->user)->get('/contacts/export');

        $response->assertOk();
        $response->assertDownload();
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        $rows = $this->parseCsv($content);
        $this->assertSame(['ID', '氏名', '性別', 'メール', '電話', '住所', '建物', 'カテゴリ', '内容', '作成日時'], $rows[0]);
        $this->assertSame([
            (string) $newer->id,
            '新井 花子',
            '女性',
            'hanako@example.com',
            '09012345678',
            '東京都渋谷区1-1-1',
            '渋谷ビル301',
            '商品トラブル',
            '新しいお問い合わせ',
            '2026-09-02 10:00:00',
        ], $rows[1]);
        $this->assertSame((string) $older->id, $rows[2][0]);
        $this->assertCount(3, $rows);
    }

    public function test_downloads_only_contacts_matching_filters(): void
    {
        $male = Contact::factory()->create(['gender' => 1]);
        Contact::factory()->create(['gender' => 2]);

        $response = $this->actingAs($this->user)->get('/contacts/export?gender=1');

        $rows = $this->parseCsv($response->streamedContent());
        $this->assertCount(2, $rows);
        $this->assertSame((string) $male->id, $rows[1][0]);
        $this->assertSame('男性', $rows[1][2]);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $response = $this->actingAs($this->user)->from('/admin')->get('/contacts/export?gender=9');

        $response->assertRedirect('/admin');
        $response->assertSessionHasErrors(['gender' => '性別の値が不正です']);
    }

    public function test_guest_cannot_download_csv(): void
    {
        $this->get('/contacts/export')->assertRedirect('/login');
    }

    /**
     * BOM を取り除き、CSV を行ごとの配列にする
     *
     * @return array<int, array<int, string|null>>
     */
    private function parseCsv(string $content): array
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, substr($content, 3));
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }
}

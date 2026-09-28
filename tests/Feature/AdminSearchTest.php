<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理画面の検索・ページネーション
 */
class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_contacts_are_paginated_by_seven(): void
    {
        Contact::factory()->count(8)->create();

        $firstPage = $this->actingAs($this->user)->get('/admin')->viewData('contacts');
        $secondPage = $this->actingAs($this->user)->get('/admin?page=2')->viewData('contacts');

        $this->assertCount(7, $firstPage->items());
        $this->assertSame(8, $firstPage->total());
        $this->assertCount(1, $secondPage->items());
    }

    public function test_filter_by_keyword_matches_name_and_email(): void
    {
        $yamada = Contact::factory()->create(['first_name' => '山田', 'last_name' => '太郎', 'email' => 'taro@example.com']);
        $suzuki = Contact::factory()->create(['first_name' => '鈴木', 'last_name' => '花子', 'email' => 'hanako@example.com']);

        $this->assertSearchResult(['keyword' => '山田'], [$yamada]);
        $this->assertSearchResult(['keyword' => '花子'], [$suzuki]);
        $this->assertSearchResult(['keyword' => 'hanako@example.com'], [$suzuki]);
        $this->assertSearchResult(['keyword' => 'example'], [$yamada, $suzuki]);
        $this->assertSearchResult(['keyword' => '山田 太郎'], [$yamada]);
    }

    public function test_filter_by_gender(): void
    {
        $male = Contact::factory()->create(['gender' => 1]);
        $female = Contact::factory()->create(['gender' => 2]);

        $this->assertSearchResult(['gender' => 2], [$female]);
        $this->assertSearchResult(['gender' => 0], [$male, $female]);
    }

    public function test_filter_by_category(): void
    {
        $category = Category::factory()->create();
        $target = Contact::factory()->for($category)->create();
        Contact::factory()->for(Category::factory())->create();

        $this->assertSearchResult(['category_id' => $category->id], [$target]);
    }

    public function test_filter_by_date(): void
    {
        $target = Contact::factory()->create(['created_at' => '2026-09-01 10:00:00']);
        Contact::factory()->create(['created_at' => '2026-09-02 10:00:00']);

        $this->assertSearchResult(['date' => '2026-09-01'], [$target]);
    }

    public function test_all_filters_can_be_combined(): void
    {
        $category = Category::factory()->create();
        $target = Contact::factory()->for($category)->create([
            'first_name' => '田中',
            'gender' => 1,
            'created_at' => '2026-09-01 10:00:00',
        ]);
        Contact::factory()->for($category)->create([
            'first_name' => '田中',
            'gender' => 2,
            'created_at' => '2026-09-01 10:00:00',
        ]);

        $this->assertSearchResult([
            'keyword' => '田中',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-09-01',
        ], [$target]);
    }

    public function test_invalid_gender_is_rejected(): void
    {
        $response = $this->actingAs($this->user)->from('/admin')->get('/admin?gender=9');

        $response->assertRedirect('/admin');
        $response->assertSessionHasErrors(['gender' => '性別の値が不正です']);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<int, Contact>  $expected
     */
    private function assertSearchResult(array $query, array $expected): void
    {
        $response = $this->actingAs($this->user)->get('/admin?'.http_build_query($query));
        $response->assertOk();

        $actualIds = collect($response->viewData('contacts')->items())->pluck('id')->sort()->values()->all();
        $expectedIds = collect($expected)->pluck('id')->sort()->values()->all();

        $this->assertSame($expectedIds, $actualIds);
    }
}

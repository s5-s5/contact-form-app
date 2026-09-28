<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactController extends Controller
{
    /**
     * 1ページあたりの件数（per_page を省略したとき）
     */
    private const DEFAULT_PER_PAGE = 20;

    /**
     * お問い合わせ一覧（検索・ページネーション付き）
     */
    public function index(IndexContactRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $contacts = Contact::with(['category', 'tags'])
            ->search($validated)
            ->latest()
            ->latest('id')
            ->paginate($validated['per_page'] ?? self::DEFAULT_PER_PAGE)
            ->withQueryString();

        return ContactResource::collection($contacts);
    }

    /**
     * お問い合わせ詳細（カテゴリ・タグ含む）
     */
    public function show(Contact $contact): ContactResource
    {
        return new ContactResource($contact->load(['category', 'tags']));
    }

    /**
     * お問い合わせ新規作成
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = Contact::createWithTags($request->validated());

        return (new ContactResource($contact->load(['category', 'tags'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * お問い合わせ更新（タグは送られた内容に置き換える）
     */
    public function update(UpdateContactRequest $request, Contact $contact): ContactResource
    {
        $contact->updateWithTags($request->validated());

        return new ContactResource($contact->load(['category', 'tags']));
    }

    /**
     * お問い合わせ削除（contact_tag の関連レコードは外部キーの設定で自動的に削除される）
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $contact->delete();

        return response()->json(null, 204);
    }
}

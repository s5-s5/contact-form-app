<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TagController extends Controller
{
    /**
     * タグを追加する
     */
    public function store(StoreTagRequest $request): RedirectResponse
    {
        Tag::create($request->validated());

        return redirect('/admin');
    }

    /**
     * タグ編集ページ
     */
    public function edit(Tag $tag): View
    {
        return view('admin.tags.edit', compact('tag'));
    }

    /**
     * タグ名を更新する
     */
    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $tag->update($request->validated());

        return redirect('/admin');
    }

    /**
     * タグを削除する（contact_tag の関連レコードは外部キーの設定で自動的に削除される）
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect('/admin');
    }
}

<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Web の管理画面と違い、gender に 0 は使わない（省略すると「すべて」）。
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'integer', 'in:1,2,3'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'date' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードを文字列で入力してください',
            'keyword.max' => 'キーワードは255文字以内で入力してください',
            'gender.integer' => '性別の値が不正です',
            'gender.in' => '性別の値が不正です',
            'category_id.integer' => '選択されたカテゴリーが存在しません',
            'category_id.exists' => '選択されたカテゴリーが存在しません',
            'date.date' => '日付の形式が正しくありません',
            'page.integer' => 'ページ番号は1以上の整数で指定してください',
            'page.min' => 'ページ番号は1以上の整数で指定してください',
            'per_page.integer' => '1ページあたりの件数は1〜100の整数で指定してください',
            'per_page.min' => '1ページあたりの件数は1〜100の整数で指定してください',
            'per_page.max' => '1ページあたりの件数は1〜100の整数で指定してください',
        ];
    }
}

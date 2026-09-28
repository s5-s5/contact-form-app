<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * お問い合わせを CSV としてダウンロードさせる
 */
class ContactCsvExporter
{
    /**
     * CSV の1行目（見出し）
     */
    private const HEADER = ['ID', '氏名', '性別', 'メール', '電話', '住所', '建物', 'カテゴリ', '内容', '作成日時'];

    /**
     * Excel で開いても文字化けしないよう、先頭に付ける BOM
     */
    private const BOM = "\xEF\xBB\xBF";

    /**
     * @param  Collection<int, Contact>  $contacts
     */
    public function download(Collection $contacts, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($contacts) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, self::BOM);
            fputcsv($stream, self::HEADER);

            foreach ($contacts as $contact) {
                fputcsv($stream, $this->toRow($contact));
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array<int, mixed>
     */
    private function toRow(Contact $contact): array
    {
        return [
            $contact->id,
            $contact->full_name,
            $contact->gender_label,
            $contact->email,
            $contact->tel,
            $contact->address,
            $contact->building,
            $contact->category?->content,
            $contact->detail,
            $contact->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

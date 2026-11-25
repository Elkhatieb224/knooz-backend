<?php

namespace App\Services\Content;

use App\Models\Setting;

class ContentService
{
    public const PAGES = [
        'about' => 'عن التطبيق',
        'terms' => 'الشروط والأحكام',
        'privacy' => 'سياسة الخصوصية',
        'faq' => 'الأسئلة الشائعة',
    ];

    public function get(string $page): array
    {
        $key = $this->key($page);
        $raw = Setting::getValue($key, '');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        if (is_array($decoded)) {
            return [
                'page' => $page,
                'title' => (string) ($decoded['title'] ?? (self::PAGES[$page] ?? $page)),
                'body' => (string) ($decoded['body'] ?? ''),
                'updated_at' => $decoded['updated_at'] ?? null,
            ];
        }

        return [
            'page' => $page,
            'title' => self::PAGES[$page] ?? $page,
            'body' => is_string($raw) ? $raw : '',
            'updated_at' => null,
        ];
    }

    public function put(string $page, string $title, string $body): array
    {
        $payload = [
            'title' => $title,
            'body' => $body,
            'updated_at' => now()->toIso8601String(),
        ];
        Setting::setValue($this->key($page), $payload);

        return $this->get($page);
    }

    /**
     * @return array<string, array{page: string, title: string, body: string, updated_at: ?string}>
     */
    public function all(): array
    {
        $out = [];
        foreach (array_keys(self::PAGES) as $page) {
            $out[$page] = $this->get($page);
        }

        return $out;
    }

    protected function key(string $page): string
    {
        return 'content.'.$page;
    }
}

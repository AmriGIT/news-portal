<?php

namespace Tests\Unit\Services;

use App\Services\ContentSanitizer;
use Tests\TestCase;

class ContentSanitizerTest extends TestCase
{
    public function test_it_preserves_allowed_content(): void
    {
        $html = app(ContentSanitizer::class)->sanitize('<h2>Judul</h2><p><strong>Tebal</strong></p><ul><li>Item</li></ul><blockquote>Kutipan</blockquote>');

        $this->assertStringContainsString('<h2>Judul</h2>', $html);
        $this->assertStringContainsString('<strong>Tebal</strong>', $html);
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<blockquote>', $html);
    }

    public function test_it_removes_dangerous_html_and_urls(): void
    {
        $html = app(ContentSanitizer::class)->sanitize('<p onclick="alert(1)">Halo<script>alert(1)</script><img src="javascript:alert(1)" onerror="alert(1)"><iframe src="https://example.test"></iframe><a href="javascript:alert(1)">Klik</a></p>');

        $this->assertStringNotContainsString('script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('iframe', $html);
        $this->assertStringContainsString('Klik', $html);
    }

    public function test_it_adds_rel_to_blank_target_links(): void
    {
        $html = app(ContentSanitizer::class)->sanitize('<a href="https://example.test" target="_blank">Link</a>');

        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function test_it_can_sanitize_tiptap_document_arrays(): void
    {
        $html = app(ContentSanitizer::class)->sanitize([
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 2],
                    'content' => [
                        ['type' => 'text', 'text' => 'Judul Array'],
                    ],
                ],
            ],
        ]);

        $this->assertStringContainsString('<h2>Judul Array</h2>', $html);
    }
}

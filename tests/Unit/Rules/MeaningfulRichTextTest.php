<?php

namespace Tests\Unit\Rules;

use App\Rules\MeaningfulRichText;
use PHPUnit\Framework\TestCase;

class MeaningfulRichTextTest extends TestCase
{
    public function test_text_content_is_accepted(): void
    {
        $this->assertPasses('<p>Isi berita</p>');
        $this->assertPasses('<h2>Judul</h2>');
        $this->assertPasses('<ul><li>Item</li></ul>');
    }

    public function test_empty_rich_text_is_rejected(): void
    {
        $this->assertFails('');
        $this->assertFails('<p><br></p>');
        $this->assertFails('<div>&nbsp;</div>');
        $this->assertFails(['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => []]]]);
    }

    private function assertPasses(mixed $value): void
    {
        $failed = false;

        (new MeaningfulRichText)->validate('content', $value, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    private function assertFails(mixed $value): void
    {
        $failed = false;

        (new MeaningfulRichText)->validate('content', $value, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertTrue($failed);
    }
}

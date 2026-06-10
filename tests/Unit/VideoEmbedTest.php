<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Testes da função video_embed_url(), que converte links do YouTube para o
 * formato embed (youtube-nocookie.com) aceito em <iframe> e rejeita URLs
 * inválidas ou com protocolos perigosos.
 */
class VideoEmbedTest extends TestCase
{
    private const EMBED = 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ';

    public function testWatchUrlConvertida(): void
    {
        $this->assertSame(self::EMBED, video_embed_url('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
    }

    public function testWatchUrlComParametrosExtras(): void
    {
        $this->assertSame(self::EMBED, video_embed_url('https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s&list=PL123'));
    }

    public function testYoutuBeConvertida(): void
    {
        $this->assertSame(self::EMBED, video_embed_url('https://youtu.be/dQw4w9WgXcQ'));
    }

    public function testShortsConvertida(): void
    {
        $this->assertSame(self::EMBED, video_embed_url('https://www.youtube.com/shorts/dQw4w9WgXcQ'));
    }

    public function testEmbedNormalizadaParaNocookie(): void
    {
        $this->assertSame(self::EMBED, video_embed_url('https://www.youtube.com/embed/dQw4w9WgXcQ'));
    }

    public function testMobileYoutubeConvertida(): void
    {
        $this->assertSame(self::EMBED, video_embed_url('https://m.youtube.com/watch?v=dQw4w9WgXcQ'));
    }

    public function testUrlNaoYoutubePassaDireto(): void
    {
        $vimeo = 'https://player.vimeo.com/video/123456789';
        $this->assertSame($vimeo, video_embed_url($vimeo));
    }

    public function testVaziaOuNulaRetornaVazio(): void
    {
        $this->assertSame('', video_embed_url(null));
        $this->assertSame('', video_embed_url(''));
        $this->assertSame('', video_embed_url('   '));
    }

    public function testUrlInvalidaRetornaVazio(): void
    {
        $this->assertSame('', video_embed_url('nao-e-uma-url'));
    }

    public function testProtocoloPerigosoRejeitado(): void
    {
        // javascript: e data: em src de iframe seriam vetores de XSS
        $this->assertSame('', video_embed_url('javascript:alert(1)'));
        $this->assertSame('', video_embed_url('data:text/html,<script>alert(1)</script>'));
    }
}

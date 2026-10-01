<?php
/**
 * @package RMD\FocalZoomPoint
 */

declare(strict_types=1);

namespace RMD\FocalZoomPoint\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RMD\FocalZoomPoint\Domain\Markdown;

final class MarkdownTest extends TestCase {

	public function test_headings_get_ids(): void {
		$this->assertSame( '<h2 id="2-background-images">2. Background images</h2>', Markdown::to_html( '## 2. Background images' ) );
		$this->assertSame( '<h3 id="rmd_fzp_style"><code>rmd_fzp_style()</code></h3>', Markdown::to_html( '### `rmd_fzp_style()`' ) );
	}

	public function test_paragraph_and_inline(): void {
		$html = Markdown::to_html( "Use **bold**, *em* and `<code>`\nacross lines, see [docs](#usage)." );
		$this->assertSame( '<p>Use <strong>bold</strong>, <em>em</em> and <code>&lt;code&gt;</code> across lines, see <a href="#usage">docs</a>.</p>', $html );
	}

	public function test_escapes_html(): void {
		$this->assertSame( '<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>', Markdown::to_html( '<script>alert(1)</script>' ) );
	}

	public function test_rejects_javascript_links(): void {
		$this->assertStringNotContainsString( 'href', Markdown::to_html( '[click](javascript:alert(1))' ) );
	}

	public function test_external_links_open_in_new_tab(): void {
		$this->assertStringContainsString( '<a href="https://example.com" target="_blank" rel="noopener noreferrer">x</a>', Markdown::to_html( '[x](https://example.com)' ) );
	}

	public function test_code_block(): void {
		$html = Markdown::to_html( "```php\n<?php echo \$a; // **not bold**\n```" );
		$this->assertSame( '<pre><code class="language-php">&lt;?php echo $a; // **not bold**</code></pre>', $html );
	}

	public function test_lists(): void {
		$this->assertSame( '<ul><li>one</li><li>two continued</li></ul>', Markdown::to_html( "- one\n- two\n  continued" ) );
		$this->assertSame( '<ol><li>first</li><li>second</li></ol>', Markdown::to_html( "1. first\n2. second" ) );
	}

	public function test_table_keeps_pipes_in_code(): void {
		$html = Markdown::to_html( "| Name | Value |\n| --- | --- |\n| `a|b` | 2 |" );
		$this->assertSame( '<table class="widefat striped"><thead><tr><th>Name</th><th>Value</th></tr></thead><tbody><tr><td><code>a|b</code></td><td>2</td></tr></tbody></table>', $html );
	}

	public function test_blockquote(): void {
		$this->assertSame( '<blockquote><p>Note: keep it.</p></blockquote>', Markdown::to_html( '> Note: keep it.' ) );
	}
}

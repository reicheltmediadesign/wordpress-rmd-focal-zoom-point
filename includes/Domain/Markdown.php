<?php
/**
 * Minimal Markdown to HTML converter for the plugin's own documentation
 * (docs/theme-integration.md), so the same file serves GitHub readers and the
 * help page in the admin area.
 *
 * Supports exactly what that file uses: headings (#–####, with ids),
 * paragraphs, unordered and ordered lists, fenced code blocks, tables,
 * blockquotes and the inline forms `code`, **bold**, *emphasis* and
 * [links](url). Everything is escaped; links are limited to http(s), mailto,
 * relative paths and #anchors.
 *
 * @package RMD\FocalZoomPoint
 */

namespace RMD\FocalZoomPoint\Domain;

final class Markdown {

	public static function to_html( string $markdown ): string {
		$lines  = preg_split( '/\R/', $markdown );
		$html   = [];
		$count  = count( $lines );
		$index  = 0;
		$buffer = [];

		$flush = static function () use ( &$buffer, &$html ): void {
			if ( $buffer ) {
				$html[] = '<p>' . self::inline( implode( ' ', array_map( 'trim', $buffer ) ) ) . '</p>';
				$buffer = [];
			}
		};

		while ( $index < $count ) {
			$line = $lines[ $index ];

			if ( preg_match( '/^```\s*([\w-]*)\s*$/', $line, $fence ) ) {
				$flush();
				$code = [];
				++$index;
				while ( $index < $count && ! preg_match( '/^```\s*$/', $lines[ $index ] ) ) {
					$code[] = $lines[ $index ];
					++$index;
				}
				++$index;
				$class  = '' !== $fence[1] ? ' class="language-' . self::escape( $fence[1] ) . '"' : '';
				$html[] = '<pre><code' . $class . '>' . self::escape( implode( "\n", $code ) ) . '</code></pre>';
				continue;
			}

			if ( preg_match( '/^(#{1,4})\s+(.+?)\s*#*\s*$/', $line, $heading ) ) {
				$flush();
				$level  = strlen( $heading[1] );
				$html[] = sprintf( '<h%1$d id="%2$s">%3$s</h%1$d>', $level, self::escape( self::slug( $heading[2] ) ), self::inline( $heading[2] ) );
				++$index;
				continue;
			}

			if ( preg_match( '/^\s*\|/', $line ) && $index + 1 < $count && preg_match( '/^\s*\|?\s*:?-{3,}/', $lines[ $index + 1 ] ) ) {
				$flush();
				$header = self::cells( $line );
				$index += 2;
				$rows   = [];
				while ( $index < $count && preg_match( '/^\s*\|/', $lines[ $index ] ) ) {
					$rows[] = self::cells( $lines[ $index ] );
					++$index;
				}
				$table = '<table class="widefat striped"><thead><tr>';
				foreach ( $header as $cell ) {
					$table .= '<th>' . self::inline( $cell ) . '</th>';
				}
				$table .= '</tr></thead><tbody>';
				foreach ( $rows as $row ) {
					$table .= '<tr>';
					foreach ( $row as $cell ) {
						$table .= '<td>' . self::inline( $cell ) . '</td>';
					}
					$table .= '</tr>';
				}
				$html[] = $table . '</tbody></table>';
				continue;
			}

			if ( preg_match( '/^\s*([-*]|\d+\.)\s+/', $line, $marker ) ) {
				$flush();
				$ordered = ctype_digit( rtrim( $marker[1], '.' ) );
				$pattern = $ordered ? '/^\s*\d+\.\s+(.*)$/' : '/^\s*[-*]\s+(.*)$/';
				$items   = [];
				while ( $index < $count ) {
					if ( preg_match( $pattern, $lines[ $index ], $item ) ) {
						$items[] = $item[1];
					} elseif ( $items && preg_match( '/^\s{2,}\S/', $lines[ $index ] ) ) {
						$items[ count( $items ) - 1 ] .= ' ' . trim( $lines[ $index ] );
					} else {
						break;
					}
					++$index;
				}
				$tag    = $ordered ? 'ol' : 'ul';
				$html[] = '<' . $tag . '>' . implode( '', array_map( static fn( string $text ): string => '<li>' . self::inline( $text ) . '</li>', $items ) ) . '</' . $tag . '>';
				continue;
			}

			if ( preg_match( '/^>\s?(.*)$/', $line ) ) {
				$flush();
				$quote = [];
				while ( $index < $count && preg_match( '/^>\s?(.*)$/', $lines[ $index ], $part ) ) {
					$quote[] = $part[1];
					++$index;
				}
				$html[] = '<blockquote>' . self::to_html( implode( "\n", $quote ) ) . '</blockquote>';
				continue;
			}

			if ( '' === trim( $line ) ) {
				$flush();
			} else {
				$buffer[] = $line;
			}
			++$index;
		}
		$flush();

		return implode( "\n", $html );
	}

	/**
	 * Heading text → id, as GitHub builds it: lower case, punctuation dropped,
	 * spaces to hyphens.
	 */
	public static function slug( string $text ): string {
		$text = strtolower( trim( preg_replace( '/[`*\[\]()]/', '', $text ) ) );
		$text = preg_replace( '/[^\p{L}\p{N}\s_-]/u', '', $text );
		return preg_replace( '/\s/', '-', $text );
	}

	/**
	 * @return string[]
	 */
	private static function cells( string $line ): array {
		$line = trim( trim( $line ), '|' );
		// A pipe inside `code` must not split the cell.
		$cells = preg_split( '/\|(?=(?:[^`]*`[^`]*`)*[^`]*$)/', $line );
		return array_map( 'trim', $cells );
	}

	private static function inline( string $text ): string {
		$codes = [];
		$text  = preg_replace_callback(
			'/`([^`]+)`/',
			static function ( array $found ) use ( &$codes ): string {
				$codes[] = '<code>' . self::escape( $found[1] ) . '</code>';
				return "\x1A" . ( count( $codes ) - 1 ) . "\x1A";
			},
			$text
		);

		$text = self::escape( $text );
		$text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
		$text = preg_replace( '/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/', '<em>$1</em>', $text );
		$text = preg_replace_callback(
			'/\[([^\]]+)\]\(([^)\s]+)\)/',
			static function ( array $found ): string {
				$url = html_entity_decode( $found[2], ENT_QUOTES, 'UTF-8' );
				if ( ! preg_match( '#^(https?://|mailto:|\#|\./|\.\./|/|[\w-]+(\.[\w]+)?(\#[\w-]*)?$)#', $url ) ) {
					return $found[1];
				}
				$external = (bool) preg_match( '#^https?://#', $url );
				return '<a href="' . self::escape( $url ) . '"' . ( $external ? ' target="_blank" rel="noopener noreferrer"' : '' ) . '>' . $found[1] . '</a>';
			},
			$text
		);

		return preg_replace_callback(
			"/\x1A(\d+)\x1A/",
			static fn( array $found ): string => $codes[ (int) $found[1] ],
			$text
		);
	}

	private static function escape( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}

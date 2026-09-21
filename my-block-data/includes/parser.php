<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse raw import text into an intro string and an array of blocks.
 *
 * Expected structure (repeated):
 *   (YYYY/MM/DD) <Headline>
 *   <Link 1>
 *   ... <Link n>
 *   <blank line>
 *
 * Any text before the first recognised "(YYYY/MM/DD) <Headline>" line is
 * treated as the introductory text.
 *
 * The date must be wrapped in round parentheses, e.g. (2024/01/15). Month
 * and day may be 1 or 2 digits, e.g. (2024/1/5) is also accepted.
 *
 * @param string $text Raw text.
 * @return array {
 *     @type string $intro  Introductory text (before first block).
 *     @type array  $blocks List of ['date' => string, 'headline' => string, 'links' => array]
 *     @type int    $skipped_lines Number of non-empty lines inside a block that were not valid URLs and were skipped.
 * }
 */
function mbd_parse_import_text( $text ) {
	$text  = str_replace( "\r\n", "\n", $text );
	$text  = str_replace( "\r", "\n", $text );
	$lines = explode( "\n", $text );

	// Matches "(YYYY/MM/DD) <rest of line>" at the start of a line.
	// Group 1 is the date WITHOUT the parentheses; group 2 is the headline.
	$date_pattern = '/^\s*\((\d{4}\/\d{1,2}\/\d{1,2})\)\s+(.+)$/u';

	$intro_lines   = array();
	$blocks        = array();
	$current       = null;
	$skipped_lines = 0;

	foreach ( $lines as $line ) {
		$trimmed = trim( $line );

		if ( preg_match( $date_pattern, $line, $m ) ) {
			// Start of a new block - close off the previous one first.
			if ( null !== $current ) {
				$blocks[] = $current;
			}
			$current = array(
				'date'     => trim( $m[1] ),
				'headline' => trim( $m[2] ),
				'links'    => array(),
			);
			continue;
		}

		if ( null !== $current ) {
			// We're inside a block.
			if ( '' === $trimmed ) {
				// Blank line: just a separator, keep going in case more
				// links or a new date-line follow.
				continue;
			}
			if ( mbd_looks_like_url( $trimmed ) ) {
				$current['links'][] = $trimmed;
			} else {
				$skipped_lines++;
			}
		} else {
			// Still in the intro section.
			$intro_lines[] = $line;
		}
	}

	if ( null !== $current ) {
		$blocks[] = $current;
	}

	return array(
		'intro'         => trim( implode( "\n", $intro_lines ) ),
		'blocks'        => $blocks,
		'skipped_lines' => $skipped_lines,
	);
}

/**
 * Loose check for whether a line looks like a URL worth keeping.
 */
function mbd_looks_like_url( $line ) {
	if ( preg_match( '#^(https?://|www\.)#i', $line ) ) {
		return true;
	}
	// Fallback: validate as URL as-is (covers things like ftp:// etc.)
	return false !== filter_var( $line, FILTER_VALIDATE_URL );
}

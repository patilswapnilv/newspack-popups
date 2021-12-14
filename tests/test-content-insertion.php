<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Class ContentInsertion Test
 *
 * @package Newspack_Popups
 */

/**
 * ContentInsertion test case.
 */
class ContentInsertionTest extends WP_UnitTestCase {
	private static $popups = []; // phpcs:ignore Squiz.Commenting.VariableComment.Missing

	public function setUp() { // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
		self::$popups = [
			self::create_inline_popup( '0' ),
			self::create_inline_popup( '70' ),
			self::create_inline_popup( '100' ),
		];
	}


	/**
	 * Create an inline popup configuration object.
	 *
	 * @param string $placement Placement, as percentage in content.
	 */
	private static function create_inline_popup( $placement ) {
		return [
			'id'      => wp_rand(),
			'content' => 'Some content.',
			'options' => [
				'placement'               => 'inline',
				'trigger_scroll_progress' => $placement,
			],
		];
	}

	/**
	 * Get the popup as shortcode - that's how inline popups are inserted into content.
	 *
	 * @param string $id ID.
	 */
	public static function rendered_popup( $id ) {
		return '<!-- wp:shortcode -->[newspack-popup id="' . $id . '"]<!-- /wp:shortcode -->';
	}

	/**
	 * Assert that serialized blocks match the block names.
	 *
	 * @param string[] $expected List of block names.
	 * @param array    $actual   Parsed blocks for assertion.
	 * @param string   $message  Message.
	 */
	private static function assertEqualBlockNames( $expected, $actual, $message = '' ) {
		$parsed_blocks = parse_blocks(
			str_replace(
				array( "\n", "\r" ),
				'',
				$actual
			)
		);
		$actual_names  = wp_list_pluck( $parsed_blocks, 'blockName' );
		self::assertEquals( $expected, $actual_names, $message );
	}

	/**
	 * Insertion into block-based post content.
	 */
	public function test_insertion_into_block_content() {
		$post_content = '
<!-- wp:image {"align":"right"} -->
<div class="wp-block-image">image</div>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>Paragraph 1</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>A heading</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Paragraph 2</p>
<!-- /wp:paragraph -->
';
		self::assertEqualBlockNames(
			[
				'core/shortcode', // Popup 1.
				'core/image',
				'core/paragraph',
				'core/shortcode', // Popup 2 – inserted before the heading, not after it.
				'core/heading',
				'core/paragraph',
				'core/shortcode', // Popup 3.
			],
			Newspack_Popups_Inserter::insert_popups_in_post_content(
				$post_content,
				self::$popups
			),
			'The popups are inserted into the content at expected positions.'
		);
	}

	/**
	 * Insertion into block-based post content, a longer version.
	 */
	public function test_insertion_into_block_content_long() {
		$post_content = '
<!-- wp:image {"align":"right"} -->
<div class="wp-block-image">image</div>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>Paragraph</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Paragraph</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Paragraph</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>A heading</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Paragraph</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Paragraph</p>
<!-- /wp:paragraph -->
';
		self::assertEqualBlockNames(
			[
				'core/image',
				'core/paragraph',
				'core/shortcode', // Popup.
				'core/paragraph',
				'core/shortcode', // Popup.
				'core/paragraph',
				'core/shortcode', // Popup.
				'core/heading',
				'core/paragraph',
				'core/paragraph',
			],
			Newspack_Popups_Inserter::insert_popups_in_post_content(
				$post_content,
				[
					self::create_inline_popup( '24' ),
					self::create_inline_popup( '50' ),
					self::create_inline_popup( '60' ),
				]
			),
			'The popups are inserted into the content at expected positions.'
		);
	}

	/**
	 * Insertion into block-based post content, when a heading is the last block.
	 */
	public function test_insertion_into_block_content_ending_with_heading() {
		$post_content = '
<!-- wp:image {"align":"right"} -->
<div class="wp-block-image">image</div>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p>Paragraph</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>A heading</h2>
<!-- /wp:heading -->
';
		self::assertEqualBlockNames(
			[
				'core/shortcode', // Popup 1.
				'core/image',
				'core/paragraph',
				'core/shortcode', // Popup 2.
				'core/heading',
				'core/shortcode', // Popup 3.
			],
			Newspack_Popups_Inserter::insert_popups_in_post_content(
				$post_content,
				self::$popups
			),
			'The popups are inserted into the content at expected positions when a heading is the last block.'
		);
	}

	/**
	 * Insertion into block-based post content, when a heading is the first block.
	 */
	public function test_insertion_into_block_content_starting_with_heading() {
		$post_content = '
<!-- wp:heading -->
<h2>A heading</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Paragraph</p>
<!-- /wp:paragraph -->
';
		self::assertEqualBlockNames(
			[
				'core/shortcode', // Popup 1.
				'core/shortcode', // Popup 2.
				'core/heading',
				'core/paragraph',
				'core/shortcode', // Popup 3.
			],
			Newspack_Popups_Inserter::insert_popups_in_post_content(
				$post_content,
				self::$popups
			),
			'The popups are inserted into the content at expected positions when a heading is the first block.'
		);
	}

	/**
	 * Insertion into block-based post content, when there are consecutive insertion-preventing blocks.
	 */
	public function test_insertion_into_block_content_consecutive() {
		$post_content = '
<!-- wp:paragraph -->
<p>Paragraph</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>A heading</h2>
<!-- /wp:heading -->

<!-- wp:image {"align":"right"} -->
<div class="wp-block-image">image</div>
<!-- /wp:image -->

<!-- wp:heading -->
<h2>A heading</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Paragraph</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Paragraph</p>
<!-- /wp:paragraph -->
';
		self::assertEqualBlockNames(
			[
				'core/shortcode', // Popup 1.
				'core/paragraph',
				'core/shortcode', // Popup 2.
				'core/heading',
				'core/image',
				'core/heading',
				'core/paragraph',
				'core/paragraph',
				'core/shortcode', // Popup 3.
			],
			Newspack_Popups_Inserter::insert_popups_in_post_content(
				$post_content,
				self::$popups
			),
			'The popups are inserted into the content at expected positions when there are consecutive insertion-preventing blocks.'
		);
	}

	/**
	 * Insertion into classic (legacy) post content.
	 */
	public function test_insertion_into_classic_content() {
		$post_content = 'Paragraph 1
<h2>A heading</h2>
Paragraph 2
<blockquote>A quote</blockquote>';
		$popups       = [
			// A popup before any content.
			self::create_inline_popup( '0' ),
			// A popup that should not be inserted right after a heading.
			self::create_inline_popup( '30' ),
			// A popup after all content.
			self::create_inline_popup( '100' ),
		];
		self::assertEqualBlockNames(
			[
				'core/shortcode', // Popup 1.
				'core/html',
				'core/shortcode', // Popup 2.
				'core/heading',
				'core/html',
				'core/html',
				'core/shortcode', // Popup 3.
			],
			Newspack_Popups_Inserter::insert_popups_in_post_content(
				$post_content,
				$popups
			),
			'The popups are inserted into the content at expected positions.'
		);
	}
}

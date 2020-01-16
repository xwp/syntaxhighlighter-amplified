<?php
/**
 * SyntaxHighlighterAmplified
 *
 * @package SyntaxHighlighterAmplified
 *
 * @wordpress-plugin
 * Plugin Name: SyntaxHighlighter Amplified
 * Description: Extension plugin for SyntaxHighlighter Evolved which uses highlight.php to add syntax highlighting in AMP responses.
 * Author: Weston Ruter, XWP
 * Author URI: https://xwp.co/
 * Version: 0.1
 * License: GPLv2 or later
 */

namespace SyntaxHighlighterAmplified;

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Abort if composer dependencies have not been installed.
if ( ! class_exists( '\Highlight\Highlighter' ) ) {
	add_action( 'admin_notices', function() {
		?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'The scrivo/highlight.php dependency is missing. Run composer install inside the syntaxhighlighter-amplified plugin directory or add the plugin as a Composer dependency to your site.', 'syntaxhighlighter-amplified' ); ?></p>
		</div>
		<?php
	} );

	return;
}

/**
 * Remove frontend hooks for SyntaxHighlighter when on AMP frontend.
 *
 * @see \SyntaxHighlighter::__construct()
 */
function update_hooks_if_amp_endpoint() {
	global $SyntaxHighlighter;
	if ( empty( $SyntaxHighlighter ) || is_admin() || ! function_exists( 'is_amp_endpoint' ) || ! is_amp_endpoint() ) {
		return;
	}

	// Remove SyntaxHighlighter's JS and CSS, and enqueue our own styles instead.
	remove_action( 'wp_head', array( $SyntaxHighlighter, 'output_header_placeholder' ), 15 );
	remove_action( 'wp_footer', array( $SyntaxHighlighter, 'maybe_output_scripts' ), 15 );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_styles' );
	add_filter( 'syntaxhighlighter_cssclasses', __NAMESPACE__ . '\filter_syntaxhighlighter_cssclasses' );
}
add_action( 'template_redirect', __NAMESPACE__ . '\update_hooks_if_amp_endpoint' );

/**
 * Add class which is added by SyntaxHighlighter via JS and which the sanitizer uses to find the pre elements.
 *
 * @param array $classes Classes.
 * @return array Classes.
 */
function filter_syntaxhighlighter_cssclasses( $classes ) {
	return array_merge( $classes, array( 'syntaxhighlighter' ) );
}

/**
 * Enqueue styles.
 */
function enqueue_styles() {
	wp_enqueue_style( 'hjjs-default', plugin_dir_url( __FILE__ ) . 'vendor/scrivo/highlight.php/styles/default.css' );
}

/**
 * Filter sanitizers.
 *
 * @param array $sanitizers Sanitizers.
 * @return array Sanitizers.
 */
function filter_amp_content_sanitizers( $sanitizers ) {
	require_once __DIR__ . '/class-amp-sanitizer.php';
	return array_merge(
		array(
			__NAMESPACE__ . '\AMP_Sanitizer' => array(),
		),
		$sanitizers
	);
}
add_filter( 'amp_content_sanitizers', __NAMESPACE__ . '\filter_amp_content_sanitizers' );

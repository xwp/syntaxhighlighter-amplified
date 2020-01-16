<?php
/**
 * Class SyntaxHighlighter_AMP_Sanitizer
 *
 * @package SyntaxHighlighterAmplified
 */

namespace SyntaxHighlighterAmplified;

/**
 * Class SyntaxHighlighter_AMP_Sanitizer
 *
 * Collects inline styles and outputs them in the amp-custom stylesheet.
 */
class AMP_Sanitizer extends \AMP_Base_Sanitizer {

	/**
	 * Styles that have been added.
	 *
	 * @var array
	 */
	protected $printed_styles = array();

	/**
	 * Sanitize CSS styles within the HTML contained in this instance's DOMDocument.
	 */
	public function sanitize() {
		foreach ( $this->dom->getElementsByTagName( 'pre' ) as $pre ) {
			$this->process_element( $pre );
		}
	}

	/**
	 * Highlight contents of syntaxhighlighter pre elements.
	 *
	 * @todo Add support for configuration options where possible: https://en.support.wordpress.com/code/posting-source-code/#configuration-parameters
	 * @param \DOMElement $pre PRE element.
	 */
	public function process_element( \DOMElement $pre ) {
		$classes = $pre->getAttribute( 'class' );
		if ( false === strpos( $classes, 'syntaxhighlighter' ) ) {
			return;
		}

		$attrs = array();
		foreach ( explode( ';', $classes ) as $attr_pair ) {
			$attr_pair = explode( ':', $attr_pair, 2 );
			if ( 2 !== count( $attr_pair ) ) {
				continue;
			}
			$attrs[ trim( $attr_pair[0] ) ] = trim( $attr_pair[1] );
		}

		$group  = 'syntaxhighlighter-amped-v1';
		$code   = $pre->textContent;
		$key    = md5( $code );
		if ( wp_using_ext_object_cache() ) {
			$result = wp_cache_get( $key, $group );
		} else {
			$result = get_transient( $key . $group );
		}
		if ( $result instanceof \WP_Error ) {
			return;
		}
		if ( ! is_array( $result ) || ! isset( $result['value'] ) && ! isset( $result['language'] ) ) {
			$result = $this->highlight( $attrs, $code );
			if ( $result instanceof \Exception ) {
				$result = new \WP_Error( 'highlight_error', $result->getMessage() );
			}
			if ( wp_using_ext_object_cache() ) {
				wp_cache_set( $key, $result, $group );
			} else {
				set_transient( $key . $group, $result, MONTH_IN_SECONDS );
			}
		}

		if ( ! is_array( $result ) || ! isset( $result['value'] ) && ! isset( $result['language'] ) ) {
			return;
		}

		while ( $pre->firstChild ) {
			$pre->removeChild( $pre->firstChild );
		}
		$fragment = $this->dom->createDocumentFragment();
		$fragment->appendXML(
			trim( str_replace( "\r", '', $result['value'] ), "\r\n" ) // Normalize line breaks.
		);
		$code_element = $this->dom->createElement( 'code' );
		$code_element->appendChild( $fragment );
		$pre->appendChild( $code_element );

		$pre->setAttribute( 'class', implode( ' ', array( $classes, 'hljs', $result['language'] ) ) );
	}

	/**
	 * Highlight the provided code.
	 *
	 * @param array  $attrs Attributes.
	 * @param string $code  Code.
	 *
	 * @return array|\Exception Result or error.
	 */
	public function highlight( $attrs, $code ) {
		try {
			$highlighter = new \Highlight\Highlighter();
			
			$language    = null;
			if ( isset( $attrs['brush'] ) ) {
				switch ( $attrs['brush'] ) {
					case 'php':
						$language = 'php';
						break;
					case 'javascript':
					case 'js':
					case 'jsx':
						$language = 'javascript';
						break;
				}
			}

			if ( $language ) {
				$r = $highlighter->highlight( $language, $code );
			} else {
				$r = $highlighter->highlightAuto( $code );
			}

			return array(
				'value'    => $r->value,
				'language' => $r->language,
			);
		} catch ( \Exception $e ) {
			return $e;
		}
	}

}

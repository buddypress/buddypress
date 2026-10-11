<?php
/**
 * Keep WordPress's diagnostic expectations available across PHPUnit versions.
 *
 * @since 15.0.0
 */
trait BP_PHPUnit_Annotations {
	/**
	 * Retrieves annotations used by WordPress's expectDeprecated() method.
	 *
	 * PHPUnit 10 removed the annotation APIs used by WordPress's test framework.
	 * Its own metadata parser still handles groups and data providers; only the
	 * two WordPress diagnostic annotations need a compatibility implementation.
	 *
	 * @since 15.0.0
	 *
	 * @return array Class and method annotations.
	 */
	public function getAnnotations(): array {
		if ( method_exists( 'PHPUnit\\Framework\\TestCase', 'getAnnotations' ) ) {
			return parent::getAnnotations();
		}

		if ( method_exists( 'PHPUnit\\Util\\Test', 'parseTestMethodAnnotations' ) ) {
			return PHPUnit\Util\Test::parseTestMethodAnnotations( get_class( $this ), $this->getName( false ) );
		}

		$annotations = array( 'class' => array(), 'method' => array() );
		$class       = new ReflectionClass( $this );

		// Match the legacy parser: direct traits first, then the class DocBlock.
		foreach ( $class->getTraits() as $trait ) {
			$annotations['class'] = array_merge( $annotations['class'], $this->bp_parse_expected_annotations( $trait->getDocComment() ) );
		}

		$annotations['class'] = array_merge( $annotations['class'], $this->bp_parse_expected_annotations( $class->getDocComment() ) );

		$method                = new ReflectionMethod( $this, $this->name() );
		$annotations['method'] = $this->bp_parse_expected_annotations( $method->getDocComment() );

		return $annotations;
	}

	/**
	 * Parses WordPress's diagnostic annotations, including one-line DocBlocks.
	 *
	 * @since 15.0.0
	 *
	 * @param string|false $doc DocBlock returned by reflection.
	 * @return array Annotation names mapped to their values.
	 */
	private function bp_parse_expected_annotations( $doc ) {
		$annotations = array();
		$doc         = (string) substr( (string) $doc, 3, -2 );

		preg_match_all( '/@(expectedDeprecated|expectedIncorrectUsage)(?:[ \t]+(.*?))?[ \t]*\r?$/m', $doc, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$annotations[ $match[1] ][] = isset( $match[2] ) ? $match[2] : '';
		}

		return $annotations;
	}
}

<?php

namespace GFExcel\Transformer;

/**
 * Interface that makes a class {@see Transformer} aware.
 * @since 1.11.1
 */
interface TransformerAwareInterface {
	/**
	 * Sets the transformer on the current class.
	 *
	 * @param Transformer $transformer The transformer.
	 *
	 * @return void
	 */
	public function setTransformer( Transformer $transformer ): void;
}

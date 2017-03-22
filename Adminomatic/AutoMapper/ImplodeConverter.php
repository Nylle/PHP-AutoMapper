<?php

namespace Adminomatic\AutoMapper {

	class ImplodeConverter implements ITypeConverter {
		private $delimiter;
		
		/**
		 * Returns a new ImplodeConverter instance. If specified, the $delimiter will be placed between every element of the source array.
		 *
		 * @param string $delimiter 
		 */
		public function __construct($delimiter='') {
			$this->delimiter = $delimiter;
		}
		
		public function Convert($source) {
			return \implode($this->delimiter, $source);
		}
	}
}
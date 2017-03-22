<?php

namespace Adminomatic\AutoMapper {

	class Property {
		/** @var string */
		public $Name;
		/** @var string */
		public $Class;
		
		/**
		 * Creates a new Property instance frtom a fully specified property name.
		 * Example: 'FullNameSpace\ClassName::PropertyName'
		 *
		 * @param type $fullClassAndPropertyName 
		 */
		public function __construct($fullClassAndPropertyName) {
			$propertyValues = \explode('::', $fullClassAndPropertyName);
			$this->Class = $propertyValues[0];
			$this->Name = $propertyValues[1];
		}
	}

}
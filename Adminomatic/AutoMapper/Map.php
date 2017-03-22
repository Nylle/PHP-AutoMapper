<?php

namespace Adminomatic\AutoMapper {

	class Map {
		/** @var Property */
		public $ForMember;
		/** @var Property */
		public $FromMember;
		/** @var ITypeConverter */
		public $TypeConverter;
		/** @var IValueResolver */
		public $ValueResolver;
		
		public function __construct($forMember, $fromMember=null) {
			$this->ForMember = new Property($forMember);
			if($fromMember !== null) {
				$this->FromMember = new Property($fromMember);
			}
		}

		/**
		 * Adds the given fully qualified property name as source to this Map.
		 * Example: 'FullNameSpace\ClassName::PropertyName'
		 *
		 * @param string $source
		 * @return MappingResult
		 */
		public function FromMember($source) {
			$this->FromMember = new Property($source);
			return new MappingResult($this);
		}
		
		/**
		 * Specifies an IValueResolver for this Map.
		 *
		 * @param IValueResolver $resolver
		 * @return ResolutionResult
		 */
		public function ResolveUsing(IValueResolver $resolver) {
			$this->ValueResolver = $resolver;
			return new ResolutionResult($this);
		}
	}

}
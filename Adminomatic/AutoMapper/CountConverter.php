<?php

namespace Adminomatic\AutoMapper {

	class CountConverter implements ITypeConverter {
		public function Convert($source) {
			return \count($source);
		}
	}

}
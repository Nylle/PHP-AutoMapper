<?php

namespace Adminomatic\AutoMapper {

	class Mapper {
		
		private $maps = array();

		/**
		 * Creates a map for the specified properties.
		 * Example: 'FullNameSpace\ClassName::PropertyName'
		 *
		 * @param string $forMember
		 * @param string $fromMember 
		 */
		public function CreateMap($forMember, $fromMember)  {
			$this->maps[$forMember] = new Map($forMember, $fromMember);
		}
		
		/**
		 * Creates a map for the specified properties using the specified ITypeConverter.
		 * Example: 'FullNameSpace\ClassName::PropertyName'
		 *
		 * @param string $forMember
		 * @param string $fromMember
		 * @param ITypeConverter $typeConverter 
		 */
		public function CreateMapUsingConverter($forMember, $fromMember, ITypeConverter $typeConverter) {
			$this->maps[$forMember] = new Map($forMember, $fromMember);
			$this->maps[$forMember]->TypeConverter = $typeConverter;
		}
		
		/**
		 * Creates a map for the specified property using the specified IValueResolver.
		 * Example: 'FullNameSpace\ClassName::PropertyName'
		 *
		 * @param string $forMember
		 * @param IValueResolver $valueResolver 
		 */
		public function CreateMapUsingResolver($forMember, IValueResolver $valueResolver) {
			$this->maps[$forMember] = new Map($forMember);
			$this->maps[$forMember]->ValueResolver = $valueResolver;
		}
		
		/**
		 * Copies the values of all public properties of $source to the corresponding public properties of $destination 
		 * with the same name if existing and returns the destination object.
		 *
		 * @param object $destination
		 * @param object $source
		 * @return object
		 */
		public function Map($destination, $source) {
			if($source === null) {
				return null;
			}
						
			if(\is_object($destination)) {
				if(\get_class($destination) == '\ReflectionProperty') {
					return $this->MapProperty($destination, $source);
				}
				return $this->MapObject($destination, $source);
			}
			
			if(\is_array($destination)) {
				return $this->MapScalarArray($destination, $source);
			}
			
			if(\class_exists($destination)) {
				return $this->MapObject(new $destination, $source);
			}
			
			//$destination type is unknown and can't be resolved. We assume it is a scalar then...
			if(\is_scalar($source)) {
				return $source;
			}

			return null;
		}
		
		private function MapMapping(Map $map, \ReflectionProperty $destinationProperty, $source){
			if($map->FromMember !== null) {
				$sourceValue = $source->{$map->FromMember->Name};
				if($map->TypeConverter !== null) {
					return $map->TypeConverter->Convert($sourceValue);
				}
				return $this->MapProperty($destinationProperty, $sourceValue);

			}
			if($map->ValueResolver !== null) {
				return $map->ValueResolver->Resolve($source);
			}
		}
		
		private function MapObject($destination, $source) {
			if(!\is_object($source)) {
				return null;
			}
			
			if(\get_class($destination) == \get_class($source)) {
				return $source;
			}

			$destinationReflection = new \ReflectionClass($destination);
			foreach($destinationReflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $destinationProperty) {
				
				$map = null;
                if($this->TryGetMap($destinationProperty, $map)) {

                    if (! ($map->FromMember) || $map->FromMember->Class == $sourceType){
                        $destination->{$destinationProperty->name} = $this->MapMapping($map, $destinationProperty, $source);
                        continue;
                    }
                }else{

                    $srcProperty = null;
                    if(self::TryGetReflectionProperty($destinationProperty->name, $source, $srcProperty)) {
                        $destination->{$destinationProperty->name} = $this->MapProperty($destinationProperty, $srcProperty->getValue($source));
                        continue;
                    }
                }
			}
			return $destination;
		}
		
		private function MapObjectArray($destinationType, $source) {
			if(!\is_array($source)) {
				//TODO: Check for ITypeConverter
				return array();
			}
			
			$result = array();
			foreach($source as $element) {
				$result[] = $this->Map(new $destinationType, $element);
			}
			return $result;
		}
		
		private function MapProperty(\ReflectionProperty $destinationProperty, $source) {
			$destinationType = null;
			if(self::TryParseType($destinationProperty, $destinationType)) {
				if(\class_exists($destinationType)) {
					return $this->MapObject(new $destinationType, $source);
				}
				
				if(self::IsScalarArrayType($destinationType)) {
					return $this->MapScalarArray(array(), $source);
				}
				
				if(self::IsScalarType($destinationType)) {
					return $this->Map(0, $source);
				}
				
				$arrayType = null;
				if(self::TryGetObjectArrayClass($destinationType, $arrayType)) {
					if(\class_exists($arrayType)) {
						return $this->MapObjectArray($arrayType, $source);
					}
					if(self::IsScalarType($arrayType)) {
						return $this->MapScalarArray(array(), $source);
					}
				}
			}
			return null;
		}
		
		private function MapScalarArray(array $destination, $source) {
			if(!\is_array($source)) {
				return array();
			}
			
			$result = array();
			foreach($source as $key => $value) {
				$result[$key] = $value;
			}
			return $result;
		}

		private static function IsScalarArrayType($type) {
			if(\strtolower($type) == 'array') {
				return true;
			}
			return false;
		}
		
		private static function IsScalarType($type) {
			switch(\strtolower($type)) {
				case "int":
				case "integer":
				case "bool":
				case "boolean":
				case "float":
				case "double":
				case "real":
				case "string":
					return true;
				default: 
					return false;
			}
		}
		
		private function TryGetMap(\ReflectionProperty $destinationProperty, &$map) {
			$key = $destinationProperty->class . '::' . $destinationProperty->name;
			if(\array_key_exists($key, $this->maps)) {
				$map = $this->maps[$key];
				return true;
			}
			return false;
		}
		
		private static function TryGetObjectArrayClass($type, &$outType) {
			if(\substr($type, -2) === '[]') {
				$outType = \substr($type, 0, -2); 
				return true;
			}
			return false;
		}
		
		private static function TryGetReflectionProperty($propertyName, $sourceClass, &$outSourceReflectionProperty) {
			$sourceReflectionClass = new \ReflectionClass($sourceClass);
			foreach($sourceReflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $sourceReflectionProperty) {
				if($sourceReflectionProperty->name == $propertyName) {
					$outSourceReflectionProperty = $sourceReflectionProperty;
					return true;
				}
			}
			return false;
		}
		
		private static function TryParseType(\ReflectionProperty $property, &$outType) {
			$matches = array();
			$result = \preg_match('/@var\\s*([^\\s]*)/i', $property->getDocComment(), $matches);
			$outType = $matches[1];
			return $result;
		}
		
		
	}
}
?>

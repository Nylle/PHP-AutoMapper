PHP-AutoMapper
==============

PHP adaption of Jimmy Bogard's famous AutoMapper for .NET to automaticially map the properties of one object to another.

PHP is different
----------------

PHP is not type safe. And while type hinting for function arguments is supported for arrays, classes and interfaces, the return types however cannot be specified.

To ensure a safe mapping, PHP AutoMapper requires some conventions to be met.


Conventions
-----------

1. Only public properties will be mapped. Protected or private properties as well as methods, getters and setters will be ignored.
2. The target class' properties need to have proper [PHPDoc](http://en.wikipedia.org/wiki/Phpdoc) elements in the form: `/** @var type */`
3. Any scalar types (int, integer, bool, boolean, float, double, real, string) will be copied without further checking (see PHP's [type juggling](http://php.net/manual/en/language.types.type-juggling.php)).
4. The type `/** @var array */` is considered to be a simple array of scalar types thus they will be copied without further checking, while keys are preserved.
5. Object types require to consist of the full namespace and class name in the form: `/** @var Full\Name\Space\ClassName */`
6. For object arrays, the type needs to be the full class name including namespace followed by empty square brackets: `/** @var NameSpace\ClassName[] */`. Keys will not be preserved.

Usage
-----

By default all properties with the same name will be mapped from the source to the destination object. Scalars are beeing copied, objects will be mapped recursively.


### Map properties with different names ###

When creating a map, the target and source properties need to be fully qualified strings containing the class name including namespace as well as the property name separated by the scope resolution operator `::`. This way only properties of the specified class will be mapped, but similarly named properties in nested objects will be purposedly ignored. Therefore every target/source property pair with different name needs to be mapped for every expected class.

    $mapper = new \Adminomatic\AutoMapper\Mapper();
    $mapper->CreateMap('NameSpace\DestinationClass::MyProperty', 'NameSpace\SourceClass::DifferentlyNamedProperty');
    $mapper->CreateMap('NameSpace\SubClass::MyProperty', 'NameSpace\SourceSubClass::DifferentlyNamedProperty');
    $myDestinationObject = $mapper->Map(new \NameSpace\DestinationClass(), $sourceObject);

### Specify a custom type converter ###

An `ITypeConverter` can only be specified for a pair of mapped properties. Otherwise the converter would be used for all properties.

    $mapper = new \Adminomatic\AutoMapper\Mapper();
    $mapper->CreateMapUsingConverter('DestinationClass::MyProperty', 
                                     'SourceClass::DifferentlyNamedProperty', 
                                     new \Adminomatic\AutoMapper\ImplodeConverter());
    $myDestinationObject = $mapper->Map(new DestinationClass(), $sourceObject);

To use the same converter for multiple properties, it is necessary to create a map for every case.

    $myImplodeConverter = new \Adminomatic\AutoMapper\ImplodeConverter();
     
    $mapper = new \Adminomatic\AutoMapper\Mapper();
    $mapper->CreateMapUsingConverter('DestinationClass::MyProperty', 'SourceClass::DifferentlyNamedProperty', $myImplodeConverter);
    $mapper->CreateMapUsingConverter('DestinationClass::OtherProperty', 'SourceClass::AnotherProperty', $myImplodeConverter);
    $myDestinationObject = $mapper->Map(new DestinationClass(), $sourceObject);

Remember: Every property must be fully qualified by including the namespace and class name, followed by the scope resolution operator and the property name.

### Resolve the destination property value from multiple source values ###

Sometimes the expected value of the destination property is depending on multiple properties of the source object. Imagine you have a source class `PersonDTO` with the properties `PersonDTO->FirstName` and `PersonDTO->LastName` but you want to combine these in the target property `PersonModel->FullName`. Specify your implementation of the `IValueResolver` to handle these cases as you like. The value resolver will always get the parent object to use the properties from, thus no source property can be specified.

    $mapper = new \Adminomatic\AutoMapper\Mapper();
    $mapper->CreateMapUsingResolver('NameSpace\DestinationClass::MyProperty', new \CustomResolver());
    $myDestinationObject = $mapper->Map(new \NameSpace\DestinationClass(), $sourceObject);

Api
---

The PHP AutoMapper comes with interfaces for creating your own type converters and value resolvers.


### ITypeConverter ###

The `ITypeConverter::Convert()` method expects the value of the source property and returns the converted value for the destination property.

    namespace Adminomatic\AutoMapper {
        interface ITypeConverter {
            function Convert($source);
        }
    }

### Included type converters ###

There are two simple ready-to-use type converters included with PHP AutoMapper.


#### CountConverter ####

    namespace Adminomatic\AutoMapper {
        class CountConverter implements ITypeConverter {
            public function Convert($source) {
                return \count($source);
            }
        }
    }

The `CountConverter` expects an array as input and returns the number of elements.

A possible example is a blog post DTO with an array of comments and you want to convert the comment array to an integer value in the blog post model that simply shows you the number of comments for this post.


#### ImplodeConverter ####

    namespace Adminomatic\AutoMapper {
        class ImplodeConverter implements ITypeConverter {
            private $delimiter;
     
            public function __construct($delimiter='') {
                $this->delimiter = $delimiter;
            }
     
            public function Convert($source) {
                return \implode($this->delimiter, $source);
            }
        }
    }

The `ImplodeConverter` expects a scalar array as input and returns a concatenated string containing all elements and if specified separated by the given separator.

A possible example could be an array of categories or tags for a blog post, which should be mapped to a single string to be shown above or below the post.

### IValueResolver ###

The `IValueResolver::Resolve()` method expects the source object and returns the resolved value for the destination property.

    namespace Adminomatic\AutoMapper {
        interface IValueResolver {
            function Resolve($source);
        }
    }


### Example for value resolver (not included) ###

Sometimes the expected value of the destination property is depending on multiple properties of the source object. You may create any value resolver you need by implementing the `IValueResolver` interface.

    namespace MyNameSpace {
        class FullNameResolver implements \Adminomatic\AutoMapper\IValueResolver {
            function Resolve($source) {
                if(!\is_object($source) || \get_class($source) != 'MyNameSpace\MyExpectedClass') {
                    return null;
                }
                return $source->FirstName . ' ' . $source->LastName;
            }
        }
    }


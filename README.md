# TODO

## Unmarshal
- bench in CI

## Common
- See why deserialization is slow
- why not iterable?

- ISSUE -> 'Relation' class is not discovered when marshalling/unmarshalling...
- Test that the Relation test case is working (marshal and unmarshal, functionally)

- validate reflection factory

- maybe move hooks out from Marshaller? (no new anymore)

- determine which services and classes are internal

- move TemplateCacheWarmer into FrameworkBundle
- create config tree in FrameworkBundle
- create a marshaller.php in FrameworkBundle and remove the MarshallerExtension
- register the compiler pass in the MarshallerExtension

## Questions
- do we really phpstan? (we might implement it, reduced)
- maybe groups?
- maybe ignore?
- add extra data?

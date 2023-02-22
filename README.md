# TODO

## Unmarshal
- validate JSON
- eager/lazy mode
- JSON pointer
- DTOs instead of hashmaps
- improve perf of lazy mode
- test that the Relation test case is working (marshal and unmarshal, functionally)
- bench in CI

## Common
- validate reflection factory
- DSL instead of options?
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

# TODO

- determine which services are internal
- determine which classes are internal

## Marshal
- create dedicated exceptions and wrap native ones

## Unmarshal
- test that it never reaches the Internal namespace
- UTF-8 BOM
- Union selector attribute?
- tests (unmarshal from component)
- create dedicated exceptions and wrap native ones
- try catch property assignment (to not have TypeError)
- same for object construction (like in serializer)
- collect errors mode (optional) -> throw at the end with errors and decoded
  object
- bench in CI

## Questions
- do we really phpstan? (we might implement it, reduced)
- maybe groups?
- maybe ignore?
- add extra data?

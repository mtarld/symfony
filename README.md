# TODO

## Marshal
- micro optim to beat json_encode -> store syntax result in the template generator

## Unmarshal

## Common
- hook -> marshal/unmarshal
- determine which services and classes are internal
- move TemplateCacheWarmer into FrameworkBundle
- create config tree in FrameworkBundle
- create a marshaller.php in FrameworkBundle and remove the MarshallerExtension
- register the compiler pass in the MarshallerExtension
- format for Symfony (tests void for example)

## Questions
- do we really phpstan? (we might implement it, reduced)
- maybe groups?
- maybe ignore?
- add extra data?
- maybe JSON pointer?

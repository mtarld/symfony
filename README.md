# TODO

## Marshal
- create dedicated exceptions and wrap native ones
- native -> internal 
- find another name for OutputInterface/StreamOutput -> Resource? (conflict with Console)

## Unmarshal
- Input object -> Resource object instead
- if constructor -> newInstanceWithoutConstructor but set defaults
- else classic new instance

## Questions
- do we really phpstan? (we might implement it, reduced)
- maybe groups?
- maybe ignore?
- add extra data?

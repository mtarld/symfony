UPGRADE FROM 7.0 to 7.1
=======================

Cache
-----

 * Deprecate `CouchbaseBucketAdapter`, use `CouchbaseCollectionAdapter` instead

DoctrineBridge
--------------

 * The `DoctrineExtractor::getTypes` method is deprecated, use `DoctrineExtractor::getType` instead

FrameworkBundle
---------------

 * Mark classes `ConfigBuilderCacheWarmer`, `Router`, `SerializerCacheWarmer`, `TranslationsCacheWarmer`, `Translator` and `ValidatorCacheWarmer` as `final`

Messenger
---------

 * Make `#[AsMessageHandler]` final

PropertyInfo
------------

 * The `PropertyTypeExtractorInterface::getTypes` method is deprecated, use `PropertyTypeExtractorInterface::getType` instead

SecurityBundle
--------------

 * Mark class `ExpressionCacheWarmer` as `final`

Translation
-----------

 * Mark class `DataCollectorTranslator` as `final`

TwigBundle
----------

 * Mark class `TemplateCacheWarmer` as `final`

Workflow
--------

 * Add method `getEnabledTransition()` to `WorkflowInterface`

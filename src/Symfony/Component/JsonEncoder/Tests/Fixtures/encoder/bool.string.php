<?php

return static function (mixed $data, array $config): \Traversable {
    yield $data ? 'true' : 'false';
};

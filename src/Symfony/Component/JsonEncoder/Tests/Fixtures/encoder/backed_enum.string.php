<?php

return static function (mixed $data, array $config): \Traversable {
    yield \json_encode($data->value);
};

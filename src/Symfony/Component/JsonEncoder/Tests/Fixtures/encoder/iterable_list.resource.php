<?php

return static function (mixed $data, mixed $stream, array $config): void {
    \fwrite($stream, \json_encode($data));
};

<?php

namespace App\Serializer;

interface Serializable
{
    public function normalize(): iterable;
}

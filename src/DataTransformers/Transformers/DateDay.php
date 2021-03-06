<?php

namespace BadChoice\Reports\DataTransformers\Transformers;

use BadChoice\Reports\DataTransformers\TransformsValueInterface;
use Carbon\Carbon;

class DateDay implements TransformsValueInterface
{
    public function transform($value)
    {
        if (! $value) {
            return "--";
        }
        return Carbon::parse($value)->timezone(auth()->user()->timezone)->day;
    }
}

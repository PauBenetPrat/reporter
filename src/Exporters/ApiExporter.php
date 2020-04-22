<?php

namespace BadChoice\Reports\Exporters;

use BadChoice\Reports\Filters\Filters;
use BadChoice\Reports\Utils\QueryUrl;

class ApiExporter extends BaseExporter
{
    public $data;

    protected function init()
    {
    }

    protected function finalize()
    {
    }

    protected function generate()
    {
        $this->data = collect();
        $this->forEachRecord(function ($row) {
            $this->data->push($this->getExportFields()->map(function ($field) use ($row) {
                return $field->getValue($row);
           }));
        });
    }

    protected function getType()
    {
        return "api";
    }
}

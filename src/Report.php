<?php

namespace BadChoice\Reports;

use BadChoice\Reports\Filters\DefaultFilters;
use BadChoice\Reports\Exporters\Old\ExcelExporter;
use Carbon\Carbon;

abstract class Report{

    protected $filtersClass     = DefaultFilters::class;
    protected $exportColumns    = [];
    protected $exportTitles     = [];
    protected $totalize         = null;

    protected $exporter;

    protected $reportExporter  = null;

    public function __construct($filters = null) {
        $this->filters = $filters ? : new $this->filtersClass( request() );
    }

    /**
     * @param $parent_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public abstract function query($parent_id = null);

    public function get($parent_id = null){
        return $this->query($parent_id)->get();
    }

    public function paginate($count, $parent_id = null){
        return $this->query($parent_id)->paginate($count);
    }

    public function chunk($count, $callback, $parent_id = null){
        return $this->query($parent_id)->chunk($count, $callback);
    }

    public function first($parent_id = null){
        return $this->query($parent_id)->first();
    }

    public function totalize($key = 'all'){
        $this->totalize = $key;
        return $this;
    }

    public function addFilter($key, $value = null){
        $this->filters->addFilter($key, $value);
        return $this;
    }

    /**
     * @param string $start
     * @param string null $end
     * @return $this
     */
    public function forDates($start, $end = null){
        if($start instanceof Carbon)        $start = $start->toDateString();
        if($end && $end instanceof Carbon)  $end = $end->toDateString();

        $this->filters->addFilter("start_date", $start);
        $this->filters->addFilter("end_date", $end ? : Carbon::parse($start)->addDay()->toDateString() );
        return $this;
    }

    public function getFilters( $parent_id = null ){
        if( $this->totalize ) $this->filters->addFilter("totalize", $this->totalize);
        return $this->filters;
    }

    public function getFilter($key){
        return $this->filters->filters()[$key] ?? null;
    }

    /**
     * @param $exporter ReportExporter
     * @return $this
     */
    public function setExporter($exporter){
        $this->exporter = $exporter;
        return $this;
    }

    public function download($parent_id = null){
        if( ! $this->exporter) $this->exporter = new ExcelExporter();
        return $this->exporter->set(
            $this->query( $parent_id ),
            $this->exportFields,
            array_merge( $this->getTransformDates(), $this->getTransformations() ))
            ->download( $this->getExportName() );
    }

    public function getExportName(){
        $className = rtrim(collect(explode("\\",get_class($this)))->last(),"Report");
        return $className . "-" . $this->filters->filters()["start_date"] . '-' . $this->filters->filters()["end_date"];
    }

    public function export($type = 'xls'){
        if($type == 'xls')          return (new $this->reportExporter)->toXls( $this->query(), $this->getExportName() );
        else if($type == 'html')    return (new $this->reportExporter)->toHtml( $this->query()->paginate(50) );
        else if($type == 'fake')    return (new $this->reportExporter($this->getFilters()))->toFake( $this->query()->get() );
        return (new $this->reportExporter)->toCsv( $this->query(), $this->getExportName() );
    }

    public function getTransformations(){
        return [];
    }

    protected function getTransformDates(){
        return [
            "created_at"    => function($value){ return $this->datetimeTransform($value);},
            "opened"        => function($value){ return $this->datetimeTransform($value);},
            "closed"        => function($value){ return $this->datetimeTransform($value);},
            "canceled"      => function($value){ return $this->datetimeTransform($value);},
            "order.opened"  => function($value){ return $this->datetimeTransform($value);},
            "order.closed"  => function($value){ return $this->datetimeTransform($value);},
        ];
    }

    private function datetimeTransform($value){
        return Carbon::parse($value)->timezone( auth()->user()->timezone)->toDatetimeString();
    }
}
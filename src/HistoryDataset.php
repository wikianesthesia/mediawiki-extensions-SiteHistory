<?php

namespace SiteHistory;

use SiteHistory\HistoryVariables\AbstractHistoryVariable;

class HistoryDataset {
    protected $data = [];
    protected $dataSpecification;

    /**
     * @var AbstractHistoryVariable[]
     */
    protected $dataVariables = [];
    protected $datetimeData;
    protected $hasRun = false;

    public function __construct( array $parameters = [] ) {
        $this->dataSpecification = new DataSpecification( $parameters );
    }

    public function addVariable( string $historyVariableClass, array $parameters = [], string $label = '' ) {
        $this->dataVariables[] = new $historyVariableClass( clone $this->dataSpecification, $parameters, $label );
    }

    public function getData(): array {
        if( !$this->hasRun ) {
            $this->data[] = array_merge(
                [ 'Datetime' ],
                $this->getDatetimeData()
            );

            foreach( $this->dataVariables as $dataVariable ) {
                $this->data[] = array_merge(
                    [ $dataVariable->getLabel() ],
                    $dataVariable->getData()
                );
            }

            $this->hasRun = true;
        }

        return $this->data;
    }

    public function getDataCsv(): string {
        $data = $this->getData();
        $csv = '';

        $fp = fopen( 'php://temp', 'r+' );

        foreach( $data as $varData ) {
            fputcsv( $fp, $varData );
        }

        rewind( $fp );

        while( !feof( $fp ) ) {
            $csv .= fgets( $fp );
        }

        fclose( $fp );

        return $csv;
    }

    public function getDataSpecification(): DataSpecification {
        return $this->dataSpecification;
    }

    public function getDatetimeData(): array {
        if( $this->datetimeData === null ) {
            $dataSpecification = $this->getDataSpecification();

            $datetimes = range(
                0,
                ceil( ( $dataSpecification->get( 'endTime' ) - $dataSpecification->get( 'startTime' ) ) / $dataSpecification->get( 'stepTime' ) ) - 1
            );

            $this->datetimeData = array_map( function( int $index ) use ( $dataSpecification ) {
                $dateFormat = $dataSpecification->get( 'stepTime' ) >= strtotime( '1 day', 0 ) ?
                    'Y-m-d' : 'Y-m-d H:i:s';
                $indexTime = $dataSpecification->get( 'startTime' ) + $index * $dataSpecification->get( 'stepTime' );
                return date( $dateFormat, $indexTime );
            }, $datetimes );
        }

        return $this->datetimeData;
    }
}
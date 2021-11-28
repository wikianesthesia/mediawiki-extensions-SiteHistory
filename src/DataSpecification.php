<?php

namespace SiteHistory;

use MWTimestamp;

class DataSpecification {
    protected $parameters = [];

    public static function getDefaultParameters(): array {
        return [
            'startTime' => SiteHistory::getInstallationTime(),
            'endTime' => (int) MWTimestamp::now( TS_UNIX ),
            'stepTime' => strtotime( '1 day', 0 )
        ];
    }

    public function __construct( array $parameters = [] ) {
        $this->addParameters( static::getDefaultParameters() );
        $this->addParameters( $parameters );
    }

    public function addParameters( array $parameters = [] ) {
        $this->parameters = array_merge( $this->parameters, $parameters );
    }

    public function getParameters(): array {
        return $this->parameters;
    }

    public function get( $parameter ) {
        return $this->parameters[ $parameter ] ?? null;
    }

    public function set( $parameter, $value = null ): bool {
        if( !isset( $this->parameters[ $parameter ] ) ) {
            return false;
        }

        $this->parameters[ $parameter ] = $value;

        return true;
    }
}
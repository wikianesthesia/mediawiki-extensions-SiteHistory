<?php

namespace SiteHistory;

use MediaWiki\MediaWikiServices;
use MWTimestamp;

class SiteHistory {

    protected static $installationTime = null;

    public static function getDataIndex( DataSpecification $dataSpecification, int $indexTime ): int {
        return floor( ( $indexTime - $dataSpecification->get( 'startTime' ) ) / $dataSpecification->get( 'stepTime' ) );
    }

    public static function getHistoryVariables(): array {
        $historyVariables = [];

        // Hooks::run( 'SiteHistoryGetHistoryVariables', $historyVariables );

        $historyVariables = [
            'SiteHistory\\HistoryVariables\\UsersRegistered',
            'SiteHistory\\HistoryVariables\\UsersConfirmed'
        ];

        return $historyVariables;
    }

    public static function getInstallationTime(): int {
        if( !static::$installationTime ) {
            $db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnectionRef( DB_REPLICA );
            $res = $db->select(
                'user',
                'MIN(user_registration) as first_registration'
            );
            $row = $db->fetchObject( $res );

            $installationTimestamp = new MWTimestamp( $row->first_registration );

            // Round to midnight
            static::$installationTime = strtotime( date('Y-m-d', $installationTimestamp->getTimestamp() ) );
        }

        return static::$installationTime;
    }

    public static function getIntegral( array $data ): array {
        $integralData = [];

        $count = 0;
        foreach( $data as $index => $value ) {
            $count += $value;
            $integralData[ $index ] = $count;
        }

        return $integralData;
    }

    public static function initializeData( DataSpecification $dataSpecification ): array {
        return array_fill( 0, ceil( ( $dataSpecification->get( 'endTime' ) - $dataSpecification->get( 'startTime' ) ) / $dataSpecification->get( 'stepTime' ) ), 0 );
    }
}
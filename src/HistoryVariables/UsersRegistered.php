<?php

namespace SiteHistory\HistoryVariables;

use MWTimestamp;
use SiteHistory\SiteHistory;

class UsersRegistered extends AbstractHistoryVariable {
    protected function run() {
        $dataSpecification = $this->getDataSpecification();
        $db = $this->getDB();

        $res = $db->select(
            'user',
            'user_registration',
            [
                'user_registration >= ' . $db->addQuotes( MWTimestamp::convert( TS_MW, $dataSpecification->get( 'startTime' ) ) ),
                'user_registration <= ' . $db->addQuotes( MWTimestamp::convert( TS_MW, $dataSpecification->get( 'endTime' ) ) ),
                'user_id NOT IN ' .
                $db->buildSelectSubquery( 'user_groups', 'ug_user', [
                    'ug_group' => 'bot',
                ], __METHOD__ )
            ],
            __METHOD__,
            [ 'ORDER BY' => 'user_registration ASC' ]
        );

        foreach( $res as $row ) {
            $rowTime = ( new MWTimestamp( $row->user_registration ) )->getTimestamp();
            $dataIndex = $this->getDataIndex( $rowTime );
            $this->data[ $dataIndex ]++;
        }

        $this->data = SiteHistory::getIntegral( $this->data );
    }
}
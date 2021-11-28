<?php

namespace SiteHistory\HistoryVariables;

use MWTimestamp;
use SiteHistory\SiteHistory;

class UsersConfirmed extends AbstractHistoryVariable {
    protected function run() {
        $dataSpecification = $this->getDataSpecification();
        $db = static::getDB();

        $res = $db->select(
            'user',
            'user_email_authenticated',
            [
                'user_email_authenticated IS NOT NULL',
                'user_email_authenticated >= ' . $db->addQuotes( MWTimestamp::convert( TS_MW, $dataSpecification->get( 'startTime' ) ) ),
                'user_email_authenticated <= ' . $db->addQuotes( MWTimestamp::convert( TS_MW, $dataSpecification->get( 'endTime' ) ) ),
            ],
            __METHOD__,
            [ 'ORDER BY' => 'user_email_authenticated ASC' ]
        );

        foreach( $res as $row ) {
            $rowTime = ( new MWTimestamp( $row->user_email_authenticated ) )->getTimestamp();
            $dataIndex = $this->getDataIndex( $rowTime );
            $this->data[ $dataIndex ]++;
        }

        $this->data = SiteHistory::getIntegral( $this->data );
    }
}
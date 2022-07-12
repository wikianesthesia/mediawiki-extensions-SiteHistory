<?php

namespace SiteHistory\HistoryVariables;

use MWTimestamp;
use SiteHistory\SiteHistory;

class PracticeGroups extends AbstractHistoryVariable {
    protected function run() {
        $dataSpecification = $this->getDataSpecification();
        $db = $this->getDB();

        $res = $db->select(
            'practicegroups',
            'registration',
            [
                'registration >= ' . $db->addQuotes( $dataSpecification->get( 'startTime' ) ),
                'registration <= ' . $db->addQuotes( $dataSpecification->get( 'endTime' ) )
            ],
            __METHOD__,
            [ 'ORDER BY' => 'registration ASC' ]
        );

        foreach( $res as $row ) {
            $rowTime = $row->registration;
            $dataIndex = $this->getDataIndex( $rowTime );
            $this->data[ $dataIndex ]++;
        }

        $this->data = SiteHistory::getIntegral( $this->data );
    }
}
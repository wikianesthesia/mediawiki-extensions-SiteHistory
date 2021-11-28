<?php

namespace SiteHistory\HistoryVariables;

use MWTimestamp;
use SiteHistory\DataSpecification;
use SiteHistory\SiteHistory;

/**
 * This calculates the history of page creation events on the wiki. Importantly, this data may not match the active
 * number of pages on the wiki since it does not adjust for deleted pages (see the NumPagesAll HistoryVariable).
 */
class Edits extends AbstractHistoryVariable {
    protected function run() {
        $dataSpecification = $this->getDataSpecification();
        $db = static::getDB();

        $includedNamespaces = $dataSpecification->get( 'namespaces' ) ?
            $db->makeList( $dataSpecification->get( 'namespaces' ) ) :
            null;

        $tables = [ 'revision' ];

        // Get all visible edits from the revision table
        $conds = [
            'rev_timestamp >= ' . $db->addQuotes( MWTimestamp::convert( TS_MW, $dataSpecification->get( 'startTime' ) ) ),
            'rev_timestamp <= ' . $db->addQuotes( MWTimestamp::convert( TS_MW, $dataSpecification->get( 'endTime' ) ) ),
        ];

        $join_conds = null;

        if( $includedNamespaces !== null ) {
            $tables[] = 'page';
            $conds[] = 'page_namespace IN (' . $includedNamespaces . ')';
            $join_conds[ 'page' ] = [
                'JOIN',
                'rev_page = page_id'
            ];
        }

        $res = $db->select(
            $tables,
            'rev_timestamp',
            $conds,
            __METHOD__,
            [ 'ORDER BY' => 'rev_timestamp ASC' ],
            $join_conds
        );

        foreach( $res as $row ) {
            $rowTime = ( new MWTimestamp( $row->rev_timestamp ) )->getTimestamp();
            $dataIndex = $this->getDataIndex( $rowTime );
            $this->data[ $dataIndex ]++;
        }

        // Get all deleted edits from the revision table
        $conds = [
            'ar_timestamp >= ' . $db->addQuotes( MWTimestamp::convert( TS_MW, $dataSpecification->get( 'startTime' ) ) ),
            'ar_timestamp <= ' . $db->addQuotes( MWTimestamp::convert( TS_MW, $dataSpecification->get( 'endTime' ) ) ),
        ];

        if( $includedNamespaces !== null ) {
            $conds[] = 'ar_namespace IN (' . $includedNamespaces . ')';
        }

        $res = $db->select(
            'archive',
            'ar_timestamp',
            $conds,
            __METHOD__,
            [ 'ORDER BY' => 'ar_timestamp ASC' ]
        );

        foreach( $res as $row ) {
            $rowTime = ( new MWTimestamp( $row->ar_timestamp ) )->getTimestamp();
            $dataIndex = $this->getDataIndex( $rowTime );
            $this->data[ $dataIndex ]++;
        }

        $this->data = SiteHistory::getIntegral( $this->data );
    }

    protected function getAdditionalDataSpecificationParameters(): array {
        // TODO implement options/select multiple for spec parameter
        return [
            'namespaces' => []
        ];
    }
}
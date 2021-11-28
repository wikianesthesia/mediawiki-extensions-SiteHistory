<?php

namespace SiteHistory\HistoryVariables;

use MWTimestamp;
use SiteHistory\DataSpecification;
use SiteHistory\SiteHistory;

/**
 * Getting an accurate page count is more complicated than it may seem. Page creations are not stored in the logging
 * table, but instead exist in the page and archive tables with parent_id = 0. Page deletions, merges, moves, and restorations
 * are stored in the logging table. This creates a lot of conditions for which the page count must be adjusted.
 */
class Pages extends AbstractHistoryVariable {

    protected function run() {
        $dataSpecification = $this->getDataSpecification();

        $preexistingPageCount = 0;

        $installationTime = SiteHistory::getInstallationTime();

        // If the data specification does not start with the wiki installation time, we need to determine
        // the number of pages that existed when the timeframe of interest started
        if( $dataSpecification->get( 'startTime' ) > $installationTime ) {
            // Create a new data specification with the starting at the wiki installation and ending at the start time
            // of the time period of interest
            $preexistingPagesDataSpecification = clone $dataSpecification;
            $preexistingPagesDataSpecification->set( 'startTime', $installationTime );
            $preexistingPagesDataSpecification->set( 'endTime', $dataSpecification->get( 'startTime' ) - 1 );

            $preexistingPageData = $this->getPagesData( $preexistingPagesDataSpecification );
            $preexistingPageCount = $preexistingPageData[ count( $preexistingPageData ) - 1 ];
        }

        $data = $this->getPagesData( $dataSpecification );

        array_walk( $data, function( &$value ) use ( $preexistingPageCount ) {
            $value += $preexistingPageCount;
        } );

        $this->data = $data;
    }

    protected function getAdditionalDataSpecificationParameters(): array {
        return [
            'namespaces' => []
        ];
    }


    /**
     * This function takes a DataSpecification as an argument to allow several time epochs to be analyzed independent of
     * the variable's main DataSpecification. This is necessary to get an accurate page count, as the number of existing
     * pages prior to the primary epoch of interest must be determined in addition to the pages during the epoch of interest.
     * This function will return integrated data.
     *
     * @param DataSpecification $dataSpecification
     * @return array
     */
    protected function getPagesData( DataSpecification $dataSpecification ): array {
        // Start with the number of pages created during the epoch of interest
        $pagesCreated = new PagesCreated( $dataSpecification );

        $data = $pagesCreated->getData();

        // To determine the actual number of pages during this period, we need to check the logging table for deletions
        // and restorations.

        $db = $this->getDB();

        $includedNamespaces = $dataSpecification->get( 'namespaces' ) ?
            $db->makeList( $dataSpecification->get( 'namespaces' ) ) :
            null;


        $res = $db->select(
            'logging',
            [ 'log_type', 'log_action', 'log_timestamp', 'log_namespace', 'log_params' ],
            $conds,
            __METHOD__,
            [ 'ORDER BY' => 'log_timestamp ASC' ],
        );










        $tables = [ 'revision' ];

        // Search the revision table for the creation time of existing pages
        // using rev_parent_id = 0
        $conds = [
            'rev_parent_id = 0',
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
            $dataIndex = SiteHistory::getDataIndex( $dataSpecification, $rowTime );
            $data[ $dataIndex ]++;
        }



        return $data;
    }
}
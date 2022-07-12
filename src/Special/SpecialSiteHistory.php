<?php

namespace SiteHistory\Special;

use MediaWiki\MediaWikiServices;
use MWTimestamp;
use OOUI;
use SiteHistory\HistoryVariables\Edits;
use SiteHistory\HistoryVariables\PagesCreated;
use SiteHistory\HistoryVariables\PracticeGroups;
use SiteHistory\HistoryVariables\UsersConfirmed;
use SiteHistory\HistoryVariables\UsersRegistered;
use SiteHistory\HistoryDataset;
use SpecialPage;

class SpecialSiteHistory extends SpecialPage {

    public function __construct() {
        parent::__construct( 'SiteHistory' );
    }

    public function execute( $subPage ) {
        global $wgSiteHistoryRequireRight;

        $out = $this->getOutput();
        $user = $this->getUser();

        $permissionManager = MediaWikiServices::getInstance()->getPermissionManager();

        if( $wgSiteHistoryRequireRight && !$permissionManager->userHasRight( $user, 'sitehistory' ) ) {
            $out->addHTML( wfMessage( 'sitehistory-permissiondenied' )->escaped() );

            return;
        }

        $request = $this->getRequest();

        if( $request->getBool( 'csv' ) ) {
            $this->exportCsv();
            return;
        }

        $out->enableOOUI();

        $out->setDisplayTitle( 'Site history' );

        return;

        $out->addHTML( new OOUI\FormLayout( [
            'method' => 'POST',
            'items' => [
                new OOUI\FieldsetLayout( [
                    'label' => 'Select data to include',
                    'items' => [
                        new OOUI\FieldLayout(
                            new OOUI\CheckboxInputWidget( [
                                'name' => 'users',
                                'selected' => true,
                            ] ),
                            [
                                'label' => 'Users',
                                'align' => 'inline',
                            ]
                        ),
                        new OOUI\FieldLayout(
                            new OOUI\ButtonInputWidget( [
                                'label' => 'Generate report',
                                'type' => 'submit',
                                'flags' => [ 'primary', 'progressive' ],
                                'icon' => 'specialPages',
                            ] ),
                            [
                                'label' => null,
                                'align' => 'top',
                            ]
                        ),
                    ]
                ] )
            ]
        ] ) );
    }

    public function exportCsv() {
        global $wgSitename;

        $this->getOutput()->disable();

        $historyDataset = New HistoryDataset();

        $historyDataset->addVariable( UsersRegistered::class );
        $historyDataset->addVariable( UsersConfirmed::class );
        $historyDataset->addVariable( PracticeGroups::class, [], 'Practice groups' );
        $historyDataset->addVariable( PagesCreated::class, [], 'Pages created (all)' );
        $historyDataset->addVariable( PagesCreated::class, [ 'namespaces' => [ 0 ] ], 'Pages created (public)' );
        $historyDataset->addVariable( PagesCreated::class, [ 'namespaces' => [ 7740 ] ], 'Pages created (practice group)' );
        $historyDataset->addVariable( Edits::class, [], 'Edits (all)'  );
        $historyDataset->addVariable( Edits::class, [ 'namespaces' => [ 0 ] ], 'Edits (public)'  );
        $historyDataset->addVariable( Edits::class, [ 'namespaces' => [ 7740 ] ], 'Edits (practice group)' );

        $csv = $historyDataset->getDataCsv();

        $filename = 'sitehistory-' . $wgSitename . '-' . MWTimestamp::now() . '.csv';

        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        echo( $csv );
    }

    protected function getAllHistoryVariables() {

    }

    protected function getGroupName() {
        return 'wiki';
    }
}
<?php

namespace SiteHistory\HistoryVariables;

use MediaWiki\MediaWikiServices;
use SiteHistory\SiteHistory;
use SiteHistory\DataSpecification;
use Wikimedia\Rdbms\DBConnRef;

abstract class AbstractHistoryVariable {
    protected $data;
    protected $dataSpecification;
    protected $description;
    protected $hasRun = false;
    protected $id;
    protected $label;

    public function __construct( DataSpecification $dataSpecification = null, array $parameters = [], string $label = '' ) {
        $this->dataSpecification = $dataSpecification ?: $this->getDataSpecification();
        $this->dataSpecification->addParameters( $parameters );
        $this->label = $label ?: null;
    }

    public function getData() {
        if( !$this->hasRun ) {
            $this->initializeData();
            $this->run();
            $this->hasRun = true;
        }

        return $this->data;
    }

    public function getDataSpecification(): DataSpecification {
        if( $this->dataSpecification === null ) {
            $this->dataSpecification = new DataSpecification( $this->getAdditionalDataSpecificationParameters() );
        }

        return $this->dataSpecification;
    }

    public function getDescription(): string {
        if( $this->description === null ) {
            $msg = wfMessage( 'sitehistory-historyvariable-' . strtolower( static::getId() ) . '-desc' );

            $this->description = $msg->exists() ? $msg->text() : '';
        }

        return $this->description;
    }

    public function getId(): string {
        if( $this->id === null ) {
            $this->id = substr( strrchr( static::class, '\\' ), 1 );
        }

        return $this->id;
    }

    public function getLabel(): string {
        if( $this->label === null ) {
            $msg = wfMessage( 'sitehistory-historyvariable-' . strtolower( $this->getId() ) . '-label' );
            $this->label = $msg->exists() ? $msg->text() : $this->getId();
        }

        return $this->label;
    }

    abstract protected function run();

    protected function getAdditionalDataSpecificationParameters(): array {
        return [];
    }

    protected function getDB(): DBConnRef {
        return MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnectionRef( DB_REPLICA );
    }

    protected function getDataIndex( int $indexTime ): int {
        return SiteHistory::getDataIndex( $this->getDataSpecification(), $indexTime );
    }

    protected function initializeData() {
        $this->data = SiteHistory::initializeData( $this->getDataSpecification() );
    }
}
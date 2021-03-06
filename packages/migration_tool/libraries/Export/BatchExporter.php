<?php

class MigrationBatchExporter
{
    protected $batch;
    protected $parsed = false;
    protected $x;

    public function __construct(MigrationBatch $batch)
    {
        $this->batch = $batch;
    }

    protected function parse()
    {
        Loader::library('content/exporter');
        $this->x = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><concrete5-cif></concrete5-cif>');
        $this->x->addAttribute('version', '1.0');

        foreach ($this->batch->getObjectCollections() as $collection) {
            $type = $collection->getItemTypeObject();
            $type->exportCollection($collection, $this->x);
        }
    }

    public function getContentXML()
    {
        return $this->getExporter()->asXML();
    }

    public function getExporter()
    {
        if (!$this->parsed) {
            $this->parse();
        }
        return $this->x;
    }

    /**
     * Loops through all pages and returns files referenced.
     */
    public function getReferencedFiles()
    {
        if (!$this->parsed) {
            $this->parse();
        }

        $regExp = '/\{ccm:export:file:(.*?)\}|\{ccm:export:image:(.*?)\}/i';
        $items = array();
        if (preg_match_all(
            $regExp,
            $this->getContentXML(),
            $matches
        )
        ) {
            if (count($matches)) {
                for ($i = 1; $i < count($matches); ++$i) {
                    $results = $matches[$i];
                    foreach ($results as $reference) {
                        if ($reference) {
                            $items[] = $reference;
                        }
                    }
                }
            }
        }
        $files = array();
        $db = Loader::db();
        foreach ($items as $item) {
            $db = Loader::db();
            $fID = $db->GetOne('select fID from FileVersions where fvFilename = ?', array($item));
            if ($fID) {
                $f = File::getByID($fID);
                if (is_object($f) && !$f->isError()) {
                    $files[] = $f;
                }
            }
        }

        return $files;
    }
}

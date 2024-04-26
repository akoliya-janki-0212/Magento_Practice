<?php
class Ccc_Report_Model_Report extends Mage_Core_Model_Abstract
{
    protected $_tempColumns = null;
    protected function _construct()
    {
        $this->_init('ccc_report/report');
    }
    public function prepareQuery($data)
    {
        $query = "SELECT ";
        $columns = [];
        $defaultCol = [];
        $joins = [];
        $mainTable = '';
        $hasjointag = false;
        foreach ($data as $_tableName => $_columns) {
            foreach ($_columns as $_column => $_params) {
                $columns[$_column] = "{$_tableName}.{$_column} AS $_column";
                foreach ($_params as $_param => $_value) {
                    if ($_param == 'label') {
                        Mage::helper('report/report')->setLabel($_column, (string) $_value);
                    }
                    if ($_param == 'join') {
                        $join = $_value;
                        $joins[] = "LEFT JOIN {$_tableName} ON {$join}";
                        $hasjointag = true;
                    }
                    if ($_param == 'default' && $_value == 'true') {
                        Mage::helper('report/report')->setDefault($_column);
                        $defaultCol[] = "{$_tableName}.{$_column} AS $_column";
                    }
                }
                if (!$hasjointag) {
                    $mainTable = $_tableName;
                }
            }
        }

        if ($this->_tempColumns !== null) {
            $query .= $columns[$this->_tempColumns];
        } else {
            $query .= implode(", ", $defaultCol);
        }
        $query .= " FROM " . $mainTable;
        if (!empty($joins)) {
            $query .= ' ' . implode(' ', $joins);
        }
        return $query;
    }
    public function getConnection($query)
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_setup');
        return $connection->fetchAll($query);

    }
    public function getTempColumns()
    {
        return $this->_tempColumns;
    }
    public function setTempColumns($tempColumn)
    {
        $this->_tempColumns = $tempColumn;
    }
}

?>
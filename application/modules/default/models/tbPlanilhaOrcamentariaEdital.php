<?php

class tbPlanilhaOrcamentariaEdital extends GenericModel {

    protected $_banco   = "SAC";
    protected $_schema  = "dbo";
    protected $_name    = 'tbPlanilhaOrcamentariaEdital';


    public function buscarPlanilhaIdCategoria($idCategoria){
       $select = $this->select();
       $select->setIntegrityCheck(false);
       $select->where('idCategoria = ?', $idCategoria);
       return $this->fetchRow($select);
    }
    
}
?>

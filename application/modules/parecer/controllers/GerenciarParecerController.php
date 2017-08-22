<?php

class Parecer_GerenciarParecerController extends MinC_Controller_Action_Abstract implements MinC_Assinatura_Controller_IDocumentoAssinaturaController
{
    private $idPronac;
    private $intTamPag = 10;
    
    const ID_TIPO_AGENTE_PARCERISTA = 1;
    
    private function validarPerfis() {
        $PermissoesGrupo = array();
        $PermissoesGrupo[] = Autenticacao_Model_Grupos::COORDENADOR_DE_PARECERISTA;
        $PermissoesGrupo[] = Autenticacao_Model_Grupos::PRESIDENTE_DE_VINCULADA;
        $PermissoesGrupo[] = Autenticacao_Model_Grupos::SUPERINTENDENTE_DE_VINCULADA;
        
        isset($this->auth->getIdentity()->usu_codigo) ? parent::perfil(1, $PermissoesGrupo) : parent::perfil(4, $PermissoesGrupo);
    }

    public function init()
    {
        parent::perfil();
        parent::init();
        $this->auth = Zend_Auth::getInstance();
        $this->grupoAtivo = new Zend_Session_Namespace('GrupoAtivo');
        $this->idTipoDoAtoAdministrativo = Assinatura_Model_DbTable_TbAssinatura::TIPO_ATO_ANALISE_INICIAL;
        
        $this->validarPerfis();
    }

    public function gerenciarAssinaturasAction()
    {
        switch ($this->grupoAtivo->codGrupo) {
        case Autenticacao_Model_Grupos::PRESIDENTE_DE_VINCULADA:
            $this->redirect("/{$this->moduleName}/gerenciar-parecer/finalizar-parecer");
            break;
        case Autenticacao_Model_Grupos::COORDENADOR_DE_PARECERISTA:
            $this->redirect("/{$this->moduleName}/gerenciar-parecer/index?tipoFiltro=validados");
            break;
        }       
    }

    public function encaminharAssinaturaAction()
    {
    }

    function obterServicoDocumentoAssinatura()
    {
    }
    
    public function indexAction()
    {
        $idusuario = $this->auth->getIdentity()->usu_codigo;
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo');
        $codOrgao = $GrupoAtivo->codOrgao;
        $this->view->codOrgao = $codOrgao;
        $this->view->idUsuarioLogado = $idusuario;
        
        $objTbAtoAdministrativo = new Assinatura_Model_DbTable_TbAtoAdministrativo();
        $this->view->quantidadeMinimaAssinaturas = $objTbAtoAdministrativo->obterQuantidadeMinimaAssinaturas(
            $this->idTipoDoAtoAdministrativo,
            $this->auth->getIdentity()->usu_org_max_superior
        );
        $this->view->idTipoDoAtoAdministrativo = $this->idTipoDoAtoAdministrativo;
        $this->view->idPerfilDoAssinante = $GrupoAtivo->codGrupo;
        
        //DEFINE PARAMETROS DE ORDENACAO / QTDE. REG POR PAG. / PAGINACAO
        if($this->_request->getParam("qtde")) {
            $this->intTamPag = $this->_request->getParam("qtde");
        }
        $order = array();

        if ($this->_request->getParam("ordem")) {
            $ordem = $this->_request->getParam("ordem");
            if ($ordem == "ASC") {
                $novaOrdem = "DESC";
            } else {
                $novaOrdem = "ASC";
            }
        } else {
            $ordem = "ASC";
            $novaOrdem = "ASC";
        }
        
        if ($this->_request->getParam("campo")) {
            $campo = $this->_request->getParam("campo");
            $order = array($campo . " " . $ordem);
            $ordenacao = "&campo=" . $campo . "&ordem=" . $ordem;

        } else {
            $campo = null;
            $order = array('DtEnvioMincVinculada', 'NomeProjeto', 'stPrincipal desc');
            $ordenacao = null;
        }

        $pag = 1;
        $get = Zend_Registry::get('get');
        if (isset($get->pag)) $pag = $get->pag;
        $inicio = ($pag > 1) ? ($pag - 1) * $this->intTamPag : 0;

        $where = array();
        $where["idOrgao = ?"] = $codOrgao;

        if ((isset($_POST['pronac']) && !empty($_POST['pronac'])) || (isset($_GET['pronac']) && !empty($_GET['pronac']))) {
            $pronac = isset($_POST['pronac']) ? $_POST['pronac'] : $_GET['pronac'];
            $where["NrProjeto = ?"] = $pronac;
            $this->view->pronacProjeto = $pronac;
        }

        if(!$this->_request->getParam("tipoFiltro")){
            $tipoFiltro = 'aguardando_distribuicao';
        } else {
            $tipoFiltro = $this->_request->getParam("tipoFiltro");
        }
        $this->view->tipoFiltro = $tipoFiltro;
        
        $tbDistribuirParecer = new tbDistribuirParecer();
        $total = $tbDistribuirParecer->painelAnaliseTecnica($where, $order, null, null, true, $tipoFiltro);
        $fim = $inicio + $this->intTamPag;
        $totalPag = (int)(($total % $this->intTamPag == 0) ? ($total / $this->intTamPag) : (($total / $this->intTamPag) + 1));
        $tamanho = ($fim > $total) ? $total - $inicio : $this->intTamPag;
        $busca = $tbDistribuirParecer->painelAnaliseTecnica($where, $order, $tamanho, $inicio, false, $tipoFiltro);

        $checarValidacaoSecundarios = array();
        foreach ($busca as $chave => $item) {
            if ($item->stPrincipal == 1) {
                $checarValidacaoSecundarios[$item->IdPRONAC] = $tbDistribuirParecer->checarValidacaoProdutosSecundarios($item->IdPRONAC);
            }
        }
        $this->view->checarValidacaoSecundarios = $checarValidacaoSecundarios;
        $this->view->idTipoDoAtoAdministrativo = $this->idTipoDoAtoAdministrativo;
        
        $paginacao = array(
            "pag" => $pag,
            "qtde" => $this->intTamPag,
            "campo" => $campo,
            "ordem" => $ordem,
            "ordenacao" => $ordenacao,
            "novaOrdem" => $novaOrdem,
            "total" => $total,
            "inicio" => ($inicio + 1),
            "fim" => $fim,
            "totalPag" => $totalPag,
            "Itenspag" => $this->intTamPag,
            "tamanho" => $tamanho
        );

        $this->view->paginacao = $paginacao;
        $this->view->qtdDocumentos = $total;
        $this->view->dados = $busca;
        $this->view->intTamPag = $this->intTamPag;        
    }

    
    public function concluiuAction()
    {
        //** Usuario Logado ************************************************/
        $idUsuario = $this->auth->getIdentity()->usu_codigo;
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo'); // cria a sess�o com o grupo ativo
        $codOrgao = $GrupoAtivo->codOrgao; //  �rg�o ativo na sess�o

        /******************************************************************/

        $idDistribuirParecer = $this->_request->getParam("idDistribuirParecer");
        $idPronac = $this->_request->getParam("idpronac");
        $observacao = $this->_request->getParam("obs");
        $tipoFiltro = $this->_request->getParam("tipoFiltro");
        
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB :: FETCH_OBJ);

        $projetos = new Projetos();

        try {
            $db->beginTransaction();

            $tbDistribuirParecer = new tbDistribuirParecer();
            $dadosWhere["t.idDistribuirParecer = ?"] = $idDistribuirParecer;

            $buscaDadosProjeto = $tbDistribuirParecer->dadosParaDistribuir($dadosWhere);

            foreach ($buscaDadosProjeto as $dp) {
                $idOrgao = $dp->idOrgao;
                
                if ($tipoFiltro == 'em_validacao') {
                    $fecharAnalise = 3;
                } else if ($tipoFiltro == 'validados' || $tipoFiltro == 'devolvida') {
                    $fecharAnalise = 1;
                    
                    if ($this->isIphan($dp->idOrgao)) {
                        $idOrgao = Orgaos::ORGAO_IPHAN_PRONAC;
                    }
                }
            
                $dados = array(
                    'DtEnvio' => $dp->DtEnvio,
                    'idAgenteParecerista' => $dp->idAgenteParecerista,
                    'DtDistribuicao' => $dp->DtDistribuicao,
                    'DtDevolucao' => $dp->DtDevolucao,
                    'DtRetorno' => MinC_Db_Expr::date(),
                    'Observacao' => $observacao,
                    'idUsuario' => $idUsuario,
                    'FecharAnalise' => $fecharAnalise,
                    'idOrgao' => $idOrgao,
                    'idPRONAC' => $dp->IdPRONAC,
                    'idProduto' => $dp->idProduto,
                    'TipoAnalise' => $dp->TipoAnalise,
                    'stEstado' => 0,
                    'stPrincipal' => $dp->stPrincipal,
                    'stDiligenciado' => null
                );

                $whereD['idDistribuirParecer = ?'] = $idDistribuirParecer;
                $salvar = $tbDistribuirParecer->alterar(array('stEstado' => 1), $whereD);
                $insere = $tbDistribuirParecer->inserir($dados);
 
            }

            /** Grava o Parecer nas Tabelas tbPlanilhaProjeto e Parecer e altera a situa��o do Projeto para  ***************/
            $projeto = new Projetos();
            $wherePro['IdPRONAC = ?'] = $idPronac;
            $buscaDadosdoProjeto = $projeto->buscar($wherePro);

            // se for produto principal
            if ($buscaDadosProjeto[0]->stPrincipal == 1) {

                /****************************************************************************************************************/
                $parecerDAO = new Parecer();
                $whereParecer['idPRONAC = ?'] = $idPronac;
                $buscarParecer = $parecerDAO->buscar($whereParecer);

                $analiseDeConteudoDAO = new Analisedeconteudo();
                $whereADC['idPRONAC = ?'] = $idPronac;
                $dadosADC = array('idParecer' => $buscarParecer[0]->IdParecer);
                $alteraADC = $analiseDeConteudoDAO->alterar($dadosADC, $whereADC);

                $planilhaProjetoDAO = new PlanilhaProjeto();
                $wherePP['idPRONAC = ?'] = $idPronac;
                $dadosPP = array('idParecer' => $buscarParecer[0]->IdParecer);
                $alteraPP = $planilhaProjetoDAO->alterar($dadosPP, $wherePP);
                /****************************************************************************************************************/
            }
            $db->commit();
            parent::message("Conclu&iacute;do com sucesso!", "parecer/gerenciar-parecer?tipoFiltro=" . $tipoFiltro, "CONFIRM");

        } catch (Zend_Exception $ex) {
            $db->rollBack();
            parent::message("Erro ao concluir " . $ex->getMessage(), "gerenciarparecer/concluir/idDistribuirParecer/" . $idDistribuirParecer . "/tipoFiltro/" . $tipoFiltro, "ERROR");
        }

    }
    
    public function finalizarParecerAction()
    {
        $idusuario = $this->auth->getIdentity()->usu_codigo;
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo');
        $codOrgao = $GrupoAtivo->codOrgao;
        $this->view->codOrgao = $codOrgao;
        $this->view->idUsuarioLogado = $idusuario;

        $objTbAtoAdministrativo = new Assinatura_Model_DbTable_TbAtoAdministrativo();
        $this->view->quantidadeMinimaAssinaturas = $objTbAtoAdministrativo->obterQuantidadeMinimaAssinaturas(
            $this->idTipoDoAtoAdministrativo,
            $this->auth->getIdentity()->usu_org_max_superior
        );
        $this->view->idTipoDoAtoAdministrativo = $this->idTipoDoAtoAdministrativo;
        $this->view->idPerfilDoAssinante = $GrupoAtivo->codGrupo;
        
        //DEFINE PARAMETROS DE ORDENACAO / QTDE. REG POR PAG. / PAGINACAO
        if($this->_request->getParam("qtde")) {
            $this->intTamPag = $this->_request->getParam("qtde");
        }
        $order = array();

        if ($this->_request->getParam("ordem")) {
            $ordem = $this->_request->getParam("ordem");
            if ($ordem == "ASC") {
                $novaOrdem = "DESC";
            } else {
                $novaOrdem = "ASC";
            }
        } else {
            $ordem = "ASC";
            $novaOrdem = "ASC";
        }
        
        if ($this->_request->getParam("campo")) {
            $campo = $this->_request->getParam("campo");
            $order = array($campo . " " . $ordem);
            $ordenacao = "&campo=" . $campo . "&ordem=" . $ordem;

        } else {
            $campo = null;
            $order = array('dtValidacao', 'NomeProjeto', 'stPrincipal desc');
            $ordenacao = null;
        }

        $pag = 1;
        $get = Zend_Registry::get('get');
        if (isset($get->pag)) $pag = $get->pag;
        $inicio = ($pag > 1) ? ($pag - 1) * $this->intTamPag : 0;

        $where = array();
        $where["idOrgao = ?"] = $codOrgao;

        if ((isset($_POST['pronac']) && !empty($_POST['pronac'])) || (isset($_GET['pronac']) && !empty($_GET['pronac']))) {
            $pronac = isset($_POST['pronac']) ? $_POST['pronac'] : $_GET['pronac'];
            $where["NrProjeto = ?"] = $pronac;
            $this->view->pronacProjeto = $pronac;
        }

        $tbDistribuirParecer = new tbDistribuirParecer();
        $tipoFiltro  = 'presidente_vinculadas';
        $total = $tbDistribuirParecer->painelAnaliseTecnica($where, $order, null, null, true, $tipoFiltro);
        $fim = $inicio + $this->intTamPag;
        
        $totalPag = (int)(($total % $this->intTamPag == 0) ? ($total / $this->intTamPag) : (($total / $this->intTamPag) + 1));
        $tamanho = ($fim > $total) ? $total - $inicio : $this->intTamPag;
        $busca = $tbDistribuirParecer->painelAnaliseTecnica($where, $order, $tamanho, $inicio, false, $tipoFiltro);

        $checarValidacaoSecundarios = array();
        foreach ($busca as $chave => $item) {
            if ($item->stPrincipal == 1) {
                $checarValidacaoSecundarios[$item->IdPRONAC] = $tbDistribuirParecer->checarValidacaoProdutosSecundarios($item->IdPRONAC);
            }
        }
        $this->view->checarValidacaoSecundarios = $checarValidacaoSecundarios;
        $this->view->idTipoDoAtoAdministrativo = $this->idTipoDoAtoAdministrativo;
        
        $paginacao = array(
            "pag" => $pag,
            "qtde" => $this->intTamPag,
            "campo" => $campo,
            "ordem" => $ordem,
            "ordenacao" => $ordenacao,
            "novaOrdem" => $novaOrdem,
            "total" => $total,
            "inicio" => ($inicio + 1),
            "fim" => $fim,
            "totalPag" => $totalPag,
            "Itenspag" => $this->intTamPag,
            "tamanho" => $tamanho
        );

        $this->view->paginacao = $paginacao;
        $this->view->qtdDocumentos = $total;
        $this->view->dados = $busca;
        $this->view->intTamPag = $this->intTamPag;        
    }

    
    /*
     * Finalização de presidente de vinculada 
     */
    public function finalizouParecerAction()
    {
        $idDistribuirParecer = $this->_request->getParam("idDistribuirParecer");
        $idPronac = $this->_request->getParam("idpronac");
        $idUsuario = $this->auth->getIdentity()->usu_codigo;
        $GrupoAtivo = new Zend_Session_Namespace('GrupoAtivo');
        $codOrgao = $GrupoAtivo->codOrgao;
        
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->setFetchMode(Zend_DB :: FETCH_OBJ);

        $projetos = new Projetos();
        
        try {
            $db->beginTransaction();
            
            if (!$this->isIphan($dp->idOrgao)) {
                $parecer = new Parecer();
                $idAtoAdministrativo = $parecer->getIdAtoAdministrativoParecerTecnico($idPronac, self::ID_TIPO_AGENTE_PARCERISTA)->current()['idParecer'];
                
                $objModelDocumentoAssinatura = new Assinatura_Model_DbTable_TbDocumentoAssinatura();
                $data = array(
                    'cdSituacao' => Assinatura_Model_TbDocumentoAssinatura::CD_SITUACAO_FECHADO_PARA_ASSINATURA
                );
                $where = array(
                    'IdPRONAC = ?' => $idPronac,
                    'idTipoDoAtoAdministrativo = ?' => $this->idTipoDoAtoAdministrativo,
                    'idAtoDeGestao = ?' => $idAtoAdministrativo,
                    'cdSituacao = ?' => Assinatura_Model_TbDocumentoAssinatura::CD_SITUACAO_DISPONIVEL_PARA_ASSINATURA,
                    'stEstado = ?' => Assinatura_Model_TbDocumentoAssinatura::ST_ESTADO_DOCUMENTO_ATIVO
                );
                $objModelDocumentoAssinatura->update($data, $where);
            }
            
            $tbDistribuirParecer = new tbDistribuirParecer();
            $dadosWhere["t.idDistribuirParecer = ?"] = $idDistribuirParecer;

            $buscaDadosProjeto = $tbDistribuirParecer->dadosParaDistribuir($dadosWhere);

            foreach ($buscaDadosProjeto as $dp) {
                
                if ($this->isIphan($dp->idOrgao)) {                

                    $idOrgao = Orgaos::ORGAO_IPHAN_PRONAC;
                    $fecharAnalise = 3;
                } else {
                    $idOrgao = $dp->idOrgao;
                    $fecharAnalise = 1;
                }
                
                $dados = array(
                    'DtEnvio' => $dp->DtEnvio,
                    'idAgenteParecerista' => $dp->idAgenteParecerista,
                    'DtDistribuicao' => $dp->DtDistribuicao,
                    'DtDevolucao' => $dp->DtDevolucao,
                    'DtRetorno' => MinC_Db_Expr::date(),
                    'Observacao' => "",
                    'idUsuario' => $idUsuario,
                    'FecharAnalise' => $fecharAnalise,
                    'idOrgao' => $idOrgao,
                    'idPRONAC' => $dp->IdPRONAC,
                    'idProduto' => $dp->idProduto,
                    'TipoAnalise' => $dp->TipoAnalise,
                    'stEstado' => 0,
                    'stPrincipal' => $dp->stPrincipal,
                    'stDiligenciado' => null
                );

                $whereD['idDistribuirParecer = ?'] = $idDistribuirParecer;
                $salvar = $tbDistribuirParecer->alterar(array('stEstado' => 1), $whereD);
                $insere = $tbDistribuirParecer->inserir($dados);
 
            }

            $projeto = new Projetos();
            $wherePro['IdPRONAC = ?'] = $idPronac;
            $buscaDadosdoProjeto = $projeto->buscar($wherePro);
            
            /// ALTERAR SITUACAO
            $inabilitadoDAO = new Inabilitado();
            $buscaInabilitado = $inabilitadoDAO->BuscarInabilitado($buscaDadosdoProjeto[0]->CgcCpf, $buscaDadosdoProjeto[0]->AnoProjeto, $buscaDadosdoProjeto[0]->Sequencial);
            
            if (count($buscaInabilitado == 0)) {
                if (!$this->isIphan($dp->idOrgao)) {
                    // somente presidente
                    $projeto->alterarSituacao($idPronac, null, 'C20', 'An&aacute;lise t&eacute;cnica conclu&iacute;da');
                } else {
                    $projeto->alterarSituacao($idPronac, null, 'B11', 'Aguardando valida&ccedil;&atilde;o do parecer t&eacute;cnico');
                }
            } else {
                // inabilitado
                $projeto->alterarSituacao($idPronac, null, 'C09', 'Projeto fora da pauta de reuni&atilde;o da CNIC porque o proponente est&aacute; inabilitado no Minist&eacute;rio da Cultura.');
            }
            
            $db->commit();
            parent::message("Conclu&iacute;do com sucesso!", "parecer/gerenciar-parecer/finalizar-parecer", "CONFIRM");

        } catch (Zend_Exception $ex) {
            $db->rollBack();
            parent::message("Erro ao concluir " . $ex->getMessage(), "parecer/gerenciar-parecer/finalizar-parecer", "ERROR");
        }
        
    }

    public function isIphan($idOrgao)
    {
        $orgaos = array(
            Orgaos::ORGAO_IPHAN_PRONAC,
            Orgaos::ORGAO_IPHAN_PRONAC,
            Orgaos::ORGAO_FUNARTE,
            Orgaos::ORGAO_FBN,
            Orgaos::ORGAO_FCP,
            Orgaos::ORGAO_FCRB,
            Orgaos::ORGAO_IBRAM,
            Orgaos::ORGAO_SUPERIOR_SAV,
            Orgaos::ORGAO_SAV_DAP
        );

        return (!in_array($dp->idOrgao, $orgaos)) ? true : false;
    }
}
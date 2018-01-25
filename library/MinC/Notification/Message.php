<?php

/* include_once APPLICATION_PATH.'/../library/Zend/Rest/Client.php'; */

/**
 * Classe para controlar As notifica��es enviadas para os dispositivos m�veis.
 * 
 * @version 1.0
 * @package application
 * @subpackage application.notification
 * @link http://www.cultura.gov.br
 * @copyright � 2016 - Minist�rio da Cultura - Todos os direitos reservados.
 */
class Minc_Notification_Message {
    
    /**
     * CPF do usu�rio que receber� a mensagem.
     * 
     * @var string
     */
    protected $cpf;

    /**
     * C�digo do projeto ou idPronac.
     * 
     * @var integer
     */
    protected $codePronac;
    
    /**
     * C�digo da dilig�ncia ou idDiligencia.
     * 
     * @var integer
     */
    protected $codeDiligencia;

    /**
     * Lista de Id dos dispositivos dos usu�rios que receber�o a notifica��o.
     * 
     * @var array
     */
    protected $listDeviceId;

    /**
     * Lista de Ids registrations dos dispositivos dos usu�rios que receber�o a notifica��o.
     * 
     * @var array 
     */
    protected $listResgistrationIds;
    
    /**
     * Tipo de mensagem.
     * 
     * @var integer
     */
    protected $tipoMensagem;

    /**
     * Titulo da mensagem.
     * 
     * @var string
     */
    protected $title;
    
    /**
     * Descri��o da mensagem.
     * 
     * @var string
     */
    protected $text;

    /**
     * Parametros para exibir os dados da notifica��o.
     * 
     * @var array
     */
    protected $listParameters;

    /**
     * Url do servi�o GCM para enviar notifica��es.
     * 
     * @var string
     */
    protected $gcmUrl;
    
    /**
     * Chave da aplica��o para acessar o servi�o GCM.
     * 
     * @var string
     */
    protected $gcmApiKey;
    
    /**
     * Lista de parametros segundo a documenta��o do servi�o que ser� consumido para o envio de notifica��es.
     * 
     * @var array
     */
    protected $listParametersService;

    /**
     * Informa��es do servi�o GCM sobre a execu��o do envio da notifica��o.
     * 
     * @var stdClass
     */
    protected $response;
    
    /**
     * Classe realizar requisi��o e usufruir servi�os.
     * 
     * @var Zend_Rest_Client 
     */
    protected $client;
    
    /**
     * Classe que abstrai a tabela de mensagens(SAC.dbo.tbMensagem)
     * 
     * @var Mensagem
     */
    protected $modelMensagem;

    public function getCpf() {
        return $this->cpf;
    }

    public function getCodePronac() {
        return $this->codePronac;
    }

    public function getCodeDiligencia() {
        return $this->codeDiligencia;
    }

    public function getListDeviceId() {
        return $this->listDeviceId;
    }

    public function getListResgistrationIds() {
        return $this->listResgistrationIds;
    }

    public function getTipoMensagem() {
        return $this->tipoMensagem;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getText() {
        return $this->text;
    }

    public function getListParameters() {
        return $this->listParameters;
    }

    public function getGcmUrl() {
        return $this->gcmUrl;
    }

    public function getGcmApiKey() {
        return $this->gcmApiKey;
    }

    public function getListParametersService() {
        return $this->listParametersService;
    }

    public function getResponse() {
        return $this->response;
    }

    public function getClient() {
        return $this->client;
    }

    public function getModelMensagem() {
        return $this->modelMensagem;
    }

    public function setCpf($cpf) {
        $this->cpf = $cpf;
        return $this;
    }

    public function setCodePronac($codePronac) {
        $this->codePronac = $codePronac;
        return $this;
    }

    public function setCodeDiligencia($codeDiligencia) {
        $this->codeDiligencia = $codeDiligencia;
        return $this;
    }

    public function setListDeviceId($listDeviceId) {
        $this->listDeviceId = $listDeviceId;
        return $this;
    }

    public function setListResgistrationIds($listResgistrationIds) {
        $this->listResgistrationIds = $listResgistrationIds;
        return $this;
    }

    public function setTipoMensagem($tipoMensagem) {
        $this->tipoMensagem = $tipoMensagem;
        return $this;
    }

    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }

    public function setText($text) {
        $this->text = $text;
        return $this;
    }

    public function setListParameters($listParameters) {
        $this->listParameters = $listParameters;
        return $this;
    }

    public function setGcmUrl($gcmUrl) {
        $this->gcmUrl = $gcmUrl;
        return $this;
    }

    public function setGcmApiKey($gcmApiKey) {
        $this->gcmApiKey = $gcmApiKey;
        return $this;
    }

    public function setListParametersService($listParametersService) {
        $this->listParametersService = $listParametersService;
        return $this;
    }

    public function setResponse(stdClass $response) {
        $this->response = $response;
        return $this;
    }

    public function setClient(Zend_Rest_Client $client) {
        $this->client = $client;
        return $this;
    }

    public function setModelMensagem(Mensagem $modelMensagem) {
        $this->modelMensagem = $modelMensagem;
        return $this;
    }

    /**
     * Envia notifica��es para dispositivos m�veis.
     * 
     * @param array $listResgistrationIds Lista de Ids dos dispositivos dos usu�rios que receber�o a notifica��o.
     * @param string $title Titulo da mensagem.
     * @param string $text Descri��o da mensagem.
     * @param array $listParameters Parametros para exibir os dados da notifica��o.
     */
    public function __construct($listResgistrationIds = NULL, $title = NULL, $text = NULL, $listParameters = NULL) {
        $config = new Zend_Config_Ini(APPLICATION_PATH .'/configs/application.ini', APPLICATION_ENV);
        $this->gcmUrl = $config->get('default')->resources->view->service->gcmUrl;
        $this->gcmApiKey = $config->get('default')->resources->view->service->gcmApiKey;
        $this->modelMensagem = new Mensagem();

        $this->listResgistrationIds = $listResgistrationIds;
        $this->title = $title;
        $this->text = $text;
        $this->listParameters = $listParameters;
        $this->loadConfig();
    }

    /**
     * Carrega configura��es padr�es de envio de notifica��es.
     * 
     * @return \Minc_Notification_Message
     */
    protected function loadConfig(){
        $this->client = new Zend_Rest_Client($this->gcmUrl);
        $this->loadListParametersService();
        
        return $this;
    }
    
    /**
     * Carrega configura��es dos parametros utilizados para configurar o servi�o de envio de notifica��es.
     * 
     * @return \Minc_Notification_Message
     */
    protected function loadListParametersService() {
        $this->listParametersService =  array(
            'priority' => 'high',
            'delay_while_idle' => true,
            'registration_ids' => $this->listResgistrationIds,
            'priority' => 'normal',
            'notification' => array(
                'icon' => 'icon',
                'title' => utf8_encode($this->title),
                'body' => utf8_encode($this->text)
            ),
            'data' => $this->listParameters
        );
        
        return $this;
    }
    
    /**
     * Envia notifica��o para para dispositivos registrados.
     * 
     * @return \Minc_Notification_Message
     */
    public function send() {
        if($this->listResgistrationIds){
            $this->loadListParametersService();
            
            # Envia notifica��o se existe configurado url do servi�o e o c�digo para consumir o servi�o GCM.
            if($this->gcmUrl && $this->gcmApiKey){
                $this->client
                    ->getHttpClient()
                        ->setHeaders(
                            array(
                                'Authorization: key='. $this->gcmApiKey,
                                'Content-Type: application/json'))
                        ->setRawData(json_encode($this->listParametersService))
                        ->setUri($this->gcmUrl);
                $this->response = json_decode($this->client->getHttpClient()->request('POST')->getBody());
            }
            $this->save();
        }
        
        return $this;
    }
    
    /**
     * Salva as mensagens enviadas no banco de dados.
     * 
     * @return \Minc_Notification_Message
     */
    private function save() {
        $messageRow = $this->modelMensagem->createRow();
        $messageRow->nrCPF = $this->cpf;
        $messageRow->idPronac = $this->codePronac;
        $messageRow->idDiligencia = $this->codeDiligencia;
        $messageRow->tpMensagem = $this->tipoMensagem;
        $messageRow->titulo = $this->title;
        $messageRow->descricao = $this->text;
        if($this->response && $this->response->success){
            $messageRow->idSuccess = $this->response->success;
            $messageRow->idMulticast = $this->response->multicast_id;
        }
        $messageRow->save();
        $this->modelMensagem->saveListDevice($messageRow, $this->listDeviceId);
        
        return $this;
    }
    
}
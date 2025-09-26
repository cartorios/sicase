<?php

namespace Cartorios\Sicase;

use Cartorios\Sicase\HttpRequest;

class ConsultaGuiaWeb extends SicaseWeb
{
    const URL_GUIA_CONSULTA = "https://sicase.tjpe.jus.br/sicase/pages/consultardetalheguia/list_consultardetalheguia.jsf";
    const URL_GUIA_DOWNLOAD = "https://sicase.tjpe.jus.br/sicase/pages/consultardetalheguia/form_consultargeral.jsf";
    const NAO_PODE_ACESSAR  = "Seu usuário não pode acessar detalhes da guia";
    
    protected $guia;
    protected $status;

    public function __construct( $diretorio = null )
    {
        parent::__construct( "consultar-guia" , $diretorio );
    }

    public function consultar( $guia )
    {
        if( $this->response == null )
        {
            throw new \Exception( "É necessário fazer o login!" );
        }
        
        $this->guia = $guia;
        
        $this->response = $this->request->get( self::URL_INDEX )
            ->debug()
            ->request();
        
        $this->lastStateView = $this->getViewState();
        $this->verificarSeLogado();
        
        $jID = $this->getJID( "Detalhe de uma Guia" );

        // ------------------------------------------
        // ------------------------------------------ SIMULAR DIGITANDO A GUIA
        // ------------------------------------------
        
        $this->response = $this->request->post( self::URL_INDEX , [
                "action" => "listaConsultarDetalheGuia",
                "autoScroll" => "",
                "drop_menu" => "Consultas",
                "j_id15" => "j_id15",
                $jID . ":hidden" => $jID,
                "javax.faces.ViewState" => $this->lastStateView,
                "menu_item" => "Detalhe+de+uma+Guia"
            ])
            ->debug()
            ->request();

        $this->lastStateView = $this->getViewState();
        $this->verificarSeLogado();
        
        // ------------------------------------------
        // ------------------------------------------ CONSULTAR A GUIA
        // ------------------------------------------
        
        $this->response = $this->request->post( self::URL_GUIA_CONSULTA , [
                "formId" => "formId",
                "formId:j_id29" => "formId:j_id29",
                "formId:waitForm" => "formId:waitForm",
                "formId:waitForm:waitPanelOpenedState" => "",
                "formId:numeroGuia" => $this->guia,
                "javax.faces.ViewState" => $this->lastStateView
            ])
            ->debug()
            ->request();
        
        $this->lastStateView = $this->getViewState();
        $this->verificarSeLogado();
        
        $this->status = $this->getGuiaStatus();
        
        if( !$this->status )
        {
            throw new \Exception( "Não conseguiu recuperar o status da guia {$this->guia}" );
        }
        
        return $this->status;
    }
    
    // public function getSicase()
    // {
    //     return SicaseResultadoUtils.get( 
    //         $this->response[ "content" ] 
    //     );
    // }
    
    public function download()
    {
        if( $this->response == null )
        {
            throw new \Exception( "É necessário fazer o login!" );
        }
        
        if( !$this->status )
        {
            throw new \Exception( "Não conseguiu recuperar o status da guia {$this->guia}" );
        }
        
        $i1 = strrpos( $this->response[ "content" ] , "document.getElementById('formConsultarDetalheGuiaBean')" );
        $i2 = strrpos( $this->response[ "content" ] , "Gerar PDF" );
        
        $codigo =  substr( $this->response[ "content" ] , $i1 , $i2 );
        $i1 = strpos( $codigo , "formConsultarDetalheGuiaBean:j_id" );
        $i2 = strpos( $codigo , "':'" );
        
        $codigo = trim( substr( $codigo , $i1 , $i2 ) );
        
        $this->response = $this->request->post( self::URL_GUIA_DOWNLOAD , [
                "formConsultarDetalheGuiaBean" => "formConsultarDetalheGuiaBean",
                $codigo => $codigo,
                "javax.faces.ViewState" => $this->lastStateView
            ])
            ->debug()
            ->request();
        
        $this->verificarSeLogado();
        
        return $this->response[ "content" ];
    }
    
    private function getGuiaStatus()
    {
        // javax.faces.ViewState
        $chave = "Situa&ccedil;&atilde;o:";
        
        $content = $this->response[ "content" ];
        if( $content == null )
        {
            return "";
        }
        
        $i1 = strpos( $content , $chave );
        $i2 = 0;
        
        if( $i1 == -1 )
        {
            return "";
        }
        
        $valor = substr( $content , $i1 + strlen( $chave ) + 1 );
        $i1 = strpos( $valor , ">"     );
        $i2 = strpos( $valor , "</th>" );
        
        return trim( substr( $valor , $i1 + 1 , $i2 ) );
    }

}
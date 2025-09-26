<?php

namespace Cartorios\Sicase;

use Cartorios\Sicase\HttpRequest;

class ConsultaSeloWeb
{
    const URL_CONSULTA = "https://sicase.tjpe.jus.br/sicase/externo/autenticidadeselo/form_validarautenticidadeselo.jsf";
    const URL_CAPTCHA  = "https://sicase.tjpe.jus.br/sicase/simpleCaptcha.png";
    
    public $request;
    public $response;

    public function __construct( $diretorio = null )
    {
        $this->request = new HttpRequest( "consultar-selo" , $diretorio );
    }

    public function getCaptcha()
    {
        $this->response = $this->request
            ->get( self::URL_CONSULTA )
            ->request();

        $this->request->save( "consulta.html" , $this->response[ "content" ] );

        $this->response = $this->request
            ->get( self::URL_CAPTCHA )
            ->request();

        return $this->request->save( "captcha.png" , $this->response[ "content" ] );
    }

    public function pesquisar( string $seloDigital , string $captcha ): array
    {
        $this->response = $this->request
            ->post( self::URL_CONSULTA , [
                "formSelo"                  => "formSelo",
                "formSelo:captchaInformado" => $captcha,
                "formSelo:seloEletronico"   => $seloDigital,
                "formSelo:j_id30"           => "formSelo:j_id30",
                "javax.faces.ViewState"     => "j_id1"
            ] )
            ->debug()
            ->request();
        
        $error = $this->getErroMensagem();
        if ( trim( $error ) !== "" )
        {
            throw new \Exception( $error );
        }

        return $this->getDados( $seloDigital );
    }

    private function getDados( string $seloDigital ): array
    {
        return [
            "ato"           => $this->getValor("Ato:"),
            "contribuinte"  => $this->getValor("Contribuinte:"),
            "cartorio"      => $this->getValor("Cart&oacute;rio:"),
            "oficial"       => $this->getValor("Oficial:"),
            "emissor"       => $this->getValor("Emissor:"),
            "data"          => $this->getValor("Emitido em:"),
            "selo"          => $seloDigital,
            "data_pesquisa" => date("Y-m-d H:i:s")
        ];
    }

    private function getValor( string $palavraChave )
    {
        $content = $this->response[ "content" ];

        $i1 = strpos( $content , $palavraChave );
        if ($i1 === false) return "";

        $error = substr($content, $i1 + strlen($palavraChave) + 1);
        $i1 = strpos($error, ">");
        $i2 = strpos($error, "</span>");

        if ($i1 === false || $i2 === false) return "";

        $value = substr($error, $i1 + 1, $i2 - ($i1 + 1));
        return html_entity_decode(trim($value), ENT_SUBSTITUTE | ENT_HTML5 , 'UTF-8' );
    }

    private function getErroMensagem()
    {
        $content = $this->response["content"];
        $start = strpos( $content , "javascript:document.getElementById('formSelo:seloEletronico').focus()" );
        if ( $start === false ) return "";
        
        $error = substr( $content , $start );
        $i1 = strpos($error, ">" );
        $i2 = strpos($error, "</a>" );
        if ($i1 === false || $i2 === false) {
            return "";
        }

        $msg = substr($error, $i1 + 1, $i2 - ($i1 + 1));
        return html_entity_decode(trim($msg), ENT_SUBSTITUTE | ENT_HTML5 , 'UTF-8' );
    }

    public function getDiretorio()
    {
        return $this->request->diretorio;
    }

}
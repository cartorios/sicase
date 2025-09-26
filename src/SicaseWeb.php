<?php

namespace Cartorios\Sicase;

class SicaseWeb
{
    const URL_LOGIN   = "https://sicase.tjpe.jus.br/sicase/pages/login.jsf";
    const URL_CAPTCHA = "https://sicase.tjpe.jus.br/sicase/simpleCaptcha.png";
    const URL_INDEX   = "https://sicase.tjpe.jus.br/sicase/pages/index.jsf";

    protected $request;
    protected $response;
    protected $lastStateView;
    private   $logado = false;

    public function __construct( $name = "sicase" , $diretorio = null )
    {
        $this->request = new HttpRequest( $name , $diretorio );
    }

    public function loginPage()
    {
        $this->response = $this->request
            ->get( self::URL_LOGIN )
            ->debug()
            ->request();
        
        $this->lastStateView = $this->getViewState();
        return $this->getCaptcha();
    }

    public function getCaptcha()
    {
        if ( $this->response === null )
        {
            return $this->loginPage();
        }

        $this->captcha = null;

        $this->response = $this->request
            ->get( self::URL_CAPTCHA )
            ->debug()
            ->request();
        
        $captchaFile = "{$this->request->diretorio}/captcha.png";
        file_put_contents( $captchaFile , $this->response[ "content" ] );

        return $captchaFile;
    }

    public function login( $login , $senha , $captcha )
    {
        if( !$this->lastStateView )
        {
            $this->lastStateView = "j_id1";
        }

        $this->response = $this->request->post( self::URL_LOGIN ,[
                "j_id12" => "j_id12",
                "j_id12:captchaInformado" => $captcha,
                "j_id12:cpf_hid" => $login,
                "j_id12:pass_hid" => $senha,
                "j_id12:j_id16" => "Conectar",
                "javax.faces.ViewState" => $this->lastStateView
            ])
            ->debug()
            ->request();
        
        $this->lastStateView = $this->getViewState();

        $this->verificarSeLogado();
        
        $this->logado = true;
        file_put_contents( "{$this->request->diretorio}/logado" , "true" );
    }

    public function indexPage()
    {
        $this->response = $this->request->get( self::URL_INDEX )
            ->debug()
            ->request();

        $this->lastStateView = getViewState();
        verificarSeLogado();
    }

    public function isLogado() 
    {
        return $this->logado;
    }

    protected function verificarSeLogado()
    {
        if (
            isset($this->response['headers']['Location']) &&
            strpos($this->response['headers']['Location'], '/login.jsf') !== false
        ) {
            throw new \Exception( "Deslogado" );
        } elseif (
            isset($this->response['content']) &&
            strpos($this->response['content'], '/login.jsf') !== false
        ) {
            //error_log("MENSAGEM: " . $this->getErroMensagem());
            throw new \Exception( "Deslogado" );
        }
    }


    // ------------------------------ //
    // ------------------------------ // OTHERS
    // ------------------------------ //

    protected function getViewState()
    {
        $chave = "javax.faces.ViewState";

        $content = isset( $this->response['content'] ) ? $this->response['content'] : null;
        if ( $content === null ) return $this->lastStateView;

        $i1 = strpos( $content , $chave );
        if ($i1 === false) return $this->lastStateView;

        $valor = substr($content, $i1 + strlen($chave) + 1);
        $i1 = strpos($valor, 'value="');
        $i2 = strpos($valor, '/>');

        if ($i1 === false || $i2 === false) return $this->lastStateView;

        $valor = substr($valor, $i1 + 7, $i2 - ($i1 + 7));
        $endQuote = strpos($valor, '"');
        if ($endQuote === false) return $this->lastStateView;

        return substr( $valor , 0 , $endQuote );
    }

    protected function getJID( $menuItem )
    {
        $palavraChave = htmlspecialchars( $menuItem , ENT_QUOTES | ENT_HTML5, 'UTF-8' );

        $content = isset( $this->response['content'] ) ? $this->response['content'] : null;
        if ( $content === null || trim($content) === '' ) return '';

        $i1 = strpos( $content , $palavraChave );
        if ($i1 === false || $i1 < 2) return '';

        $content = substr($content, 0, $i1 - 2);

        $i1 = strrpos($content, 'id="');
        if ($i1 === false) return '';

        $content = substr($content, $i1 + 4);

        $i1 = strpos($content, '"');
        if ($i1 === false) return '';

        return substr($content, 0, $i1);
    }

    public function getDiretorio()
    {
        return $this->request->diretorio;
    }
    
}
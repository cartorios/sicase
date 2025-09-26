<?php

namespace Cartorios\Sicase;

class HttpRequest
{
    public $diretorio;
    public $contador = 0;
    
    public $url;
    public $content;

    public $ch;

    public $debugFile;

    public function __construct( $name = "sicase" , $diretorio = null )
    {
        $this->diretorio = $diretorio 
            ? $diretorio 
            : tempnam( sys_get_temp_dir() , "{$name}-" );

        if( !is_dir( $this->diretorio ) )
        {
            unlink( $this->diretorio ); // delete a file
            mkdir ( $this->diretorio ); // create a diretory
        }

        // cria o arquivo de cookies
        if(  file_exists( "{$this->diretorio}/contador"    ) ) $this->contador = intval( file_get_contents( "{$this->diretorio}/contador" ) );
        if( !file_exists( "{$this->diretorio}/cookies.txt" ) ) file_put_contents( "{$this->diretorio}/cookies.txt" , "" );
    }

    public function autoRedirect( $value )
    {
        curl_setopt( $this->ch , CURLOPT_FOLLOWLOCATION , $value );
        return $this;
    }

    public function debug()
    {
        $this->debugFile = fopen( "{$this->diretorio}/{$this->contador}-request.txt" , 'w+' );

        curl_setopt( $this->ch , CURLOPT_VERBOSE , true );
        curl_setopt( $this->ch , CURLOPT_STDERR  , $this->debugFile );

        return $this;
    }

    // -------------- //

    public function get( $url )
    {
        $this->url = $url;

        $this->ch = curl_init( $url );
        curl_setopt( $this->ch , CURLOPT_HEADER         , true );
        curl_setopt( $this->ch , CURLOPT_RETURNTRANSFER , true );
        curl_setopt( $this->ch , CURLOPT_FOLLOWLOCATION , true );
        curl_setopt( $this->ch , CURLOPT_COOKIEJAR      , "{$this->diretorio}/cookies.txt" );
        curl_setopt( $this->ch , CURLOPT_COOKIEFILE     , "{$this->diretorio}/cookies.txt" );
        curl_setopt( $this->ch , CURLOPT_HTTPHEADER , [
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language: en-US,en;q=0.5",
            "Connection: keep-alive",
            "DNT: 1",
            "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:59.0) Gecko/20100101 Firefox/59.0",
            "Upgrade-Insecure-Requests: 1"
        ]);

        return $this;
    }

    public function post( $url , $fields )
    {
        $this->url = $url;
        $this->content = http_build_query( $fields );

        $this->get( $url );
        curl_setopt( $this->ch , CURLOPT_POST       , true );
        curl_setopt( $this->ch , CURLOPT_POSTFIELDS , http_build_query( $fields ) );

        return $this;
    }

    public function request()
    {
        $response = $this->requestAndResponse();

        if( $this->debugFile )
        {
            $this->save( "response.txt"  , json_encode( $response , JSON_PRETTY_PRINT ) , false );
            $this->save( "content.html"  , $response["content"] );
        }

        $this->close();

        return $response;
    }

    private function requestAndResponse()
    {
        $response = curl_exec( $this->ch );

        if ( $response === false )
        {
            $retorno = [
                "error"   => true,
                "url"     => $this->url,

                'status'  => curl_errno( $this->ch ),
                'headers' => [],
                'content' => curl_error( $this->ch )
            ];

            return $retorno;
        }

        $headerSize = curl_getinfo( $this->ch , CURLINFO_HEADER_SIZE );
        $httpCode   = curl_getinfo( $this->ch , CURLINFO_HTTP_CODE   );

        $headersRaw = substr( $response , 0 , $headerSize );
        $content    = substr( $response , $headerSize );

        $headers = [];
        foreach ( explode("\r\n", $headersRaw) as $headerLine )
        {
            if (strpos($headerLine, ':') !== false) {
                list($key, $value) = explode(':', $headerLine, 2);
                $headers[trim($key)] = trim($value);
            }
        }

        return [
            "error"   => false,
            "url"     => $this->url,

            'status'  => $httpCode,
            'headers' => $headers,
            'content' => $content
        ];
    }

    private function close()
    {
        curl_close( $this->ch );

        if( $this->debugFile )
        {
            fwrite( $this->debugFile , "\n\nRequest body:\n" );
            fwrite( $this->debugFile , $this->content . "\n" );

            fclose( $this->debugFile );
            $this->debugFile = null;
        }
    }

    public function save( $name , $content , $increment = true )
    {
        $filename = "{$this->diretorio}/{$this->contador}-{$name}";
        file_put_contents( $filename , $content );

        if( $increment ) $this->contador++;
        file_put_contents( "{$this->diretorio}/contador" , $this->contador );

        return $filename;
    }
    
}
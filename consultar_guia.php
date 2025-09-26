<?php

require_once __DIR__ . '/vendor/autoload.php';

use Cartorios\Sicase\ConsultaGuiaWeb;

session_start();

$env   = parse_ini_file( ".env" );
$login = $env[ "login" ];
$senha = $env[ "senha" ];
$guia  = $env[ "guia"  ];

$diretorio   = isset( $_SESSION[ 'diretorio'   ] ) ? $_SESSION[ 'diretorio'   ] : null;
$captchaFile = isset( $_SESSION[ 'captchaFile' ] ) ? $_SESSION[ 'captchaFile' ] : null;
$captchaContent = null;
$captcha        = null;
$resultado      = null;

if( isset( $_GET[ "guia" ] ) )
{
    $captcha = $_GET[ "captcha" ];
    $login = $_GET[ "login" ];
    $senha = $_GET[ "senha" ];
    $guia  = $_GET[ "guia"  ];

    $consulta  = new ConsultaGuiaWeb( $diretorio );
    try
    {
        if( !$consulta->isLogado() )
        {
            $consulta->login( $login , $senha , $captcha );
        }

        $resultado = [
            "status" => $consulta->consultar( $guia ),
            "pdf"    => base64( "applcation/pdf" , $consulta->download() )
        ];
    }
    catch( \Exception $e )
    {
        $resultado = $e->getMessage();
    }
}
else
{
    $consulta = new ConsultaGuiaWeb( $diretorio );
    $diretorio   = $_SESSION['diretorio']   = $consulta->getDiretorio();
    $captchaFile = $_SESSION['captchaFile'] = $consulta->getCaptcha();
}

function base64( $mimeType , $content )
{
    $base64 = base64_encode( $content );
    return "data:{$mimeType};base64,{$base64}";
}

?>

<html>
    <head>
        <meta charset="utf-8">
        <title>Consultar Selo Digital</title>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.4/css/bulma.min.css">
    </head>
    <body>
        <div class="container mt-4">
            <form method="get" action="">
                <!-- <div class="field">
                    <label class="label" for="diretorio">Diretório:</label>
                    <div class="control">
                        <input class="input" name="diretorio" value="<?= $consulta->getDiretorio() ?>">
                    </div>
                </div> -->

                <div class="columns">
                    <div class="column field">
                        <label class="label" for="login">Login:</label>
                        <div class="control">
                            <input class="input" type="text" id="login" name="login" value="<?= $login ?>" required>
                        </div>
                    </div>

                    <div class="column field">
                        <label class="label" for="senha">Senha:</label>
                        <div class="control">
                            <input class="input" type="text" id="senha" name="senha" value="<?= $senha ?>" required>
                        </div>
                    </div>

                    <div class="column field">
                        <label class="label" for="captcha">Captcha:</label>
                        <div class="control">
                            <input class="input" type="text" id="captcha" name="captcha" value="<?= $captcha ?>" required>
                        </div>
                    </div>

                    <div class="column">
                        <img src="<?= base64( "image/png" , file_get_contents( $captchaFile ) ); ?>" />
                    </div>
                </div>
                
                <div class="field">
                    <label class="label" for="guia">Guia Nº:</label>
                    <div class="control">
                        <input class="input" type="text" id="guia" name="guia" value="<?= $guia ?>" required>
                    </div>
                </div>

                <button class="button is-link" type="submit">Consultar</button>
            </form>

            Resultado:
            <?= print_r( $resultado ); ?>
        </div>
    </body>
</html>
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Cartorios\Sicase\ConsultaSeloWeb as ConsultaSeloWeb;

session_start();

$diretorio   = isset( $_SESSION[ 'diretorio'   ] ) ? $_SESSION[ 'diretorio'   ] : null;
$captchaFile = isset( $_SESSION[ 'captchaFile' ] ) ? $_SESSION[ 'captchaFile' ] : null;
$captcha     = null;
$selo        = null;
$resultado   = null;

if( isset( $_GET[ "captcha" ] ) )
{
    $captcha = $_GET[ "captcha"      ];
    $selo    = $_GET[ "selo_digital" ];

    $consulta  = new ConsultaSeloWeb( $diretorio );
    
    try
    {
        $resultado = $consulta->pesquisar( $selo , $captcha );    
    }
    catch( \Exception $e )
    {
        $resultado = $e->getMessage();
    }
}
else
{
    $consulta = new ConsultaSeloWeb( $diretorio );
    $diretorio   = $_SESSION['diretorio']   = $consulta->getDiretorio();
    $captchaFile = $_SESSION['captchaFile'] = $consulta->getCaptcha();
}

function base64( $filePath )
{
    $fileContent = file_get_contents($filePath);
    $base64EncodedData = base64_encode($fileContent);

    $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeType = mime_content_type($filePath); // Requires fileinfo extension
    if ($mimeType === false) {
        // Fallback for common image types if fileinfo isn't available
        switch (strtolower($fileExtension)) {
            case 'jpg':
            case 'jpeg':
                $mimeType = 'image/jpeg';
                break;
            case 'png':
                $mimeType = 'image/png';
                break;
            case 'gif':
                $mimeType = 'image/gif';
                break;
            default:
                $mimeType = 'application/octet-stream'; // Generic binary
                break;
        }
    }

    return "data:{$mimeType};base64,{$base64EncodedData}";
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
                </div>

                <div class="field">
                    <label class="label" for="captchaFile">Arquivo Captcha:</label>
                    <div class="control">
                        <input class="input" name="captchaFile" value="<?= $captchaFile ?>">
                    </div>
                </div> -->

                <div class="columns">
                    <div class="column field">
                        <label class="label" for="captcha">Captcha:</label>
                        <div class="control">
                            <input class="input" type="text" id="captcha" name="captcha" value="<?= $captcha ?>" required>
                        </div>
                    </div>

                    <div class="column">
                        <img src="<?= base64( $captchaFile ); ?>" />
                    </div>
                </div>
                
                <div class="field">
                    <label class="label" for="selo_digital">Selo Digital:</label>
                    <div class="control">
                        <input class="input" type="text" id="selo_digital" name="selo_digital" value="<?= $selo ?>" required>
                    </div>
                </div>

                <button class="button is-link" type="submit">Consultar</button>
            </form>

            Resultado:
            <?= print_r( $resultado ); ?>
        </div>
    </body>
</html>
<?php
require __DIR__  . '/init-app.php';


$azureAppClientId   = getenv('AZURE_APP_CLIENT_ID', 'n/a');
$azureAppSecret     = getenv('AZURE_APP_CLIENT_SECRET', 'n/a');

$accessToken        = isset($_SESSION['ms_access_token'])   ? $_SESSION['ms_access_token']     : '';   // real use case get this from db
$refreshToken       = isset($_SESSION['ms_refresh_token'])  ? $_SESSION['ms_refresh_token']    : '';   // real use case get this from db
$tokenExpires       = isset($_SESSION['ms_token_expires'])  ? $_SESSION['ms_token_expires']    : 0;      // real use case get this from db
$email              = isset($_SESSION['ms_email'])          ? $_SESSION['ms_email']            : '';        // real use case get this from db
$host               = isset($_SERVER['HTTP_HOST'])          ? $_SERVER['HTTP_HOST']            : '';
$urlGetToken        = "http://{$host}/ms-oauth2callback.php";
$isNeedRequestToken = empty($accessToken);
$isExpiredToken     = $tokenExpires < time();

if ($isNeedRequestToken) {
    header('Location: ' . $urlGetToken, true, 302);
    return;
}
?>
<html>

<head>
    <title>Microsoft - OAuth2 IMAP example with  Mail</title>
</head>

<body>
    <h4><a href="/">Back to home page</a> to clear session</h4>
    <?php
    try {
        // Flow use refresh token           - https://tools.ietf.org/html/rfc6749#section-1.5
        // Guide use refresh token php sdk  - https://github.com/thephpleague/oauth2-google#refreshing-a-token
        //
        // How to test this case, (open file storage/sessions/sess_*) 
        // search 'ms_token_expires' and change value of 'ms_token_expires' to current unix epoch time,
        // get 'current unix epoch time' from here https://www.epochconverter.com (or copy value: 1607493960) and refresh browser
        if ($isExpiredToken) {
            // In real use case run this script 1 time at midnight, or interval 2 hour, 
            // access_token expired because  access token life time it's short 1h. 
            // So need use refresh_token to make new access_token 
            // (in prod env, don't need check token isExpired because we know token is expired)
            $grant      = new League\OAuth2\Client\Grant\RefreshToken();
            $provider   = new TheNetworg\OAuth2\Client\Provider\Azure([
                'clientId'                => $azureAppClientId,
                'clientSecret'            => $azureAppSecret,
                'redirectUri'             => $urlGetToken,
                'defaultEndPointVersion'  => TheNetworg\OAuth2\Client\Provider\Azure::ENDPOINT_VERSION_2_0
            ]);
            /** @var League\OAuth2\Client\Token\AccessTokenInterface */
            $newToken   = $provider->getAccessToken($grant, [
                'refresh_token' => $refreshToken
            ]);
            // echo "<pre>", print_r($newToken, true), "</pre>"; die(__FILE__.__LINE__);
            // real use case store those to db
            $_SESSION['ms_access_token']   = $newToken->getToken();
            $_SESSION['ms_refresh_token']  = $newToken->getRefreshToken();
            $_SESSION['ms_token_expires']  = $newToken->getExpires(); // Unix timestamp at which the access token expires
            $accessToken                = $newToken->getToken();
            echo "<p>used ms_refresh_token to renew access token success</p>";
        }

        showMicrosoftMailInboxByPop3($email, $accessToken);
        
        echo "<h4><a href='send-office365mail.php'>Click here</a> to test send mail</h4>";
    } catch (Exception $e) {
        $isUnauthorizeRequest = $e->getCode() === 401;
        if ($isUnauthorizeRequest) {
            echo "<h1>Access token timeout or invalid access token</h1>";
            echo "<h5>In prod env, need try renew access_token:</h5>";
            echo "<ol>
                <li>
                    Don't need user interactive, your app need use refresh_token to renew access_token. store new access_token into db and retry run task again.
                </li>
                <li>
                    Need user interactive on web UI, when refresh_token invalid (expired after 3 month, or user changed password,etc ). Step same first time user setup imap, smtp mail setting
                </li>
            </ol>";
        }
        
        echo "<p><a href='/'>Click here</a> to clean up session and start again</p>";

        echo "<h2>Full exception trace</h2>";
        echo "<pre>",$e, "</pre>";
    }
    
    ?>
</body>

</html>

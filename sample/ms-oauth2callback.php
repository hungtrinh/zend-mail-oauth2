<?php
/**
 * A redirect URI is the location where the Microsoft identity platform redirects a user's client and sends security tokens after authentication.
 * 
 * @see https://docs.microsoft.com/en-us/azure/active-directory/develop/quickstart-register-app#add-a-redirect-uri
 * 
 * @see https://github.com/microsoftgraph/msgraph-training-phpapp/tree/main/demo
 * 
 */
require __DIR__  . '/init-app.php';

$adAppClientId            = getenv('AZURE_APP_CLIENT_ID', 'n/a');
$clientSecret             = getenv('AZURE_APP_CLIENT_SECRET', 'n/a') ;

$host                     = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$path                     = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
// if you change this file name 'ms-oauth2callback.php' or change app domain contain this file please change azure app setting too (callback) @see https://go.microsoft.com/fwlink/?linkid=2083908
$urlHandlerOauth2Response = "http://{$host}{$path}"; //http://localhost:8080/ms-oauth2callback.php
$error                    = isset($_GET['error']) ? $_GET['error'] : null;
$code                     = isset($_GET['code']) ? $_GET['code'] : '';
$state                    = isset($_GET['state']) ? $_GET['state'] : '';
$oauth2state              = isset($_SESSION['ms_oauth2state']) ? $_SESSION['ms_oauth2state'] : '';
$isOauth2AuthError        = !empty($error);
$isRequestGetOauth2Code   = empty($code);
$isCSRFAttach             = empty($state) || ($state !== $oauth2state);

$provider = new TheNetworg\OAuth2\Client\Provider\Azure([
    'clientId'          => $adAppClientId,
    'clientSecret'      => $clientSecret,
    'redirectUri'       => $urlHandlerOauth2Response,
    'scope'            => [
        'email',
        'openid',
        'offline_access',
        'https://outlook.office.com/IMAP.AccessAsUser.All',
        'https://outlook.office.com/POP.AccessAsUser.All',
        'https://outlook.office.com/SMTP.Send'
    ],
    'defaultEndPointVersion'  => TheNetworg\OAuth2\Client\Provider\Azure::ENDPOINT_VERSION_2_0
]);

if ($isOauth2AuthError) {
    // Got an error, probably user denied access
    exit('Got error: ' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8'));
    return;
} 

if ($isRequestGetOauth2Code) {
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['ms_oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;
}

if ($isCSRFAttach) {
    unset($_SESSION['ms_oauth2state']);
    exit('Invalid state');
}

/** @var TheNetworg\OAuth2\Client\Token\AccessToken $token */
$token          = $provider->getAccessToken('authorization_code', [
    'code' => $code
]);

/** @var TheNetworg\OAuth2\Client\Provider\AzureResourceOwner $resourceOwner */
$resourceOwner = $provider->getResourceOwner($token);
$email         = $resourceOwner->claim('email');

$accessToken    = $token->getToken();
$refreshToken   = $token->getRefreshToken();
$tokenExpires   = $token->getExpires();


$_SESSION['ms_access_token']  = $accessToken;   // real use case store this to db
$_SESSION['ms_refresh_token'] = $refreshToken;  // real use case store this to db
$_SESSION['ms_token_expires'] = $tokenExpires;  // real use case store this to db. Unix timestamp at which the access token expires
$_SESSION['ms_email']         = $email;         // real use case store this to db

header('Location: ' . "http://$host/list-msmail.php", true, 302);

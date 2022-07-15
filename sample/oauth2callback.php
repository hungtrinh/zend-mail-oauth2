<?php
require __DIR__  . '/init-app.php';


$googleAppClientId        = getenv('GOOGLE_APP_CLIENT_ID', 'n/a');
$googleAppSecret          = getenv('GOOGLE_APP_CLIENT_SECRET', 'n/a');

$host                     = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$path                     = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$urlHandlerOauth2Response = "http://{$host}{$path}";
$error                    = isset($_GET['error']) ? $_GET['error'] : null;
$code                     = isset($_GET['code']) ? $_GET['code'] : '';
$state                    = isset($_GET['state']) ? $_GET['state'] : '';
$oauth2state              = isset($_SESSION['oauth2state']) ? $_SESSION['oauth2state'] : '';
$isOauth2AuthError        = !empty($error);
$isRequestGetOauth2Code   = empty($code);
$isCSRFAttach             = empty($state) || ($state !== $oauth2state);

$provider = new League\OAuth2\Client\Provider\Google([
    'clientId'     => $googleAppClientId,
    'clientSecret' => $googleAppSecret,
    'redirectUri'  => $urlHandlerOauth2Response,
    'accessType'   => 'offline',
    'scopes'       => [
        'https://www.googleapis.com/auth/userinfo.email', 
        'https://mail.google.com/',
    ]
]);

if ($isOauth2AuthError) {
    // Got an error, probably user denied access
    exit('Got error: ' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8'));
    return;
} 

if ($isRequestGetOauth2Code) {
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;
}

if ($isCSRFAttach) {
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
}

/** @var League\OAuth2\Client\Token\AccessToken $token */
$token = $provider->getAccessToken('authorization_code', [
    'code' => $code
]);

/** @var \League\OAuth2\Client\Provider\GoogleUser $resourceOwner */
$resourceOwner = $provider->getResourceOwner($token);

$accessToken    = $token->getToken();
$refreshToken   = $token->getRefreshToken();
$tokenExpires   = $token->getExpires();
$email          = $resourceOwner->getEmail();
$token->hasExpired();
$_SESSION['access_token']  = $accessToken;          // real use case store this to db. Access tokens: 4096 characters 
$_SESSION['refresh_token'] = $refreshToken;         // real use case store this to db. Refresh tokens: 512 characters
$_SESSION['token_expires'] = $tokenExpires;         // real use case store this to db. Unix timestamp at which the access token expires
$_SESSION['email']         = $email;                // real use case store this to db

header('Location: ' . "http://$host/list-gmail.php", true, 302);
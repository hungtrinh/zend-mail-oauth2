<?php
$appRootDir = realpath(dirname(__DIR__));
chdir($appRootDir);

date_default_timezone_set('Asia/Tokyo');
error_reporting(-1); 
ini_set('error_log', __DIR__ . '/storage/logs/php_error.log');
session_save_path( __DIR__ . '/storage/sessions');
ini_set("display_errors", "on");
session_start();


if (
    false === getenv('GOOGLE_APP_CLIENT_ID') && 
    false === getenv('AZURE_APP_CLIENT_ID') 
) {
    echo "
    <h1>Please provide env variable client id vs client secret of google (and or azure) app </h1>
    
    <h2>Close current php build-in server and run again</h2>
        <pre>$ cd ./sample</pre>
        <p>Test gmail, azure office 365 mail same time</p>
            <pre>$ GOOGLE_APP_CLIENT_ID='xx' GOOGLE_APP_CLIENT_SECRET='x1x1' AZURE_APP_CLIENT_ID='yyy' AZURE_APP_CLIENT_SECRET='y1y1' php -S 0:8080</pre>
        <p>Test gmail</p>
            <pre>$ GOOGLE_APP_CLIENT_ID='xx' GOOGLE_APP_CLIENT_SECRET='x1x1' php -S 0:8080</pre>
        <p>Test azure office 365 mail</p>
            <pre>$ AZURE_APP_CLIENT_ID='yyy' AZURE_APP_CLIENT_SECRET='y1y1' php -S 0:8080</pre>

    <h2>Guide create azure app</h4>
        <ol>
            <li><a target='_blank' href='https://docs.microsoft.com/en-us/azure/active-directory/develop/quickstart-register-app#register-an-application'>Register an application</a></li>
            (At sep 8 input redirect input url value 'http://localhost:8080/ms-oauth2callback.php')
            <li><a target='_blank' href='https://docs.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth#register-your-application'>Setting Access Token Scope:</a> 'IMAP.AccessAsUser.All', 'POP.AccessAsUser.All', 'SMTP.Send'</li>
            <li>
                Copy follow info: 
                <ul><li>AZURE_APP_CLIENT_ID = '{Application (client) ID}'</li><li>AZURE_APP_CLIENT_SECRET</li></ul>
            </li>
        </ol>
    
    <h2>Guide create google app</h4>
        <p><a target='_blank' href='https://www.emailarchitect.net/easendmail/sdk/html/object_oauth.htm'>Gmail Authenticate an IMAP, POP or SMTP connection using OAuth</a></p>
        <p>Please select Application type 'Web application', after that add URI 'http://localhost:8080/oauth2callback.php' at Authorised redirect URIs</p>
        <ul>
            <li>GOOGLE_APP_CLIENT_ID = '{Oauth client id}'</li>
            <li>GOOGLE_APP_CLIENT_SECRET = '{Oauth client secret}'</li>
        </ul>
    ";
    exit(0);
}

require_once 'vendor/autoload.php';

/**
 * Tries to login to IMAP and show inbox stats.
 *
 * @param string $email 
 * @param string $accessToken 
 * @return void 
 */
function showGmailInbox( $email, $accessToken)
{
    $imap = new Zend_Mail_Protocol_ImapOauth2('imap.gmail.com', '993', true);
    if (!$imap->login($email, $accessToken)) {
        echo "<h1>Imap login failed</h1>";
        echo "<ol><li>email: $email</li><li>access token: $accessToken</li></ol>";
        echo '<h3>User don\'t grant privileges  access to gmail or invalid access token</h3>';
        echo "<p><a href='/'>Click here</a> to back home page (will destroy app session to clean up app state)</p>";
        return;
    }
    echo "<h1>IMAP - Mail inbox of $email</h1>";
    showInbox($imap);
}

/**
 * Tries to login to IMAP and show inbox stats.
 *
 * @param string $mailSender    mail sender
 * @param string $accessToken   oauth2 access token
 * @param string $mailTo        mail receive
 * @return void 
 */
function sendGmail( $mailSender, $accessToken, $mailTo)
{
    $transport = new Zend_Mail_Transport_Smtp('smtp.gmail.com', [
        'ssl'               => 'tls',
        'port'              => 587,
        'auth'              => 'oauth2', //factory will create instance of Zend_Mail_Protocol_Smtp_Auth_Oauth2
        'email'             => $mailSender,
        'accessToken'       => $accessToken,
    ]);

    Zend_Mail::setDefaultTransport($transport);
    Zend_Mail::setDefaultFrom($mailSender, 'sender FullName');
    Zend_Mail::setDefaultReplyTo($mailSender,'sender FullName');

    $mail = new Zend_Mail();
    $date = date('Y-m-d H:i:s P');
    $mail
        ->addTo($mailTo)
        ->setSubject("Test xoauh2 - smtp $date")
        ->setBodyText("$date : smtp + xoauth2 send mail test");

    $mail->send();

    echo "<p>Send first mail success to $mailTo</p>";

    $secondMail = new Zend_Mail();
    $secondMail->addTo($mailTo)
        ->setSubject("Test xoauh2 - smtp - second message $date")
        ->setBodyText("$date : smtp + xoauth2 send - second message");
    $secondMail->send();

    echo "<p>Send second mail success to $mailTo</p>";
}

/**
 * Tries to login to IMAP and show inbox stats.
 *
 * @param string $email 
 * @param string $accessToken 
 * @return void 
 */
function showMicrosoftMailInbox( $email, $accessToken)
{
    $imap = new Zend_Mail_Protocol_ImapOauth2('outlook.office365.com', '993', true);
    if (!$imap->login($email, $accessToken)) {
        echo "<h1>Imap login failed</h1>";
        echo "<ol><li>email: $email</li><li>access token: $accessToken</li></ol>";
        echo '<h3>User don\'t grant privileges  access to gmail or invalid access token</h3>';
        echo "<p><a href='/'>Click here</a> to back home page (will destroy app session to clean up app state)</p>";
        return;
    }
    echo "<h1>IMAP - Mail inbox of $email</h1>";
    showInbox($imap);
}

/**
 * Tries to login to IMAP and show inbox stats.
 *
 * @param string $mailSender    mail sender
 * @param string $accessToken   oauth2 access token
 * @param string $mailTo        mail receive
 * @return void 
 */
function sendOffice365Mail( $mailSender, $accessToken, $mailTo)
{
    $transport = new Zend_Mail_Transport_Smtp('smtp.office365.com', [
        'ssl'               => 'tls',
        'port'              => 587,
        'auth'              => 'oauth2', //Zend_Mail_Protocol_Smtp_Auth_Oauth2
        'email'             => $mailSender,
        'accessToken'       => $accessToken,
    ]);
    
    Zend_Mail::setDefaultFrom($mailSender, 'Trinh Duc Hung');
    Zend_Mail::setDefaultReplyTo($mailSender,'Trinh Duc Hung');

    $mail = new Zend_Mail();
    $date = date('Y-m-d H:i:s P');
    $mail
        ->addTo($mailTo)
        ->setSubject("Test xoauh2 - smtp $date")
        ->setBodyText("$date : smtp + xoauth2 send mail test");

    $mail->send($transport);

    echo "<p>Send first mail success to $mailTo</p>";

    $secondMail = new Zend_Mail();
    $secondMail->addTo($mailTo)
        ->setSubject("Test xoauh2 - smtp - second message $date")
        ->setBodyText("$date : smtp + xoauth2 send - second message");
    $secondMail->send($transport);

    echo "<p>Send second mail success to $mailTo</p>";
}

/**
 * Given an open and authenticated IMAP connection, displays some basic info
 * about the INBOX folder.
 * 
 * @param Zend_Mail_Protocol_Imap $imap 
 * @return void 
 */
function showInbox($imap)
{
    /**
     * Print the INBOX message count and the subject of all messages
     * in the INBOX
     */
    $mail = new Zend_Mail_Storage_Imap($imap);
    echo '<h2>Total messages: ' . $mail->countMessages() . "</h2>\n";

    $index = 0;
    $max = 15;
    echo '<ol>';
    foreach($mail as $messageNum => $message) {
        echo '<li>' . htmlentities($message->subject) . "</li>\n";
        if ($max === ++$index) break;
    }
    echo '</ol>';
}

/**
 * Tries to login to IMAP and show inbox stats.
 *
 * @param string $email 
 * @param string $accessToken 
 * @return void 
 */
function showGmailInboxByPop3( $email, $accessToken)
{
    $pop3Protocol = new Zend_Mail_Protocol_Pop3Oauth2('pop.gmail.com', '995', $ssl=true);
    if (!$pop3Protocol->login($email, $accessToken)) {
        echo "<h1>Pop3 login failed</h1>";
        echo "<ol><li>email: $email</li><li>access token: $accessToken</li></ol>";
        echo '<h3>User don\'t grant privileges  access to gmail or invalid access token</h3>';
        echo "<p><a href='/'>Click here</a> to back home page (will destroy app session to clean up app state)</p>";
        return;
    }
    echo "<h1>POP3 - Mail inbox of $email</h1>";
    
    $mail = new Zend_Mail_Storage_Pop3($pop3Protocol);
    echo '<h2>Total messages: ' . $mail->countMessages() . "</h2>\n";

    $index = 0;
    $max = 15;
    echo '<ol>';
    foreach($mail as $messageNum => $message) {
        echo '<li>' . htmlentities($message->subject) . "</li>\n";
        if ($max === ++$index) break;
    }
    echo '</ol>';
}

/**
 * Tries to login to IMAP and show inbox stats.
 *
 * @param string $email 
 * @param string $accessToken 
 * @return void 
 */
function showMicrosoftMailInboxByPop3( $email, $accessToken)
{
    $pop3Protocol = new Zend_Mail_Protocol_Pop3Oauth2('outlook.office365.com', '995', $ssl=true);
    if (!$pop3Protocol->login($email, $accessToken)) {
        echo "<h1>Pop3 login failed</h1>";
        echo "<ol><li>email: $email</li><li>access token: $accessToken</li></ol>";
        echo '<h3>User don\'t grant privileges  access to gmail or invalid access token</h3>';
        echo "<p><a href='/'>Click here</a> to back home page (will destroy app session to clean up app state)</p>";
        return;
    }
    echo "<h1>POP3 - Mail inbox of $email</h1>";
    
    $mail = new Zend_Mail_Storage_Pop3($pop3Protocol);
    echo '<h2>Total messages: ' . $mail->countMessages() . "</h2>\n";

    $index = 0;
    $max = 15;
    echo '<ol>';
    foreach($mail as $messageNum => $message) {
        echo '<li>' . htmlentities($message->subject) . "</li>\n";
        if ($max === ++$index) break;
    }
    echo '</ol>';
}
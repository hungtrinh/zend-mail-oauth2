
# zend-mail-oauth2 (Zend Framework 1)

This package help project use zend-mail (zend Framework 1) work with oauth2 authentication (XOAUTH2)

PHP 5.3-8.x compatible

Tested on Gmail, Azure AD:

- [Gmail - Authenticate an IMAP, POP or SMTP connection using OAuth](https://developers.google.com/gmail/imap/xoauth2-protocol)

- [Azure AD - Authenticate an IMAP, POP or SMTP connection using OAuth](https://docs.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth)

**Table of Contents**
<!-- TOC -->

- [Installation](#installation)
- [Usage](#usage)
  - [Code examples working with gmail (azure mail)](#code-examples-working-with-gmail-azure-mail)
  - [Retrieve mail over IMAP](#retrieve-mail-over-imap)
  - [Retrieve mail over POP3](#retrieve-mail-over-pop3)
  - [Send mail over SMTP](#send-mail-over-smtp)
  - [How to get $oauth2AccessToken](#how-to-get-oauth2accesstoken)

<!-- /TOC -->

## Installation

```bash
composer require hungtrinh/zend-mail-oauth2
```

## Usage

### Code examples working with gmail (azure mail)

- Guide create google web app, azure web app (provide client_id, secret_id) for your webapp to obtain oauth2 access_token, refresh_token.
- Authenticate with oauth2 (xoauth2 protocol): email + access_token
- Retrieve 15 over IMAP
- Retrieve 15 over POP3
- Send email over SMTP

```bash
git checkout https://github.com/hungtrinh/zend-mail-oauth2.git
cd zend-mail-oauth2
composer install
cd zend-mail-oauth2/sample
php -S 0:8080
```

Open browser with url <http://localhost:8080>.

### Retrieve mail over IMAP

```php
$email              = 'your_gmail@gmail.com';
$oauth2AccessToken  = 'oauth2_access_token of your_gmail@gmail.com';
$imapGmailHost      = 'imap.gmail.com';
$imap365Host        = 'outlook.office365.com'; //use this host if working with office365
$imapProtocol       = new Zend_Mail_Protocol_ImapOauth2($imapGmailHost, $port = '993', $ssl = true);

if (!$imapProtocol->login($email, $oauth2AccessToken)) {
     throw new \DomainException('Invalid email or oauth2 access token expired');
}
$index  = 0;
$max    = 10;
$mail   = new Zend_Mail_Storage_Imap($imapProtocol);
echo $mail->countMessages();
foreach($mail as $messageNum => $message) {
    echo $message->subject;
    if ($max === ++$index) {
        break;
    }
}
```

### Retrieve mail over POP3

```php
$email               = 'your_gmail@gmail.com';
$$oauth2AccessToken  = 'oauth2_access_token of your_gmail@gmail.com';
$popGmailHost        = 'pop.gmail.com';
$pop365Host          = 'outlook.office365.com'; //use this host if working with office365
$pop3Protocol        = new Zend_Mail_Protocol_Pop3Oauth2($popGmailHost, $port = '995', $ssl = true);

if (!$pop3Protocol->login($email, $oauth2AccessToken)) {
     throw new \DomainException('Invalid email or oauth2 access token expired');
}
$index  = 0;
$max    = 10;
$mail   = new Zend_Mail_Storage_Pop3($pop3Protocol);
echo $mail->countMessages();
foreach($mail as $messageNum => $message) {
    echo $message->subject;
    if ($max === ++$index) {
        break;
    }
}
```

### Send mail over SMTP

```php
$office365Host      = 'smtp.office365.com'; //use this host if working with office365
$gmailHost          = 'smtp.gmail.com';
$mailSender         = 'mail_sender@gmail.com';
$oauth2AccessToken  = 'access token of mail_sender@gmail.com';
$transport          = new Zend_Mail_Transport_Smtp($gmailHost, [
    'ssl'           => 'tls',
    'port'          => 587,
    'auth'          => 'oauth2', // Zend_Mail_Protocol_Smtp_Auth_Oauth2
    'email'         => $mailSender,
    'accessToken'   => $oauth2AccessToken,
]);

Zend_Mail::setDefaultTransport($transport);
Zend_Mail::setDefaultFrom($mailSender, 'sender fullname');
Zend_Mail::setDefaultReplyTo($mailSender,'sender fullname');

$mail = new Zend_Mail();
$date = date('Y-m-d H:i:s P');
$mail->addTo($mailTo)
    ->setSubject("Test xoauh2 - smtp $date")
    ->setBodyText("$date : smtp + xoauth2 send mail test");

$mail->send();
```

### How to get $oauth2AccessToken

Your current web app need add functional to maintain access_token

- public uri handler oauth2 response from google / azure / other web app <http://your_app_domainoauth2-callback.php> (persist access_token, refresh_token, expire_time to db or other persist storage)
- access_token have short lifetime (1h) so you can (optional)
  - setting cron-job run every 50 minute use refresh_token to get new access_token, after that persist access_token, refresh_token, expire_time to db. other process will use access_token to send or retrieve email
  - Or in process send/retrieve email use expire_time to verify if access_token is expired then use refresh_token to get new access_token

Full guide please see section [Code examples working with gmail (azure mail)](#code-examples-working-with-gmail-azure-mail)

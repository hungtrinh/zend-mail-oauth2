<?php
require __DIR__ . "/init-app.php";
session_unset();
?>
<!doctype html>
<html lang="en">
  <head>
    <title>Imap + Xoauth2 sample</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  </head>
  <body>
    <h4>Sample integrate XOauth2 authentication with IMAP, SMTP</h4>

    <ol>
        <li> <a href="list-gmail.php"> IMAP - GMAIL - Listintg top 15 your email</a></li>
        <li> <a href="list-msmail.php">IMAP - AZURE - Listintg top 15 your email</a></li>
    </ol>  
    
  </body>
</html>
<?php

define('IN_DEV', 0);
$_FORBIDDEN = <<< EOF
      <html><head><title>Error</title></head><body>
         <h1>Ошибка 403 (Forbidden, доступ запрещен)</h1>
         Доступ к файлу/папке ограничен. <a href="javascript:history.go(-1)">Вернитесь</a> на предыдущую страницу.
      </body></html>
EOF;
//ini_set('display_errors', 'Off');

require_once realpath('.') . '/config.php';

require_once realpath('.') . '/mysql.php';
$db = new TSimpleDB( $_DB, true );

// Неделя #3 Безопасность
require_once realpath('.') . '/classes.php';
$user = new TUser( $db, $_COOKIE["name"] );
//print "Hello {$user->GetName()}<br/>";
$user->RegisterNew( 'Julia', 'anonymous@gmail.com', 'Trmint$N4n' );
//$mess = new TMessages( $db, $_COOKIE["name"] );
//$mess = new TMessages( $db, 'Hiori' );
//if( $mess->Select( -1, 2 ) ) print_r( $mess[1] );
//print( $mess->SomeAboutAPI( 2 ) );

// Неделя #4 Внедрение сторонних библиотек.
//require_once '/usr/ports/devel/php-composer/vendor/autoload.php';
//$transport = (new Swift_SmtpTransport('smtp.google.com', 25))
//    ->setUsername('atomuli.yadalato.mail')
//    ->setPassword('SecretPa$$w0rd')
//;
//$mailer = new Swift_Mailer( $transport );
//$message = ( new Swift_Message( 'Проверочка' ) )
//    ->setFrom(['atomuli.yadalato.mail@gmail.com' => 'Атомули Ядалато'])
//    ->setTo(['hiorirm@gmail.com' => 'Hiori'])
//    ->setBody('какой-то текст')
//;
//$result = $mailer->send( $message );

require_once realpath('.') . '/functions.php';
WatermarkImage('cover.jpg','stamp.png','new_image_name.jpg',200);

//$mess->NewMessage( 'Hiori', 'Какой-то текст', array( '/usr/htdocs/3.jpg', '/usr/htdocs/4.png' ) );

?>
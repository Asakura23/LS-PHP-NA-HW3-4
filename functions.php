<?php
function WatermarkImage( $target, $wtrmrk_file, $newcopy, $newsize ) {
    // Читаем изображение марки
    $watermark = imagecreatefrompng( $wtrmrk_file );

    // Читаем изображение на которое будем добавлять
    $img = imagecreatefromjpeg( $target );
    // Его размеры
    $img_w = imagesx( $img );
    $img_h = imagesy( $img );
    $wtrmrk_w = imagesx( $watermark );
    $wtrmrk_h = imagesy( $watermark );

    // Считаем координаты центра
    $dst_x = ( $img_w / 2 ) - ( $wtrmrk_w / 2 );
    $dst_y = ( $img_h / 2 ) - ( $wtrmrk_h / 2 );

    // Добавляем марку
    imagecopy( $img, $watermark, $dst_x, $dst_y, 0, 0, $wtrmrk_w, $wtrmrk_h );
    // Пропорционально изменяем размер до 200
    $ret = imagecreatetruecolor( $newsize, $newsize / ( $img_w / $img_h ) );
    imagecopyresized( $ret, $img, 0, 0, 0, 0, $newsize, $newsize / ( $img_w / $img_h ), $img_w, $img_h );

    // Сохраняем в файл
    imagejpeg( $ret, $newcopy, 100 );

    // Чистим память
    imagedestroy( $img );
    imagedestroy( $watermark );
}

?>
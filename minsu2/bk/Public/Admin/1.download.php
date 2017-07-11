<?php
$file= isset($_GET['down']) ? $_GET['down'] : "./huiju.zip";
$daxiao = filesize($file);
header("Content-Type:application/force-download");
header("Content-Type:application/download");
//二进制文件
header("Content-Type:application/octet-stream");
//header("Content-Type:application/msword");
//获得文件名
$fileName = basename($file);
//下载窗口中显示的文件名
header("Content-Disposition:attachment;filename={$fileName}");
header("Content-Transfer-Encoding: binary");
//不缓存页面
header("Pragma: no-cache"); 
//不在页面输出打开，直接下载
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
//ob_clean();
//flush();
//文件尺寸单位
header("Accept-Ranges:bytes");
//文件大小
header("Accept-Length:". $daxiao);
//读出文件内容
readfile($file);
 ?>

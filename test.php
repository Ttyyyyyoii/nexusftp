<?php
$c = ftp_connect('127.0.0.1');
ftp_login($c, 'jona', 'jona');
ftp_pasv($c, true);
error_clear_last();
var_dump(ftp_mkdir($c, '/test_dir_123'));
print_r(error_get_last());
var_dump(ftp_delete($c, '/htdocs/index.html'));
print_r(error_get_last());
?>

<?php
include('var.global.php');

echo new_safe_slash("end of the\ day'\n");
echo new_safe_slash("end of the\ day'\n");
echo new_safe_slash("end of the\\\ day\\\\\''\n");

?>

<?php
require_once("Parsedown.php");
$Parsedown = new Parsedown();


echo $Parsedown->text(file_get_contents('README.md'));

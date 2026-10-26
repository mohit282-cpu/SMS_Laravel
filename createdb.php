<?php
try{$pdo=new PDO('mysql:host=127.0.0.1;port=3306','root','');$pdo->exec('CREATE DATABASE IF NOT EXISTS sms_laravel');echo 'done';}catch(Exception $e){echo $e->getMessage();}
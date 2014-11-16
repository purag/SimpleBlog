<?php
  // create class
  class db {
    public $pdo;
  
    public function connect(){
      // database credentials
      $server = ' ';
      $name = ' ';
      $username = ' ';
      $password = ' ';
      
      // connect
      $this->pdo = new PDO("mysql:host=$server;dbname=$name",$username,$password);
    }
  }
  
  // assign class to variable
  $db = new db();
  $db->connect();
  $dbh = $db->pdo;
?>
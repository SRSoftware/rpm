<?php

require_once('db.php'); // set $database_host, $database_port, $database_name, and $database_pass in db.php


function dbConnection(){
        global $database_host, $database_port, $database_name, $database_pass;
        $conn=mysql_connect($database_host.":".$database_port,$database_name,$database_pass);
        if ($conn === false) return false;
        mysql_select_db($database_name,$conn);
        mysql_query("CREATE TABLE users (uid INT NOT NULL PRIMARY KEY AUTO_INCREMENT,name TEXT)");
        mysql_query("CREATE TABLE games (gid INT NOT NULL PRIMARY KEY AUTO_INCREMENT,date DATE,uid INT, comments TEXT)");
        mysql_query("CREATE TABLE user_games (uid INT NOT NULL, gid INT NOT NULL, PRIMARY KEY(uid,gid))");

        return true;
}

function warn($message){
	print $message;
	die(-1);
}

function head(){
?><!DOCTYPE html>
  <html lang="de">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
      <title>Rock Paper Mensa</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <link href="bootstrap.min.css" rel="stylesheet" media="screen" />
      <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
      <!--[if lt IE 9]>
          <script src="bootstrap/js/html5shiv.js"></script>
          <script src="bootstrap/js/respond.min.js"></script>
          <![endif]-->
    </head>
    <body>
    
      <div class="page-header">
	<h1>Rock Paper Mensa</h1>
      </div>

      <script type="text/javascript" src="jquery/jquery-2.0.3.min.js"></script>
      <script type="text/javascript" src="form.js"></script>
<?}

function foot(){ ?>
    </body>
  </html>
<?php
}

function form(){
?><form class="form" role="form" method="POST" action=".">
  <button type="submit" class="btn" disabled>Submit</button>

  <div class="row" id="template">
    <div class="col-lg-7">
      <input placeholder="Spielername" style="width: 60%"
	     onkeyup="onName(this)" onchange="onName(this)" />
    </div>
    <div class="col-lg-2">
      <input name="played[]" type="checkbox" disabled
	     onchange="onCheckbox(this)" />
    </div>
    <div class="col-lg-2">
      <input name="lost" type="radio" disabled
	     onchange="onRadio(this)"/>
    </div>
  </div>
  
  <div class="row">
    <div class="col-lg-7">Hans Wurst</div>
    <div class="col-lg-2">
      <input name="played[]" type="checkbox" value="Hans Wurst"
	     onchange="onCheckbox(this)" />
    </div>
    <div class="col-lg-2">
      <input name="lost" type="radio" value="Hans Wurst" disabled
	     onchange="onRadio(this)"/>
    </div>
  </div>
</form>
<?php }


function resultsStored(){
	if (!isset($_POST['lost'])) return false;
	
	print "Content of \$_POST:<pre><code>\n";
	print_r($_POST);
	print "</code></pre>";

	return true;
	// store results here, don't forget to use mysql_real_escape_string for text arguments
}

head();

if (!dbConnection()) warnDB();

if (resultsStored()){
	print "Results stored in database.";
} else {
	print form();
}

foot();


?>

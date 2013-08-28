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

function head(){ ?>
        <!DOCTYPE html>
        <html lang="en">
                <head> 
                        <title>Bootstrap 101 Template</title>
                        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                        <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" media="screen" />
                        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
                        <!--[if lt IE 9]>
                                <script src="bootstrap/js/html5shiv.js"></script>
                                <script src="bootstrap/js/respond.min.js"></script>
                        <![endif]-->
                </head>
        <body>
        <h1>Rock Paper Mensa</h1>
<?}

function foot(){ ?>
	// footer html/closure goes here
<?php }

function form(){ ?>
	// web form goes here
<?php }


function resultsStored(){
	if (!isset($_POST['some_necessary_input_field'])) return false;
	
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

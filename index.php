<?php

if (! @include_once('db.php')){ // set $database_host, $database_port, $database_name, and $database_pass in db.php
	warn("Crap. I'm not able to find my database setting. Can you help me with a db.php file, please?");
}


function dbConnection(){
        global $database_host, $database_port, $database_name, $database_pass;

	$mysqli = new mysqli($database_host, $database_name, $database_pass, $database_name,$database_port);
	if ($mysqli->connect_errno) return false;

        $mysqli->query("CREATE TABLE users (uid INT NOT NULL PRIMARY KEY AUTO_INCREMENT,name TEXT)");
        $mysqli->query("CREATE TABLE games (gid INT NOT NULL PRIMARY KEY AUTO_INCREMENT,date DATE,uid INT, comments TEXT)");
        $mysqli->query("CREATE TABLE user_games (uid INT NOT NULL, gid INT NOT NULL, PRIMARY KEY(uid,gid))");

        return $mysqli;
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
	global $mysqli;

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
 
<?php
  $players=$mysqli->query("
	SELECT ui,name
	FROM users NATURAL JOIN user_games
	GROUP BY uid
	ORDER BY COUNT(gid) DESC, gid DESC"); // alter here to order with respect to number of games played

  while ($player= $players->fetch_assoc()){ ?> 
  <div class="row">
    <div class="col-lg-7"><?php print $player['name']; ?></div>
    <div class="col-lg-2">
<?php printf(
      '<input name="played[]" type="checkbox" value="%d" onchange="onCheckbox(this)" />',
						 $player['uid']); ?>
    </div>
    <div class="col-lg-2">
<?php printf(
      '<input name="lost" type="radio" value="%d" disabled onchange="onRadio(this)"/>',
					$player['uid']); ?>
    </div>
  </div>
<?php   } // end while
?>
</form>
<?php

} // function

function createPlayer($name){
	global $mysqli;
	$query=$mysqli->prepare("INSERT INTO users VALUES (0, ?)");
	$query->bind_param("s",$name);
	$query->execute();
	$query->close();
	return $mysqli->insert_id;
}

function createNewPlayers(){
	$played=$_POST['played'];
	$changed=false;
	foreach ($played as $number => $player){
		if (!is_numeric($player)){
			$id=createPlayer($player);
			$played[$number]=$id;
			$changed=true;
			if ($_POST['lost']==$player) $_POST['lost']=$id;
		}
	}
	if ($changed) $_POST['played']=$played;
}

function createGame(){
  global $mysqli;

  $loserId=$_POST['lost'];
  $comment=null;
  if (isset($_POST['comment'])) $comment=$_POST['comment'];

  $query=$mysqli->prepare("INSERT INTO games (gid, date, uid, comments) VALUES (0, NOW(), ?, ?)");
  $query->bind_param('is',$loserId,$comment);
  $query->execute();
  $query->close();
  return $mysqli->insert_id;
}

function assignPlayers($game){
  global $mysqli;

  $query=$mysqli->prepare("INSERT INTO user_games (uid, gid) VALUES (?,?)");
  foreach ($_POST['played'] as $player){
	$query->bind_param('ii',$player,$game);
	$query->execute();
  }
  $query->close();
}

function resultsStored(){
	if (!isset($_POST['lost'])) return false;
	
	createNewPlayers();
	$game=createGame();
	assignPlayers($game);

/*	print "Content of \$_POST:<pre><code>\n";
	print_r($_POST);
	print "</code></pre>"; */


	return true;
}

function simpleStat(){
	global $mysqli;
	$res=$mysqli->query("SELECT COUNT(gid) AS games,name FROM users NATURAL JOIN games GROUP BY name");
	?> <p><br/><br/> <?php
	while ($row=$res->fetch_assoc()){
		print $row['name']." lost ".$row['games']." games, so far.<br/>";
	}
       ?> </p><?php
}

head();

$mysqli=dbConnection();

if ($mysqli===false) warn("Hooray! No database in sight. I'm going to sleep now.");

if (resultsStored()){
	print "Results stored in database.<br/>";
} else {
	print form();
}

//simpleStat();

foot();


?>

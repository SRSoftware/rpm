<?php

if (! @include_once('db.php')){ // set $database_host, $database_port, $database_name, and $database_pass in db.php
	warn("Crap. I'm not able to find my database setting. Can you help me with a db.php file, please?");
}


function dbConnection(){
        global $database_host, $database_port, $database_name, $database_pass;

	$mysqli = new mysqli($database_host, $database_name, $database_pass, $database_name,$database_port);
	if ($mysqli->connect_errno) return false;

        $mysqli->query('CREATE TABLE users (uid INT NOT NULL PRIMARY KEY AUTO_INCREMENT,name TEXT)');
        $mysqli->query('CREATE TABLE games (gid INT NOT NULL PRIMARY KEY AUTO_INCREMENT,date DATE,uid INT, comments TEXT)');
        $mysqli->query('CREATE TABLE user_games (uid INT NOT NULL, gid INT NOT NULL, PRIMARY KEY(uid,gid))');
	$mysqli->query('CREATE VIEW participation AS
			  SELECT name,count(uid) AS count,uid
                          FROM user_games NATURAL JOIN users
                          GROUP BY uid
                          ORDER BY count DESC');
	$mysqli->query('CREATE VIEW losses AS
			  SELECT name,count(gid) AS losses,users.uid
                          FROM games RIGHT JOIN users ON games.uid=users.uid
                          GROUP BY users.uid
                          ORDER BY losses DESC');
        return $mysqli;
}

function warn($message){ 
	print $message;
	foot();
	die(-1);
}

function head(){
?><!DOCTYPE html>
  <html lang="de">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
      <title>Rock Paper Mensa</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <link href="style.css" rel="stylesheet" media="screen" />
      <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
      <!--[if lt IE 9]>
          <script src="bootstrap/js/html5shiv.js"></script>
          <script src="bootstrap/js/respond.min.js"></script>
          <![endif]-->
    </head>
    <body>
    
      <script type="text/javascript" src="jquery/jquery-2.0.3.min.js"></script>
      <script type="text/javascript" src="jquery/tablesorter.min.js"></script>
      <script type="text/javascript" src="form.js"></script>
<?}

function foot(){ ?>
      <div class="ad">Created with PHP, MySQL, JavaScript and CSS. Sources can be downloaded from <a href="https://github.com/StephanRichter/rpm">GitHub</a>.</div>
    </body>
  </html>
<?php
}

function form(){
	global $mysqli;
	$action='.';
        if (isset($_GET['stat'])){
          $action='.?stat='.$_GET['stat'];
	}
	

print '<form class="form" role="form" method="POST" action="'.$action.'">'; ?>
  <div class="headbutton">
    <h1>Rock Paper Mensa</h1>
    <button type="submit" class="btn" disabled>Submit</button>
  </div>

  <div class="row" id="template">
    <div class="col-lg-2">
      <input name="played[]" type="checkbox" disabled
	     onchange="onCheckbox(this)" />
    </div>
    <div class="col-lg-2">
      <input name="lost" type="radio" disabled
	     onchange="onRadio(this)"/>
    </div>
    <div class="col-lg-7">
      <input placeholder="Spielername" style="width: 60%"
             onkeyup="onName(this)" onchange="onName(this)" />
    </div>
  </div>
 
<?php
  $players=$mysqli->query('
	SELECT uid,name
	FROM users NATURAL JOIN user_games
	GROUP BY uid
	ORDER BY COUNT(gid) DESC, gid DESC'); // order by frequence and last played

  while ($player= $players->fetch_assoc()){ ?>
  <div class="row">
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
    <div class="col-lg-7"><?php print $player['name']; ?></div>

  </div>
<?php   } // end while
?>
</form>
<?php

} // function

function invalidQuery($query){
	warn('was not able to execute query '.$query);
}

function getOrCreatePlayer($name){
	global $mysqli;

	// lookup if user name exists
	$query='SELECT uid FROM users WHERE name = binary ?'; // binary necessary to distinguish between JOE and joe
	$statement=$mysqli->prepare($query);
	$statement->bind_param('s',$name);
	if (!$statement->execute()) invalidQuery($query);
	$statement->bind_result($uid);
	if ($statement->fetch()) { // if we get a result: return uid
		$statement->close();
		return $uid;
	} 

	// player not existing, yet: create!
	$query=$mysqli->prepare('INSERT INTO users VALUES (0, ?)');
	$query->bind_param('s',$name);
	$query->execute();
	$query->close();
	return $mysqli->insert_id;
}

function getPlayerName($id){
	global $mysqli;
	$query='SELECT name FROM users WHERE uid=?';
	$statement=$mysqli->prepare($query);
	$statement->bind_param('i',$id);
	if (!$statement->execute()) invalidQuery($query);
        $statement->bind_result($name);
        if ($statement->fetch()) { // if we get a result: return uid
                $statement->close();
                return $name;
        }
	return "unknown";
}

function createNewPlayers(){
	$played=$_POST['played'];
	$changed=false;
	$used_ids = array();
	foreach ($played as $index => $id){
		if (!is_numeric($id)){
			$id=getOrCreatePlayer($id);
			$played[$index]=$id;
			$changed=true;
			if ($_POST['lost']==$id) $_POST['lost']=$id;
		}		
		
		if (isset($used_ids[$id])) warn('Hey, player '.getPlayerName($id)." seems to be a bit schizophrenic. He's participating manifoldly. I can't stand this.");
		$used_ids[$id]=true;
	}
	if ($changed) $_POST['played']=$played;
	return true;
}

function createGame(){
  global $mysqli;

  $loserId=$_POST['lost'];
  $comment=null;
  if (isset($_POST['comment'])) $comment=$_POST['comment'];

  $query=$mysqli->prepare('INSERT INTO games (gid, date, uid, comments) VALUES (0, NOW(), ?, ?)');
  $query->bind_param('is',$loserId,$comment);
  $query->execute();
  $query->close();
  return $mysqli->insert_id;
}

function assignPlayers($game){
  global $mysqli;

  $query=$mysqli->prepare('INSERT INTO user_games (uid, gid) VALUES (?,?)');
  foreach ($_POST['played'] as $player){
	$query->bind_param('ii',$player,$game);
	$query->execute();
  }
  $query->close();
}

function resultsStored(){
	if (!isset($_POST['lost'])) return false;
	
	if (!createNewPlayers())
	  return false;
	$game=createGame();
	assignPlayers($game);

/*	print "Content of \$_POST:<pre><code>\n";
	print_r($_POST);
	print "</code></pre>"; */


	return true;
}

function simpleStat(){
	global $mysqli;
	$res=$mysqli->query('SELECT name,losses,count FROM losses NATURAL JOIN participation ORDER BY losses/count DESC');
	?> <p><br/><br/> <?php
	while ($row=$res->fetch_assoc()){
		print $row['name'].' lost '.$row['losses'].' of '.$row['count'].' game';
		if ($row['count']!=1) print 's';
		print ' ('.($row['losses']*100/$row['count']).'%), so far.<br/>';
	}
       ?> </p><?php
}

function nerdStat() {
  global $mysqli;

  // get the number of players per game for all games
  $res = $mysqli->query('SELECT gid, COUNT(uid) AS size FROM user_games GROUP BY gid');
  $size = array();
  while ($row = $res->fetch_assoc()){
    $size[$row['gid']] = $row['size'];
  }

  // get player names
  $res = $mysqli->query('SELECT uid, name FROM users');
  $name = array();
  while ($row = $res->fetch_assoc()){
    $name[$row['uid']] = $row['name'];
  }

  // iterate over all game participations
  $res = $mysqli->query('SELECT gid, uid FROM user_games');
  $userDist = array();
  while ($row = $res->fetch_assoc()) {
    if (!isset($userDist[$row['uid']])) {
       $userDist[$row['uid']] = array(0 => 1.0);
    }
    $dist = $userDist[$row['uid']];
    $newdist = array();
    foreach ($dist as $l => $p) {
      if (!isset($newdist[$l]  )) $newdist[$l  ] = 0.0;
      if (!isset($newdist[$l+1])) $newdist[$l+1] = 0.0;
      $s = $size[$row['gid']];
      $newdist[$l  ] = $newdist[$l  ] + $dist[$l] * ($s-1) / $s;
      $newdist[$l+1] = $newdist[$l+1] + $dist[$l]          / $s;
    }
    $userDist[$row['uid']] = $newdist;
  }


/*******************/

	$lost=array();
	$games=array();

        $res=$mysqli->query('SELECT uid,losses FROM losses');
        ?> <p><br/><br/> <?php
        while ($row=$res->fetch_assoc()){
		$lost[$row['uid']]=$row['losses'];
		$games[$row['uid']]=count($userDist[$row['uid']]);
        }

  // result header
?><table id="nerdscore" class="tablesorter">
<thead>
  <tr>
    <th>player</th>
    <th>#lost</th>
    <th>#games</th>
    <th>score (min)</th>
    <th>score (max)</th>
    <th>dist</th>
  </tr>
</thead>
<tbdoy><?php

  // result rows
  foreach ($userDist as $id => $dist) {
    // compute score & dist graph
    $scoreMin = 0;
    $scoreMax = 0;
    $svg = '';
    foreach ($dist as $l => $p) {
      $dc = "b";
      if ($l <= $lost[$id]) { $scoreMax = $scoreMax + $p; $dc = "e"; }
      if ($l <  $lost[$id]) { $scoreMin = $scoreMin + $p; $dc = "s"; }
      $svg = $svg . '<rect class="' . $dc
                      . '" x="' . $l
                      . '" y="' . (1.0 - $p)
                      . '" height="' . $p
                      . '" width="1"  />"';

    }

    // print result row
    ?>
  <tr>
    <td><?=$name[$id] ?></td>
    <td><?=$lost[$id] ?></td>
    <td><?=$games[$id] ?></td>
    <td><?=round($scoreMin,3) ?></td>
    <td><?=round($scoreMax,3) ?></td>
    <td>
      <svg viewBox="0 0 <?=($l + 1) ?> 1" width="200" height="1em" version="1.1" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <?=$svg ?>
      </svg>
    </td>
  </tr><?php
  }

  // result footer
?></tbody>
</table>
<script type="text/javascript">
  $(document).ready(function() {
    $("#nerdscore").tablesorter({headers: {5: {sorter: false}}});
  });
</script>
<?php
}

head();

$mysqli=dbConnection();
if ($mysqli===false) warn("Hooray! No database in sight. I'm going to sleep now.");

if (resultsStored()){
	print 'Results stored in database.<br/>';
} else {
	print form();
}

if (isset($_GET['stat'])) {
  switch ($_GET['stat']){
    case 'simple':
      simpleStat(); break;
    case 'nerd':
      nerdStat(); break;
  }
}

foot();


?>

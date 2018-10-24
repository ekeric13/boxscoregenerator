<?php
// get started with: php -S 127.0.0.1:8666

// https://stats.nba.com/game/0021800040/?sort=FTA&dir=1

// https://stats.nba.com/stats/boxscoretraditionalv2?
// https://stats.nba.com/stats/boxscoresummaryv2?GameID=0021800040
include 'simple_html_dom.php';
date_default_timezone_set('America/Los_Angeles');

$stat_headers = array(
  'Host: stats.nba.com',
  'Connection: keep-alive',
  'Accept-Encoding: gzip, deflate, br',
  'Accept: application/json',
  'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36'
);

$scores = 'https://data.nba.com/data/5s/v2015/json/mobile_teams/nba/2018/scores/00_todays_scores.json';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $scores);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
  'Host: data.nba.com',
  'Connection: keep-alive',
  'Accept-Encoding: gzip, deflate, br',
  'Accept: application/json',
  'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36'
));
curl_setopt($ch, CURLOPT_ENCODING, "");
$response = curl_exec($ch);
$json = json_decode($response, TRUE);
curl_close($ch);
$games = $json['gs']['g'];


$ready = false;
// looks for qs in window.location
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

$parsed = $actual_link;

if (isset($_GET['gameID'])) {
	$gameID = $_GET['gameID'];
	$ready = true;
}

function isJson($string) {
 json_decode($string);
 return (json_last_error() == JSON_ERROR_NONE);
}

if ($ready) {

  // works for some reason...
  // wget --header='Connection: keep-alive' --header='User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36' --header='Host: stats.nba.com' --header='Accept: text/html,application/xhtml+xml,application/xml;' https://stats.nba.com/stats/boxscoresummaryv2\?GameId\=0021800041


  // docs: http://us3.php.net/manual/en/function.curl-setopt.php
  $ch = curl_init();
  $data = http_build_query(array(
   'GameID'  => $gameID
  ));
  curl_setopt($ch, CURLOPT_URL, "https://stats.nba.com/stats/boxscoresummaryv2?".$data);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $stat_headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_ENCODING, "");
  curl_setopt($ch, CURLOPT_VERBOSE, true);
  $response = curl_exec($ch);
  $json = json_decode($response, TRUE);
  curl_close($ch);

  $boxscoreSummary = $json['resultSets'];

  $ch = curl_init();
  $data = http_build_query(array(
   'GameID'  => $gameID,
   'EndPeriod' => '10',
   'EndRange' => '28800',
   'RangeType' => '0',
   'Season' => '2018-19',
   'StartPeriod' => '1',
   'StartRange' => '0'
  ));
  curl_setopt($ch, CURLOPT_URL, "https://stats.nba.com/stats/boxscoretraditionalv2?" . $data);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $stat_headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_ENCODING, "");
  $response = curl_exec($ch);
  $json = json_decode($response, TRUE);
  curl_close($ch);

  $boxscore = $json['resultSets'];

  $format = [
          "Player",
          "Pos",
          "Min",
          "FG",
          "FT",
          "3PT",
          "+/-",
          "OR",
          "Reb",
          "A",
          "Blk",
          "Stl",
          "TO",
          "PF",
          "Pts"
        ];
  $visitorBoxArr = [$format];
  $homeBoxArr = [$format];

	$visitorShort = $boxscoreSummary[5]['rowSet'][1][4];
	$visitorName = $boxscoreSummary[5]['rowSet'][1][5];
	$visitorScore = $boxscoreSummary[5]['rowSet'][1][22];

	$homeShort = $boxscoreSummary[5]['rowSet'][0][4];
	$homeName = $boxscoreSummary[5]['rowSet'][0][5];
	$homeScore = $boxscoreSummary[5]['rowSet'][0][22];

  foreach ($boxscore[0]['rowSet'] as $value) {
    $data = [
      $value[5],
      $value[6],
      $value[8],
      "{$value[9]}-{$value[10]}",
      "{$value[15]}-{$value[16]}",
      "{$value[12]}-{$value[13]}",
      $value[27],
      $value[18],
      $value[20],
      $value[21],
      $value[23],
      $value[22],
      $value[24],
      $value[25],
      $value[26]
    ];

    if ($value[2] == $visitorShort) {
      array_push($visitorBoxArr, $data);
    } else {
      array_push($homeBoxArr, $data);
    }
  }

  foreach ($boxscore[1]['rowSet'] as $value) {
    $data = [
      "Totals",
      "&nbsp;",
      "&nbsp;",
      "{$value[6]}-{$value[7]}({$value[8]})",
      "{$value[12]}-{$value[13]}({$value[14]})",
      "{$value[9]}-{$value[10]}({$value[11]})",
      $value[24],
      $value[15],
      $value[17],
      $value[18],
      $value[20],
      $value[19],
      $value[21],
      $value[22],
      $value[23]      
    ];
    if ($value[3] == $visitorShort) {
      array_push($visitorBoxArr, $data);
    } else {
      array_push($homeBoxArr, $data);
    }
  }

  $visitorBox = $visitorBoxArr;
  $homeBox = $homeBoxArr;


	$textToReddit = getRedditText($visitorShort, $visitorName, $visitorScore, $visitorBox, $homeShort, $homeName, $homeScore, $homeBox);



//	// $boxscoreandmorebs = "[boxscoreandmore.com](https://www.boxscoreandmore.com/#/boxscore/".$nbaID."/game)";

	// // $textToReddit = "Box Score: ".$boxscoreandmorebs." \n\n".$textToReddit;
}



/*
 * Function getBoxScore
 *
 * This function forms a 2D array containing box score data.
 * 1 Row for each player (> 7 and <= 15) plus one for team totals.
 * Columns are: Name, POS, MIN, FGM-A, 3PM-A, FTM-A, +/-, OFF, DEF, TOT
 * AST, PF, ST, TO, BS, BA, PTS except for when a player doesn't play,
 * in that case columns while be: Name and Comment.
 *
 * @param (DOM object)
 * @return (array)
*/
function getBoxScore($teamData) {
	$teamArray = array();
	$i = 0;
	$rowCount = count($teamData->find('tr'));
	foreach ($teamData->find('tr') as $row) {
		// Ignore first 3 rows
		if ($i >= 3) {
			$teamArray[$i - 3] = array();
			$j = 0;
			for ($j = 0; $j < 17; $j++) {
				$col = $row->find('td', $j);
				if ($col != "") {
					if ($j == 0 && $i != $rowCount - 2) {
						$teamArray[$i - 3][$j] = $col->find('a', 0)->innertext;
					} else {
						$teamArray[$i - 3][$j] = $col->innertext;
					}
				} else {
					$teamArray[$i - 3][$j] = "-";
				}
			}
		}
		$i++;
	}
	return $teamArray;
}

/*
 * Function printBoxScore
 *
 * This function prints a 2D array containing a team's box score
 * data.
 *
 * @param (array)
*/
function printBoxScore($teamArray) {
	$lenI = count($teamArray);
	for ($i = 0; $i < $lenI; $i++) {
		$lenJ = count($teamArray[$i]);
		for ($j = 0; $j < $lenJ; $j++) { 
			echo $teamArray[$i][$j]." | ";
		}
		echo $i."<br>";
	}
}

function short($player) {
	return substr($player, 0, 1).". ".strstr($player, " ");
}

function noDash($date) {
	return str_replace("-", "", $date);
}

function dash($date) {
	return substr($date, 0, 4)."-".substr($date, 4, 2)."-".substr($date, 6, 2);
}

function printHTMLTable($name, $short, $boxscore) {
?>
	<div class="row">
	<div class="col-md-10 col-md-offset-1">
		<table class="table table-hover" style='text-align: center'>
			<thead>
				<tr style='font-weight: bold'>
					<th style='text-align: left'><?php echo $name; ?></th>
					<?php
					$numCols = count($boxscore[0]);
					for($i = 0; $i < $numCols; $i++) {
						// Don't show Player and POS
						if ($i >= 2) {
							echo "<th>".$boxscore[0][$i]."</th>";
						}
					}
					?>
				</tr>
			</thead>
			<tbody>
<?php
	$lenI = count($boxscore);
	for ($i = 1; $i < $lenI; $i++) {
		$lenJ = count($boxscore[$i]); ?>
				<tr>
<?php
		for ($j = 0; $j < $lenJ; $j++) {
			// Don't show POS
			if ($j != 1) {
				if ($j == 0) {
					echo "<td style='text-align: left'>".$boxscore[$i][$j]."</td>";
				} else {
					echo "<td>".$boxscore[$i][$j]."</td>";
				}
			} else {
				// show if its a DNP - reason column
				if (strlen($boxscore[$i][$j]) > 7) {
					echo "<td colspan=13>".$boxscore[$i][$j]."</td>";
				} 
			}
		}
?>
				</tr>
<?php
	}
?>
			</tbody>
		</table>
	</div>
	</div>
<?php
}

function getRedditText($awayShort, $awayName, $awayScore, $awayBox, $homeShort, $homeName, $homeScore, $homeBox) {
	$text = "
**[](/".$awayShort.") ".$awayShort."**|";
	
	$numCols = count($awayBox[0]);
	for($i = 0; $i < $numCols; $i++) {
		if ($i >= 2) {
			$text .= "**".$awayBox[0][$i]."**|";
		}
	}


	$text .= "
|:---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
";

	$text .= getTableText($awayBox);

	$text .= "
**[](/".$homeShort.") ".$homeShort."**|";
	$numCols = count($homeBox[0]);
	for($i = 0; $i < $numCols; $i++) {
		if ($i >= 2) {
			$text .= "**".$homeBox[0][$i]."**|";
		}
	}

	$text .= "
|:---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
";

	$text .= getTableText($homeBox);

	$text .= "
||
|:-:|
|^[boxscoregenerator.herokuapp.com](https://boxscoregenerator.herokuapp.com) ^by ^/u/Obi-Wan_Ginobili and ^/u/boxscoreandmore|";

	return $text;

}

function getTableText($box) {
	$text = "";
	$lenI = count($box);
	$numCols = count($box[0]);
	for ($i = 1; $i < $lenI; $i++) {
		$lenJ = count($box[$i]);
		for ($j = 0; $j < $lenJ; $j++) {
			if ($j != 1) {
				$text .= $box[$i][$j]."|";
			} else {
				if (strlen($box[$i][$j]) > 7) {
					//$text .= $box[$i][$j]."|";
				}
			}
		}
		if ($lenJ < $numCols) {
			for ($j = 0; $j < $numCols - $lenJ; $j++) {
				$text .= "|";
			}
		}
		$text .= "\n";
	}
	return $text;
}

function getShortName($teamName) {
	switch ($teamName) {
		case 'Boston':
			return "BOS";
		case 'Broolyn':
			return "BKN";
		case 'New York':
			return "NYK";
		case 'Philadelphia':
			return "PHI";
		case 'Toronto':
			return "TOR";
		case 'Chicago':
			return "CHI";
		case 'Cleveland':
			return "CLE";
		case 'Detroit':
			return "DET";
		case 'Indiana':
			return "IND";
		case 'Milwaukee':
			return "MIL";
		case 'Atlanta':
			return "ATL";
		case 'Charlotte':
			return "CHA";
		case 'Miami':
			return "MIA";
		case 'Orlando':
			return "ORL";
		case 'Washington':
			return "WAS";
		case 'Golden State':
			return "GSW";
		case 'LA Clippers':
			return "LAC";
		case 'LA Lakers':
			return "LAL";
		case 'Phoenix':
			return "PHX";
		case 'Sacramento':
			return "SAC";
		case 'Dallas':
			return "DAL";
		case 'Houston':
			return "HOU";
		case 'Memphis':
			return "MEM";
		case 'New Orleans':
			return "NOP";
		case 'San Antonio':
			return "SAS";
		case 'Denver':
			return "DEN";
		case 'Minnesota':
			return "MIN";
		case 'Oklahoma City':
			return "OKC";
		case 'Portland':
			return "POR";
		case 'Utah':
			return "UTA";
		default:
			return "";
	}
}

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>NBA Box Score Generator</title>

	<link rel="shortcut icon" href="chalmers.ico"> 

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

	<link rel="stylesheet" href="https://cdn.materialdesignicons.com/1.5.54/css/materialdesignicons.min.css">

	<!-- Latest compiled and minified JavaScript -->
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>


	<style type="text/css">
		.mdi::before {
		    font-size: 24px;
		    line-height: 14px;
		}
		.btn .mdi::before {
		    position: relative;
		    top: 4px;
		}
		.btn-xs .mdi::before {
		    font-size: 18px;
		    top: 3px;
		}
		.btn-sm .mdi::before {
		    font-size: 18px;
		    top: 3px;
		}
		.dropdown-menu .mdi {
		    width: 18px;
		}
		.dropdown-menu .mdi::before {
		    position: relative;
		    top: 4px;
		    left: -8px;
		}
		.nav .mdi::before {
		    position: relative;
		    top: 4px;
		}
		.navbar .navbar-toggle .mdi::before {
		    position: relative;
		    top: 4px;
		    color: #FFF;
		}
		.breadcrumb .mdi::before {
		    position: relative;
		    top: 4px;
		}
		.breadcrumb a:hover {
		    text-decoration: none;
		}
		.breadcrumb a:hover span {
		    text-decoration: underline;
		}
		.alert .mdi::before {
		    position: relative;
		    top: 4px;
		    margin-right: 2px;
		}
		.input-group-addon .mdi::before {
		    position: relative;
		    top: 3px;
		}
		.navbar-brand .mdi::before {
		    position: relative;
		    top: 2px;
		    margin-right: 2px;
		}
		.list-group-item .mdi::before {
		    position: relative;
		    top: 3px;
		    left: -3px
		}
	</style>
</head>

<body>
	<div class="container">
		<div class="row">
			<h1 style="text-align: center; padding-bottom: 10px;">NBA Box Score Generator for Reddit</h1>
			<div class="col-md-3 col-md-offset-2">

				<form action="" method="GET">
					<div class="row" style="margin-top: 15px">
						<div class="col-md-8">
							<select name="gameID">
							<?
							foreach($games as $game) {
								$id = $game['gid'];
								$visitor = $game['v']['ta'];
								$home = $game['h']['ta'];
								$matchup = $visitor." @ ".$home;

								echo "<option value='".$id."'>".$matchup."</option>";
							}
							?>
							</select>
							<input type="submit" value="Go!" class="btn btn-primary">
						</div>
					</div>
				</form>
				<p><B>Now with LIVE results!</B></p>

			</div>
			<div class="col-md-6 col-md-offset-1">
				<p>Made for <a href="http://reddit.com/r/nba">/r/NBA</a> by <a href="http://reddit.com/user/Obi-Wan_Ginobili">/u/Obi-Wan_Ginobili.</a> and <a href="http://reddit.com/user/boxscoreandmore">boxscoreandmore</a> 
				<br>
				Report any issues on <a href="https://github.com/ekeric13/boxscoregenerator">Github <i class="mdi mdi-github-circle"></i></a> or send either <a href="http://reddit.com/user/Obi-Wan_Ginobili">Obi-Wan_Ginobili</a> a PM or <a href="http://reddit.com/user/boxscoreandmore">boxscoreandmore</a> a PM.
			</div>
		</div> <!-- End row -->

		<hr>

<?php
	if ($ready) {

		// Print HTML box score tables
		printHTMLTable($visitorName, $visitorShort, $visitorBox);
		printHTMLTable($homeName, $homeShort, $homeBox);
?>

		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<h2>Copy text below to Reddit</h2>
				<p>Use Ctrl-A</p>
				<textarea cols="130" rows="50"><?php echo $textToReddit; ?></textarea>
			</div>
		</div>
<?php
	} else {
?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<!--<h3 style="text-align: center">Game hasn't started or values are invalid.</h3>-->
				<h3 style="text-align: center">Select a game from the list and click Go!</h3>
				<p style="text-align: center">Empty list means no game have started.</p>
			</div>
		</div>
<?php
	}
?>
	</div>


<!-- 	<script>
	  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	  ga('create', 'UA-75603173-1', 'auto');
	  ga('send', 'pageview');

	</script> -->
</body>

</html>
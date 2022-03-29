<?php
require_once('guessum_lib.php');

# get user info
$user = fb_get_user();

if ( $user ) {
	$user_info = get_user_info($user);
} else {
	header("Location: $base_url");
	exit;
}

# select a question
if ( !($matchup = gen_matchup($user)) ) {
	header("Location: $base_url/index.php?qout=1");
	exit;
}

# calibration stats
$coverage = calibration($user);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<script type="text/javascript" src="scripts/jquery-1.4.2.min.js"></script>
	<script language="javascript" type="text/javascript" src="scripts/jquery.flot.min.js"></script>
	<script type="text/javascript" src="scripts/autoNumeric-1.4.3.js"></script>
	<script type="text/javascript" src="scripts/jquery.countdown.min.js"></script>
	<script type="text/javascript" src="scripts/jquery.corner.js"></script>
	<script type="text/javascript" src="scripts/guessum.js"></script>
	<script language="javascript" type="text/javascript" src="scripts/calplot.js.php?id=<?php print($user); ?>"></script>
	<script type="text/javascript" src="scripts/question.js.php?t=<?php print($matchup['timelimit']); ?>"></script>
	<link rel="stylesheet" type="text/css" href="css/guessum.css" />
	<link rel="stylesheet" type="text/css" href="css/auto.css" />
	<link rel="stylesheet" type="text/css" href="css/jquery.countdown.css" />
	<title>Guessum</title>
</head>

<body>
<div id="content">

<div id="logo" onclick="window.location='index.php';">Guess<span style="color: rgb(140, 163, 209)">um</span></div>
<div id="band1"></div>
<div id="band2">know what you know</div>

<div id="sidepane">
	<div id='sidebar'>
  	Time Left
  	<div id="myclock"></div>
  	<div class="score_cont">
    	Total Score
    	<div class="score"><?php print($matchup['score']); ?></div>
 		</div>
 		<div class="score_cont">
    	Question
    	<div class="question_num"><?php print("$matchup[question_num] of $session_length"); ?></div>
 		</div>
	</div>
	
	<div id="calplot50_box" class="calplot_text">
		<?php if (isset($coverage['0.5'])) { print(interval_advice(.5, $coverage['0.5'])); } ?>
		<div id="calplot50" style="width:125px;height:50px"></div>
	</div>
	<div id="calplot90_box" class="calplot_text">
		<?php if (isset($coverage['0.9'])) { print(interval_advice(.9, $coverage['0.9'])); } ?>
		<div id="calplot90" style="width:125px;height:50px"></div>
	</div>
</div>

<div id='mainpane'>
  <div class="question">
  <?php print($matchup['question']); ?>
  </div>

  <div class="level">I am <strong><?php print($matchup['level']*100); ?>% sure</strong> the answer is in the range</div>
	<form id="answer_form" action="answer.php" method="POST">
		<div class="answer">
			<input name="lower_bound" id="lower_bound" value="lower bound" class="auto" alt="<?php print($matchup['input_format']); ?>" size="15" type="text" /> to
			<input name="upper_bound" id="upper_bound" value="upper bound" class="auto" alt="<?php print($matchup['input_format']); ?>" size="15" type="text" />
			<input type="hidden" name="matchup_id" value="<?php print($matchup['id']); ?>">
		</div>
		<div class="mybutton" onclick='$("#answer_form").submit();'>Submit</div>
	</form>
</div>

</div>
</body>
</html>

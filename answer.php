<?php

require_once('guessum_lib.php');

if (!isset($_POST['matchup_id']) || !isset($_POST['lower_bound']) || !isset($_POST['upper_bound'])) {
	header("Location: $base_url");
	exit;
}

# get user info
if ( $user = fb_get_user() ) {
	$user_info = get_user_info($user);
} else {
	header("Location: $base_url");
	exit;
}

# score the response
if ( !($scored_matchup = score_matchup($_POST['matchup_id'], $_POST['lower_bound'], $_POST['upper_bound'])) ) {
	header("Location: $base_url/question.php");
	exit;
}

# if we're at the end of the session, redirect to the summary page
# if it's a prediction question, go to the next question (without showing results)
if ($scored_matchup['completed']) {
	header("Location: $base_url/summary.php?id=$scored_matchup[session_id]");
	exit;
} else if ($scored_matchup['prediction']) {
	header("Location: $base_url/question.php");
	exit;
}

# calibration stats
$coverage = calibration($user);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<!--[if IE]><script language="javascript" type="text/javascript" src="scripts/excanvas.min.js"></script><![endif]-->
	<script type="text/javascript" src="scripts/jquery-1.4.2.min.js"></script>
	<script language="javascript" type="text/javascript" src="scripts/jquery.flot.min.js"></script>
	<script type="text/javascript" src="scripts/jquery.corner.js"></script>
	<script type="text/javascript" src="scripts/jquery.numberformatter.min.js"></script>
	<script type="text/javascript" src="scripts/guessum.js"></script>
	<script language="javascript" type="text/javascript" src="scripts/calplot.js.php?id=<?php print($user); ?>"></script>
	<script type="text/javascript" src="scripts/answer.js.php?id=<?php print($_POST['matchup_id']); ?>"></script>
	<link rel="stylesheet" type="text/css" href="css/guessum.css" />
	<title>Guessum</title>
</head>

<body>
<div id="content">

<div id="logo" onclick="window.location='index.php';">Guess<span style="color: rgb(140, 163, 209)">um</span></div>
<div id="band1"></div>
<div id="band2">know what you know</div>

<div id='sidepane'>
	<div id='sidebar'>
		Time Left
		<div class="score">00</div>
		<div class="score_cont">
			Total Score
			<div class="score"><?php print($scored_matchup['session_score']); ?></div>
		</div>
		<div class="score_cont">
	  	Question
	  	<div class="question_num"><?php print("{$scored_matchup['question_num']} of {$session_length}"); ?></div>
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
		<?php print($scored_matchup['question']); ?>
	</div>

	<div id="revealedanswer" class="revealedanswer">
		<span id="theanswer">Answer: <?php print(my_number_format($scored_matchup['answer'], $scored_matchup['question_id'])); ?></span>
	</div>
	<div class="plot_title"><?php print($scored_matchup['level']*100); ?>% Intervals</div>
	<div id="plot_placeholder" class="flot_plots" style="width:550px;height:100px;"></div>

	<div id="revealed_info" style="visibility: hidden;">
		<div class="plot_title">Scores This Round</div>
		<div id="revealed_score" class="flot_plots" style="width:550px;height:100px;"></div>

		<div class="mybutton" onclick="window.location='question.php';">Next</div>
	</div>
</div>

</div>
</body>
</html>

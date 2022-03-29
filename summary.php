<?php

require_once('guessum_lib.php');

# check session id was passed in
if (!isset($_GET['id'])) {
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

# get session info
$session_info = session_summary($_GET['id']);
if (!$session_info || !$session_info['completed']) {
	header("Location: $base_url");
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
	<script type="text/javascript" src="scripts/jquery.numberformatter.min.js"></script>
	<script type="text/javascript" src="scripts/jquery.corner.js"></script>
	<script type="text/javascript" src="scripts/guessum.js"></script>
	<script language="javascript" type="text/javascript" src="scripts/calplot.js.php?id=<?php print($user); ?>"></script>
	<script type="text/javascript" src="scripts/summary.js.php?id=<?php print($_GET['id']); ?>"></script>
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
			<div class="score"><?php print($session_info['score']); ?></div>
		</div>
		<div class="score_cont">
	  	Question
	  	<div class="question_num"><?php print("$session_info[question_num] of $session_length"); ?></div>
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
	<div class="plot_title">Final Scores</div>
	<div id="session_score" class="flot_plots" style="width:550px;height:100px;"></div>
	
	<?php foreach ($session_info['matchups'] as $key => $matchup) { ?>
		<div class="summary_qbox">
			<?php print($matchup['question']); ?>
			<div id="summary_plot_q<?php print($key); ?>" class="summary_flot_plots" style="width:262px;height:80px;"></div>
		</div>
	<?php } ?> 
	
	<div class="mybutton_wide"  onclick="window.location='question.php';">Play Again</div>
</div>

</div>
</body>
</html>

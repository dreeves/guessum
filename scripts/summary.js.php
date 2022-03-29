<?php

require_once('../guessum_lib.php');

# prepare output for javascript
header("content-type: application/x-javascript");

if (!isset($_GET['id'])) {
	print("window.location='$base_url';");
	exit;
}

# check user credentials
if ( !($user = fb_get_user()) ) {
	print("window.location='$base_url';");
	exit;
}

# get session info
$session_info = session_summary($_GET['id']);
if ( !$session_info || ($user != $session_info['user_id']) ) {
	print("window.location='$base_url';");
	exit;
}

?>
$(document).ready(function() {
	/* plot scores */
	<?php
		$yourscore = $session_info['score'];
		$oppscore = 100*sizeof($session_info['matchups']) - $yourscore;
		$xmax = ($yourscore + $oppscore) * 1.1;
		if ( max($yourscore, $oppscore) >= 1000 ) {
			$xmax += 30;
		}
		print("plot_scores('session_score',$yourscore,$oppscore,$xmax);\n");
	?>

	/* plot summary for all the questions */
	<?php 
		foreach ($session_info['matchups'] as $i=>$matchup) {
			$a1 = $matchup['a1'] != '' ? $matchup['a1'] : 'false';
			$b1 = $matchup['b1'] != '' ? $matchup['b1'] : 'false';
			$a2 = $matchup['a2'] != '' ? $matchup['a2'] : 'false';
			$b2 = $matchup['b2'] != '' ? $matchup['b2'] : 'false';
			$answer = $matchup['answer'] != '' ? $matchup['answer'] : 'false';
			print("plot_intervals('summary_plot_q$i',$a1,$b1,$a2,$b2,$answer,true,false,$matchup[decimals],$matchup[commas]);\n");
		}
	?>
});

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

# get matchup info
$matchup = get_matchup_info($_GET['id']);
if ($matchup && $user == $matchup['user_id']) {
	$response1 = get_response_info($matchup['response1_id']);
	$response2 = get_response_info($matchup['response2_id']);
	$question_info = get_question_info($matchup['question_id']);
	$source = get_source($question_info['category']);
	$session = get_session($user);
} else {
	print("window.location='$base_url';");
	exit;
}
?>
/* set vars for plotting */
var a1 = <?php print($response1['lower_bound'] != '' ? $response1['lower_bound'] : 'false'); ?>;
var b1 = <?php print($response1['upper_bound'] != '' ? $response1['upper_bound'] : 'false'); ?>;
var a2 = <?php print($response2['lower_bound']); ?>;
var b2 = <?php print($response2['upper_bound']); ?>;
var answer = <?php print($question_info['answer']); ?>;
var source = '<?php print($source); ?>';
var score = <?php print($matchup['score']); ?>;
var decimals = <?php print($question_info['decimals']); ?>;
var commas = <?php print($question_info['commas']); ?>;

$(document).ready(function() {
	/* plot confidence intervals (without revealing the answer) */
	plot_intervals("plot_placeholder",a1,b1,a2,b2,answer,false,true,decimals,commas);
	
	/* plot scores */
	plot_scores('revealed_score', score, 100-score, 110);

	/* reveal answer after a pause */
	$('#revealedanswer').animate({opacity: 1}, 1000, function() {
		plot_intervals("plot_placeholder",a1,b1,a2,b2,answer,true,true,decimals,commas);
		$('#revealed_info').css("visibility","visible");
		
		/* display source on hover */
		if (source) { showOnHover("theanswer", "Source: " + source); }
	});
});

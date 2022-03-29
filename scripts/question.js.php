<?php

# prepare output for javascript
header("content-type: application/x-javascript");

?>
var timelimit = <?php print( isset($_GET['t']) ? $_GET['t'] : '0' ); ?>;

$(document).ready(function () {
	/* setup input validation */
	$('input.auto').focus(function() {
		$('input.auto').autoNumeric();
	});
		
	/* clear "lower bound" / "upper bound" when user starts entering ranges */
	$('input.auto').focus(function() {
		if ($(this).val() == 'upper bound' || $(this).val() == 'lower bound') { 
			$('#lower_bound').val('');
			$('#lower_bound').css("color","black");
			$('#upper_bound').val('');
			$('#upper_bound').css("color","black");
		}
	});

	/* setup countdown clock */
	function submitresponse() {
		$('#answer_form').submit();
	}
	$('#myclock').countdown({until: timelimit, compact:true, format: 'S', onExpiry: submitresponse});
});


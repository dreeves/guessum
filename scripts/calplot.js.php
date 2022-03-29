<?php

require_once('../guessum_lib.php');

# prepare output for javascript
header("content-type: application/x-javascript");

if (!isset($_GET['id'])) {
	print("window.location='$base_url';");
	exit;
}

# check user credentials
if ( !($user = fb_get_user()) || $user != $_GET['id'] ) {
	print("window.location='$base_url';");
	exit;
}

# get calibration stats
if (!($coverage = calibration($user))) {
	exit;
}

?>

/* plot calibration */
function plotcal(divid, level, coverage) {
	level = level*100;
	coverage = coverage*100;
	
	var xticks = new Array();
	for (var i=0; i<=10; i++) {
		if (i % 2) {
			xticks.push([i*10,i]);
		} else {
			xticks.push([i*10,'']);
		}
	} 
	
	var plot_options = {
		points: {
			fill: true,
			fillColor: '#EDC240'
		},
		yaxis: {
			min: level-10,
			max: level+10,
			ticks: [[level,'']]
		},
		xaxis: {
			min: 0,
			max: 100,
			ticks: xticks
		},
		grid: { 
			markings: [ { xaxis: { from: level, to: level }, color: "#A8A8A8" } ]
		},
		series: {
			points: { show: true }
		}
	};
	
	$("#" + divid + "_box").css("display", "block")
	$.plot($("#" + divid), [[[coverage,level]]], plot_options);
}

$(document).ready(function () {
	<?php
	$cov = $coverage['0.5'];
	print("plotcal('calplot50', 0.5, $cov);\n");
	
	$cov = $coverage['0.9'];
	print("plotcal('calplot90', 0.9, $cov);\n");
	?>
});


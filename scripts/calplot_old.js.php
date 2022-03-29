<?php

require_once('../guessum_lib.php');

# prepare output for javascript
header("content-type: application/x-javascript");

if (!isset($_GET['id'])) {
	print("window.location='$base_url';");
	exit;
}

# check user credentials
$facebook = fb_connect();
if ( !($user = $facebook->get_loggedin_user()) || $user != $_GET['id'] ) {
	print("window.location='$base_url';");
	exit;
}

# get calibration stats
if (!($coverage = calibration($user))) {
	exit;
}
?>

/* set vars for plotting */
var coverage = new Array();
<?php
foreach ($coverage as $key=>$val) {
	$key = $key * 100;
	$val = $val * 100;
	print("coverage['$key'] = '$val';\n");
}
?>
caldata = new Array();
for (var key in coverage) {
	caldata.push([key,coverage[key]]);
}

$(document).ready(function () {
	/* generate calibration plot */
	var plot_options = {
		yaxis: {
			min: 0,
			max: 100,
			ticks: [[10,'10'],[30,'30'],[50,'50'],[70,'70'],[90,'90']]
		},
		xaxis: {
			min: 0,
			max: 100,
			ticks: [[10,'10'],[30,'30'],[50,'50'],[70,'70'],[90,'90']]
		},
		grid: { 
			hoverable: true,
			autoHighlight: false
		}
	};
	
	var calplot = $.plot($("#calplot"), [
		{
			data: [[0,0],[100,100]],
			lines: {show: true},
		},
		{
			data: caldata,
			points: { show: true },
		}], plot_options);
	$('#sidepane .plot_title').css("visibility","visible");
		
	/* show calibration tips on hover */
	function showTooltip(x, y, contents) {
		$('<div id="tooltip">' + contents + '</div>').css( {
	  		position: 'absolute',
	     	top: y + 18,
	     	left: x,
			width: '150px',
	     	border: '1px solid #fdd',
	     	padding: '2px',
			'font-size': '12px',
	     	'background-color': '#fee'
		}).appendTo("body");
	}
	
	$("#calplot").bind("plothover", function (event, pos, item) {
		if (item) {
			var x = parseFloat(item.datapoint[0]).toFixed(2);
			var y = parseFloat(item.datapoint[1]).toFixed(2);
			$("#tooltip").remove();
			calplot.unhighlight();
			if (x < 100 && x > 0) {
				calplot.highlight(item.series, item.datapoint);
				var msg = 'When you are &#8220;' + Math.round(x) + '% sure&#8221; ';
				msg += 'you are typically correct ' + Math.round(y) + '% of the time';
				showTooltip(pos.pageX, pos.pageY, msg);
			}
		} else {
			$("#tooltip").remove();
			calplot.unhighlight();
		}
  });
});


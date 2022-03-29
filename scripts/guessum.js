/* format tick labels to add commas */
function format_tick_labels(divid) {
	$("#" + divid + " .tickLabel").each(function() {
		var myval = $(this).html();
		if (myval == parseFloat(myval) && myval >= 10000) {
			$(this).format({format:"#,###", locale:"us"});
		}
	});
}

/* add annotations to plot */
function annotate_plot(myplot, mydiv, x, y, label) {
	var o = myplot.pointOffset({ x: x, y: y});
	mydiv.append('<div style="position:absolute;left:' + (o.left + 5) + 'px;top:' + (o.top - 10) + 'px;font-size:16px">' + label + '</div>');
}

/* add commas to a string: 10000 -> 10,000 */
function nfmt(num, decimals, commas) {
	var nStr = num.toFixed(decimals);
	if (commas) {
		x = nStr.split('.');
		x1 = x[0];
		x2 = x.length > 1 ? '.' + x[1] : '';
		var rgx = /(\d+)(\d{3})/;
		while (rgx.test(x1)) {
			x1 = x1.replace(rgx, '$1' + ',' + '$2');
		}
		nStr = x1 + x2;
	}
	return nStr;
}

/* plot responses and answer for a given question */
function plot_intervals(divid, a1, b1, a2, b2, answer, showanswer, showlabels, decimals, commas) {
	var int_submitted = true;
	var labelwidth = 0;
	var yourlabel = "";
	var opplabel = "";
	
	if (a1 === false || b1 === false) {
		a1 = a2;
		b1 = b2;
		int_submitted = false;
	}
	
	var int1 = [[a1,1],[b1,1]];
  var int2 = [[a2,0],[b2,0]];
	var num_ticks = Math.min(Math.max(3, 50/Math.log(Math.abs(Math.max(b1,b2))+2)), 6);
	
	var a1_fmt = nfmt(a1, decimals, commas);
	var b1_fmt = nfmt(b1, decimals, commas);
	var a2_fmt = nfmt(a2, decimals, commas);
	var b2_fmt = nfmt(b2, decimals, commas);
	
	if (showlabels) {
		yourlabel = "Your Interval";
		opplabel = "Opponent's Interval";
		labelwidth = 150;
	}
		
	var int_options = {
		series: {
	    lines: { show: true },
	    points: { show: true }
	  },
		yaxis: {
			min: -1,
			max: 2,
			ticks: [[0, opplabel], [1, yourlabel]],
			labelWidth: labelwidth
		},
		xaxis: {
			min: 1.1 * Math.min(a1,a2,answer) - .1 * Math.max(b1,b2,answer),
			max: 1.1 * Math.max(b1,b2,answer) - .1 * Math.min(a1,a2,answer),
			ticks: num_ticks
		},
		grid: { 
			hoverable: true
		},
		colors: ['#AFD8F8', '#EDC240']
	};
	if (showanswer) {
		int_options.grid.markings = [ { xaxis: { from: answer, to: answer }, color: "#bb0000" } ];
	}
	
	var mydiv = $("#" + divid);
	if (int_submitted) {
		$.plot(mydiv, [int2, int1], int_options);
	} else {
		$.plot(mydiv, [int2], int_options);
	}
	format_tick_labels(divid);
	
	mydiv.bind("plothover", function (event, pos, item) {
		var x,y;
		if (item) {
			x = parseFloat(item.datapoint[0]).toFixed(2);
			y = parseFloat(item.datapoint[1]).toFixed(2);
		} else {
			x = parseFloat(pos.x).toFixed(2);
			y = parseFloat(pos.y).toFixed(2);
		}
		var closeToYours = int_submitted && Math.abs(y-1) < .25 && x >= a1 && x <= b1;
		var closeToOpponents = Math.abs(y) < 0.25 && x >= a2 && x <= b2;
		
		$("#tooltip").remove();
		if (closeToYours) {
    	showTooltip(pos.pageX, pos.pageY, a1_fmt + ' to ' + b1_fmt);
  	}	else if (closeToOpponents) {
			showTooltip(pos.pageX, pos.pageY, a2_fmt + ' to ' + b2_fmt);
		}
  });
	mydiv.mouseleave(function() {
		$("#tooltip").remove();
	});
}

/* show tooltips */
function showTooltip(x, y, contents) {
	$('<div id="tooltip">' + contents + '</div>').css( {
  	position: 'absolute',
  	top: y + 18,
  	left: x,
		'text-align': 'left',
		'max-width': '200px',
  	border: '1px solid #fdd',
  	padding: '2px',
		'font-size': '12px',
  	'background-color': '#fee'
	}).appendTo("body");
}

/* show tooltip on hover over div */
function showOnHover(divid, msg) {
	var mydiv = $('#' + divid);
	mydiv.mousemove(function(event) {
		$("#tooltip").remove();
		showTooltip(event.pageX, event.pageY, msg);
	});
	mydiv.mouseleave(function() {
		$("#tooltip").remove();
	});
}

/* plot scores */
function plot_scores(divid, yourscore, oppscore, xmax) {
	var score_options = {
		series: {
			bars: {
				show: true,
				horizontal: true,
				align: 'center',
				barWidth: 0.5,
			}
		},
		yaxis: {
			min: -1,
			max: 2,
			ticks: [[0, "Opponent's Score"], [1, "Your Score"]],
			labelWidth: 150
		},
		xaxis: {
			min: 0,
			max: xmax
		},
		colors: ['#AFD8F8', '#EDC240']
	};
	
	var mydiv = $('#' + divid);
	var score_plot = $.plot(mydiv, [[[oppscore,0]], [[yourscore,1]]], score_options);
	annotate_plot(score_plot, mydiv, yourscore, 1, yourscore);
	annotate_plot(score_plot, mydiv, oppscore, 0, oppscore);
}

$(document).ready(function() {
	/* round corners */
	$('#band1').corner("round top 4px");
	$('#band2').corner("round bottom 4px");
	$('#band3').corner("round bottom 4px");
	$('.answer').corner();
	$('#sidebar').corner();
	$('.mybutton').corner();
	$('.mybutton_wide').corner();
});
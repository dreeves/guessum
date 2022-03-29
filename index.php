<?php

require_once('guessum_lib.php');

# get list of high scorers
$leaders = get_leaders();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
	<script type="text/javascript" src="scripts/jquery-1.4.2.min.js"></script>
	<link href="scripts/facebox/facebox.css" media="screen" rel="stylesheet" type="text/css"/>
	<script src="scripts/facebox/facebox.js" type="text/javascript"></script>
	<link rel="stylesheet" type="text/css" href="css/guessum.css" />
	<title>Guessum</title>
</head>
<body>
<script type="text/javascript">
  window.fbAsyncInit = function() {
    FB.init({
      appId      : '<?php echo $fb_appid;?>',
      xfbml      : true,
      cookie: true,
      version    : 'v2.9'
    });
    FB.AppEvents.logPageView();
  };

  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "//connect.facebook.net/en_US/sdk.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));

	<?php if (isset($_GET['qout'])) { ?>
		var msg = "<div id='popup'>Sorry, you've answered all our trivia questions!";
		msg += "<br/>Please try back again later.</div>";
		jQuery.facebox(msg);
	<?php } ?>
</script>

<div id="content">

<div id="logo" onclick="window.location='index.php';">Guess<span style="color: rgb(140, 163, 209)">um</span></div>
<div id="band1"></div>
<div id="band2">know what you know</div>

<div id='mainpane-instruct'>
Welcome to <strong>Guessum</strong>, a game
created in 2010 by
<a href="https://5harad.com">Sharad Goel</a>,
<a href="http://www.dangoldstein.com">Dan Goldstein</a>, and
<a href="http://dreev.es">Daniel Reeves</a>
(with thanks to 
<a href="https://github.com/skalinchuk/">Sergii Kalinchuk</a>
for help resurrecting it in 2017)
that helps you learn to make better predictions. 
Log in via Facebook to start playing.
<div style="width:200px; margin:20px auto; text-align:center;">
<fb:login-button v="2" size="medium" onlogin="window.location='question.php';">Start Playing</fb:login-button>
</div>
<strong>How it works</strong>
<p>You are paired with a randomly selected partner with whom you compete to answer a series of 10 trivia questions. 
For each question&mdash;for example, <em>What was Martin Luther King, Jr.'s age at death?</em>&mdash;you 
have 60 seconds to specify an interval of numbers (e.g., 30 - 45) that you believe contains the true answer.
Each question is worth 100 points for which you and your partner are vying. 
The smaller your interval, the more points you get&mdash;but you're penalized if your interval 
doesn't contain the actual answer.</p>
	
<p>In each round you're asked either for an interval that you are 
&#8220;50% sure&#8221; contains the correct answer, or for one that you are &#8220;90% sure&#8221; contains the correct answer.
The greater your degree of certainty, the more you are penalized for missing the answer. 
So, it's worse to miss the answer when you are &#8220;90% sure&#8221; 
than when you are &#8220;50% sure&#8221;.</p>
	
<p>After you answer the trivia questions, we'll ask you to make a prediction about some future event. 
Come back in a few months to see how well you've learned to predict the future!</p>
	
<p><strong>Important</strong></p>
<p>When you are &#8220;90% sure&#8221; that your interval contains the correct answer, 
you should expect to get the question right 9 out of 10 times. 
Likewise, when you are &#8220;50% sure&#8221;, your interval should contain the answer 5 out of 10 times.
Your actual hit rate is shown next to each question, 
and you should use that information to help adjust the size of your intervals. 
For example, if your 50% intervals typically contain the true answer only 2 out of 10 times, 
then they are too narrow and you should widen them; 
and if they typically contain the true answer 8 out of 10 times, 
then they are too wide and you should narrow them.</p>

<p>PS, no googling!</p>
</div>

<div id="leaderboard">
	<?php if ($leaders['today']) { ?>
	<strong>Today's High Scorers</strong>
	<table>
		<?php for ($i = 0; $i < sizeof($leaders['today']); $i++) { ?>
			<tr>
				<td width="125px" align="left"><?php print($i+1 . ". " . $leaders['today'][$i]['name']); ?></td>
				<td width="75px" align="right"><?php print($leaders['today'][$i]['score']); ?></td>
			</tr>
		<?php } ?>
	</table>
	<?php } ?>
	
	<?php if ($leaders['alltime']) { ?>
		<br/>
		<strong>All-Time High Scorers</strong>
		<table>
			<?php for ($i = 0; $i < sizeof($leaders['alltime']); $i++) { ?>
				<tr>
					<td width="125px" align="left"><?php print($i+1 . ". " . $leaders['alltime'][$i]['name']); ?></td>
					<td width="75px" align="right"><?php print($leaders['alltime'][$i]['score']); ?></td>
				</tr>
			<?php } ?>
		</table>
	<?php } ?>
</div>

</div>

</body>
</html>

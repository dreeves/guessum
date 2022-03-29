<?php

require_once('config.php');
require_once('bot.php');
//require_once('facebook.php');
require_once('facebook-sdk/autoload.php');

session_start();

# connect to mysql database and return handle
function db_connect()
{
  $dbh = new PDO('mysql:host=localhost;dbname=guessum', $GLOBALS['db_user'], $GLOBALS['db_pass'],
		array(PDO::ATTR_PERSISTENT => true,
		  		PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
		  		PDO::ATTR_EMULATE_PREPARES => true,
					PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
  return $dbh;
}

// connect to facebook and return handle
function fb_connect()
{
	$facebook = new Facebook\Facebook(['app_id' => $GLOBALS['fb_appid'], 'app_secret' =>$GLOBALS['fb_secret'], 'default_graph_version' => 'v2.5']);
	return $facebook;
}

function fb_get_user()
{
    $fb = fb_connect();
    if(isset($_SESSION['fb_access_token'])) {
          $response = $fb->get('/me?fields=id,name,first_name,last_name', $_SESSION['fb_access_token']);
          $userNode = $response->getGraphUser();
          return $userNode->getId();
    }
    $helper = $fb->getJavaScriptHelper();
    try {
      $accessToken = $helper->getAccessToken();
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
      // When Graph returns an error
      echo 'Graph returned an error: ' . $e->getMessage();
      exit;
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
      // When validation fails or other local issues
      echo 'Facebook SDK returned an error: ' . $e->getMessage();
      exit;
    }
    if (isset($accessToken)) {
        $fb->setDefaultAccessToken((string) $accessToken);
        $_SESSION['fb_access_token'] = (string) $accessToken;
        try {
          $response = $fb->get('/me?fields=id,name,first_name,last_name');
          $userNode = $response->getGraphUser();
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
          // When Graph returns an error
          echo 'Graph returned an error: ' . $e->getMessage();
          exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
          // When validation fails or other local issues
          echo 'Facebook SDK returned an error: ' . $e->getMessage();
          exit;
        }
        return $userNode->getId();
    }
    return false;
}

# generate a random string of the specified length
function rand_str($length)
{
  # [0-9a-zA-Z]
  $charset = array_merge(range(48,57), range(65,90), range(97,122));

  $str = "";
  for ($i = 0; $i < $length; $i++) {
    $str .= chr($charset[array_rand($charset)]);
  }
  return $str;
}

# get user info; if user doesn't exisit, create an account
function get_user_info($user)
{
	$dbh = db_connect();
	$sth = $dbh->prepare('SELECT * FROM users WHERE id = ?');
	$sth->execute(array($user));
	if (!($user_info = $sth->fetch())) {
		# get users name from FB
        $fb = fb_connect();
        $response = $fb->get('/me?fields=id,name,first_name,last_name', $_SESSION['fb_access_token']);
        $graphUser = $response->getGraphUser();
        $user_details['first_name'] = $graphUser->getFirstName();
		$user_details['last_name'] = $graphUser->getLastName();

		# insert user info into db
		$sth = $dbh->prepare('INSERT INTO users SET id = ?, first_name = ?, last_name = ?, ip = ?');
		$sth->execute(array($user, $user_details['first_name'], $user_details['last_name'], $_SERVER['REMOTE_ADDR']));
		$user_info = array('id'=>$user, 'first_name'=>$user_details['first_name'],
			'last_name'=>$user_details['last_name'], 'question_order'=>'');
	}
	return $user_info;
}

function get_question_info($question_id)
{
	$dbh = db_connect();
	$sth = $dbh->prepare('SELECT * FROM questions WHERE id = ?');
	$sth->execute(array($question_id));

	$question_info = $sth->fetch();
	# if celebrity, convert birthdate to age
	if ($question_info && $question_info['category'] == 'celebrities') {
		$birthdate = strptime($question_info['answer'],'%Y%m%d');
		$today = getdate();
		$age = $today['year'] - ($birthdate['tm_year'] + 1900);
		if ($birthdate['tm_yday'] > $today['yday']) { $age -= 1; }
		$question_info['answer'] = $age;
	}

	return $question_info;
}

function get_source($category) {
	$dbh = db_connect();
	$sth = $dbh->prepare('SELECT source FROM sources WHERE category = ?');
	$sth->execute(array($category));
	return $sth->fetch(PDO::FETCH_ASSOC);
}

function get_response_info($response_id)
{
	$dbh = db_connect();
	$sth = $dbh->prepare('SELECT * FROM responses WHERE id = ?');
	$sth->execute(array($response_id));
	return $sth->fetch(PDO::FETCH_ASSOC);
}

function get_matchup_info($matchup_id)
{
	$dbh = db_connect();
	$sth = $dbh->prepare('SELECT * FROM matchups WHERE id = ?');
	$sth->execute(array($matchup_id));
	return $sth->fetch(PDO::FETCH_ASSOC);
}

function get_session_info($session_id)
{
	$dbh = db_connect();
	$sth = $dbh->prepare('SELECT * FROM sessions WHERE id = ?');
	$sth->execute(array($session_id));
	return $sth->fetch(PDO::FETCH_ASSOC);
}

# given an interval [a,b], a confidence level, and an answer,
# compute the proper score for the player
function proper_score($a, $b, $level, $answer, $scale = 1)
{
	if ($a > $b || $level <= 0 || $level >= 1) {
		return FALSE;
	}

	$not_in_interval = $answer < $a || $answer > $b;
	$loss = $scale * ( $b - $a + $not_in_interval * 2/(1-$level) * min(abs($answer-$b),abs($answer-$a)) );
	$score = -$loss;

	return $score;
}

# given two intervals, [a1,b1] and [a2,b2], a confidence level and
# an answer, compute the normalized scores for player 1
# (the player scores sum to 100)
function normalized_score($a1, $b1, $a2, $b2, $level, $answer)
{
	$r1 = proper_score($a1, $b1, $level, $answer);
	$r2 = proper_score($a2, $b2, $level, $answer);

	if ($r1 !== FALSE && $r2 !== FALSE) {
		$s1 = round(100*$r2/($r1+$r2));
		return $s1;
	} else {
		return FALSE;
	}
}

# format a number so that it has commas and at most one decimal place
# (if decimal is 0, then it is stripped).
# if a question id is provided, formatting spec is taken from db
function my_number_format($num, $question_id=FALSE) {
	if (!is_numeric($num)) {
		$num = '';
	} else if ($question_id) {
		$question_info = get_question_info($question_id);
		$num = number_format($num, $question_info['decimals']);
		if (!$question_info['commas']) { $num = str_replace(',', '', $num); }
	} else {
		$num = number_format($num, 1);
		$pieces = explode('.', $num);
		if ($pieces[1] == '0') { $num = $pieces[0]; }
	}
	return $num;
}

# get list of alltime and recent high scorers
function get_leaders() {
	$dbh = db_connect();

	# alltime leaders
	$sth = $dbh->prepare('SELECT first_name, last_name, score FROM sessions,users 
		WHERE sessions.user_id = users.id AND completed = 1 ORDER BY score DESC LIMIT 50');
	$sth->execute();
	$alltime = array();
	while ($row = $sth->fetch()) {
		$name = strtolower($row['first_name'] . " " . $row['last_name'][0] . ".");
		$alltime[] = array('name'=>$name, 'score'=>$row['score']);
	}

	# today's leaders
	$sth = $dbh->prepare('SELECT first_name, last_name, score FROM sessions,users 
		WHERE sessions.user_id = users.id AND completed = 1 AND ts + INTERVAL 1 DAY >= NOW()
		ORDER BY score DESC LIMIT 5');
	$sth->execute();
	$today = array();
	while ($row = $sth->fetch()) {
		$name = strtolower($row['first_name'] . " " . $row['last_name'][0] . ".");
		$today[] = array('name'=>$name, 'score'=>$row['score']);
	}

	return array('alltime'=>$alltime, 'today'=>$today);
}

# compute the calibration for a given user using an exponentially weighted average
function calibration($user) {
	$alpha = 0.1;

	$dbh = db_connect();
	$sth= $dbh->prepare('SELECT level, in_interval FROM responses WHERE in_interval IS NOT NULL AND user_id = ? ORDER BY ts');
	$sth->execute(array($user));
	$rows = $sth->fetchAll();

	$coverage = array('0.5'=>0.3, '0.9'=>0.5);
	foreach ($rows as $row) {
		$coverage[$row['level']] = $alpha*$row['in_interval'] + (1-$alpha)*$coverage[$row['level']];
	}

	return $coverage;
}

# return an array of question categories for the given user
# prediction specifies whether or to return prediction or non-prediction categories
function get_categories($user=FALSE, $prediction=FALSE) {
	$dbh = db_connect();

	$categories = FALSE;
	if ($user) {
		$user_info = get_user_info($user);
		$catstr = $prediction ? $user_info['pred_category_order'] : $user_info['category_order'];
		$categories = explode(" ", $catstr);
	}

	if (!$categories) {
		$sth = $dbh->prepare('SELECT DISTINCT category FROM questions WHERE prediction = ? ORDER BY RAND()');
		$sth->execute(array($prediction));
		$categories = $sth->fetchAll(PDO::FETCH_COLUMN);
	}

	return $categories;
}

# pull a random question for the given user
function gen_question($user, $prediction)
{
	$dbh = db_connect();

	# select a question from the next category in the list, proceeding down
	# the category list until a valid question is found
	$refresh = TRUE;
	$categories = get_categories($user, $prediction);
	do {
		if (!$categories) {
			# generate a new category listing at most once
			if ($refresh) {
				$categories = get_categories(FALSE, $prediction);
				$refresh = FALSE;
			} else {
				return FALSE;
			}
		}
		$category = array_shift($categories);

		$sth = $dbh->prepare('SELECT id FROM questions WHERE category = ? AND display = 1
			AND id NOT IN (SELECT question_id FROM responses WHERE user_id = ?) ORDER BY RAND() LIMIT 1');
		$sth->execute(array($category, $user));
		$question_id = $sth->fetchColumn();
	} while (!$question_id);

	# select a confidence level
	$user_info = get_user_info($user);
	if ($level = $user_info['next_level']) {
		$next_level = '';
	} else {
		$levels = array('0.5','0.9');
		shuffle($levels);
		list($level, $next_level) = $levels;
	}

	# write the updated category and level orders back to the db
	$field = ($prediction ? "pred_category_order" : "category_order");
	$sth = $dbh->prepare("UPDATE users SET $field = ?, next_level = ? WHERE id = ?");
	$sth->execute(array(implode(" ",$categories), (float)$next_level, $user));

	return array('id'=>$question_id,'level'=>$level);
}

# generate advice for given user coverage
function interval_advice($level, $coverage) {
	$num = round($coverage*100)/10;
	$fmt_level = round($level*100) . "%";

	$advice = "<strong>Your $fmt_level intervals are <br/>";
	if ($num - $level*10 > .5) {
		$advice .= "TOO WIDE";
	} else if ($num - $level*10 < -.5) {
		$advice .= "TOO NARROW";
	} else {
		$advice .= "JUST RIGHT";
	}
	$advice .= "</strong>\n";
	$advice .= "<br/>When you are $fmt_level sure you are typically correct <br/>$num out of 10 times\n";

	return $advice;
}


# check if user has an active session
function get_session($user) {
	$dbh = db_connect();
	$sth = $dbh->prepare('SELECT * FROM sessions WHERE user_id = ? AND ts + INTERVAL 1 HOUR >= NOW() ORDER BY ts DESC LIMIT 1');
	$sth->execute(array($user));

	return $sth->fetch();
}

# get a summary of questions, responses, answers, scores for a given session
function session_summary($session_id) {
	$dbh = db_connect();

	if ( !($session_info = get_session_info($session_id)) ) {
		return FALSE;
	}

	$sth = $dbh->prepare('SELECT matchups.question_id, matchups.score, 
		responses1.lower_bound AS a1, responses1.upper_bound AS b1, responses2.lower_bound AS a2, responses2.upper_bound AS b2 
		FROM matchups LEFT JOIN responses AS responses1 ON (matchups.response1_id = responses1.id) 
		LEFT JOIN responses AS responses2 ON (matchups.response2_id = responses2.id) 
		WHERE session_id = ? AND NOT prediction ORDER BY matchups.submit_ts');
	$sth->execute(array($session_id));
	$matchups = array();
	while ($matchup = $sth->fetch(PDO::FETCH_ASSOC)) {
		$question_info = get_question_info($matchup['question_id']);
		if (!$question_info['prediction']) {
			$matchup['question'] = $question_info['question'];
			$matchup['answer'] = $question_info['answer'];
			$matchup['decimals'] = $question_info['decimals'];
			$matchup['commas'] = $question_info['commas'];
			$matchups[] = $matchup;
		}
	}

	$session_info['matchups'] = $matchups;
	return $session_info;
}

# For a specified question and level, return a confidence interval.
# Interval is past user-submitted response when possible; otherwise
# it is automatically generated
function gen_response($question_id, $level)
{
	$dbh = db_connect();
	$sth = $dbh->prepare('SELECT response1_id FROM matchups WHERE question_id = ? AND level = ? AND score >= 10 ORDER BY RAND() LIMIT 1');
	$sth->execute(array($question_id, $level));
	$response_id = $sth->fetchColumn();

	if (!$response_id) {
		# use an automatic response
		$question_info = get_question_info($question_id);
                error_log("[DBG: " . $question_info['answer'] . "(" .
                          $level*100 . ") " .
                          $question_info['plausible_lower'] . "-".
                          $question_info['plausible_upper'] . "]");
		$interval = bot_response($question_info['answer'], $level*100, $question_info['plausible_lower'], $question_info['plausible_upper']);
		$in_interval = $interval[0] <= $question_info['answer'] && $question_info['answer'] <= $interval[1];

		$sth = $dbh->prepare('INSERT INTO responses SET user_id = ?, question_id = ?, level = ?, lower_bound = ?, upper_bound = ?, in_interval = ?, ts = NOW()');
		$sth->execute(array('guessum_bot', $question_id, $level, $interval[0], $interval[1], (int)$in_interval));
		$response_id = $dbh->lastInsertId();
		return array('response_id'=>$response_id, 'user_id'=>'guessum_bot', 'a'=>$interval[0], 'b'=>$interval[1]);
	} else {
		$response_info = get_response_info($response_id);
		return array('response_id'=>$response_id, 'user_id'=>$response_info['user_id'], 'a'=>$response_info['lower_bound'], 'b'=>$response_info['upper_bound']);
	}
}

# create a matchup for a given user
function gen_matchup($user)
{
	global $question_time_limit;
	global $session_length;
	global $pred_ques_pos;
	$dbh = db_connect();

	# get session info
	$session = get_session($user);

	# check if any matchups are still live for the user
	$sth = $dbh->prepare('SELECT id, question_id, level, TIME_TO_SEC(TIMEDIFF(NOW(), create_ts)) AS timegone FROM matchups 
		WHERE user_id = ? AND create_ts + INTERVAL ? SECOND > NOW() AND submit_ts is NULL');
	$sth->execute(array($user, $question_time_limit));
	if ($response = $sth->fetch()) {
		$matchup_id = $response['id'];
		$level = $response['level'];
		$question_id = $response['question_id'];
		$question_time_limit -= $response['timegone'];
	} else {
		# generate a new matchup
		$matchup_id = rand_str(16);

		# check if session already exists; else create a new one
		if (!$session || $session['question_num'] >= $session_length) {
			$session_id = rand_str(16);
			$sth = $dbh->prepare('INSERT INTO sessions SET id = ?, user_id = ?, score = 0, question_num = 1, completed = 0, ts = NOW()');
			$sth->execute(array($session_id, $user));
			$session = array('id'=> $session_id, 'score'=>0, 'question_num'=>1);
		} else {
			$sth = $dbh->prepare('UPDATE sessions SET question_num = question_num + 1 WHERE id = ?');
			$sth->execute(array($session['id']));
			$session['question_num'] += 1;
		}

		# check if it's time for a prediction question
		$prediction = in_array($session['question_num'], $pred_ques_pos);
		if ( !($selected_question = gen_question($user, $prediction)) ) {
			return FALSE;
		}

		$question_id = $selected_question['id'];
		$level = $selected_question['level'];

		# generate the opponent's interval
		$opponent = gen_response($question_id, $level);

		$sth = $dbh->prepare('INSERT INTO matchups SET id = ?, session_id = ?, question_id = ?, level = ?, user_id = ?, prediction = ?, response2_id = ?, score = 0, create_ts = NOW()');
		$sth->execute(array($matchup_id, $session['id'], $question_id, $level, $user, (int)$prediction, $opponent['response_id']));
	}

	# construct the input format string
	$question_info = get_question_info($question_id);
	$input_format = $question_info['commas'] ? "n0c3p$question_info[decimals]S" : "n0x3p$question_info[decimals]S";

	# return result
	return array('id'=>$matchup_id, 'question'=>$question_info['question'],
		'level'=>$level, 'timelimit'=>$question_time_limit, 'input_format'=>$input_format,
		'question_num'=>$session['question_num'], 'score'=>$session['score']);
}

# scores a matchup given a matchup id, and an interval [a,b]
function score_matchup($matchup_id, $a, $b)
{
	global $question_time_limit;
	global $session_length;
	$matchup_info = get_matchup_info($matchup_id);
	$dbh = db_connect();

	# check that id is valid, that time hasn't expired (10s leeway), that the matchup hasn't been previously scored
	if ( !$matchup_info || $matchup_info['submit_ts']
			|| (strtotime($matchup_info['create_ts']) + $question_time_limit + 10 < time()) ) {
		return FALSE;
	}

	$question_info = get_question_info($matchup_info['question_id']);
	$opponent = get_response_info($matchup_info['response2_id']);

	$a = preg_replace('/[^\d.\-]/', '', $a);
	$b = preg_replace('/[^\d.\-]/', '', $b);

	if (!is_numeric($a) || !is_numeric($b)) {
		# record the response
		$a = ''; $b = '';
		$sth = $dbh->prepare('INSERT INTO responses SET user_id = ?, question_id = ?, level = ?, ts = NOW(), ip = ?');
		$sth->execute(array($matchup_info['user_id'], $matchup_info['question_id'], $matchup_info['level'], $_SERVER['REMOTE_ADDR']));
		$response_id = $dbh->lastInsertId();
		$score = 0;
	} else {
		# make sure $a <= $b
		if ($a > $b) { list($a,$b) = array($b,$a); }

		# if it's a prediction question, just record the response; else score against opponent
		if ($question_info['prediction']) {
			$sth = $dbh->prepare('INSERT INTO responses SET user_id = ?, question_id = ?, level = ?, lower_bound = ?, upper_bound = ?, ts = NOW(), ip = ?');
			$sth->execute(array($matchup_info['user_id'], $matchup_info['question_id'], $matchup_info['level'], $a, $b, $_SERVER['REMOTE_ADDR']));
		} else {
			$score = normalized_score($a, $b, $opponent['lower_bound'], $opponent['upper_bound'], $matchup_info['level'], $question_info['answer']);
			$in_interval = $a <= $question_info['answer'] && $question_info['answer'] <= $b;
			$sth = $dbh->prepare('INSERT INTO responses SET user_id = ?, question_id = ?, level = ?, lower_bound = ?, upper_bound = ?, in_interval = ?, ts = NOW(), ip = ?');
			$sth->execute(array($matchup_info['user_id'], $matchup_info['question_id'], $matchup_info['level'], $a, $b, (int)$in_interval, $_SERVER['REMOTE_ADDR']));
		}
		$response_id = $dbh->lastInsertId();
	}

	# record matchup
	if ($question_info['prediction']) {
		$sth = $dbh->prepare('UPDATE matchups SET response1_id = ?, submit_ts = NOW() WHERE id = ?');
		$sth->execute(array($response_id, $matchup_id));
	} else {
		$sth = $dbh->prepare('UPDATE matchups SET response1_id = ?, score = ?, submit_ts = NOW() WHERE id = ?');
		$sth->execute(array($response_id, $score, $matchup_id));
	}

	# update session
	$session = get_session_info($matchup_info['session_id']);
	$completed = ($session['question_num'] >= $session_length);
	if ($question_info['prediction']) {
		$sth = $dbh->prepare('UPDATE sessions SET ts = NOW(), completed = ? WHERE id = ?');
		$sth->execute(array($completed, $session['id']));
	} else {
		$sth = $dbh->prepare('UPDATE sessions SET score = score + ?, completed = ?, ts = NOW() WHERE id = ?');
		$sth->execute(array($score, (int)$completed, $session['id']));
		$session['score'] += $score;
	}

	# return info
	if ($question_info['prediction']) {
		$ret = array('session_id'=>$session['id'], 'session_score'=>$session['score'],
		'question_num'=>$session['question_num'], 'completed'=>$completed, 'prediction'=>TRUE);
	} else {
		$ret = array('question'=>$question_info['question'], 'question_id'=>$question_info['id'], 'prediction'=>FALSE,
			'level'=>$matchup_info['level'], 'answer'=>$question_info['answer'], 'score'=>$score,
			'session_id'=>$session['id'], 'session_score'=>$session['score'],
			'question_num'=>$session['question_num'], 'completed'=>$completed,
			'a1'=>$a, 'b1'=>$b,
			'a2'=>$opponent['lower_bound'], 'b2'=>$opponent['upper_bound']);
	}
	return $ret;
}

?>

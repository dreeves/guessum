<?php

# change the width of interval [a,b] by factor k (still centered the same)
function widen($a, $b, $k)  # note that if k<1 then we're actually shrinking.
{
  $m = ($a+$b)/2;
  return array($m-($m-$a)*$k, $m+($b-$m)*$k);
}

# rand($a, $b) gives a random integer from a to b; this gives a real.
function rand_real($a, $b)
{
  $r = rand();  # random int from 0 to getrandmax()
  return (1-$r/getrandmax())*$a + $r/getrandmax()*$b;
}

/**  [found this on the web; made tweaks and fixes]
 * Rounding to significant digits ( just like JS toPrecision() )
 *
 * @number <float> value to round
 * @sf <int> Number of significant figures
 */
function sigfigify($number, $sf) {
  if($number<10) $sf = 1; # hackety

  // How many decimal places do we round and format to?
  // @note May be negative.
  $dp = floor($sf - log10(abs($number)));

  $numberFinal = round($number, $dp); // Round as a regular number.

  //If the original number it's halp up rounded, don't need the last 0
  $arrDec = explode('.',$numberFinal);
  if(count($arrDec) == 1) $xx = "";
  if(strlen($number) > strlen($numberFinal) && $dp > strlen($xx))
    $valorFinal=sprintf("%.".($dp-1)."f", $number);
  else
    //Leave the formatting to format_number(), but always format 0 to 0dp.
    $valorFinal=str_replace(',', '', number_format($numberFinal, 
                                                  0 == $numberFinal ? 0 : $dp));
  return $valorFinal;
}

function bot_response($t, $c, $u = NULL, $v = NULL) 
{
  $probgood = .2;  # probabiliy of coming up with a good interval when $c==50.
  $k = 1.25;  # parameter for how wide the intervals are.
  $sf = 2;  # number of sig figs in answer (but always give an integer).
  if(rand_real(0,1) < $probgood*50/$c) {  # interval shall be good.
    if($t<10) {
      $a = round(rand_real(0,$t));
      if($a > $t) $a = $t;
      $b = round(rand_real($t,$t+7));
      if($b < $t) $b = $t;
    } else {
      $a = sigfigify(rand_real($t/$k,$t), $sf);
      if($a > $t) $a = $t;
      $b = sigfigify(rand_real($t,$t*$k), $sf);
      if($b < $t) $b = $t;
    }
  } elseif(rand_real(0,1)<.5) {  # interval shall be too low.
    if($t<10) {
      $a = round(rand_real(0,$t/2));
      if($a >= $t) $a = $t-1;
      $b = round(rand_real($a,$t-1));
      if($b >= $t) $b = $t-1;
    } else {
      $a = sigfigify(rand_real($t/(2*$k),$t/$k), $sf);
      if($a >= $t) $a = $t-1;
      $b = sigfigify(rand_real($a,$t/$k), $sf);
      if($b >= $t) $b = $t-1;
    }
  } else {  # interval shall be too high.
    if($t<10) {
      $a = round(rand_real($t+1,$t+7));
      if($a < $t) $a = $t+1;
      $b = round(rand_real($a,$t+17));
      if($b < $a) $b = $a+1;
    } else {
      $a = sigfigify(rand_real($t*$k, $t*2*$k), $sf);
      if($a < $t) $a = $t;
      $b = sigfigify(rand_real($a, $t*2*$k), $sf);
      if($b < $a) $b = $a+1;
    }
  }
  $a = max($a, 0);
  if($a==$b) {
    $d = abs(preg_replace('/^[^0]*/', '1', $a));
    return array($a-($d>=$a ? 0 : $d), $b+$d);
  }
  if($b<$a) $b = $a;

  if(!is_null($u) && $a < $u) $a = $u;
  if(!is_null($u) && $b < $u) $b = round(rand_real($u, $t));
  if(!is_null($v) && $b > $v) $b = $v;
  if(!is_null($v) && $a > $v) $a = round(rand_real($t, $v));
  #if($a < $u) $a = $u;
  #if($b < $u) $b = sigfigify(rand_real($u, $t), $sf);
  #if($b > $v) $b = $v;
  #if($a > $v) $a = sigfigify(rand_real($t, $v), $sf);
    
  return array($a,$b);
  

  #if($t<10) { 
  #  $m = rand(3,17);
  #  $d = rand(1,3);
  #  return array($m-$d, $m+$d);
  #}
    
  #$k = 1.33;
  #$w = .03;
  #$a = exp(rand_real(log($t/$k), log($t*$k)));
  #$b = exp(rand_real(log($t/$k), log($t*$k)));
  #if($a > $b) { $tmp = $a; $a = $b; $b = $tmp; }
  #list($a, $b) = widen($a, $b, $w*$c/50);
  #list($a, $b) = array(sigfigify($a,2), sigfigify($b,2));
  #if($a==$b) {
    #$d = abs(preg_replace('/^[^0]*/', '1', $a));
    #return array($a-($d==$a ? 0 : $d), $b+$d);
  #}
  #return array($a, $b);  
}

# testing...
if(FALSE) {
$t = 12345;
$t = 123456;
$t = 1000000;
$t = 1234567890;
$t = 1;
$t = 3;
$t = 7;
$t = 10;
$t = 15;
$t = 123;
$t = 1000;
$t = 1234;

$c = 90;
$sum = 0;
$zerow = 0;
for($i=0; $i<1000; $i++) {
  list($a, $b) = bot_response($t, 90, 1000, 2000);
  #if($a==$b) die("ZERO WIDTH!!!!!!!!!!!!!\n");
  if($a==$b) $zerow++;
  if($a>$b) die("NEGATIVE WIDTH!!!!!!!!!!!\n");
  $sum += ($a <= $t && $t <= $b ? 1 : 0);
  echo "($a, $b)  ";
}
echo "\nCovered: ", $sum/$i, " for true answer $t with ", $zerow/$i, " zero-width\n";
}

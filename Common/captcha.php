<?
$captchaID = $_REQUEST["id"];
$_SESSION['APFSDS_Captcha'][$captchaID] = false; // not ready yet;

if (isset($_REQUEST["r"]) && isset($_REQUEST["g"]) && isset($_REQUEST["b"])) {
	$r = $_REQUEST["r"]+0;
	$g = $_REQUEST["g"]+0;
	$b = $_REQUEST["b"]+0;
} else {
	$r = 0;
	$g = 0;
	$b = 0;
}


srand();
$text = '';
$length=3;
for ($i=0; $i<$length; $i++) {
	$c = chr(79);
	while ($c == chr(79))
			$c=chr(rand(65,89));
	$text.=$c;
}

$_SESSION['APFSDS_Captcha'][$captchaID] = $text;

$rV = imageCreate(250, 50);
$bgColor = imageColorAllocate($rV, $r,$g,$b);
$fgColor = imageColorAllocate($rV, 255, 255, 0);
$fgColor2 = imageColorAllocate($rV, 255, 255, 0);

for ($i=0; $i<$length; $i++) {
	if (rand(1,2)==1) {
		$angle = rand(0, 25);
	} else {
		$angle = rand(335, 360);
	}
	$x = rand(20,25);
	imagettftext($rV, $x, $angle, 25+$i*40+1, 32+1, $fgColor, "/usr/share/fonts/truetype/ttf-dejavu/DejaVuSans.ttf", substr($text, $i, 1));
	imagettftext($rV, $x, $angle, 25+$i*40, 32, $fgColor, "/usr/share/fonts/truetype/ttf-dejavu/DejaVuSans.ttf", substr($text, $i, 1));
}

//imagefilter($rV, IMG_FILTER_EMBOSS);

for ($i=0; $i<3; $i++) {
	imageellipse($rV, rand(1,250), rand(1,50), rand(50, 100), rand(12,25), $bgColor);
}

$style = array($fgColor, $fgColor, $fgColor,$fgColor,$bgColor, $bgColor,  $bgColor,  $bgColor, $bgColor, $bgColor);
imagesetstyle($rV, $style);
//imageline($rV, 0, rand(1,10), 250, rand(20,50), IMG_COLOR_STYLED);
//imageline($rV, 0, rand(20,50), 250, rand(1,30), IMG_COLOR_STYLED);
//imageellipse($rV, rand(1,250), rand(1,50), rand(50, 100), rand(12,25), IMG_COLOR_STYLED);

header('Content-type: image/png');
header('APFSDS_Captcha: By MZX. Based on X-Captcha by J Houle');

imagePNG($rV);
imageDestroy($rV);

?>

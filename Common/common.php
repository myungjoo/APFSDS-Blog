<?
// in php.ini
//   Auto Session Start : On
//   MAX_FILE_SIZE, UPLOAD_MAX_FILESIZE, MEMORY_LIMIT, MAX_EXECUTION_TIME, 
//  POST_MAX_SIZE should be set for file uploading/attachment
// 
// file permissions.
//   As well as read permissions for every files for web server,
//  write permission(with directory suid) on LOG directory.
//  
//  in DB, Blog.accessURL is considered to be pointing at a file without
// using GET data (ex> .php?Do=xx&blahblah..). To enable this, need to do 
// other things..
//

//$DOCTYPE= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">";
include_once("php7.inc.php");

$DOCTYPE = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
$maxType= 3;
$typeSTR[0]='Standard Web Board';
$typeSTR[1]='Guestbook';
$typeSTR[2]='Blog';
$permissionSTR[0]='Guest/Anybody';
$permissionSTR[1]='Rejected Applicant';
$permissionSTR[2]='Inactive Account';
$permissionSTR[3]='Regular Membership';
$permissionSTR[4]='Advanced Membership';
$permissionSTR[100]='Administrator';

$CSS='https://www.babot.net/Common/default.css';
$ICON='https://www.babot.net/favicon.ico';


$keyIterator = 0; // Includes hiddenCounter in version 3.
$imageCounter = 0;

$startYear = 2002;
$mysqlIP = 'localhost';
$mysqlID = 'homepage';
$mysqlPWD = '--------';
$mysqlDBName = 'homepage';

$maxUpload = 10240; // in kBytes
$mkdirMOD = 0750; // chmod of new directories for attachments

$cookieValidFor = (30*24*60*60); // Cookie Login valid for 30 days.
$cookiePath = '/';
$cookieDomain = 'www.babot.net';

$minTitleLength = 1;
$maxTitleLength = 200;

$URL = "https://www.babot.net/";
$LOCAL = "/home/www/";
$LOCALSEPERATOR= '/';
$LOGDIR = $LOCAL."LOG/";
$ATTACHMENTDIR = $LOCAL."Blog/AttachedFiles/";

$MAIL="besthm1 at sparcs dot organization";
$myName = mb_convert_encoding("함명주"  , "utf-8", "euc-kr");
$COPYRIGHT = "<div class=\"COPYRIGHT\" style=\"position: relative; z-index: 1; left: 100px; width: 600px; height: 50px \">&nbsp;&nbsp;&nbsp;&nbsp;COPYRIGHT(C) by <A href=\"https://babot.net\">MyungJoo Ham $myName</A>.<BR>&nbsp;&nbsp;&nbsp;&nbsp;COPYRIGHT(C) Since 2002. 2. 5<BR>&nbsp;&nbsp;&nbsp;&nbsp;<A Href=\"https://babot.net\">MyungJoo's LogBook</A>: Each article's copyright and reponsibility is on each author. Admin has the right to delete and copy. <BR></div>";
$COPYRIGHT_NOMARGIN="<div class=\"COPYRIGHT\" style=\"position: relative; z-index: 1; left: 0px; width: 600px; height: 50px \">&nbsp;&nbsp;&nbsp;&nbsp;COPYRIGHT(C) by <A href=\"https://babot.net\">MyungJoo Ham $myName</A>.<BR>&nbsp;&nbsp;&nbsp;&nbsp;COPYRIGHT(C) Since 2002. 2. 5<BR>&nbsp;&nbsp;&nbsp;&nbsp;<A Href=\"https://babot.net\">MyungJoo's Mainpage</A>: Each article's copyright and reponsibility is on each author. Admin has the right to delete and copy. <BR></div>";

$DEBUG = false;
$_link = null;
$db_conn = false;

$divTop = "divTop";
$divTopLogin = "divTopLogin";
$divTopMenu = "divTopMenu";
$divLeft = "divLeft";
$divBody = "divBody";
$divContent = "divContent";

$divWritePanel = "divWritePanel";
$divCommentShow = "divCommentShow";
$divMemoShow = "divMemoShow";
$divNameTag = "divNameTag";
$divPopup = "divPopup";

$imoticonKeyList = array();
$imoticonDB = array();

function debug($STR)
{
    if ($GLOBALS["DEBUG"])
	print $STR."<br />\n";
}

function DBConnect()
{
    global $imoticonDB, $imoticonKeyList, $URL;

    if (!$GLOBALS["db_conn"])
    {
	$link = mysql_connect($GLOBALS["mysqlIP"], $GLOBALS["mysqlID"], $GLOBALS["mysqlPWD"])
		or die("Could not connect database".$GLOBALS["mysqlIP"]."...");
	debug("Connected successfully<br />");
	mysql_select_db($GLOBALS["mysqlDBName"], $link) or die("Could not select database [".$GLOBALS["mysqlDBName"]."]...");
	debug("Connected and Selected successfully.<br />");
	$GLOBALS["_link"] = $link;
	$GLOBALS["db_conn"]=true;

	// Cache imoticon list:
	$rs = DBQ("select imoticon, filename, imageURL from IMOTICONS");
	while ($r = mysql_fetch_row($rs)) {
		$imoticonKeyList[] = $r[0];
		if (strlen($r[1])>0)
			$imoticonDB[$r[0]] = $URL."Imoticons/".$r[1];
		else
			$imoticonDB[$r[0]] = $r[2];
	}
	mysql_free_result($rs);

	return $link;
    }

	// Cache imoticon list:
	$rs = DBQ("select imoticon, filename, imageURL from IMOTICONS");
	while ($r = mysql_fetch_row($rs)) {
		$imoticonKeyList[] = $r[0];
		if (strlen($r[1])>0)
			$imoticonDB[$r[0]] = $URL."Imoticons/".$r[1];
		else
			$imoticonDB[$r[0]] = $r[2];
	}
	mysql_free_result($rs);



	return $GLOBALS["_link"];
}
function DBClose()
{
    if ($GLOBALS["db_conn"])
    {
	$GLOBALS["db_conn"]=false;
	mysql_close($GLOBALS["_link"]);
    }
}
function DBQ($QUERY)
{
    debug(html2text($QUERY));
    $result = mysqli_query($GLOBALS["_link"], $QUERY);
    if (!$result)
    {
	die("INVALID QUERY : ".html2text($QUERY)."<br />\n".mysql_error()."<br />\n");
    }
    return $result;
}


// Misc..
function getNick($id)
{
	if (strlen($id)<1)
		return "";

	$rs = DBQ("select nickname from USERS where id='$id'");
	$r = mysql_fetch_row($rs);
	$nick = $id;
	if ($r && strlen($r[0])>1)
		$nick = '<b>'.$r[0].'</b>';
	mysql_free_result($rs);
	return $nick;
}
function logwrite($str)
{
    $fp = fopen($GLOBALS["LOGDIR"].date("Ym").".txt" , "a");
    fputs($fp, date("Y-m-d H:i:s")." [".$_SESSION['APFSDS_ID']."(".$_SESSION['APFSDS_Perm'].")] from [".$_SERVER['REMOTE_ADDR']."] :".$str."\r\n");
    fclose($fp);
}
function request($name)
{
	if ((isset($_POST[$name]) && is_array($_POST[$name])) || (isset($_GET[$name]) && is_array($_GET[$name]))) {
		$array = (isset($_POST[$name]))?$_POST[$name]:$_GET[$name];
		$returnValue = array();
		foreach ($array as $key=>$value)
			$returnValue[$key] = $value;
		return $returnValue;
	}
    return sqlSafeRequest(trim(isset($_POST[$name])?$_POST[$name]:(isset($_GET[$name])?$_GET[$name]:'')));
}
function html2text($str)
{
	return str_replace(array("<", ">"), array("&lt;", "&gt;"), $str);
}
function findLink($str)
{
	$ret = '';
	$cursor = 0;
	$length = strlen($str);
	while ($cursor<$length) {
		$oldCursor = $cursor;
		$cursor = strpos(substr($str, $cursor), "https://");
		if ($cursor===FALSE) {
			$ret.=substr($str, $oldCursor);
			break;
		} else {
			$cursor+=$oldCursor;
			$nxtpos = strpos(substr($str, $cursor), " ");
			if ($nxtpos===FALSE) {
				$ret.=substr($str, $oldCursor, $cursor).'<a href="'.
					substr($str, $cursor)."\" target=\"_blank\">".
					substr($str, $cursor, 40)."</a>";
				break;
			}
			$ret.=substr($str, $oldCursor, $cursor).'<a href="'.
				substr($str, $cursor, $nxtpos)."\" target=\"_blank\">".
				substr($str, $cursor, min(40, $nxtpos))."</a>";
			$cursor+=$nxtpos;
		}
	}
	return $ret;
}
function sqlSafeRequest($str)
{
	$len = strlen($str);
	if ($len < 1)
		return "";
	$cursor = 0;
	if ($str[0]=="'")
		$str[0]="`";
	while ($cursor>=0 && $cursor<$len) {
		$cursor = strpos($str, "'", $cursor+1);
		if ($cursor===false)
			break;
		if ($str[$cursor-1]!="\\")
		{	
			print ("ERRORnous string returned.");
			$str[$cursor]="`";
		}
	}
    return str_replace(array("'"), array("'"), $str);
}
function sqlSafe($str)
{
    return str_replace(array("'"), array("\\'"), $str);
}
function htmlPropertySafe($str)
{
	return str_replace(array("\\", "\"", "\r", "\n"),array("\\\\", "&#34;", "", "\\n"), $str);
}
function requestLength($name, $length)
{
    return substr(request($name), 0, $length);
}
function requestInt($name)
{
	if (request($name)=='')
		return false;
    return request($name)+0;
}
function requestList($name, $integer=false, $distinct = false)
{
    $string = request($name);
	if ($string=='')
		return array();
    $returnValue = array();
    $tok = explode(",", $string);
	$num = sizeof($tok);
	$count = 0;
	for ($i=0;$i<$num;$i++)
	{
		$available = true;
		if ($integer===true)
			$tok[$i] = $tok[$i]+0;
		if ($distinct===true)
			for ($j=0;$j<$i;$j++)
				if ($tok[$i]==$tok[$j])
				{
					$available=false;
					break;
				}
		if ($available===true)
			$returnValue[$count++] = $tok[$i];
	}
	return $returnValue;
}
function hideIp($ip)
{
    $tok = strtok($ip, ".");
    $ret = "";
    $counter = 0;
    while ($tok)
    {
    	switch($counter)
    	{
    		case 0:
    			$ret = $ret.$tok;
    			break;
    		case 1:
    			$ret = $ret.".*";
    			break;
    		case 2:
    			$ret = $ret.".*.";
    			break;
    		case 3:
    		default:
    			$ret = $ret.$tok;
    	}
    	$counter++;
    	$tok = strtok(".");
    }
    return $ret;
}
function javascriptCompatible($string)
{
    return str_replace(array("\\", "/>", "</", "'", "\r", "\n"), array("\\", "\\/>", "<\\/", "\\'", "", "\\n"), $string);
}

function ipstatLogin($id)
{
	DBQ("update IPSTAT set lastid='$id' where ip='".$_SERVER['REMOTE_ADDR']."'");
}
function ipstatConnect()
{
	$rs = DBQ("select ip, lastaccess from IPSTAT where ip='".$_SERVER['REMOTE_ADDR']."'");
	$r = mysql_fetch_row($rs);
	mysql_free_result($rs);
	if ($r) {
		if (strncmp($r[1], date('Y-m-d'), 10)==0)
			DBQ("update IPSTAT set lastaccess = NOW(), access = access + 1 where ip='".$_SERVER['REMOTE_ADDR']."' ");
		else
			DBQ("update IPSTAT set lastaccess = NOW(), access = access + 1, accessday = accessday + 1  where ip='".$_SERVER['REMOTE_ADDR']."' ");
	}
	else
		DBQ("insert into IPSTAT values('".$_SERVER['REMOTE_ADDR']."', DEFAULT, null, DEFAULT, DEFAULT, DEFAULT, DEFAULT, 1, 1, 0, 0, 0, null)");
}
function ipstatUpdate($article, $comment, $memo, $spamArticle, $spamComment)
{
}
function ipstatGrouping()
{
	$rs = DBQ("select * from IPSTATGROUP");
	while ($r = mysql_fetch_assoc($rs)) {
		$minIP = (0+$r['ipBase']) & (0+$r['ipBitMask']);
		$maxIP = $minIP | (0xFFFFFFFF ^ (0 + $r['ipBitMask']));
		$rsI = DBQ("select * from IPSTAT where ip>='$minIP' and ip<='$maxIP'");

		mysql_free_result($rsI);
	}
	mysql_free_result($rs);
}
function showError($str)
{
	return '<div class="ERROR">'.$str.'</div>';
}
function htmlConventionOld ($content, $attached, $articleID, $blogID='default')
{
	global $URL;

	$rest = 0;
	$hiddenCounter = 0;
	$imageCounter = 0;
	$randomKey = rand(100, 999);
	// $attachmentURL is an array of URLs for the attachment. 
	
	// 1. Simple Tags
	$result = '';
	$first = true;

	$hiddenCounter = 0;
	// From Here, It's for version 1 which is deprecated and not prohibited.

	$result = '';

	// 2. Tags with URL and Attachment
	$tok = strtok($content, "@");
	while ($tok !== false)
	{
		if (strcasecmp(substr($tok, 0, 3), "img")==0) {
			$tokURL = strstr($tok, '"');
			$tokURL = substr($tokURL, 1, strpos($tokURL, '"', 1)-1);
			$result=$result.
				"<img src=\"$tokURL\" alt=\"APFSDS Blog Image\" id=\"conventionImage$randomKey$imageCounter\"  /><script type=\"text/javascript\">imageAutoResizeIMGObjects.push(document.getElementById('conventionImage$randomKey$imageCounter'));</script>".substr($tok, strpos($tok, '"', strpos($tok, '"')+1)+1);
			$imageCounter++;
		}
		else if (strcasecmp(substr($tok, 0, 4), "link")==0) {
			$tokURL = strstr($tok, '"');
			$tokURL = substr($tokURL, 1, strpos($tokURL, '"', 1)-1);
			$result=$result.
				"<a href=\"$tokURL\" target=\"_blank\">$tokURL</a>".substr($tok, strpos($tok, '"', strpos($tok, '"')+1)+1);
		}
		else if (strcasecmp(substr($tok, 0, 1), "#")==0) {
			$tokURL = strstr($tok, '"');
			if (strlen($tokURL)>0 && strpos($tokURL, '"', 1)>0)
			{
				$tokURL = substr($tokURL, 1, strpos($tokURL, '"', 1)-1);
				if (isset($attached[$tokURL+0])) {
					if (strcasecmp(".gif",substr($attached[$tokURL+0], strlen($attached[$tokURL+0])-4))==0 ||
						strcasecmp(".jpg",substr($attached[$tokURL+0], strlen($attached[$tokURL+0])-4))==0 ||
						strcasecmp(".jpeg",substr($attached[$tokURL+0], strlen($attached[$tokURL+0])-5))==0 ||
						strcasecmp(".png",substr($attached[$tokURL+0], strlen($attached[$tokURL+0])-4))==0 ||
						strcasecmp(".bmp",substr($attached[$tokURL+0], strlen($attached[$tokURL+0])-4))==0)
					{
						$result=$result.
							"<img src=\"".$URL."Blog/AttachedFiles/$blogID/$articleID/".urlencode($attached[$tokURL+0])."\" id=\"conventionImage$randomKey$imageCounter\" /><script type=\"text/javascript\">imageAutoResizeIMGObjects.push(document.getElementById('conventionImage$randomKey$imageCounter'));</script>".substr($tok, strpos($tok, '"', strpos($tok, '"')+1)+1);
						$imageCounter++;
					}
					else
					{
						$result=$result.
							"<a href=\"".$URL."Blog/AttachedFiles/$blogID/$articleID/".$attached[$tokURL+0]."\" target=\"_blank\">".
							"Attachment[".($tokURL+0)."]: ".$attached[$tokURL+0]."</a>".substr($tok, strpos($tok, '"', strpos($tok, '"')+1)+1);
					}
				}
				else if ($attached!=null) {
					$result=$result."<br /><div class=\"ERROR\">Attachment ".$tokURL." Not Found!</div><br />".
						substr($tok, strpos($tok, '"', strpos($tok, '"')+1)+1);
				}
			}
			else
			{
				$result=$result."@#".$tok;
			}
		}
		else if (strcasecmp(substr($tok, 0, 6), "hidden")==0) {
			$tokContent = strstr($tok, '"');
			$rest += strpos($tokContent, '"');
			$tokTitle = substr($tok, 6, strpos($tok, '"')-6);
			$tokTitle = str_replace('\'\'', '"', $tokTitle);
			$tokContent = substr($tokContent, 1, strpos($tokContent, '"', 1)-1);
			$rest = strpos($tok, '"', strpos($tok, '"')+1);
			$tokContent = str_replace('\'\'', '"', $tokContent);
			$result.=
				'<div class="blogShowHiddenContentTitle" style="display:inline"><a href="javascript:showHiddenContentToggle(\'hidden'.$articleID.'_'.$hiddenCounter.'\'); setHeight2(); " title="Show it.">'.$tokTitle.'</a></div>'.
				'<div class="blogShowHiddenContent" id="hidden'.$articleID.'_'.$hiddenCounter.'" style="height:0px; display:none; width:800px;">'.$tokContent.'</div>'.
				substr($tok, $rest+1);
			$hiddenCounter++;

		} else if (strcasecmp(substr($tok, 0, 1), "+")==0) {
			$result = $result."@".substr($tok, 1);
		}
		else {
			if ($first) {
				$result=$tok;
				$first=false;
			}
			else $result = $result."@".$tok;
		}
		$tok = strtok("@");
	}

	// 3. Escape
	//$result = str_replace("@@", "@", $result);
	return $result;

}
function getIndirectArgument($content, $cursor, $attached, $articleID, $blogID)
{
	global $URL, $keyIterator, $imageCounter;
	if (substr($content, $cursor, 2)=='[[') { // Indirect Aggument!
		$parsed = APFSDShtmlConventionParser(substr($content, $cursor+2), '[[', $attached, $articleID, $blogID);
		$argument = $parsed[0];
		$newCursor = $parsed[1];
		$closed = $parsed[2];
		if ($closed === TRUE)
			return array(0=>$argument, 1=>($newCursor+2));
	}
	return array(0=>'', 1=>0);
}


function APFSDShtmlConventionParser($content, $tag, $attached, $articleID, $blogID='default')
{ 	// RETURN: (0=>Parsed Text, 1=>Next Cursor(Parsed until here), 2=>Correctly Closed T/F)

	global $URL, $keyIterator, $imageCounter;

	$rV = '';
	if (!is_null($tag)) {
		if ($tag!='[[')
			$endingTag = '[/'.$tag.']';
		else
			$endingTag = ']]';
	}


	$cursor = 0;
	$length = strlen($content);
	$randomKey = $randKey = rand(0, 10000);

	while ($cursor < $length) {
		$closingArgumentTag = strpos(substr($content, $cursor), ']]');
		// should break if $endingTag is detected.
		$firstOccurrence = strpos(substr($content, $cursor), '[');

		if ($tag === '[[') {
			if ($closingArgumentTag !== FALSE &&
					($firstOccurrence === FALSE ||
					 $closingArgumentTag < $firstOccurrence)) {
				return array(0=>$rV.substr($content, $cursor, $closingArgumentTag), 1=>($cursor + $closingArgumentTag+ 2), 2=>TRUE);
			}
			if ($closingArgumentTag === FALSE)
				return array(0=>'', 1=>0, 2=>FALSE);
		}

		if ($firstOccurrence !== FALSE) { //// REVIEWING HERE.....
			if (!is_null($tag) &&
				(substr($content, $cursor + $firstOccurrence, strlen($endingTag)) 
					== $endingTag)
			   ) {
				return array(0=>$rV.substr($content, $cursor, $firstOccurrence), 1=>($cursor + $firstOccurrence + strlen($endingTag)), 2=>TRUE);
			}
		} else { // No Tag Found
			if (is_null($tag))
				return array(0=>$rV.substr($content, $cursor), 1=>($cursor + $length), 2=>TRUE);
			else
				return array(0=>'', 1=>0, 2=>FALSE);
		}
		if ($tag === '[[') {
			if ($closingArgumentTag !== FALSE &&
					($firstOccurrence === FALSE ||
					 $closingArgumentTag < $firstOccurrence)) {
				return array(0=>$rV.substr($content, $cursor, $closingArgumentTag), 1=>($cursor + $firstOccurrence + 2), 2=>TRUE);
			}
			if ($closingArgumentTag === FALSE)
				return array(0=>'', 1=>0, 2=>FALSE);
		}

		$tagName = '';
		$tagDirectArgument = '';
		$openingNewTag = strpos(substr($content, $cursor+$firstOccurrence+1), '[');
		if ($openingNewTag!==FALSE && $openingNewTag <= 3) {
			$rV.=substr($content, $cursor, $firstOccurrence+1+$openingNewTag);
			$cursor += $firstOccurrence+1+$openingNewTag;
			continue;
		}

		$commandList = array('img', 'url', 'att', 'hid', 'tag', 'qot', 'log');
		$style = 0;
		$tagText = '';
		// check if it's in the valid tag form
		if ($content[$cursor+$firstOccurrence+1]!=']' &&
				$content[$cursor+$firstOccurrence+2]!=']' &&
				$content[$cursor+$firstOccurrence+3]!=']')
		{
			$rV.=substr($content, $cursor, $firstOccurrence);
			$cursor+=$firstOccurrence;

			if ($content[$cursor+4]==']') {
				$tagName = substr($content, $cursor+1, 3);
				$tagDirectArgument = '';
				$cursor += 5;
				$style = 1;
			} else if ($content[$cursor+4]=='=') {
				$closingTag = strpos(substr($content, $cursor+1), ']');
				if ($openingNewTag!==FALSE && $openingNewTag < $closingTag) {
					$rV.=substr($content, $cursor, 1+$openingNewTag-$firstOccurrence);
					$cursor += 1+$openingNewTag-$firstOccurrence;
					continue;
				}
				$tagName = substr($content, $cursor+1, 3);
				$tagDirectArgument = substr($content, $cursor+5, 
						$closingTag - 4);
				$cursor += 6 + strlen($tagDirectArgument);
				$style = 2;
			} else {
				$rV.= substr($content, $cursor, 1);
				$cursor++;
				continue;
			}
			$correctTag = FALSE;
			foreach ($commandList as $value)
				if ($value == $tagName) {
					$correctTag= TRUE;
					break;
				}
			if ($style == 1)
				$tagText='['.$tagName.']';
			else
				$tagText='['.$tagName.'='.$tagDirectArgument.']';
			if ($correctTag===FALSE)
			{
				$rV.=$tagText;
				continue;
			}
		} else {
			$rV.=substr($content, $cursor, $firstOccurrence+1);
			$cursor += $firstOccurrence + 1;
			continue;
		}

		switch($tagName)
		{
			case 'img':
			case 'url':
			case 'att':
				$tagNestedContent = '';
				$tagIndirectArgument='';
				if ($style == 1) {
					$parsed = getIndirectArgument($content, $cursor, $attached, $articleID, $blogID);
					$tagIndirectArgument=$parsed[0];
					$cursor+=$parsed[1];
					$tagContent = APFSDShtmlConventionParser(substr($content, $cursor), $tagName, $attached, $articleID, $blogID);
					if ($tagContent[2]===FALSE) {
						$rV.=$tagText;
						$cursor-=$parsed[1];
						continue;
					}
					$cursor+=$tagContent[1];
					$tagNestedContent = $tagContent[0];
				}
				switch($tagName)
				{
					case 'img':
						if ($tagIndirectArgument=='')
							$tagIndirectArgument = 'APFSDS Blog Image';
						$imageURL=$tagDirectArgument.$tagNestedContent;
						$imageALT=$tagIndirectArgument;
						$rV.="<img src=\"".$imageURL."\" id=\"conventionImage$randomKey$imageCounter\" alt=\"".$imageALT."\" /><script type=\"text/javascript\">imageAutoResizeIMGObjects.push(document.getElementById('conventionImage$randomKey$imageCounter'));</script>";
						$imageCounter++;
						break;
					case 'url':
						$urlstr = $tagDirectArgument.$tagNestedContent;
						$rV.='<a href="'.$urlstr.'" target="_new">';
						if ($tagIndirectArgument=='')
							$tagIndirectArgument = (strlen($urlstr)>30)?
								(substr($urlstr, 0, 30).'...'):$urlstr;
						$rV.=$tagIndirectArgument.'</a>';
						break;
					case 'att':
						$attachmentNum = ($tagDirectArgument.$tagNestedContent) + 0;
						if (isset($attached[$attachmentNum])) {
							if (strcasecmp(".gif",substr($attached[$attachmentNum], strlen($attached[$attachmentNum])-4))==0 ||
								strcasecmp(".jpg",substr($attached[$attachmentNum], strlen($attached[$attachmentNum])-4))==0 ||
								strcasecmp(".jpeg",substr($attached[$attachmentNum], strlen($attached[$attachmentNum])-5))==0 ||
								strcasecmp(".png",substr($attached[$attachmentNum], strlen($attached[$attachmentNum])-4))==0 ||
								strcasecmp(".bmp",substr($attached[$attachmentNum], strlen($attached[$attachmentNum])-4))==0)
							{
								$rV.=
									"<a href=\"".$URL."Blog/AttachedFiles/$blogID/$articleID/".($attached[$attachmentNum])."\" target=\"_blank\"><img src=\"".$URL."Blog/AttachedFiles/$blogID/$articleID/".($attached[$attachmentNum])."\" id=\"conventionImage$randomKey$imageCounter\" /></a><script type=\"text/javascript\">imageAutoResizeIMGObjects.push(document.getElementById('conventionImage$randomKey$imageCounter'));</script>";
								$imageCounter++;
							}
							else
							{
								$rV.=
									"<a href=\"".$URL."Blog/AttachedFiles/$blogID/$articleID/".$attached[$attachmentNum]."\" target=\"_blank\">".
									"Attachment[".($attachmentNum)."]: ".$attached[$attachmentNum]."</a>";
							}
						}
						else if ($attached!=null) {
							$result=$result."<br /><div class=\"ERROR\">Attachment ".$insideTag."/".$attachmentNum." Not Found!</div><br />";
						}
				}
				break;
			case 'hid':
			case 'tag':
			case 'qot':
			case 'log':
				$parsed = getIndirectArgument($content, $cursor, $attached, $articleID, $blogID);
				$tagIndirectArgument=$parsed[0];
				$cursor+=$parsed[1];
				$tagContent = APFSDShtmlConventionParser(substr($content, $cursor), $tagName, $attached, $articleID, $blogID);
				if ($tagContent[2]===FALSE) {
					$rV.=$tagText;
					$cursor-=$parsed[1];
					continue;
				}
				$cursor+=$tagContent[1];
				$tagNestedContent = $tagContent[0];
				switch($tagName)
				{
					case 'hid':
						$keyIterator++;
						$rV.= 
							'<div class="blogShowHiddenContentTitle" style="display:inline"><a href="javascript:showHiddenContentToggle(\'hidden'.$articleID.'_'.$keyIterator.'x'.$randKey.'\'); setHeight2(); " title="Show it.">'.$tagDirectArgument.$tagIndirectArgument.'</a></div>'.
							'<div class="blogShowHiddenContent" id="hidden'.$articleID.'_'.$keyIterator.'x'.$randKey.'" style="height:0px; display:none; width:800px;">'.
							$tagNestedContent.'</div>';
						break;
					case 'tag':
						$rV.=str_replace(array("\n", "\r"), array("",""), $tagNestedContent);
						break;
					case 'qot':
						$rV.= 
							'<div style="margin:10px; margin-top:5px" class="quote">'.
							'<div style="margin-bottom:2px;font-size:9pt">Quote:</div>'.
							'<table cellpadding="6" cellspacing="0" border="0" width="97%">'.
							'<tr><td style="border:1px inset;background:#E0F0FF;font-size:9.5pt;padding-left:5px;padding-right:5px;padding-top:5px">';
						if (strlen($tagIndirectArgument)>0)
							$rV.='<div>Originally Posted by <strong>'.$tagIndirectArgument.'</strong></div>';
						if (strlen($tagDirectArgument)>0)
							$rV.='<div><h4>'.$tagDirectArgument.'</h4></div>';
						$rV.='<div style="font-weight:lighter;font-size:9pt">'.$tagNestedContent.'</div></td></tr></table></div><br />';
						break;
					case 'log':
						$level = 3;
						if (strlen($tagDirectArgument)>0) {
							$level = $tagDirectArgument + 0;
							if ($level<3)
								$level = 3;
							if ($level>100)
								$level = 100;
						}
						$insideTag.= "<table border=\"0\" width=\"98%\"><tr><td style=\"border:1px inset;background:#FFE5E5;font-size:9.5pt;padding-left:5px;padding-right:5px;padding-top:5px;\"><p style=\"color:#FF0000\">------ Confidential Contents Start. Only for Logged Users. ------</p>".$tagNestedContent."<p style=\"color:#FF0000\">------ Confidential Contents End. ------</p></td></tr></table>";
						if ($_SESSION['APFSDS_Perm']<$level || $_SESSION['APFSDS_Logged']!=1) 
						{
							$insideTag = "<table border=\"0\" width=\"98%\"><tr><td style=\"border:1px inset;background:#FFE5E5;font-size:9.5pt;padding-left:5px;padding-right:5px;padding-top:5px;\"><p style=\"color:#FF0000\">You need to log-in to read the hidden contents...</p></td></tr></table>";
						}
						$rV.= $insideTag;
						break;
				}
				break;
			default:
		}
	}
	return array(0=>$rV, 1=>$cursor, 2=>is_null($tag));
}
function addImoticon($key, $filename, $imageURL, $description)
{
	$key = str_replace("'","`",substr($key, 0, 8));
	if ($_SESSION['APFSDS_Logged']==1 && $_SESSION['APFSDS_Perm']>=4) {
		$rs = DBQ("select * from IMOTICONS where imoticon='$key'");
		$r = mysql_fetch_assoc($rs);
		if ($r)
		{
			mysql_free_result($rs);
			return "ERROR: Imoticon @$key Already Exists!";
		}
		mysql_free_result($rs);
		if (strlen($key)<1) {
			return "ERROR: No valid key (@$key) was provided...";
		}
		if (strlen($key)>8) {
			return "ERROR: Key $key is too long...";
		}
		$authorized = '0';
		if ($_SESSION['APFSDS_Perm']>=100)
			$authorized = '1';
		DBQ("insert into IMOTICONS values ('$key',".
				"'".$filename."',".
				"'".urlencode($valueURL)."',".
				"'".$description."',".
				"'".$_SESSION['APFSDS_ID']."',".
				$authorized.", ".
				"NOW())");
		return 0;
	}
	return "ERROR: Permission Denied.";
}
function removeImoticon($key)
{
	global $LOCAL, $LOCALSEPERATOR;

	$key = str_replace("'","`",substr($key, 0, 8));
	if ($_SESSION['APFSDS_Logged']==1 && $_SESSION['APFSDS_Perm']>=4) {
		$rs = DBQ("select creator, filename from IMOTICONS where imoticon='$key'");
		$r = mysql_fetch_assoc($rs);
		if ($r)
		{
			$creator = $r['creator'];
			$filename = $r['filename'];
			mysql_free_result($rs);
			if ($creator == $_SESSION['APFSDS_ID'] || $_SESSION['APFSDS_Perm']>=100) {
				DBQ("delete from IMOTICONS where imoticon='$key'");

				if (strlen($filename)>0) {
					if (unlink($LOCAL."Imoticons".$LOCALSEPERATOR.$filename))
						print ("Imoticon Local Copy is Successfully Removed...");
					else
						return ("ERROR: Imoticon file could not be deleted...");
				}
				return 0;
			}
			return "ERROR: Permission Denied (Username mismatched).";
		}
		mysql_free_result($rs);
		return "ERROR: Imoticon $key Not Found.";
	}
	return "ERROR: Permission Denied (You need to be at least level 4)";
}
function parseImoticons($content)
{
	global $imoticonDB, $imoticonKeyList, $URL;


	$cursor = 0;
	$length = strlen($content);
	$rV = '';
	while ($cursor < $length) {
		$pos = strpos($content, '@', $cursor);
		if ($pos===FALSE) {
			return $rV.substr($content, $cursor);
		}
		$rV.=substr($content, $cursor, $pos-$cursor);
		$cursor = $pos;
		$possibleKey = substr($content, $cursor+1, 8);
		$endingKey = array(' ', "\n", "\r", "<", "@");
		$endedBySpace=FALSE;
		$keyEnded = 8;
		foreach ($endingKey as $value) {
			$keyEnded2 = strpos($possibleKey, $value);
			if ($keyEnded===FALSE || ($keyEnded!==FALSE && $keyEnded>$keyEnded2 && $keyEnded2!==FALSE)) {
				$keyEnded = $keyEnded2;
				if ($value == ' ')
					$endedBySpace = TRUE;
				else
					$endedBySpace = FALSE;
			}
		}
		if ($keyEnded!==FALSE) {
			$possibleKey = substr($possibleKey, 0, $keyEnded);
		} else
		if (strlen($possibleKey)<1) {
			$rV.="@";
			$cursor++;
			continue;
		}
		if (isset($imoticonDB[$possibleKey])) {
			$rV.="<img src=\"".$imoticonDB[$possibleKey]."\" alt=\"Imoticon $possibleKey\" />";
			$cursor += 1 + strlen($possibleKey);
			if ($endedBySpace===TRUE)
				$cursor++;
		} else {
			$rV.="@".$possibleKey;
			$cursor += 1 + strlen($possibleKey);
		}
	}
	return $rV;
}
function htmlConvention($content, $attached, $articleID, $blogID='default')
{
	global $URL, $keyIterator, $imageCounter;


	$hiddenCounter = 0;
	$randomKey = rand(100, 999);
	// $attachmentURL is an array of URLs for the attachment. 
	
	// 1. Simple Tags
	$content = str_replace(array("[s]", "[del]"), array("<del>", "<del>"), $content);
	$content = str_replace(array("[/s]", "[/del]"), array("</del>", "</del>"), $content);
	$content = str_replace(array("[u]", "[/u]"), array("<u>", "</u>"), $content);
	$content = str_replace(array("[i]", "[/i]"), array("<i>", "</i>"), $content);
	$content = str_replace(array("[b]", "[/b]"), array("<b>", "</b>"), $content);
	$content = str_replace(array("[]"), array("&nbsp;"), $content);
	$content = str_replace(array("[l]"), array("&#91;"), $content);
	$content = str_replace(array("[r]"), array("&#93;"), $content);


	$parsed = APFSDShtmlConventionParser($content, NULL, $attached, $articleID, $blogID='default');
	$content = $parsed[0];

	$content = parseImoticons($content);
	
	return $content;
	// From here, it's version 2, which is disabled...

	$result = '';
	$first = true;

	$cursor = 0;
	$hiddenCounter = 0;
	$length = strlen($content);

	while ($cursor <= $length) {
		$firstOccurrence = strpos(substr($content, $cursor), '[img]');
		if ($firstOccurrence === FALSE) $firstOccurrence = -1;

		$style = 1;
		$occ = strpos(substr($content, $cursor), '[img]');
		if ($occ !== FALSE) $firstOccurrence = ($firstOccurrence>$occ || $firstOccurrence===-1)?$occ:$firstOccurrence;
		$occ = strpos(substr($content, $cursor), '[url]');
		if ($occ !== FALSE) $firstOccurrence = ($firstOccurrence>$occ || $firstOccurrence===-1)?$occ:$firstOccurrence;
		$occ = strpos(substr($content, $cursor), '[att]');
		if ($occ !== FALSE) $firstOccurrence = ($firstOccurrence>$occ || $firstOccurrence===-1)?$occ:$firstOccurrence;
		$occ = strpos(substr($content, $cursor), '[hid]');
		if ($occ !== FALSE) $firstOccurrence = ($firstOccurrence>$occ || $firstOccurrence===-1)?$occ:$firstOccurrence;
		$occ = strpos(substr($content, $cursor), '[tag]');
		if ($occ !== FALSE) $firstOccurrence = ($firstOccurrence>$occ || $firstOccurrence===-1)?$occ:$firstOccurrence;
		$occ = strpos(substr($content, $cursor), '[qot]');
		if ($occ !== FALSE) $firstOccurrence = ($firstOccurrence>$occ || $firstOccurrence===-1)?$occ:$firstOccurrence;
		$occ = strpos(substr($content, $cursor), '[log]');
		if ($occ !== FALSE) $firstOccurrence = ($firstOccurrence>$occ || $firstOccurrence===-1)?$occ:$firstOccurrence;
		$styleOneFirstOcc = $firstOccurrence;

		$occ = strpos(substr($content, $cursor), '[url=');
		if ($occ !== FALSE) {
			$firstOccurrence = ($firstOccurrence>$occ || $firstOccurrence===-1)?$occ:$firstOccurrence;
			$style = 2;
		}
		$occ = strpos(substr($content, $cursor), '[img=');
		if ($occ !== FALSE) {
			$firstOccurrence = ($firstOccurrence>$occ || $firstOccurrence===-1)?$occ:$firstOccurrence;
			$style = 2;
		}
		$occ = strpos(substr($content, $cursor), '[att=');
		if ($occ !== FALSE) {
			$firstOccurrence = ($firstOccurrence>$occ || $firstOccurrence===-1)?$occ:$firstOccurrence;
			$style = 2;
		}

		if ($firstOccurrence===-1) {
			$result .= substr($content, $cursor);
			break;
		} else {
			if ($styleOneFirstOcc!=-1 && $styleOneFirstOcc <= $firstOccurrence)
				$style=1;
		}
		$result .= substr($content, $cursor, $firstOccurrence);

		$endTagFound = TRUE;
		switch (substr($content, $cursor+$firstOccurrence+1, 3)){
			case 'img':
				if ($style==1) {
					$endTag = strpos(substr($content, $cursor+$firstOccurrence+5), '[/img]');
					if ($endTag === FALSE)
					{
						$endTagFound = FALSE;
						$result.="<div class=\"ERROR\"><h3>[img] Not Closed</h3>".substr($content, $cursor+$firstOccurrence)."</div>";
						break;
					}
					$insideTag = substr($content, $cursor+$firstOccurrence+5, $endTag);
					$result.="<img src=\"".trim($insideTag)."\" alt=\"APFSDS Blog Image\" />";
					$cursor += $firstOccurrence + 5 + $endTag + 6;
				} else { // $style==2
					$endTag = strpos(substr($content, $cursor+$firstOccurrence+5), ']');
					if ($endTag === FALSE)
					{
						$endTagFound = FALSE;
						$result.="<div class=\"ERROR\"><h3>[img= Not Closed</h3>".substr($content, $cursor+$firstOccurrence)."</div>";
						break;
					}
					$insideTag = substr($content, $cursor+$firstOccurrence+5, $endTag);
					$result.="<img src=\"".trim($insideTag)."\" alt=\"APFSDS Blog Image\" />";
					$cursor += $firstOccurrence + 5 + $endTag + 1;
				}
				break;
			case 'url':
				if ($style==1) {
					$endTag = strpos(substr($content, $cursor+$firstOccurrence+5), '[/url]');
					if ($endTag === FALSE)
					{
						$endTagFound = FALSE;
						$result.="<div class=\"ERROR\"><h3>[url] Not Closed</h3>".substr($content, $cursor+$firstOccurrence)."</div>";
						break;
					}
					$insideTag = substr($content, $cursor+$firstOccurrence+5, $endTag);

					if (substr($insideTag, 0, 2)=='[[') {
						$insideTag=substr($insideTag, 2);
						$titleEnd = strpos($insideTag, ']]');
						if ($titleEnd === FALSE) {
							$title = mb_substr($insideTag, 0, 30);
							if (mb_strlen($insideTag)>30)
								$title.="...";
							$result.="<a href=\"".$insideTag."\" target=\"_new\" >".$title."</a>";
						} else {
							$title = substr($insideTag, 0, $titleEnd);
							$title = htmlConvention($title, $attached, $articleID, $blogID);
							$url = trim(substr($insideTag, $titleEnd+2));
							$result.="<a href=\"$url\" target=\"_new\" >$title</a>";
						}

					} else {
						$title = mb_substr($insideTag, 0, 30);
						if (mb_strlen($insideTag)>30)
							$title.="...";
						$result.="<a href=\"".$insideTag."\" target=\"_new\" >".$title."</a>";
					}
					$cursor += $firstOccurrence + 5 + $endTag + 6;
				} else {
					$endTag = strpos(substr($content, $cursor+$firstOccurrence+5), '[/url]');
					$offsetAdd = 0;
					if ($endTag === FALSE)
					{
						$urlEnd = strpos(substr($content, $cursor+$firstOccurrence+5), ']');
						if ($urlEnd === FALSE) {
							$endTagFound = FALSE;
							$result.="<div class=\"ERROR\"><h3>[url= Not Closed 1</h3>".substr($content, $cursor+$firstOccurrence)."</div>";
							break;
						} else {
							$url = substr($content, $cursor+$firstOccurrence+5, $urlEnd);
							$title = mb_substr($url, 0, 30);
							if (mb_strlen($url)>30)
								$title.="...";
							$result.="<a href=\"$url\" target=\"_new\" >$title</a>";
						}
						$offsetAdd = $urlEnd+1;
					} else {
						$insideTag = substr($content, $cursor+$firstOccurrence+5, $endTag);
						$urlEnd = strpos($insideTag, ']');
						if ($urlEnd === FALSE || $urlEnd > $endTag) {
							$endTagFound = FALSE;
							$result.="<div class=\"ERROR\"><h3>[url= Not Closed 2</h3>".substr($content, $cursor+$firstOccurrence)."</div>";
							break;
						} else {
							$url = substr($insideTag, 0, $urlEnd);
							$title = trim(substr($insideTag, $urlEnd+1));
							$title = htmlConvention($title, $attached, $articleID, $blogID);
							$result.="<a href=\"$url\" target=\"_new\" >$title</a>";
						}
						$offsetAdd = $endTag + 6;
					}
					$cursor += $firstOccurrence + 5 + $offsetAdd;
				}
				break;
			case 'att':
				$endTag = strpos(substr($content, $cursor+$firstOccurrence+5), '[/att]');
				$offsetAdd = 0;
				if ($style==1) {
					if ($endTag === FALSE)
					{
						$endTagFound = FALSE;
						$result.="<div class=\"ERROR\"><h3>[att] Not Closed</h3>".substr($content, $cursor+$firstOccurrence)."</div>";
						break;
					}
					$insideTag = trim(substr($content, $cursor+$firstOccurrence+5, $endTag));
					$attachmentNum = $insideTag+0;

					$offsetAdd = $endTag + 6;
				} else {
					$endTag = strpos(substr($content, $cursor+$firstOccurrence+5), ']');

					if ($endTag === FALSE)
					{
						$endTagFound = FALSE;
						$result.="<div class=\"ERROR\"><h3>[att= Not Closed</h3>".substr($content, $cursor+$firstOccurrence)."</div>";
						break;
					}
					$insideTag = trim(substr($content, $cursor+$firstOccurrence+5, $endTag));
					$attachmentNum = $insideTag+0;

					$offsetAdd = $endTag + 1;
				}
	
				if (isset($attached[$attachmentNum])) {
					if (strcasecmp(".gif",substr($attached[$attachmentNum], strlen($attached[$attachmentNum])-4))==0 ||
						strcasecmp(".jpg",substr($attached[$attachmentNum], strlen($attached[$attachmentNum])-4))==0 ||
						strcasecmp(".jpeg",substr($attached[$attachmentNum], strlen($attached[$attachmentNum])-5))==0 ||
						strcasecmp(".png",substr($attached[$attachmentNum], strlen($attached[$attachmentNum])-4))==0 ||
						strcasecmp(".bmp",substr($attached[$attachmentNum], strlen($attached[$attachmentNum])-4))==0)
					{
						$result.=
							"<a href=\"".$URL."Blog/AttachedFiles/$blogID/$articleID/".($attached[$attachmentNum])."\" target=\"_blank\"><img src=\"".$URL."Blog/AttachedFiles/$blogID/$articleID/".($attached[$attachmentNum])."\" id=\"conventionImage$randomKey$imageCounter\" /></a><script type=\"text/javascript\">imageAutoResizeIMGObjects.push(document.getElementById('conventionImage$randomKey$imageCounter'));</script>";
						$imageCounter++;
					}
					else
					{
						$result.=
							"<a href=\"".$URL."Blog/AttachedFiles/$blogID/$articleID/".$attached[$attachmentNum]."\" target=\"_blank\">".
							"Attachment[".($attachmentNum)."]: ".$attached[$attachmentNum]."</a>";
					}
				}
				else if ($attached!=null) {
					$result=$result."<br /><div class=\"ERROR\">Attachment ".$insideTag."/".$attachmentNum." Not Found!</div><br />";
				}

				$cursor += $firstOccurrence + 5 + $offsetAdd;
				break;
			case 'hid':
				$endTag = strpos(substr($content, $cursor+$firstOccurrence+5), '[/hid]');
				if ($endTag === FALSE)
				{
					$endTagFound = FALSE;
					$result.="<div class=\"ERROR\"><h3>[hid] Not Closed</h3>".substr($content, $cursor+$firstOccurrence)."</div>";
					break;
				}
				$insideTag = substr($content, $cursor+$firstOccurrence+5, $endTag);
				$randKey = rand(0, 10000);

				if (substr($insideTag, 0, 2)=='[[') {
					$insideTag=substr($insideTag, 2);
					$titleEnd = strpos($insideTag, ']]');
					if ($titleEnd === FALSE) {
						$result.=
							'<div class="blogShowHiddenContentTitle" style="display:inline"><a href="javascript:showHiddenContentToggle(\'hidden'.$articleID.'_'.$hiddenCounter.'x'.$randKey.'\'); setHeight2(); " title="Show it.">Show It</a></div>'.
							'<div class="blogShowHiddenContent" id="hidden'.$articleID.'_'.$hiddenCounter.'x'.$randKey.'" style="height:0px; display:none; width:800px;">'.htmlConvention($insideTag,$attached,$articleID, $blogID).'</div>';
						$hiddenCounter++;
					} else {
						$title = substr($insideTag, 0, $titleEnd);
						$title = htmlConvention($title, $attached, $articleID, $blogID);
						$url = trim(substr($insideTag, $titleEnd+2));

						$result.=
							'<div class="blogShowHiddenContentTitle" style="display:inline"><a href="javascript:showHiddenContentToggle(\'hidden'.$articleID.'_'.$hiddenCounter.'x'.$randKey.'\'); setHeight2(); " title="Show it.">'.$title.'</a></div>'.
							'<div class="blogShowHiddenContent" id="hidden'.$articleID.'_'.$hiddenCounter.'x'.$randKey.'" style="height:0px; display:none; width:800px;">'.htmlConvention($url,$attached,$articleID,$blogID).'</div>';
						$hiddenCounter++;
					}

				} else {
					$result.=
						'<div class="blogShowHiddenContentTitle" style="display:inline"><a href="javascript:showHiddenContentToggle(\'hidden'.$articleID.'_'.$hiddenCounter.'x'.$randKey.'\'); setHeight2(); " title="Show it.">Show It</a></div>'.
						'<div class="blogShowHiddenContent" id="hidden'.$articleID.'_'.$hiddenCounter.'x'.$randKey.'" style="height:0px; display:none; width:800px;">'.htmlConvention($insideTag,$attached,$articleID,$blogID).'</div>';
					$hiddenCounter++;
				}
				$cursor += $firstOccurrence + 5 + $endTag + 6;
	
				break;
			case 'tag':
				$endTag = strpos(substr($content, $cursor+$firstOccurrence+5), '[/tag]');
				if ($endTag === FALSE)
				{
					$endTagFound = FALSE;
					$result.="<div class=\"ERROR\"><h3>[tag] Not Closed</h3>".substr($content, $cursor+$firstOccurrence)."</div>";
					break;
				}
				$insideTag = substr($content, $cursor+$firstOccurrence+5, $endTag);
				$insideTag = str_replace(array("\n", "\r"), array("",""), $insideTag);
				$result.= $insideTag;

				$cursor += $firstOccurrence + 5 + $endTag + 6;
				break;
			case 'log':
				$endTag = strpos(substr($content, $cursor+$firstOccurrence+5), '[/log]');
				if ($endTag === FALSE)
				{
					$endTagFound = FALSE;
					$result.="<div class=\"ERROR\"><h3>[log] Not Closed</h3>".substr($content, $cursor+$firstOccurrence)."</div>";
					break;
				}
				$insideTag = "<table border=\"0\" width=\"98%\"><tr><td style=\"border:1px inset;background:#FFE5E5;font-size:9.5pt;padding-left:5px;padding-right:5px;padding-top:5px;\"><p style=\"color:#FF0000\">------ Confidential Contents Start. Only for Logged Users. ------</p>".substr($content, $cursor+$firstOccurrence+5, $endTag)."<p style=\"color:#FF0000\">------ Confidential Contents End. ------</p></td></tr></table>";
				if ($_SESSION['APFSDS_Perm']<3 || $_SESSION['APFSDS_Logged']!=1) 
				{
					$insideTag = "<p style=\"color:#1010FF\">[You need to log in to read the hidden contents :P]</p>";
					$insideTag = "<table border=\"0\" width=\"98%\"><tr><td style=\"border:1px inset;background:#FFE5E5;font-size:9.5pt;padding-left:5px;padding-right:5px;padding-top:5px;\"><p style=\"color:#FF0000\">You need to log-in to read the hidden contents...</p></td></tr></table>";
				}
				$result.= $insideTag;

				$cursor += $firstOccurrence + 5 + $endTag + 6;
				break;
	
			case 'qot':
				$endTag = strpos(substr($content, $cursor+$firstOccurrence+5), '[/qot]');
				if ($endTag === FALSE)
				{
					$endTagFound = FALSE;
					$result.="<div class=\"ERROR\"><h3>[qot] Not Closed</h3>".substr($content, $cursor+$firstOccurrence)."</div>";
					break;
				}
				$insideTag = substr($content, $cursor+$firstOccurrence+5, $endTag);

				$title='';
				$inside='';

				if (substr($insideTag, 0, 2)=='[[') {
					$insideTag=substr($insideTag, 2);
					$titleEnd = strpos($insideTag, ']]');
					if ($titleEnd === FALSE) {
						$title='';
						$inside = $insideTag;
					} else {
						$title = substr($insideTag, 0, $titleEnd);
						$title = htmlConvention($title, $attached, $articleID, $blogID);
						$inside = substr($insideTag, $titleEnd+2);
					}

				} else {
					$title='';
					$inside = $insideTag;
				}
				$result .= '<div style="margin:10px; margin-top:5px" class="quote">'.
					'<div style="margin-bottom:2px;font-size:9pt">Quote:</div>'.
					'<table cellpadding="6" cellspacing="0" border="0" width="97%">'.
					'<tr><td style="border:1px inset;background:#E0F0FF;font-size:9.5pt;padding-left:5px;padding-right:5px;padding-top:5px">';
				if ($title!='')
					$result.='<div>Originally Posted by <strong>'.$title.'</strong></div>';
				$result.='<div style="font-weight:lighter;font-size:9pt">'.htmlConvention($inside,$attached,$articleID,$blogID).'</div></td></tr></table></div><br />';

				$cursor += $firstOccurrence + 5 + $endTag + 6;
				break;
			default:
				$cursor += $firstOccurrence + 1;
				$result.= substr($content, $cursor, $firstOccurrence+1)."[error occurred]";

				break;
		}

		if ($endTagFound === FALSE)
			break;
		
	}
	return $result;

	// From Here, It's for version 1 which is deprecated and not prohibited.

	$result = '';

	// 2. Tags with URL and Attachment
	$tok = strtok($content, "@");
	while ($tok !== false)
	{
		if (strcasecmp(substr($tok, 0, 3), "img")==0) {
			$tokURL = strstr($tok, '"');
			$tokURL = substr($tokURL, 1, strpos($tokURL, '"', 1)-1);
			$result=$result.
				"<img src=\"$tokURL\" alt=\"APFSDS Blog Image\" id=\"conventionImage$randomKey$imageCounter\"  /><script type=\"text/javascript\">imageAutoResizeIMGObjects.push(document.getElementById('conventionImage$randomKey$imageCounter'));</script>".substr($tok, strpos($tok, '"', strpos($tok, '"')+1)+1);
			$imageCounter++;
		}
		else if (strcasecmp(substr($tok, 0, 4), "link")==0) {
			$tokURL = strstr($tok, '"');
			$tokURL = substr($tokURL, 1, strpos($tokURL, '"', 1)-1);
			$result=$result.
				"<a href=\"$tokURL\" target=\"_blank\">$tokURL</a>".substr($tok, strpos($tok, '"', strpos($tok, '"')+1)+1);
		}
		else if (strcasecmp(substr($tok, 0, 1), "#")==0) {
			$tokURL = strstr($tok, '"');
			if (strlen($tokURL)>0 && strpos($tokURL, '"', 1)>0)
			{
				$tokURL = substr($tokURL, 1, strpos($tokURL, '"', 1)-1);
				if (isset($attached[$tokURL+0])) {
					if (strcasecmp(".gif",substr($attached[$tokURL+0], strlen($attached[$tokURL+0])-4))==0 ||
						strcasecmp(".jpg",substr($attached[$tokURL+0], strlen($attached[$tokURL+0])-4))==0 ||
						strcasecmp(".jpeg",substr($attached[$tokURL+0], strlen($attached[$tokURL+0])-5))==0 ||
						strcasecmp(".png",substr($attached[$tokURL+0], strlen($attached[$tokURL+0])-4))==0 ||
						strcasecmp(".bmp",substr($attached[$tokURL+0], strlen($attached[$tokURL+0])-4))==0)
					{
						$result=$result.
							"<img src=\"".$URL."Blog/AttachedFiles/$blogID/$articleID/".urlencode($attached[$tokURL+0])."\" id=\"conventionImage$randomKey$imageCounter\" /><script type=\"text/javascript\">imageAutoResizeIMGObjects.push(document.getElementById('conventionImage$randomKey$imageCounter'));</script>".substr($tok, strpos($tok, '"', strpos($tok, '"')+1)+1);
						$imageCounter++;
					}
					else
					{
						$result=$result.
							"<a href=\"".$URL."Blog/AttachedFiles/$blogID/$articleID/".$attached[$tokURL+0]."\" target=\"_blank\">".
							"Attachment[".($tokURL+0)."]: ".$attached[$tokURL+0]."</a>".substr($tok, strpos($tok, '"', strpos($tok, '"')+1)+1);
					}
				}
				else if ($attached!=null) {
					$result=$result."<br /><div class=\"ERROR\">Attachment ".$tokURL." Not Found!</div><br />".
						substr($tok, strpos($tok, '"', strpos($tok, '"')+1)+1);
				}
			}
			else
			{
				$result=$result."@#".$tok;
			}
		}
		else if (strcasecmp(substr($tok, 0, 6), "hidden")==0) {
			$tokContent = strstr($tok, '"');
			$rest += strpos($tokContent, '"');
			$tokTitle = substr($tok, 6, strpos($tok, '"')-6);
			$tokTitle = str_replace('\'\'', '"', $tokTitle);
			$tokContent = substr($tokContent, 1, strpos($tokContent, '"', 1)-1);
			$rest = strpos($tok, '"', strpos($tok, '"')+1);
			$tokContent = str_replace('\'\'', '"', $tokContent);
			$result.=
				'<div class="blogShowHiddenContentTitle" style="display:inline"><a href="javascript:showHiddenContentToggle(\'hidden'.$articleID.'_'.$hiddenCounter.'\'); setHeight2(); " title="Show it.">'.$tokTitle.'</a></div>'.
				'<div class="blogShowHiddenContent" id="hidden'.$articleID.'_'.$hiddenCounter.'" style="height:0px; display:none; width:800px;">'.$tokContent.'</div>'.
				substr($tok, $rest+1);
			$hiddenCounter++;

		} else if (strcasecmp(substr($tok, 0, 1), "+")==0) {
			$result = $result."@".substr($tok, 1);
		}
		else {
			if ($first) {
				$result=$tok;
				$first=false;
			}
			else $result = $result."@".$tok;
		}
		$tok = strtok("@");
	}

	// 3. Escape
	//$result = str_replace("@@", "@", $result);
	return $result;
}
function listFromList($list, $delim)
{
	if (!is_array($list))
		return false;
	$returnArray = array();
	$count = 0;
	$num = sizeof($list);
	for ($i=0; $i<$num; $i++) {
		if ($list[$i]==='' || $list[$i]===null)
			continue;
		$l2 = explode($delim, $list[$i]);
		for ($j=0; $j<sizeof($l2); $j++) {
			if ($l2[$j]==='' || $l2[$j]===null)
				continue;
			$returnArray[$count] = $l2[$j];
			$count++;
		}
	}
	return $returnArray;
}
$msg[0] = mb_convert_encoding('문자메세지 보내기', 'UTF-8', 'EUC-KR');

?>

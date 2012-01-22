<?

// Dedicated for babot.net old version.
// 
// This version is for testing. Not for actual migration
//



$oldDB = 'homepageBackup';
$newDB = 'homepage';
$IP = 'localhost';
$ID = 'homepage';
$PWD= 'bhdmzx53';
$DEBUG = false;

include_once("Common/common.php");
DBConnect();

function newDB()
{
	DBClose();
	debug("Close DB");
	$link = mysql_connect($GLOBALS['IP'], $GLOBALS['ID'], $GLOBALS['PWD']) or die ("Cannot connect DB");
	debug("Switch to newDB");
	mysql_select_db($GLOBALS['newDB'], $link) or die("Cannot Select ".$GLOBALS['newDB']);
	$GLOBALS['_link'] = $link;
	$GLOBALS['db_conn'] = true;
	return $link;
}
function oldDB()
{
	DBClose();
	debug("Close DB");
	$link = mysql_connect($GLOBALS['IP'], $GLOBALS['ID'], $GLOBALS['PWD']) or die ("Cannot connect DB");
	debug("Switch to oldDB(".$GLOBALS['oldDB'].")");
	mysql_select_db($GLOBALS['oldDB'], $link) or die("Cannot Select ".$GLOBALS['oldDB']);
	$GLOBALS['_link'] = $link;
	$GLOBALS['db_conn'] = true;
	return $link;
}

// migrate USERS, BBS, BBS_ARTICLE, BBS_CONTENT, BBS_COMMENT into the new DB
oldDB();
$rs = DBQ("select * from BBS_ARTICLE where bbs_id='guestbook' order by id ASC");
while ($r = mysql_fetch_assoc($rs))
{
	$rs2 = DBQ("select content from BBS_CONTENT where bbs_id='guestbook' ".
			"and id=".$r['id']." order by sequence ASC");
	$content = '';
	while ($r2 = mysql_fetch_row($rs2))
		$content=$content.$r2[0];
	// print "<HR>".html2text($content)."<HR>".html2text(sqlSafe($content))."<HR>";
	mysql_free_result($rs2);
	newDB();

	DBQ("INSERT INTO BlogArticle values(".
		"'guestbook', ".
		$r['id'].", ".
		(($r['thread']!=null)?$r['thread']:"null").", ".
		"'".sqlSafe($r['title'])."', ".
		(($r['writer']!=null)?"'".sqlSafe($r['writer'])."', ":"null, ").
		(($r['guestinfo']!=null)?"'".sqlSafe($r['guestinfo'])."', ":"null, ").
		"'', '', ".
		(($r['guestpassword']!=null)?"'".sqlSafe($r['guestpassword'])."', ":"null, ").
		"'".sqlSafe($r['createon'])."', ".
		(($r['modified']!=null)?"'".sqlSafe($r['modified'])."', ":"null, ").
		"'".sqlSafe($r['ip'])."', ".
		$r['html'].", ".
		$r['hit'].", ".
		(($r['filename']!=null)?"1, ":"0, ").
		"0, ".
		"'".sqlSafe($content)."', null, null, null, 0)");
	if ($r['filename']!=null)
		DBQ("INSERT INTO BlogArticleAttached values (".
			"'guestbook', ".
			$r['id'].", ".
			"0, ".
			"'".sqlSafe($r['filename'])."', ".
			"'".sqlSafe($r['filemime'])."')");
	oldDB();
	$rsc = DBQ("select * from BBS_COMMENT where bbs_id='guestbook'and ".
			"id = ".$r['id']." order by sequence ASC");
	newDB();
	$commentContent='';
	$lastWriter=''; $lastGuestInfo=''; $lastCreateOn=''; $lastIP='';
	while ($rc = mysql_fetch_assoc($rsc))
	{
		if ($commentContent!='' && $rc['continued']==0)
		{
			DBQ("INSERT INTO BlogComment values (".
				"'guestbook', ".
				$r['id'].", ".
				"null, ".
				"null, ".
				(($lastWriter!=null)?
				 	("'".sqlSafe($lastWriter)."', null, null, null, null, "):
					("null, '".sqlSafe($lastGuestInfo)."', '', '', '', ")
				).
				"'".sqlSafe($lastIP)."', ".
				"'".sqlSafe($lastCreateOn)."', ".
				"null, ".
				"'".sqlSafe($commentContent)."', ".
				"0)");
			$commentContent='';
		}
		$commentContent=$commentContent.$rc['content'];
		$lastWriter=$rc['writer']; $lastGuestInfo=$rc['guestinfo']; 
		$lastCreateOn=$rc['createon']; $lastIP=$rc['ip'];
	}
	if ($commentContent!='')
		DBQ("INSERT INTO BlogComment values (".
			"'guestbook', ".
			$r['id'].", ".
			"null, ".
			"null, ".
			(($lastWriter!=null)?
				("'".sqlSafe($lastWriter)."', null, null, null, null, "):
				("null, '".sqlSafe($lastGuestInfo)."', '', '', '', ")
			).
			"'".sqlSafe($lastIP)."', ".
			"'".sqlSafe($lastCreateOn)."', ".
			"null, ".
			"'".sqlSafe($commentContent)."', ".
			"0)");
	mysql_free_result($rsc);
	oldDB();
}
mysql_free_result($rs);

?>

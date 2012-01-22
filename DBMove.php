<?

// Dedicated for babot.net old version.
// 
// This version is for testing. Not for actual migration
//


$DROP = 'YES';
require("DBSchema.php");

$oldDB = 'homepage';
$newDB = 'apfsds';
$IP = 'localhost';
$ID = 'homepage';
$PWD= 'bhdmzx53';
$DEBUG = false;

DBClose();
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
$rs = DBQ("select * from USERS");
newDB();
while ($r = mysql_fetch_assoc($rs))
{
	DBQ("INSERT INTO USERS VALUES('".sqlSafe($r['id'])."', '".sqlSafe($r['password'])."', ".
		$r['permission'].", ".$r['level'].", ".
		$r['experience'].",".$r['money'].", ".
		"'".sqlSafe($r['playingas'])."', '".sqlSafe($r['realname'])."', null, ".
		$r['sex'].", '".sqlSafe($r['birth'])."', '".sqlSafe($r['telephone'])."', ".
		"'".sqlSafe($r['cellular'])."', '".sqlSafe($r['address'])."', ".
		"'".sqlSafe($r['job'])."', '".sqlSafe($r['email'])."', '".sqlSafe($r['comment'])."', ".
		"null , '".sqlSafe($r['lastlogdate'])."', '".sqlSafe($r['lastlogip'])."', ".
		"'".sqlSafe($r['undefined'])."')");
}
mysql_free_result($rs);
oldDB();
$rs = DBQ("select * from BBS");
// dealt as a tag. moves into 'default' Blog
newDB();
while ($r = mysql_fetch_assoc($rs))
{
	if (substr($r['id'],0,2)=='NN')
		continue;
	if ($r['id']=='guestbook')
		continue;
	DBQ("INSERT INTO BlogTag values(".
		"'default', null, ".
		"'".sqlSafe($r['id'])."', ".
		"0, ".
		"0, ".
		$r['permission_read'].", ".
		$r['permission_write'].")");
}
mysql_free_result($rs);
oldDB();
$rs = DBQ("select * from BBS_ARTICLE order by id ASC");
while ($r = mysql_fetch_assoc($rs))
{
	if (substr($r['bbs_id'],0,2)=='NN')
		continue;
	if ($r['bbs_id']=='guestbook')
		continue;
	$rs2 = DBQ("select content from BBS_CONTENT where bbs_id='".$r['bbs_id']."' ".
			"and id=".$r['id']." order by sequence ASC");
	$content = '';
	while ($r2 = mysql_fetch_row($rs2))
		$content=$content.$r2[0];
	// print "<HR>".html2text($content)."<HR>".html2text(sqlSafe($content))."<HR>";
	mysql_free_result($rs2);
	newDB();
	$rs3 = DBQ("select tagID from BlogTag where tagTitle='".$r['bbs_id']."'");
	$rtag = mysql_fetch_row($rs3);
	$tagID = $rtag[0];
	mysql_free_result($rs3);

	DBQ("INSERT INTO BlogArticle values(".
		"'default', ".
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
			"'default', ".
			$r['id'].", ".
			"0, ".
			"'".sqlSafe($r['filename'])."', ".
			"'".sqlSafe($r['filemime'])."')");
	DBQ("INSERT INTO BlogTagArticleAssoc values (".
		"'default', ".
		"$tagID, ".
		$r['id'].")");
	oldDB();
	$rsc = DBQ("select * from BBS_COMMENT where bbs_id='".$r['bbs_id']."'and ".
			"id = ".$r['id']." order by sequence ASC");
	newDB();
	$commentContent='';
	$lastWriter=''; $lastGuestInfo=''; $lastCreateOn=''; $lastIP='';
	while ($rc = mysql_fetch_assoc($rsc))
	{
		if ($commentContent!='' && $rc['continued']==0)
		{
			DBQ("INSERT INTO BlogComment values (".
				"'default', ".
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
			"'default', ".
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

$DEBUG = true;

oldDB();
$rs = DBQ("select * from STATUS");
newDB();
while ($r = mysql_fetch_row($rs))
	DBQ("INSERT INTO STATUS values (".($r[0]+1).")");
mysql_free_result($rs);

oldDB();
$rs = DBQ("select * from STATUS_DEF");
newDB();
while ($r = mysql_fetch_assoc($rs))
	DBQ("INSERT INTO STATUS_DEF values (".
			(($r['id']+0)+1).',\''.sqlSafe($r['description']).'\',\''.sqlSafe($r['icon']).'\')');
mysql_free_result($rs);

oldDB();
$rs = DBQ("select * from MEMO order by createon");
newDB();
while ($r=mysql_fetch_assoc($rs))
	DBQ("INSERT INTO MEMO values (".
			$r['id'].', null, \''.sqlSafe($r['content']).'\', \''.sqlSafe($r['ip']).'\',\''.sqlSafe($r['createon']).'\')');
mysql_free_result($rs);

oldDB();
$rs = DBQ("select * from IPSTAT");
newDB();
while ($r=mysql_fetch_row($rs))
	DBQ("INSERT INTO IPSTAT values ('".sqlSafe($r[0])."','".sqlSafe($r[1])."','".sqlSafe($r[2])."','".sqlSafe($r[3])."','".sqlSafe($r[4])."','".sqlSafe($r[5]).
			"','".sqlSafe($r[6])."',".$r[7].",".$r[8].",".$r[9].",".$r[10].",".$r[11].",'".sqlSafe($r[12])."')");
mysql_free_result($rs);
	
oldDB();
$rs = DBQ("select * from COUNTER");
newDB();
while ($r=mysql_fetch_row($rs))
	DBQ("INSERT INTO COUNTER values ('".sqlSafe($r[0])."',".$r[1].",'".sqlSafe($r[2])."')");
mysql_free_result($rs);

oldDB();
$rs = DBQ("select * from ORACLE");
newDB();
while ($r=mysql_fetch_row($rs))
	DBQ("INSERT INTO ORACLE values (null, '".sqlSafe($r[0])."','".sqlSafe($r[1])."','".sqlSafe($r[2])."', ".$r[3].")");
mysql_free_result($rs);


?>

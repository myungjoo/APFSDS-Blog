<?
// This does
//  1. add/delete Memo
//  2. show Memo (returns a string of <tr></tr>rows and its javascript)
//  4. show Recent Comment List (returns a string of <tr></tr>rows and its javascript)
//  5. add/modify/delete Oracle
//  6. show Oracle (returns a string of <tr></tr>rows a table)
//  7. add/delete/set Status
//  8. show Status (returns of a string)
//  9. show Recent PhotoComment List (returns a string of <tr></tr>rows)
//  9.5. show Recent non-photo PhotoComment List (returns a string of <tr></tr>rows)
// 10. show list of link (returns string of <tr></tr>rows)
// 11. show random picture and comment (returns string)
// 12. show counter (returns string)
// 13. show list of new(not confirmed yet) users (returns string of <table></table>)
// 14. show/add(by guest)/delete/update/confirm/reject users
// 15. describe an account/author (with an independent popup window.)


// show : works as php functions
//   returns array( [string], [javascript data-definition code without "<script></script>"]);

// others(add/delete/modify/set): works as an independent php file.
//   call it in a div/iframe or in a pop-up window

if (isset($DOCTYPE))
$alreadyCalled = true;
else {
    $alreadyCalled = false;
    $securityCheckOnly = true;
    include_once("header.php");
}

if (!$alreadyCalled) // it is the first time to run // read request() and follow it
{
    print $DOCTYPE."\n";
?>
<html>
<head>
<title>Misc Functions</title>
<link rel="stylesheet" type="text/css" href="<?=$CSS?>" />
<link rel="shortcut icon" href="<?=$ICON?>" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="generator" content="MZX" />
</head>
<body onload="loading();" onunload="exiting();" class="miscWindow" >
<script type="text/javascript" src="<?=$URL?>Common/javascriptFunctions.js"></script>
<script type="text/javascript">
<?=$javascriptFunction?>
var height = 400;
var width = 320;
function loading()
{
//    window.resizeTo(height, width);
}   
function exiting()
{
}   
</script>
<?
    DBConnect();
    // WORK AREA : you need to resize the window size. use functions in javascriptFunctions.js

    $instruction = request("DO");
    $closeWindow = false;
    $ip = $_SERVER['REMOTE_ADDR'];

    function listOfUser() {
	global $URL;
	print "<table>";
	$rs = DBQ("select * from USERS order by lastlogdate");
	while ($r = mysql_fetch_assoc($rs)) {
	    print "<tr><td><a href=\"".$URL."Common/miscFunction.php?DO=showUsers&USERID=".urlencode($r['id']).
	    "\">".$r['id']."(".$r['permission'].")</a></td>";
	    if ($_SESSION['APFSDS_Perm']==100)
	    print "<td>".$r['lastlogdate']."</td>";
	    print "<td>";
	    if (strlen($r['nameTagURL'])>0)
	    print "<img src=\"".$r['nameTagURL']."\" alt=\"nametag\" />";
	    print "</td></tr>";
	}
	print "</table>";
    }

    switch($instruction)
    {
	case 'addMemo':
		if ($_SESSION['APFSDS_Logged']==1) {
			$CONTENT = request("CONTENT");
			DBQ("insert into MEMO values (null, ".(($_SESSION['APFSDS_Logged']==1)?("'".$_SESSION['APFSDS_ID']."'"):"null").", '".$CONTENT."', '".$ip."', null)");
		}
		$closeWindow = true;
	    break;
	case 'deleteMemo':
		$id = request("MEMOID");
		$rs=DBQ("select author, ip from MEMO where id=$id");
		$r = mysql_fetch_row($rs);
		mysql_free_result($rs);
		if ($r) {
			if ($_SESSION['APFSDS_Perm']==100 || ($r['author']!=null && strlen($r['author'])>0 && $_SESSION['APFSDS_ID']==$r['author']) || (strlen($r['author'])==0 && $ip = $r['ip'])) {
				DBQ("delete from MEMO where id=$id");
				$closeWindow = true;
			} else {
				print "<div class=\"ERROR\">ERROR: Permission Denied.</div>";
				$closeWindow = false;
			}
		}
		else {
			print "<div class=\"ERROR\">ERROR: Memo Not Exists.</div?";
			$closeWindow = false;
		}
	    break;
	case 'addOracle':
		$TITLE = request("TITLE");
		$PRICE = request("PRICE");
		if ($_SESSION['APFSDS_Logged']==1 && ($_SESSION['APFSDS_Perm']==100)) {
			DBQ("insert into ORACLE values (null, null, '$TITLE', '$PRICE', 0)");
			$closeWindow = true;
		} else {
			print "<div class=\"ERROR\">ERROR: Permission Denied.</div>";
			$closeWindow = false;
		}
	    break;
	case 'boughtOracle':
		$id = request("ORACLEID");
		if ($_SESSION['APFSDS_Logged']==1 && ($_SESSION['APFSDS_Perm']==100)) {
			DBQ("update ORACLE set status = abs(status - 1) where id=$id");
			$closeWindow = true;
		} else {
			print "<div class=\"ERROR\">ERROR: Permission Denied.</div>";
			$closeWindow = false;
		}
	    break;
	case 'deleteOracle':
		$id = request("ORACLEID");
		if ($_SESSION['APFSDS_Logged']==1 && ($_SESSION['APFSDS_Perm']==100)) {
			DBQ("delete from ORACLE where id=$id");
			$closeWindow = true;
		} else {
			print "<div class=\"ERROR\">ERROR: Permission Denied.</div>";
			$closeWindow = false;
		}
	    break;
	case 'addStatus':
		$CONTENT = request("CONTENT");
		$ICONURL = request("ICONURL");
		if ($_SESSION['APFSDS_Logged']==1 && ($_SESSION['APFSDS_Perm']==100)) {
			DBQ("insert into STATUS_DEF values(null, '$CONTENT', ".
					((strlen($ICONURL)>0)?("'".$ICONURL."'"):"null").
					")");
			$closeWindow = true;
		} else {
			print "<div class=\"ERROR\">ERROR: Permission Denied.</div>";
			$closeWindow = false;
		}
	    break;
	case 'deleteStatus':
		$id = request("STATUSID");
		if ($_SESSION['APFSDS_Logged']==1 && ($_SESSION['APFSDS_Perm']==100)) {
			DBQ("delete from STATUS_DEF where id=$id");
			$closeWindow = true;
		} else {
			print "<div class=\"ERROR\">ERROR: Permission Denied.</div>";
			$closeWindow = false;
		}
	    break;
	case 'setStatus':
		$id = request("STATUSID");
		if ($_SESSION['APFSDS_Logged']==1 && ($_SESSION['APFSDS_Perm']==100)) {
			DBQ("delete from STATUS");
			DBQ("insert into STATUS values($id)");
			$closeWindow = true;
		} else {
			print "<div class=\"ERROR\">ERROR: Permission Denied.</div>";
			$closeWindow = false;
		}
	    break;
	case 'showUsers':
		$id = request("USERID");
		$closeWindow = false;
		print "<script type=\"text/javascript\">width=640; height=600;</script>";
		if ($_SESSION['APFSDS_Logged']==1 && $_SESSION['APFSDS_Perm']>=3) {
			if (request("LIST")=="YES" || $_SESSION['APFSDS_Perm']==100)
				listOfUser();
			else
				$id = $_SESSION['APFSDS_ID'];
			if (strlen($id)>0) {
				$rs = DBQ("select * from USERS where id='$id'");
				$r = mysql_fetch_assoc($rs);
				mysql_free_result($rs);
				if ($r) {
					if ($_SESSION['APFSDS_Logged']==1 && ($_SESSION['APFSDS_Perm']==100)) {
						print "<form method=\"post\" action=\"".$URL."Common/miscFunction.php\">".
							'<input type="hidden" name="USERID" value="'.$id.'" />'.
							'<input type="hidden" name="DO" value="updateUser">'.
							"<table>".
							"<tr><th>ID</th><td>".$r['id']."</td></tr>".
							'<tr><th>Password Reset</th><td><input type="password" name="PASSWORD" size="10" /></td></tr>'.
							'<tr><th>Permission</th><td><input type="text" name="PERMISSION" size="4" value="'.$r['permission'].'" /></td></tr>'.
							'<tr><th>Level</th><td><input type="text" name="LEVEL" size="8" value="'.$r['level'].'" /></td></tr>'.
							'<tr><th>Experience</th><td><input type="text" name="EXPERIENCE" size="8" value="'.$r['experience'].'" /></td></tr>'.
							'<tr><th>Money</th><td><input type="text" name="MONEY" size="8" value="'.$r['money'].'" /></td></tr>'.
							'<tr><th>Character</th><td><select name="CHARS">';
						$rs = DBQ("select * from CHARS");
						while ($c = mysql_fetch_assoc($rs)) {
							print '<option value="'.$c['id'].'" ';
							if ($c['id']==$r['chars'])
								print " selected=\"selected\" ";
							$onclick = '';
							if (strlen($c['pictureURL'])>0)
								$onclick.="<img src=&#34;".$c['pictureURL']."&#34; alt=&#34;pic&#34; />";
							if (strlen($c['smallPictureURL'])>0)
								$onclick.="<img src=&#34;".$c['smallPictureURL']."&#34; alt=&#34;pic&#34; />";
							if (strlen($c['infoURL'])>0)
								$onclick.="<a href=&#34;".$c['infoURL']."&#34; target=&#34;_blank&#34;>Info URL</a>";
							print 'onclick="alert(\'chosen\'); document.getElementById(\'char\').innerHTML=\''.$onclick.'\'; ">'.$c['id'].'('.$c['flag'].')</option>';
						}
						mysql_free_result($rs);
						print "</select><div id=\"char\" style=\"display:inline\"></div></td></tr>";

						print '<tr><th>Realname</th><td><input type="text" name="REALNAME" value="'.htmlPropertySafe($r['realname']).'" size="20" /></td></tr>'.
							'<tr><th>Nickname</th><td><input type="text" name="NICKNAME" value="'.htmlPropertySafe($r['nickname']).'" size="20" /></td></tr>'.
							'<tr><th>Sex</th><td><select name="SEX"><option value="0">Undefined</option><option value="1">Male</option><option value="2">Female</option><option value="'.$r['sex'].'" selected="selected">';
						switch ($r['sex']) {
							case 0: print "Undefined"; break;
							case 1: print "Male"; break;
							case 2: print "Female"; break;
							default: print "ERROR"; break;
						}
						print '</option></select></td></tr>';
						print '<tr><th>BirthDate</th><td><input type="text" name="BIRTH" value="'.$r['birth'].'" size="20" /></td></tr>'.
							'<tr><th>Telephone</th><td><input type="text" name="TELEPHONE" value="'.htmlPropertySafe($r['telephone']).'" size="20" /></td></tr>'.
							'<tr><th>Cellular</th><td><input type="text" name="CELLULAR" value="'.htmlPropertySafe($r['cellular']).'" size="20" /></td></tr>'.
							'<tr><th>Address</th><td><input type="text" name="ADDRESS" value="'.htmlPropertySafe($r['address']).'" size="80" /></td></tr>'.
							'<tr><th>Job</th><td><input type="text" name="JOB" value="'.htmlPropertySafe($r['job']).'" size="40" /></td></tr>'.
							'<tr><th>Email</th><td><input type="text" name="EMAIL" value="'.htmlPropertySafe($r['email']).'" size="80" /></td></tr>'.
							'<tr><th>Comment</th><td><input type="text" name="COMMENT" value="'.htmlPropertySafe($r['comment']).'" size="80" /></td></tr>'.
							'<tr><th>Name Tag URL</th><td><input type="text" name="NAMETAGURL" value="'.htmlPropertySafe($r['nameTagURL']).'" size="80" /></td></tr>'.
							'<tr><th>Last Login Date</th><td>'.$r['lastlogdate'].'</td></tr>'.
							'<tr><th>Last Login IP</th><td>'.$r['lastlogip'].'</td></tr>'.
							'<tr><th>Undefined Flag</th><td><input type="text" name="UNDEFINED" value="'.htmlPropertySafe($r['undefined']).'" size="80" /></td></tr>'.
							'<tr><td colspan="2"><input type="submit" value="Update"></td></tr>'.
							'</table></form><br />';
						print '<form method="post" action="'.$URL.'Common/miscFunction.php">'.
							'<input type="hidden" name="DO" value="deleteUser" />'.
							'<input type="hidden" name="USERID" value="'.$id.'" />'.
							'Admin Password <input type="password" name="ADMINPASSWORD" size="20" />'.
							' <input type="submit" value="Delete This Account('.$id.')" /></form>';

					} else if ($_SESSION['APFSDS_ID']==$id) {
						print "<form method=\"post\" action=\"".$URL."Common/miscFunction.php\" id=\"update\" >".
							'<script type="text/javascript">'.
							'var thisForm = document.getElementById(\'update\');'.
							'</script>'.
							'<input type="hidden" name="USERID" value="'.$id.'" />'.
							'<input type="hidden" name="DO" value="updateUser">'.
							"<table>".
							"<tr><th>ID</th><td>".$r['id']."</td></tr>".
							'<tr><th>Current Password</th><td><input type="password" name="OLDPASSWORD" size="10" /></td></tr>'.
							'<tr><th>New Password</th><td><input type="password" name="NEWPASSWORD" size="10" /></td></tr>'.
							'<tr><th>New Password, Again</th><td><input type="password" name="NEWPASSWORD2" size="10" /></td></tr>'.
							'<tr><th>Permission</th><td>'.$r['permission'].'</td></tr>'.
							'<tr><th>Level</th><td>'.$r['level'].'</td></tr>'.
							'<tr><th>Experience</th><td>'.$r['experience'].'</td></tr>'.
							'<tr><th>Money</th><td>'.$r['money'].'</td></tr>'.
							'<tr><th>Character</th><td><select name="CHARS">';
						$rs = DBQ("select * from CHARS where flag = 'personal' ");
						while ($c = mysql_fetch_assoc($rs)) {
							print '<option value="'.$c['id'].'" ';
							if ($c['id']==$r['chars'])
								print " selected=\"selected\" ";
							$onclick = '';
							if (strlen($c['pictureURL'])>0)
								$onclick.="<img src=&#34;".$c['pictureURL']."&#34; alt=&#34;pic&#34; />";
							if (strlen($c['smallPictureURL'])>0)
								$onclick.="<img src=&#34;".$c['smallPictureURL']."&#34; alt=&#34;pic&#34; />";
							if (strlen($c['infoURL'])>0)
								$onclick.="<a href=&#34;".$c['infoURL']."&#34; target=&#34;_blank&#34;>Info URL</a>";
							print 'onclick="alert(\'chosen\'); document.getElementById(\'char\').innerHTML=\''.$onclick.'\'; ">'.$c['id'].'('.$c['flag'].')</option>';
						}
						mysql_free_result($rs);
						print "</select><div id=\"char\" style=\"display:inline\"></div></td></tr>";

						print '<tr><th>Realname</th><td>'.htmlPropertySafe($r['realname']).'</td></tr>'.
							'<tr><th>Nickname</th><td><input type="text" name="NICKNAME" value="'.htmlPropertySafe($r['nickname']).'" size="20" /></td></tr>'.
							'<tr><th>Sex</th><td>';
						switch ($r['sex']) {
							case 0: print "Unknown/Sexual Minority"; break;
							case 1: print "Male"; break;
							case 2: print "Female"; break;
							default: print "ERROR"; break;
						}
						print '</td></tr>';
						print '<tr><th>BirthDate</th><td><input type="text" name="BIRTH" value="'.$r['birth'].'" size="20" /></td></tr>'.
							'<tr><th>Telephone</th><td><input type="text" name="TELEPHONE" value="'.htmlPropertySafe($r['telephone']).'" size="20" /></td></tr>'.
							'<tr><th>Cellular</th><td><input type="text" name="CELLULAR" value="'.htmlPropertySafe($r['cellular']).'" size="20" /></td></tr>'.
							'<tr><th>Address</th><td><input type="text" name="ADDRESS" value="'.htmlPropertySafe($r['address']).'" size="80" /></td></tr>'.
							'<tr><th>Job</th><td><input type="text" name="JOB" value="'.htmlPropertySafe($r['job']).'" size="40" /></td></tr>'.
							'<tr><th>Email</th><td><input type="text" name="EMAIL" value="'.htmlPropertySafe($r['email']).'" size="80" /></td></tr>'.
							'<tr><th>Comment</th><td><input type="text" name="COMMENT" value="'.htmlPropertySafe($r['comment']).'" size="80" /></td></tr>'.
							'<tr><th>Name Tag URL</th><td>(Notify Admin to update) '.htmlPropertySafe($r['nameTagURL']).'</td></tr>'.
							'<tr><th>Last Login Date</th><td>'.$r['lastlogdate'].'</td></tr>'.
							'<tr><th>Last Login IP</th><td>'.$r['lastlogip'].'</td></tr>'.
							'<tr><td colspan="2"><input type="submit" value="Update" onclick="if (thisForm.NEWPASSWORD.value!=thisForm.NEWPASSWORD2.value) { alert (\'New Password Mismatch\'); return false; } else return true; " ></td></tr>'.
							'</table></form><br />';
					} else {
						print "<table>".
							"<tr><th>ID</th><td>".$r['id']."</td></tr>".
							'<tr><th>Permission</th><td>'.$r['permission'].'</td></tr>'.
							'<tr><th>Level</th><td>'.$r['level'].'</td></tr>'.
							'<tr><th>Experience</th><td>'.$r['experience'].'</td></tr>'.
							'<tr><th>Character</th><td>';
						$rs = DBQ("select * from CHARS where id='".$r['chars']."' ");
						if ($c = mysql_fetch_assoc($rs)) {
							if (strlen($c['pictureURL'])>0)
								print "<img src=&#34;".$c['pictureURL']."&#34; alt=&#34;pic&#34; />";
							if (strlen($c['smallPictureURL'])>0)
								print "<img src=&#34;".$c['smallPictureURL']."&#34; alt=&#34;pic&#34; />";
							if (strlen($c['infoURL'])>0)
								print "<a href=&#34;".$c['infoURL']."&#34; target=&#34;_blank&#34;>Info URL</a>";
						}
						mysql_free_result($rs);
						print "</td></tr>";

						print '<tr><th>Realname</th><td>'.htmlPropertySafe($r['realname']).'</td></tr>'.
							'<tr><th>Nickname</th><td>'.htmlPropertySafe($r['nickname']).'</td></tr>'.
							'<tr><th>Sex</th><td>';
						switch ($r['sex']) {
							case 0: print "Unknown/Sexual Minority"; break;
							case 1: print "Male"; break;
							case 2: print "Female"; break;
							default: print "ERROR"; break;
						}
						print '</td></tr>';
						print '<tr><th>BirthDate</th><td>'.$r['birth'].'</td></tr>'.
							'<tr><th>Telephone</th><td>'.htmlPropertySafe($r['telephone']).'</td></tr>'.
							'<tr><th>Cellular</th><td>'.htmlPropertySafe($r['cellular']).'</td></tr>'.
							'<tr><th>Address</th><td>'.htmlPropertySafe($r['address']).'</td></tr>'.
							'<tr><th>Job</th><td>'.htmlPropertySafe($r['job']).'</td></tr>'.
							'<tr><th>Email</th><td>'.htmlPropertySafe($r['email']).'</td></tr>'.
							'<tr><th>Comment</th><td>'.htmlPropertySafe($r['comment']).'</td></tr>'.
							'<tr><th>Name Tag URL</th><td>'.htmlPropertySafe($r['nameTagURL']).'</td></tr>'.
							'</table><br />';
					}
				}
				else {
					print "<div class=\"ERROR\">ERROR: User $id not found</div>";
				}
			}
		} else {
			print "<div class=\"ERROR\">ERROR: Permission Denied</div>";
		}
	    break;
	case 'newUser': // by guest
		$closeWindow = false;
		?>
<form method="post" action="<?=$URL?>Common/miscFunction.php" id="newuser" >
<input type="hidden" name="DO" value="newUserEntered">
<script type="text/javascript">
var thisForm = document.getElementById("newuser");
height = 640;
width = 600;
</script>
<table>
<tr><th colspan="2">Mandatory Data</th></tr>
<tr><td colspan="2">Please contact the admin personally after you enter the informaion to be validated.<br /><img src="text.png" alt="korean text" /></td></tr>
<tr><th>Check</th><td><img src="<?=$URL?>Common/captcha.php?id=newuser" alt="captcha" /> Please type the text on the image <input type="text" size="10" name="CAPTCHA" /></td></tr>
<tr><th>ID</th><td><input type="text" name="ID" size="20" />
<input type="button" value="Check Validity" onclick="window.open('<?=$URL?>Common/miscFunction.php?DO=newUserIDCheck&USERID='+thisForm.ID.value,'_blank','directories=no,height=100,location=no,menubar=no,resizable=no,scrollbars=no,status=no,titlebar=no,toolbar=no,width=300');"></td></tr>
<tr><th>Password</th><td><input type="password" name="PASSWORD" size="20" /><br />
<input type="password" name="PASSWORD2" size="20" /></td></tr>
<tr><th>Real Name</th><td><input type="text" name="REALNAME" size="40" /></td></tr>
<tr><th colspan="2">Optional Data (You don't need to write this if you don't want.) </th></tr>
<tr><th>Nickname</th><td><input type="text" name="NICKNAME" size="40" /></td></tr>
<tr><th>Sex</th><td><select name="SEX"><option value="0">Sexual Minority / Undefined</option><option value="1">Male</option><option value="2">Female</option></select></td></tr>
<tr><th>Birthdate</th><td><input type="text" name="BIRTHDATE" size="20" value="1981-02-09" /> YYYY-MM-DD</td></tr>
<tr><th>Telephone</th><td><input type="text" name="TELEPHONE" size="20" /></td></tr>
<tr><th>Cellular</th><td><input type="text" name="CELLULAR" size="20" /></td></tr>
<tr><th>Address</th><td><input type="text" name="ADDRESS" size="80" /></td></tr>
<tr><th>Job</th><td><input type="text" name="JOB" size="40" /></td></tr>
<tr><th>Email</th><td><input type="text" name="EMAIL" size="80" /></td></tr>
<tr><th>Comment</th><td><input type="text" name="COMMENT" size="80" /></td></tr>
<tr><th>Name Tag</th><td>Consult Admin for NameTag Image</td></tr>
<tr><td colspan="2"><input type="submit" value="Submit this account application" onclick="if (thisForm.PASSWORD.value!=thisForm.PASSWORD2.value) { alert('Password Mismatch'); return false;} else return true; " /></td></tr>
</table>
</form>
		<?
	    break;
	case 'newUserEntered': // by guest
		$closeWindow = false;
		$captchaEntered = request("CAPTCHA");
		$captchaGenerated = $_SESSION['APFSDS_Captcha']['newuser'];
		if (strlen($captchaGenerated)<3 || strcasecmp($captchaEntered, $captchaGenerated)!=0) {
			print "<div class=\"ERROR\">ERROR: Captcha Check Failed.(Image and text mismatch) </div>";
			break;
		}
		$id = request("ID");
		if (strlen($id)<3) {
			print "<div class=\"ERROR\">ERROR: ID Too Short</div>";
			break;
		}
		switch($id) {
			case 'admin':
			case 'mzx':
			case 'apfsds':
				print "<div class=\"ERROR\">ERROR: $id is predefined keyword</div>";
				break;
			default:
				DBQ("START TRANSACTION");
				$rs = DBQ("select id from USERS where id='$id'");
				$r = mysql_fetch_row($rs);
				mysql_free_result($rs);
				if ($r) {
					print "<div class=\"ERROR\">ERROR: $id already exists.</div>";
					DBQ("COMMIT"); // actually, nothing changed. but this is expected to be faster
					break;
				}
				DBQ("insert into USERS values ('$id', '".request("PASSWORD")."', 0, 0, 0, 0, 'private', '".
						request("REALNAME")."', '".request("NICKNAME")."', ".requestInt("SEX").", '".
						request("BIRTHDATE")."', '".request("TELEPHONE")."', '".request("CELLULAR")."', '".
						request("ADDRESS")."', '".request("JOB")."', '".request("EMAIL")."', '".
						request("COMMENT")."', null, NOW(), '".$_SERVER['REMOVE_ADDR']."', null)");
				print "New Account Application for $id Created<br />";
				print '<form method="post" action="'.$URL.'Common/miscFunction.php">';
				print '<input type="hidden" name="USERID" value="'.$id.'" />';
				print '<input type="hidden" name="PASSWORD" value="'.request("PASSWORD").'" />';
				print '<input type="submit" value="Cancel and delete this applicatoin" />';
				print '</form>';
				DBQ("COMMIT");
		}
	    break;
	case 'cancelNewUser': // by guest
		$id = request("USERID");
		$passwd = request("PASSWORD");
		DBQ("START TRANSACTION");
		$rs = DBQ("select permission from USERS where id='$id' and password='$passwd'");
		$r = mysql_fetch_row($rs);
		if ($r && $r[0]==0) {
			DBQ("delete from USERS where id='$id'");
		} else {
			print "<div class=\"ERROR\">ERROR: Permission Denied / $id Already Accepted</div>";
		}
		DBQ("COMMIT");
	case 'newUserIDCheck': // by guest
		$id = request("USERID");
		$rs = DBQ("select id from USERS where id='$id'");
		$r = mysql_fetch_row($rs);
		mysql_free_result($rs);
		if ($r) {
			print "You cannot use this id. Somebody already took it.";
		} else {
			if (strlen($id)<3)
				print "[$id]<3 is too short. ";
			else
				switch($id) {
					case 'admin':
					case 'mzx':
					case 'apfsds':
						print "<div class=\"ERROR\">ERROR: $id is predefined keyword</div>";
						break;
					default:
						print "You can use $id.";
				}
		}
		print '<br /><input type="button" style="width:200px" value=" Close Window " onclick="window.close();" />';
	    break;
	case 'deleteUser':
		$id = request("USERID");
		if ($id=='admin')
			print "<div class=\"ERROR\">ERROR: admin cannot be removed.</div>";
		else if ($_SESSION['APFSDS_Logged']==1 && ($_SESSION['APFSDS_Perm']==100)) // only admin can do this.
		{
			$rs = DBQ("select id from USERS where id='admin' and password='".request("ADMINPASSWORD")."'");
			$r = mysql_fetch_row($rs);
			mysql_free_result($rs);
			if (!$r) {
				print "<div class=\"ERROR\">Admin Password Mismatch</div>";
				break;
			}

			DBQ("delete from USERS where id='$id'");
			$affected = mysql_affected_rows();
			if ($affected<1)
				print "<div class=\"ERROR\">$id not deleted: maybe $id not exists</div>";
			else
				print "Account $id Deleted.";
		} else {
			print "<div class=\"ERROR\">ERROR: Permission Denied.</div>";
		}
	    break;
	case 'updateUser':
		$id = request("USERID");
		if (strlen($id)<1) {
			print "<div class=\"ERROR\">ERROR: ID Too Short.</div>";
			break;
		}
		if ($_SESSION['APFSDS_Perm']<100 && $id!=$_SESSION['APFSDS_ID']) {
			print "<div class=\"ERROR\">ERROR: ID Different.</div>";
			break;
		}
		if ($_SESSION['APFSDS_Perm']==100)
			listOfUser();
		if ($_SESSION['APFSDS_Logged']==1 && ($_SESSION['APFSDS_ID']==$id || $_SESSION['APFSDS_Perm']==100)){
			DBQ("START TRANSACTION");
			$rs=DBQ("select id from USERS where id='$id'");
			$r=mysql_fetch_row($rs);
			mysql_free_result($rs);
			if (!$r) {
				print "<div class=\"ERROR\">ERROR: ID $id Not Exists.</div>";
				DBQ("COMMIT");
				break;
			}
			if ($_SESSION['APFSDS_Perm']<100) {
				$rs=DBQ("select id from USERS where id='$id' and password='".request("OLDPASSWORD")."' ");
				$r = mysql_fetch_row($rs);
				mysql_free_result($rs);
				if (!$r) {
					print "<div class=\"ERROR\">ERROR: Wrong Password.</div>";
					DBQ("COMMIT");
					break;
				}
			}
			$rs=DBQ("select flag from CHARS where id='".request("CHARS")."'");
			$r=mysql_fetch_row($rs);
			mysql_free_result($rs);
			if (!$r) {
				print "<div class=\"ERROR\">ERROR: Wrong Char Definition.(Not Exists)</div>";
				DBQ("COMMIT");
				break;
			}
			if ($r[0]=='adminOnly' && $_SESSION['APFSDS_Perm']<100) {
				print "<div class=\"ERROR\">ERROR: Char with adminOnly </div>";
				DBQ("COMMIT");
				break;
			}
			DBQ("update USERS set ".
						((strlen(request("NEWPASSWORD"))>3)?("password='".request("NEWPASSWORD")."', "):"").
						"chars='".request("CHARS")."', ".
						"nickname='".request("NICKNAME")."', ".
						"birth='".request("BIRTH")."', ".
						"telephone='".request("TELEPHONE")."', ".
						"cellular='".request("CELLULAR")."', ".
						"address='".request("ADDRESS")."', ".
						"job='".request("JOB")."', ".
						"email='".request("EMAIL")."', ".
						"comment='".request("COMMENT")."' ".
					"where id='$id'");
			if ($_SESSION['APFSDS_Perm']==100) {
				DBQ("update USERS set ".
						"permission=".requestInt("PERMISSION").", ".
						"level=".requestInt("LEVEL").", ".
						"experience=".requestInt("EXPERIENCE").", ".
						"money=".requestInt("MONEY").", ".
						"realname='".request("REALNAME")."', ".
						"sex=".requestInt("SEX").", ".
						"nameTagURL='".request("NAMETAGURL")."', ".
						"undefined='".request("UNDEFINED")."' ".
						"where id='$id'");
			}
			DBQ("COMMIT");
		}
	    break;
	case 'confirmUser':
		if ($_SESSION['APFSDS_Perm']==100) {
			$id = request("USERID");
			DBQ("update USERS set permission=3 where id='$id' and permission=0");
			$affected = mysql_affected_rows();
			if ($affected<1)
				print '<div class="ERROR">ERROR: '.$id.' not exists or already accepted.</div>';
			else
				print 'ID '.$id.' Accepted.';
		}
	    break;
	case 'rejectUser':
		if ($_SESSION['APFSDS_Perm']==100) {
			$id = request("USERID");
			DBQ("update USERS set permission=1 where id='$id' and permission=0");
			$affected = mysql_affected_rows();
			if ($affected<1)
				print '<div class="ERROR">ERROR: '.$id.' not exists or already accepted.</div>';
			else
				print 'ID '.$id.' Rejected and set deleted flag.';
		}
	    break;
	case 'describeAuthor':
		// with request("AUTHORID") and request("GUESTINFO") --> request("GUESTNAME");
		print '<div class="describeAuthor">';
		if (strlen(request("AUTHORID"))>0) {
			print "Requested : ".request("AUTHORID")."  (Under Construction) ";
		} else {
			print "Requested(Guest) : ".request("GUESTNAME")."  (Under Construction) ";
		}
		print '</div>';
		break;
	default:
		print '<div class="ERROR">ERROR: Undefined Action Requested : '.$instruction.' .</div>';
	    break;
    }
    if ($closeWindow)
    {
		print '<script type="text/javascript">window.close(); </script>';
	// WORK AREA
	// print a script to close the window
    }

?>
</body>
</html>
<?
    return;
}
function showMemo($idPrefix, $num, $skip=0)
{
	global $divMemoShow;
    $returnStr = '';
    $dataScript= '';
	$allMemoContent = '<table>';
	$dataScript.='var memo = new Array(); ';

	$rs = DBQ("select * from MEMO order by id desc");
	$written = 0;
	while ($r = mysql_fetch_assoc($rs)) {
		if  ($skip > 0) {
			$skip --;
			continue;
		}
		$returnStr.='<tr><td class="memoList"><a href="javascript:panelToggle(\''.$divMemoShow.'\', memo['.
			$written.'], \'MemoTop\',\'memo'.$written.'\');"><div>'.html2text($r['content']).'</div></a></td></tr>';
		$dataScript.='memo['.$written.'] = "'.javascriptCompatible(
			"<table><tr><th>".$r['ip']." ".(($r['author']!=null && $r['author']!='')?($r['author']):"")."</th><td class=\\\"memoCreated\\\">".$r['createon']."</td></tr>".
				"<tr><td colspan=\\\"2\\\" class=\\\"memoContent\\\">".htmlPropertySafe($r['content']))."</td></tr>".
			(($_SESSION['APFSDS_Perm']==100)?("<tr><td colspan=\\\"2\\\"><input type=\\\"button\\\" value=\\\"Delete\\\" onclick=\\\"window.open('".$URL."Common/miscFunction.php?DO=deleteMemo&MEMOID=".$r['id']."');\\\"></td></tr>"):"").
			"</table>".
			'"; ';
		$allMemoContent.=javascriptCompatible(
				"<tr><th>".$r['ip']." ".(($r['author']!=null && $r['author']!='')?($r['author']):"")."</th><td class=\\\"memoCreated\\\">".$r['createon']."</td>".
				"<td class=\\\"memoContent\\\">".
				htmlPropertySafe($r['content']).
				'</td></tr>');
		$written ++;
		if ($written >= $num)
			break;
	}
	mysql_free_result($rs);
	$dataScript.='var allMemo = "'.$allMemoContent.'<\/table>"; ';
    return array($returnStr, $dataScript);
}
function showRecentComment($idPrefix, $num, $blogID, $skip=0)
{
	global $divCommentShow;
    $returnStr = '';
    $dataScript= '';
	$dataScript.='var comment = new Array(); ';

	$rs = DBQ("select permissionRead, admin from Blog where id='$blogID'");
	$r = mysql_fetch_row($rs);
	mysql_free_result($rs);
	if (!$r || $_SESSION['APFSDS_Perm']<$r[0]) {
		return array('', '');
	}
	$admin = $r[1];

	$rs = DBQ("select * from BlogComment where blogID='$blogID' order by modifyDate DESC");
	$written = 0;
	while ($r=mysql_fetch_assoc($rs)) {
		if  ($skip > 0) {
			$skip --;
			continue;
		}
		if ($r['secret']>0 && $r['secret']>$_SESSION['APFSDS_Perm'] && $_SESSION['APFSDS_Perm']!=100 && ($_SESSION['APFSDS_ID']!=$r['author'] || $_SESSION['APFSDS_Perm']<3))
			continue;
		$rsT = DBQ("select tagID from BlogTagArticleAssoc where blogID='$blogID' and articleID=".$r['articleID']);
		$readable = true;
		$tagTitle = '';
		while ($rT = mysql_fetch_row($rsT)) {
			$rsTag = DBQ("select * from BlogTag where blogID='$blogID' and tagID=".$rT[0]);
			$rTag = mysql_fetch_assoc($rsTag);
			if ($rTag) {
				$tagTitle.=" ".$rTag['tagTitle']." ";
				if ($rTag['permissionRead']>$_SESSION['APFSDS_Perm'])
					$readable = false;
				if ($rTag['accessControlRead']==1) {
					$rsAC = DBQ("select user from BlogTagAccessControl where blogID='$blogID' and tagID=".$rT[0]." and user='".$_SESSION['APFSDS_ID']."'");
					if (!mysql_fetch_row($rsAC))
						$readable = false;
					mysql_free_result($rsAC);
				}
			}
			mysql_free_result($rsTag);
		}
		mysql_free_result($rsT);
		$rsA = DBQ("select title from BlogArticle where blogID='$blogID' and articleID=".$r['articleID']);
		$rA = mysql_fetch_row($rsA);
		$title = $rA[0];
		mysql_free_result($rsA);
		if (!$readable)
			continue;
		$writer = html2text($r['author'].$r['guestName']);
		if (strlen($r['author'])>0) {
			$rsAuthor = DBQ("select nickname from USERS where id='".$r['author']."'");
			$rAuthor = mysql_fetch_row($rsAuthor);
			if ($rAuthor)
			{
				if (strlen($rAuthor[0])>0)
					$writer = '<b>'.html2text($rAuthor[0]).'</b>';
			}
		}
		$returnStr.='<tr><td class="commentList"><a href="javascript:panelToggle(\''.$divCommentShow.'\', comment['.
			$written.'], \'CommentTop\',\'comment'.$written.'\');"><div>'.mb_substr($writer.":".htmlConvention(html2text($r['content']), null, ''), 0, 60).'</div></a></td></tr>';
		$dataScript.='comment['.$written.'] = \''.javascriptCompatible(
			"<table><tr><th>".$tagTitle."</th><td class=\\\"commentTitle\\\"><a href=\\\"".$URL."?BLOG=".urlencode($blogID)."&Article=".urlencode($r['articleID'])."\\\">".$title.
			"</a></td><td class=\\\"commentDate\\\">".$r['modifyDate']."</td></tr>".
			"<tr><td colspan=\\\"3\\\">".
				htmlPropertySafe(htmlConvention(html2text($r['content']), null, '')).
			"</td></tr></table>"
			).'\'; ';
		$written ++;
		if ($written >= $num)
			break;
	}
	mysql_free_result($rs);
    return array($returnStr, $dataScript);
}
function showOracle()
{
	global $URL;
    $returnStr = '';
    $dataScript= '';
	if ($_SESSION['APFSDS_Perm']==100) {
		$returnStr.=
			'<tr><td><form method="post" action="'.$URL.'Common/miscFunction.php" target="_blank">'.
			'<input type="hidden" name="DO" value="addOracle" />'.
			'<input type="text" name="TITLE" style="width:115px" /><br />'.
			'<input type="text" name="PRICE" style="width:115px" /><br />'.
			'<input type="submit" value="Add Oracle" /></form></td></tr>'.

			'<tr><td><form method="post" action="'.$URL.'Common/miscFunction.php" target="_blank">'.
			'<input type="hidden" name="DO" value="boughtOracle" />'.
			'<select name="ORACLEID" style="width:115px" >';
		$rs = DBQ("select id, title from ORACLE order by id");
		while ($r=mysql_fetch_row($rs)) {
			$returnStr.='<option value="'.$r[0].'">'.$r[1].'</option>';
		}
		mysql_free_result($rs);
		$returnStr.=
			'</select><br />'.
			'<input type="submit" value="Toggle" /></form></td></tr>'.

			'<tr><td><form method="post" action="'.$URL.'Common/miscFunction.php" target="_blank">'.
			'<input type="hidden" name="DO" value="deleteOracle" />'.
			'<select name="ORACLEID" style="width:115px" >';
		$rs = DBQ("select id, title from ORACLE order by id");
		while ($r=mysql_fetch_row($rs)) {
			$returnStr.='<option value="'.$r[0].'">'.$r[1].'</option>';
		}
		mysql_free_result($rs);
		$returnStr.=
			'</select><br />'.
			'<input type="submit" value="Delete" /></form></td></tr>';
	}
	$rs = DBQ("select title, price, status from ORACLE order by id");
	while ($r = mysql_fetch_row($rs)) {
		$returnStr.='<tr><td class="oracle"><div>';
		if ($r[2]==1)
			$returnStr.='<del>'.$r[0].'<br />'.$r[1].'</del>';
		else
			$returnStr.=$r[0].'<br />'.$r[1];
		$returnStr.'</div></td></tr>';
	}
	mysql_free_result($rs);
    return array($returnStr, $dataScript);
}
function showStatus()
{
	global $URL;
    $returnStr = '';
    $dataScript= '';
	$rs = DBQ("select sd.description, sd.icon from STATUS_DEF as sd , STATUS as s where sd.id=s.id");
	$r = mysql_fetch_row($rs);
	if ($r) {
		$returnStr.="<tr><td class=\"status\"><div>";
		if ($r[1]!=null && $r[1]!='')
			$returnStr.='<img src="'.$r[1].'" alt="icon" />';
		$returnStr.=$r[0].'</div></td></tr>';
	}
	mysql_free_result($rs);
	if ($_SESSION['APFSDS_Perm']==100) {
		$returnStr.=
			'<tr class="status"><td><form method="post" action="'.$URL.'Common/miscFunction.php" target="_blank">'.
			'<input type="hidden" name="DO" value="addStatus" />'.
			'<input type="text" name="CONTENT" style="width:115px" /><br />'.
			'<input type="text" name="ICONURL" style="width:115px" /><br />'.
			'<input type="submit" value="Add Status" /></form></td></tr>'.

			'<tr><td><form method="post" action="'.$URL.'Common/miscFunction.php" target="_blank">'.
			'<input type="hidden" name="DO" value="setStatus" />'.
			'<select name="STATUSID" style="width:115px" >';
		$rs = DBQ("select id, description from STATUS_DEF order by id");
		while ($r=mysql_fetch_row($rs)) {
			$returnStr.='<option value="'.$r[0].'">'.$r[1].'</option>';
		}
		mysql_free_result($rs);
		$returnStr.=
			'</select><br />'.
			'<input type="submit" value="Set" /></form></td></tr>'.

			'<tr><td><form method="post" action="'.$URL.'Common/miscFunction.php" target="_blank">'.
			'<input type="hidden" name="DO" value="deleteStatus" />'.
			'<select name="STATUSID" style="width:115px" >';
		$rs = DBQ("select id, description from STATUS_DEF order by id");
		while ($r=mysql_fetch_row($rs)) {
			$returnStr.='<option value="'.$r[0].'">'.$r[1].'</option>';
		}
		mysql_free_result($rs);
		$returnStr.=
			'</select><br />'.
			'<input type="submit" value="Delete" /></form></td></tr>';
	}

    return array($returnStr, $dataScript);
}
function showLink($generalCategory, $detailedCategory=false)
{
    $returnStr = '';
    $dataScript= '';
	if ($detailedCategory==false)
		$rs = DBQ("select subject, link, memo from LINK_CONTENT where categoryshort='$generalCategory' ");
	else
		$rs = DBQ("select subject, link, memo from LINK_CONTENT where categoryshort='$generalCategory' and specificsubject='$detailedCategory'");
	while ($r = mysql_fetch_assoc($rs)) {
		$returnStr.= '<tr><td class="link">'.
			'<a href="'.$r['link'].'" target="_blank" alt="'.$r['memo'].'"><div>'.$r['subject'].'</div></a></td></tr>';
	}
	mysql_free_result($rs);
    return array($returnStr, $dataScript);
}
function showLinkAll()
{
    $returnStr = '';
    $dataScript= '';
	$rs = DBQ("select subject, link, memo from LINK_CONTENT");
	while ($r = mysql_fetch_assoc($rs)) {
		$returnStr.= '<tr><td class="link"><div>'.
			'<a href="'.$r['link'].'" target="_blank" alt="'.$r['memo'].'">'.$r['subject'].'</a></div></td></tr>';
	}
	mysql_free_result($rs);
    return array($returnStr, $dataScript);
}
function showPhotoComment($num)
{
    $returnStr = '';
    $dataScript= '';
	$rs = DBQ("select * from PhotoComment where photoFilename!='NA' order by id desc");
	$shown = 0;
	while ($r = mysql_fetch_assoc($rs)) {
		$nick = $r['guestInfo'];
		if (strlen($r['author'])>0) {
			$nick = $r['author'];
			$rsN = DBQ("select nickname from USERS where id='".$r['author']."'");
			$rN = mysql_fetch_row($rsN);
			if ($rN && strlen($rN[0])>1)
			{
				$nick = '<b>'.$rN[0].'</b>';
			}
			mysql_free_result($rsN);
		}

		$shown ++;
		$returnStr.="<tr><td class=\"photoList\"><a href=\"".$URL."album".$r['photoDir'].(($r['photoFilename']=='NA')?"":($r['photoFilename']."!"))."\"><div>".
			$nick.":".htmlConvention(html2text($r['content']), array(), '')."</div></a></td></tr>";
		if ($num <= $shown) break;
	}
	mysql_free_result($rs);
	return array($returnStr, $dataScript);
}
function showNonPhotoPhotoComment($num)
{
    $returnStr = '';
    $dataScript= '';
	$rs = DBQ("select * from PhotoComment where photoFilename='NA' order by id desc");
	$shown = 0;
	while ($r = mysql_fetch_assoc($rs)) {
		$nick = $r['guestInfo'];
		if (strlen($r['author'])>0) {
			$nick = $r['author'];
			$rsN = DBQ("select nickname from USERS where id='".$r['author']."'");
			$rN = mysql_fetch_row($rsN);
			if ($rN && strlen($rN[0])>1)
			{
				$nick = '<b>'.$rN[0].'</b>';
			}
			mysql_free_result($rsN);
		}

		$shown ++;
		$returnStr.="<tr><td class=\"photoList\"><a href=\"".$URL."album".$r['photoDir'].(($r['photoFilename']=='NA')?"":($r['photoFilename']."!"))."\"><div>".
			$nick.":".htmlConvention(html2text($r['content']), array(), '')."</div></a></td></tr>";
		if ($num <= $shown) break;
	}
	mysql_free_result($rs);
	return array($returnStr, $dataScript);
}

function showTagList() 
{
    $returnStr = '<tr><td class=\"tagList\">';
    $dataScript= '';

	$rs = DBQ("select * from BlogTag where blogID='default'");
	while ($r = mysql_fetch_assoc($rs)) {
		$accessable = true;
		if ($r['permissionRead']<=$_SESSION['APFSDS_Perm']) {
			if ($r['accessControlRead']==1) {
				$rsAC = DBQ("select user from BlogTagAccessControl where blogID='default' and tagID=".$r['tagID']." and user='".$_SESSION['APFSDS_ID']."' ");
				$rAC = mysql_fetch_row($rsAC);
				if (!$rAC)
					$accessable = false;
				mysql_free_result($rsAC);
			}
		} else
			$accessable = false;
		if ($accessable)
			$returnStr.='<a href="'.$URL.'?sTag='.urlencode($r['tagID']).'">'.
				$r['tagTitle'].'</a>  ';
		/*
		else
			$returnStr.=$r['tagTitle'].'  ';
		*/
	}
	mysql_free_result($rs);
	$returnStr.=
		'</td></tr>';
	return array($returnStr, $dataScript);
}
function showSearch()
{
    $returnStr = '';
    $dataScript= 'var searchInterface = \''.
			'<table>'.
				'<tr><th colspan="2">Search By Tag</th></tr>'.
					'<form method="post" action="'.$URL.'"><tr><td>';
	$rs = DBQ("select * from BlogTag where blogID='default'");
	while($r = mysql_fetch_assoc($rs)) {
		$dataScript.='<input type="checkbox" name="sTagArray[]" value="'.$r['tagID'].'" />'.$r['tagTitle'].'  ';
	}
	mysql_free_result($rs);
	$dataScript.=
					'</td><td><input type="submit" value="Search" /></td></tr></form>'.
				'<tr><th colspan="2">Search By Date</th></tr>'.
					'<form method="post" action="'.$URL.'"><tr><td>'.
					'Start Date<input type="text" name="sStartDate" value="1981-02-09" />'.
					'End Date<input type="text" name="sEndDate" value="'.date('Y-m-d').'" />'.
					'</td><td><input type="submit" value="Search" /></td></tr></form>'.
				'<tr><th colspan="2">Search By Content - under construction</th></tr>'.
					'<form><tr><td></td><td><input type="submit" value="Search" /></td></tr></form>'.
				'<tr><th colspan="2">Search By Google</th></tr>'.
					'<form><tr><td></td><td><input type="submit" value="Search" /></td></tr></form>'.
			'</table>'.
		'\';';
	return array($returnStr, $dataScript);
}
?>

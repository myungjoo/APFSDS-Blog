<?
// 1. Load common.php and connect DB
include_once("common.php");
DBConnect();
$displayMessage = '';

if (!isset($BLOGID))
{
	$BLOGID=request("BLOGID");
	if ($BLOGID=='')
		$BLOGID='default';
}


// 3. Process cookie (for CookieLogin)
if (isset($_COOKIE['APFSDS_ID']) && (!isset($_SESSION['APFSDS_Logged']) || $_SESSION['APFSDS_Logged']==0) ) {
	$id = sqlsafe(trim($_COOKIE['APFSDS_ID']));
	$cookieKey = sqlsafe(trim($_COOKIE['APFSDS_KEY']));
	$lastPasswordInput = sqlsafe(trim($_COOKIE['APFSDS_KEYDATE']));
	$rs = DBQ("select userID from COOKIELOGIN where userID='$id' and cookieKey='$cookieKey' and passwordEntered='$lastPasswordInput'");
	$r = mysql_fetch_row($rs);
	mysql_free_result($rs);
	if ($r) {
		$rs = DBQ("select id, permission, level from USERS where id='".$id."'");
		$r = mysql_fetch_row($rs);
		$_SESSION['APFSDS_FL']=1;
		$_SESSION['APFSDS_Logged']=1;
		$_SESSION['APFSDS_ID'] = $id;
		$_SESSION['APFSDS_Perm'] = $r[1];
		$_SESSION['APFSDS_Level'] = $r[2];
		$_SESSION['APFSDS_DBName'] = $mysqlDBName;
		mysql_free_result($rs);
		DBQ("update USERS set lastlogdate=NOW(), lastlogip='".$_SERVER['REMOTE_ADDR']."' where id='$id'");
	}
}

// 4. Security features.
if (!isset($_SESSION['APFSDS_ConnectedOnce']) || $_SESSION['APFSDS_ConnectedOnce']!='yes') {
	ipstatConnect();
	$_SESSION['APFSDS_ConnectedOnce']='yes';
}
if (isset($_SESSION['APFSDS_FL']))
{ // FirstLoad : for spam filtering
    if ($_SESSION['APFSDS_FL']>0)
	$_SESSION['APFSDS_FL'] = 100; 
    else
	$_SESSION['APFSDS_FL'] = 1;
}
if (!isset($_SESSION['APFSDS_Logged']))
{
    $_SESSION['APFSDS_Logged']=0;
    $_SESSION['APFSDS_FL'] += 1;
}
if (!isset($_SESSION['APFSDS_ID']))
{
    $_SESSION['APFSDS_ID']="";
    $_SESSION['APFSDS_FL'] += 2;
}
if (!isset($_SESSION['APFSDS_Level']))
{
    $_SESSION['APFSDS_Level']=0;
    $_SESSION['APFSDS_FL'] += 4;
}
if (!isset($_SESSION['APFSDS_Perm']))
{
    $_SESSION['APFSDS_Perm']=0;
    $_SESSION['APFSDS_FL'] += 8;
}
if (!isset($_SESSION['APFSDS_DBName']) || $_SESSION['APFSDS_DBName']!=$mysqlDBName)
{
    $_SESSION['APFSDS_FL'] = 0;
    $_SESSION['APFSDS_Logged'] = 0;
    $_SESSION['APFSDS_ID'] = '';
    $_SESSION['APFSDS_Level'] = 0;
    $_SESSION['APFSDS_Perm'] = 0;
    $_SESSION['APFSDS_DBName'] = $mysqlDBName;
}
if ($_SESSION['APFSDS_Logged']==1)
{
	$error = false;
	if (!isset($_SESSION['APFSDS_ID']) || strlen($_SESSION['APFSDS_ID'])<1){
		logwrite("IDS: Logged set without ID");
		$error = true;
	} else {
		$rs = DBQ("select id, permission from USERS where id='".$_SESSION['APFSDS_ID']."'");
		$r = mysql_fetch_row($rs);
		if (!$r) {
			logwrite("IDS: False ID[".$_SESSION['APFSDS_ID']."]");
			$error = true;
		} else {
			if ($r[1]!=$_SESSION['APFSDS_Perm']) {
				logwrite("IDS: False Permission[".$_SESSION['APFSDS_Perm']."]");
				$error = true;
			}
		}
		mysql_free_result($rs);
		if ($error) {
			$_SESSION['APFSDS_FL'] = 0;
			$_SESSION['APFSDS_Logged'] = 0;
			$_SESSION['APFSDS_ID'] = '';
			$_SESSION['APFSDS_Level'] = 0;
			$_SESSION['APFSDS_Perm'] = 0;
		}
	}

}

if ($securityCheckOnly===true)
	return;

// 5. Authentification
$requestLOG = request("LOG");
if ($requestLOG=="IN")
{
    $requestID = request("ID");
    $requestPassword = request("PASSWORD");
    $rs = DBQ("select permission, level, lastlogdate, lastlogip from USERS where id='$requestID' and password='$requestPassword'");
    if($r = mysql_fetch_array($rs))
    {
	$permission = $r['permission'];
	$level = $r['level'];
	$lastlogdate = $r['lastlogdate'];
	$lastlogip = $r['lastlogip'];
	switch($permission)
	{
	    case 1:
		$displayMessage = $displayMessage.'<div class="notice"> The admin did not confirm your account yet. You cannot access your account yet. </div>\n';
		$_SESSION['APFSDS_Logged'] = 0;
		$_SESSION['APFSDS_ID'] = '';
		$_SESSION['APFSDS_Level'] = 0;
		$_SESSION['APFSDS_Perm'] = 0;
		logwrite("Unconfirmed ID user tried to login");
		break;
	    case 2:
		$displayMessage = $displayMessage.'<div class="notice"> The admin rejected your account. You cannot access your account and it is now deleted. </div>\n';
		$_SESSION['APFSDS_Logged'] = 0;
		$_SESSION['APFSDS_ID'] = '';
		$_SESSION['APFSDS_Level'] = 0;
		$_SESSION['APFSDS_Perm'] = 0;
		DBQ("delete from USERS where id='$requestID'");
		logwrite("Rejected ID logged by user and deleted");
		break;
	    default:
		if ($permission>=3)
		{
		    $displayMessage = $displayMessage."<div class=\"notice\" style=\"display:inline\"> Last Login: from $lastlogip at $lastlogdate </div>\n";
		    DBQ("update USERS set lastlogdate=CURRENT_TIMESTAMP, lastlogip='".$_SERVER['REMOTE_ADDR']."' where id='$requestID'");
		    $_SESSION['APFSDS_Logged'] = 1;
		    $_SESSION['APFSDS_ID'] = $requestID;
		    $_SESSION['APFSDS_Level'] = $level;
		    $_SESSION['APFSDS_Perm'] = $permission;
		    logwrite("LOGIN[$permission/$level]");
		    ipstatLogin($requestID);

			if (request("AUTOLOGIN")=="YES") {
				$now = date("Y-m-d H:i:s");
				srand();
				$key = date("YmdHis").$_SERVER['REMOTE_ADDR'].rand(1000001,9999999).rand(10001,99999).rand(100001,999999).'vc'.rand(100,999);
				setcookie("APFSDS_ID", $requestID, time()+$cookieValidFor, $cookiePath, $cookieDomain, 0);
				setcookie("APFSDS_KEY", $key, time()+$cookieValidFor, $cookiePath, $cookieDomain, 0);
				setcookie("APFSDS_KEYDATE", $now, time()+$cookieValidFor, $cookiePath, $cookieDomain, 0);
				DBQ("insert into COOKIELOGIN values('$requestID', '$key', '$now')");
				DBQ("delete from COOKIELOGIN where passwordEntered < '".date('Y-m-d H:i:s', time()-$cookieValidFor)."'");
				$displayMessage.="Auto Login Activated.";
			}
		}
		else
		{
			$displayMessage = $displayMessage."<div class=\"notice\"> ID $requestID Deleted/Rejected/Inactive/Not Confirmed. </div>";
			if ($permission==1)
				DBQ("delete from USERS where id='$requestID'");
		    $_SESSION['APFSDS_Logged'] = 0;
		    $_SESSION['APFSDS_ID'] = '';
		    $_SESSION['APFSDS_Level'] = 0;
		    $_SESSION['APFSDS_Perm'] = 0;
		    logwrite("ERROR permission<3 account found: '$requestID':'$requestPassword'");
		}
	}
    }
    else
    { // log-in failure;
	$_SESSION['APFSDS_Logged'] = 0;
	$_SESSION['APFSDS_ID'] = '';
	$_SESSION['APFSDS_Level'] = 0;
	$_SESSION['APFSDS_Perm'] = 0;
	$displayMessage = $displayMessage."<div class=\"notice\"> Authentification Failure </div>\n";
	logwrite("Login Failure: ".substr($requestID,0,80)."/".substr($requestPassword, 0,80));
    }

}
else if ($requestLOG=="OUT")
{
    $_SESSION['APFSDS_Logged'] = 0;
    $_SESSION['APFSDS_ID'] = '';
    $_SESSION['APFSDS_Level'] = 0;
    $_SESSION['APFSDS_Perm'] = 0;
    $_SESSION['APFSDS_DBName'] = $mysqlDBName;
	DBQ("delete from COOKIELOGIN where passwordEntered='".$_COOKIE['APFSDS_KEYDATE']."' and userID='".$_COOKIE['APFSDS_ID']."' and cookieKey='".$_COOKIE['APFSDS_KEY']."'");
	setcookie("APFSDS_ID", $requestID, time()-$cookieValidFor, $cookiePath, $cookieDomain, 0);
	setcookie("APFSDS_KEY", $key, time()-$cookieValidFor, $cookiePath, $cookieDomain, 0);
	setcookie("APFSDS_KEYDATE", $now, time()-$cookieValidFor, $cookiePath, $cookieDomain, 0);
}
// 6. Interface (HTML)

// 6.0. Print out XHTML-declaration
print $DOCTYPE."\n";

// 6.1. Top <div> sections definition
?>
<html>
<head>
<title><?=$headerBrowserTitleText?></title>
<link rel="stylesheet" type="text/css" href="<?=$CSS?>" />
<link rel="shortcut icon" href="<?=$ICON?>" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="generator" content="MZX" />
</head>
<body onload="loading();" onunload="exiting();">
<script type="text/javascript" src="<?=$URL?>Common/javascriptFunctions.js"></script>
<script type="text/javascript">
<?=$javascriptFunction?>
function loading()
{
<?=$javascriptLoadingAddition?>
}
function exiting()
{
<?=$javascriptUnloadAddition?>
}
</script>
<?
// 6.2. Top menus / Login/out interface / Left Menu
?>
<div id="<?=$divTop?>" style="position:absolute; z-index:4; left:0px; top:0px; width:100%; height:70px; background-image:url(/image/babotTop.jpg); background-repeat:no-repeat;">
<h2 style="display:inline; align:left;"><?=$headerTopTitleText?></h2>
<div id="<?=$divTopLogin?>" style="position:absolute; z-index:6; top:0px; right:0px; height:40px">
<?
if ($_SESSION['APFSDS_Logged']==1)
{
?>
<table class="login">
    <tr><td>Welcome! <?=$_SESSION["APFSDS_ID"]?> [<?=$_SESSION['APFSDS_Perm']?>]</td>
    <td class="loginButton"><form action="<?=$URL?><?=$recallAddressAfterURL?>" method="post" name="logout"><input type="hidden" name="LOG" value="OUT"><input type="submit" value="Logout" /></form></td></tr>
	<tr class="loginButton"><td colspan="2">
		<input type="button" value="Account Setting" onclick="window.open('<?=$URL?>Common/miscFunction.php?DO=showUsers');" />
	</td></tr>
</table><br />
<?
}
else
{
?>
<form action="<?=$URL?><?=$recallAddressAfterURL?>" method="post" name="login">
<div class="login">
<input type="hidden" name="LOG" value="IN" />
    ID <input type="text" name="ID" size="8" />
	Password <input type="password" name="PASSWORD" size="8" /><br />
<div class="loginButton">
	<input type="checkbox" name="AUTOLOGIN" value="YES" style="border:0px;width:14px;" />AutoLogin <input type="submit" value="Login" style="width:40px" />
	<input type="button" value="New Account" onclick="window.open('<?=$URL?>Common/miscFunction.php?DO=newUser');" />
</div>
</div>
</form>
<?
}
?>
</div>
<div style="position:absolute; z-index:6; top:45px; left:0px;" id="<?=$divTopMenu?>">
<table class="topmenu">
<tr>
<td><a href="<?=$URL?>" <?if ($headerTopMenuSelected=='Blog') print("class=\"topmenuSelected\""); ?>>Blog</a></td>
<td><a href="<?=$URL?>profile/" <?if ($headerTopMenuSelected=='Profile') print("class=\"topmenuSelected\""); ?>>Profile</a></td>
<td><a href="<?=$URL?>academic/" <?if ($headerTopMenuSelected=='Academic') print("class=\"topmenuSelected\""); ?>>Academic</a></td>
<td><a href="<?=$URL?>photo/" <?if ($headerTopMenuSelected=='Photo') print("class=\"topmenuSelected\""); ?>>Photo</a></td>
<td><a href="<?=$URL?>link/" <?if ($headerTopMenuSelected=='Link') print("class=\"topmenuSelected\""); ?>>Link</a></td>
<th>&nbsp;</th>
<td><a href="<?=$URL?>en/" <?if ($headerTopMenuSelected=='English') print("class=\"topmenuSelected\""); ?>>In English</a></td>
</tr>
</table>
</div>
</div>
<div id="<?=$divPopup?>" style="position:absolute; z-index:10; left:0px; top:30px; width:800px; height:18px; "><?=$displayMessage?></div>
<div id="<?=$divWritePanel?>" style="position:absolute; z-index:6; left:126px; top:71px; width:0px; height:0px;"></div>
<div id="<?=$divCommentShow?>" class="comment" style="position:absolute; z-index:7; left:126px; top:100px; display:none;"></div>
<div id="<?=$divMemoShow?>" class="memo" style="position:absolute; z-index:8; left:126px; top:100px; display:none;"></div>
<div id="searchDiv" class="search" style="position:absolute; z-index:9; left:126px; top:100px; display:none;"></div>
<?
// 6.3. Start of the body div
?>

<?
	$securityCheckOnly = true;
	include_once("header.php");
	print $DOCTYPE."\n";
	$perPage = 10;
?>
<html>
<head>
<title>Memo Pad</title>
<link rel="stylesheet" type="text/css" href="<?=$CSS?>" />
<link rel="shortcut icon" href="<?=$ICON?>" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="generator" content="MZX" />
</head>
<body onload="loading();" onunload="exiting();" class="miscWindow" >
<script type="text/javascript" src="<?=$URL?>Common/javascriptFunctions.js"></script>
<script type="text/javascript">
<?=$javascriptFunction?>
function loading()
{
	autoResize(830);
	setHeight2();
}
function exiting()
{
}
</script>
<?
$skip = 0;
if (isset($_POST["SKIP"]) || isset($_GET["SKIP"]))
{
	$skip = requestInt("SKIP");
	$_SESSION['APFSDS_MSKIP']=$skip;
}
else
{
	$skip = $_SESSION['APFSDS_MSKIP']+0;
}

if (isset($_POST["PERPAGE"]) || isset($_GET["PERPAGE"]))
{
	$perPage = requestInt("PERPAGE");
	$_SESSION['APFSDS_MPERPAGE']=$perPage;
}
else
	$perPage = max($_SESSION['APFSDS_MPERPAGE'],10);


DBConnect();
$do = request("DO");
switch ($do) {
	case "WRITE":
		$content = request("CONTENT");
		if ($_SESSION['APFSDS_Logged']==1 && $_SESSION['APFSDS_Perm']>=3) {
			$ip = $_SERVER['REMOTE_ADDR'];
			DBQ("insert into MEMO values (null, '".$_SESSION['APFSDS_ID']."', ".
					"'$content', '$ip', null)");
		}
		break;
	case "DELETE":
		$id = requestInt("ID");
		$rs2 = DBQ("select author from MEMO where id=$id");
		$r2 = mysql_fetch_array($rs2);
		if ($r2) {
			$author = $r2[0];
			if ($_SESSION['APFSDS_Logged']==1 && (
						$_SESSION['APFSDS_Perm']>=100 ||
						($_SESSION['APFSDS_ID']==$author &&
						 strlen($author)>0))) {
				DBQ("delete from MEMO where id=$id");
			}
		}
		mysql_free_result($rs2);
		break;
	default:
}
$rs = DBQ("select * from MEMO order by id desc");
if ($skip>0) {
	mysql_data_seek($rs, $skip);
}
$shown = 0;
?>
<table class="MEMO" style="width:95%">
	<?
if ($_SESSION['APFSDS_Logged']==1) {
	?>
		<tr>
			<td colspan="4" class="MEMO3">
			<form name="ADDMEMO" id="ADDMEMO" action="memopad.php" method="POST">
			<input type="hidden" name="DO" value="WRITE" />
			<input type="text" name="CONTENT" size="60" />
			<input type="submit" value="Write" />
			</form>
			</td>
		</tr>
	<?
} else {
	?>
		<tr><td colspan="4" class="MEMO3"><h3>Log-in in order to write memo</h3></td></tr>
	<?
}
while ($r=mysql_fetch_assoc($rs))
{
	?>  <tr><? if (strlen($r['author'])>0) {
		print "<td class=\"MEMO3\">";
		print "<a href=\"$URL"."Common/miscFunction.php?DO=describeAuthor&AUTHORID=".urlencode($r['author'])."\" target=\"_blank\">";
		$rs2 = DBQ("select nickname from USERS where id='".$r['author']."'");
		$r2 = mysql_fetch_array($rs2);
		$nickname = '';
		if ($r2)
			$nickname = $r2[0];
		mysql_free_result($rs2);
		if (strlen($nickname)>0)
			print "<b>".$nickname."</b>";
		else
			print $r['author']; 
		print "</a>";
	} else print "<td class=\"MEMO1\">".$r['ip']; ?></td>
		<td class="MEMO2"><?
		if ($_SESSION['APFSDS_Logged']==1 && (
					$_SESSION['APFSDS_Perm']>=100 ||
					($_SESSION['APFSDS_ID']==$r['author'] &&
					 strlen($r['author'])>0))) {
			print "<a href=\"memopad.php?SKIP=$skip&DO=DELETE&ID=".$r['id']."\" onclick=\"javascript:return confirm('Really?');\">x</a>";
		}
		flush();
		?></td>
		<td class="MEMO2"><?=$r['createon']?></td>
		<td class="MEMO3"><?=parseImoticons(findLink(html2text($r['content'])))?></td>
		</tr>
		<?
		$shown++;
	if ($shown>=$perPage)
		break;
}
?>
<tr><td class="MEMO1" colspan="2" style="text-align:left"><a href="memopad.php?SKIP=0">First</a>
<?
if ($skip>0)
{
	$next = $skip-$perPage;
	if ($next<0)
		$next = 0;
	print "<a href=\"memopad.php?SKIP=$next\">Previous</a>";
}
?>
</td><td class="MEMO1" colspan="2" style="text-align:right">
<?
if (mysql_fetch_assoc($rs))
{
	$next = $skip+$perPage;
	print "<a href=\"memopad.php?SKIP=$next\">Next</a>&nbsp;&nbsp;&nbsp;";
}
if ($perPage!=10) {
?>
<a href="memopad.php?PERPAGE=10">[Compact]</a>
<? }
if ($perPage!=30) {
?>
<a href="memopad.php?PERPAGE=30">[Extend]</a>
<? } ?>
</td></tr>
</table>
<?
mysql_free_result($rs);
?>

</body>
</html>

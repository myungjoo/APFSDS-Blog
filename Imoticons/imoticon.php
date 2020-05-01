<?
include_once("../Common/common.php");
DBConnect();


?>
<html>
<head>
<title></title>
<link rel="stylesheet" type="text/css" href="<?=$CSS?>" />
</head>
<body class="nameTag" onload="loading();">
<script type="text/javascript" src="<?=$URL?>Common/javascriptFunctions.js"></script>
<script type="text/javascript">
function loading()
{
	<? if (request("DO")!="USAGE") { ?>
	setHeight2();
	setHeight3();
	<? } ?>
}
</script>
<?
$command = request("DO");
switch($command) {
case "USAGE":
	$parentDIV = request("PARENTID");
	$parentID = 'commentTextarea_'.request("PARENTID");
	$i = 0;
	?>
	<div style="font-size:8.5pt" id="list">
	<?
	foreach ($imoticonDB as $key => $value) {
		$i++;
	?>
		<a href="javascript:addImoticon('<?=$parentID?>','<?=$key?>');"><img src="<?=$value?>" alt="$key" id="imoticon<?=$i?>" /></a>&nbsp;
	<?
	}
	?>
	<br /><br />Note that auto emoticon insertion is supported ONLY with FireFox...
	</div>
<script type="text/javascript">
var size;
var i;
size = <?=$i?>;
for (i=1; i<=size; i++) {
	var obj = document.getElementById('imoticon'+i);
	if (obj.height > 30) {
		obj.height = 30;
	}
}
var div = document.getElementById('list');
parent.document.getElementById('<?=$parentDIV?>').style.height = div.scrollHeight;
</script>
	</body></html>
	<?
	return;
	break;
case "DELETE":
	$rV = removeImoticon(request("KEY"));
	if ($rV===0) {
		print("Emoticon ".request("KEY")." Removed...");
	} else {
		print($rV);
	}
	return;
	break;
case "ADD":
	if ($_SESSION['APFSDS_Perm']<4) {
		print ("ERROR: Permission Denied...");
		return;
	}

	$filaname = ''; // NEED TO UPLOAD...

	DBQ("START TRANSACTION");


	$type = request("TYPE");
	$key = request("KEY");
	$description = request("DESCRIPTION");
	$filename = "";
	$imageURL = "";

	$rs = DBQ("select * from IMOTICONS where imoticon='$key'");
	$r = mysql_fetch_row($rs);
	mysql_free_result($rs);
	if ($r) {
		print ("ERROR: Key @$key Already Exists...");
		DBQ("ROLLBACK");
		return;
	}

	if ($type=="URL") {
		$imageURL = request("URL");
	} elseif ($type=="UPLOAD") {
		// $_FILES["attachment"][];
		$clientFilename = $_FILES["attachment"]['name'];
		$fileMime = $_FILES['attachment']['type'];
		$fileSize = $_FILES['attachment']['size'];
		$localTmpFilename = $_FILES['attachment']['tmp_name'];

		$prefix = rand();
		$localFilename = $prefix.".".$clientFilename;
		while (is_file($LOCAL."Imoticon".$LOCALSEPERATOR.$localFilename)) {
			$prefix = rand();
			$localFilename = $prefix.".".$clientFilename;
		}
		if (!move_uploaded_file($localTmpFilename, $LOCAL."Imoticons".$LOCALSEPERATOR.$localFilename)) {
			print ("ERROR: Upload Failed...");
			DBQ("ROLLBACK");
			return;
		}
		$filename = $localFilename;

	} else {
		print("ERROR: Uploading Mode Not Selected...");
		DBQ("ROLLBACK");
		return;
	}

	$rV = addImoticon(request("KEY"),$filename,$imageURL,request("DESCRIPTION"));
	if ($rV===0) {
		print("Emoticon ".request("KEY")." Added...");
		DBQ("COMMIT");
	} else {
		print($rV);
		unlink($LOCAL."Imoticons".$LOCALSEPERATOR.$filename);
		DBQ("ROLLBACK");
	}
	return;
	break;
default:
	}
?>
	<h3><center>List of Emoticons</center></h3>
	<table border="1" style="font-size:9pt;width:100%">
	<tr>
	<th>Emoticon Key</th><th>Image Filename</th><th>Created By</th><th>Authorized</th><th>Date</th>
	</tr>
<?
	$rs = DBQ("select * from IMOTICONS order by created desc");
	while ($r = mysql_fetch_assoc($rs))
	{
?>
	<tr>
	<td>@<?=$r['imoticon']?>
<?
		if ($_SESSION['APFSDS_Logged']==1 && (
			$_SESSION['APFSDS_Perm']==100 ||
			($_SESSION['APFSDS_ID']==$r['creator'] &&
			strlen($r['creator'])>0))) {
?>
				<a href="<?=$URL?>Imoticons/imoticon.php?DO=DELETE&KEY=<?=urlencode($r['imoticon'])?>" style="color:#FF0000;font-size:8pt;" onclick="return confirm('really?');">delete</a>
<?
			}
?>
		</td>
		<td>
<?  
		if (strlen($r['filename'])>0)
			print "Uploaded Image: <img src=\"".$URL."Imoticons/".$r['filename']."\" alt=\"Internal Image\" />(".$r['filename'].")";
		else
			print "External Image: <img src=\"".$r['imageURL']."\" alt=\"External Image\" />(".$r['imageURL'].")";
?>
		</td>
		<td><?=$r['creator']?></td>
		<td><? if ($r['authorized']==0) print "NO"; else print "YES"; ?></td>
		<td><?=$r['created']?></td>
	</tr>
	<tr>
		<td colspan="5">
		<?=$r['description']?>
		</td>
	</tr>
<?
	}
	mysql_free_result($rs);
?>
</table>
<br />

<?
	if ($_SESSION['APFSDS_Perm']>=4) {
?>
<h3><center>Add Emoticons by <?=$_SESSION['APFSDS_ID']?></center></h3>
<form method="POST" enctype="multipart/form-data" action="<?=$URL?>Imoticons/imoticon.php" id="AddImoticon">
<input type="hidden" name="DO" value="ADD" />
<table style="font-size:9pt;width:100%">
<tr><th style="text-align:right">Emoticon Key (up to 8 characters)</th>
	<td>@<input type="text" name="KEY" maxlength="8" /></td>
</tr>
<tr><th style="text-align:right">File Upload <input type="radio" name="TYPE" value="UPLOAD" checked="YES" /></th>
	<td><input type="file" name="attachment" size="50" /></td>
</tr>
<tr><th style="text-align:right">URL <input type="radio" name="TYPE" value="URL" /></th>
	<td><input type="text" name="URL" size="80" value="https://" /></td>
</tr>
<tr><th style="text-align:right">Description</th>
	<td><textarea cols="50" name="DESCRIPTION" rows="4"></textarea></td>
</tr>
<?
		if ($_SESSION['APFSDS_Perm']<100) {
?>
		<tr><th colspan="2">After the emoticon is registered, the authorization of admin is required in order to use the emoticon...<br />Notify the admin if he/she does not respond in time.
		</th></tr>
<?
		}
?>
<tr>
	<td colspan="2">
	<input type="submit" style="width:500px" value="Register the emoticon" />
	</td>
</tr>
</table>
</form>
<?
	}
?>

<br />
</body>
</html>


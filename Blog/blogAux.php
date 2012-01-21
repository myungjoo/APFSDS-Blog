<? //second-degree independent(running in a div-iframe) message in PopupDiv
$securityCheckOnly=true;
include_once("../Common/header.php");
include_once("engine.php");
?>
<html>
<head>
<title></title>
<link rel="stylesheet" type="text/css" href="<?=$CSS?>" />
</head>
<body class="message" onload="loading();">
<script type="text/javascript" src="<?=$URL?>Common/javascriptFunctions.js"></script>
<script type="text/javascript">
function loading()
{
	autoResize(830);
	setHeight2();
<?
	if ((request("DO")=="SHOWWRITE" || request("DO")=="SHOWREPLY" || request("DO")=="SHOWUPDATE") && request("contentResize")!='no')
		print("setHeight3();");
	?>
}
</script>
<?
$DO = request("DO");
$CONTENTDIV = request("CDIV"); // CommentShowDiv or ArticleShowDiv
$BLOGID = request("BLOGID");
if ($BLOGID=='')
	$BLOGID='default';
$ARTICLEID = requestInt("ARTICLEID");
switch($DO)
{
case "SHOWMANAGETAG":
	print showManageTag($BLOGID);
	break;
case "WRITECOMMENT":
	$message = doWriteComment(requestInt("THREAD"));
	?>
	<script type="text/javascript">
	var cdiv = parent.document.getElementById("<?=$divPopup?>");
	cdiv.innerHTML = '<? print javascriptCompatible($message); ?>';
	var w = parent.document.getElementById('if'+window.name);
	var wsrc = w.src;
	w.src = wsrc;
	</script>
	<?
	print showArticle($BLOGID, $ARTICLEID, false, false);
	break;
case "DELETECOMMENT":
	$message = doDeleteComment($BLOGID, $ARTICLEID);
	?>
	<script type="text/javascript">
	var cdiv = parent.document.getElementById("<?=$divPopup?>");
	cdiv.innerHTML = '<? print javascriptCompatible($message); ?>';
	var w = parent.document.getElementById('if'+window.name);
	var wsrc = w.src;
	w.src = wsrc;
	</script>
	<?
	print showArticle($BLOGID, $ARTICLEID, false, false);
	break;
case "WRITEARTICLE":
	print doWriteArticle($divPopup);
	break;
case "UPDATEARTICLE":
	print doUpdateArticle($BLOGID, $ARTICLEID);
/*
	?>
	<script type="text/javascript">
	var cdiv = parent.document.getElementById("<?=$divPopup?>");
	cdiv.innerHTML = '<? print javascriptCompatible($message); ?>';
	var w = parent.document.getElementById('if'+window.name);
	var wsrc = w.src;
	w.src = wsrc;
	</script>
	<?
	print showArticle($BLOGID,$ARTICLEID,false,false);
*/
	break;
case "DELETEARTICLE":
	$message = doDeleteArticle($BLOGID, $ARTICLEID);
	?>
	<script type="text/javascript">
	var cdiv = parent.document.getElementById("<?=$divPopup?>");
	cdiv.innerHTML = "<? print $message; ?>";
	setHeight2();
	</script>
	<?
	break;
case "READARTICLE":
		print showArticle($BLOGID, $ARTICLEID, false, false);
		break;
case "SHOWCOMMENT":
		print showComment($BLOGID, $ARTICLEID, 0, true, 'setHeight2();');
		break;
case "SHOWWRITE":
		if (request("contentResize")=='no')
			$contentResize = false; 
		else
			$contentResize = true;
		print showWriteInterface($BLOGID, 0, $contentResize);
		break;
case "SHOWREPLY":
		print showWriteInterface($BLOGID, requestInt("THREAD"));
		break;
case "SHOWUPDATE":
		print showUpdateInterface($BLOGID, $ARTICLEID);
		break;
}
?>

</body>
</html>


<?
include_once("common.php");
print $DOCTYPE."\n";
$about = request("About");
?>
<html>
<head>
<title><?=$headerBrowserTitleText?></title>
<link rel="stylesheet" type="text/css" href="<?=$CSS?>" />
<link rel="shortcut icon" href="<?=$ICON?>" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="generator" content="MZX" />
</head>
<body onload="loading();" onunload="exiting();" class="helpDocument">
<script type="text/javascript" src="<?=$URL?>Common/javascriptFunctions.js"></script>
<script type="text/javascript">
<?=$javascriptFunction?>
function loading()
{
}
function exiting()
{
}
</script>
<?
switch($about)
{
	case "HTMLConvention":
?>
<table class="newTagTitle" border="1px" style="font-size:11pt">
<tr>
	<th colspan="3"> Convention Version 3 <br />Nesting Allowed. Incomplete/Wrong Tags do not disrupt other contents. Work in Progress.<br />Progress: Implementation Completed. Testing The Parser...</th>
</tr>
<tr><td style="font-size:12pt;font-weight:bold">[img]URL[/img]<br />[img=URL]</td><td> Show the image</td></tr>
<tr><td style="font-size:12pt;font-weight:bold">[url][[Title]]URL[/url]<br />[url]URL[/url]<br />[url=URL]</td><td> A link to the url<br />[url][[Title]] will be implemented in version 3.<br />Usage of [url=Title]URL[/url] is disabled.</td></tr>
<tr><td style="font-size:12pt;font-weight:bold">[s] content [/s]<br />[del] content [/del]</td><td> <del>A Stroke/Del</del></td></tr>
<tr><td style="font-size:12pt;font-weight:bold">[l], [r]</td><td> [ and ] respectively </td></tr>
<tr><td style="font-size:12pt;font-weight:bold">[u] content [/u]</td><td> <u>Underline</u></td></tr>
<tr><td style="font-size:12pt;font-weight:bold">[att]attachment number[/att]<br />[att=attachment number]</td><td> Show/Link of the attachment num</td></tr>
<tr><td style="font-size:12pt;font-weight:bold">[hid][[title]]Content[/hid]</td><td> Hidden content that can be showed/hidden by user</td></tr>
<tr><td style="font-size:12pt;font-weight:bold">[tag]Content[/tag]</td><td> any \n would be ignored in this zone</td></tr>
<tr><td style="font-size:12pt;font-weight:bold">[qot][[citation]]Content[/qot]<br />[qot=title][[citation]]Content[/qot]</td><td> Content with citation<br />[qot=title] will be implemented in version 3.</td></tr>
<tr><td style="font-size:12pt;font-weight:bold">[log]Content[/log]<br />[log=level]Content[/log]</td><td> Hidden content limited to the level (default = 3, logged user). 100 for admin<br />[log=level] will be implemented in version 3. </td></tr>
<tr><td style="font-size:12pt;font-weight:bold">[]</td><td> A space (&amp;nbsp) </td></tr>
<tr><th colspan="2"> Imoticons/JB (Work In Progress) These will be stored in DB... Key length is limited to 8<br />Add a space or @ after the imoticon to be parsed!</th></tr>
<tr><td style="font-size:12pt;font-weight:bold">@lol</td><td> </td></tr>
<tr><td style="font-size:12pt;font-weight:bold">@:)</td><td> </td></tr>
<tr><td style="font-size:12pt;font-weight:bold">@:(</td><td> </td></tr>
<tr><td style="font-size:12pt;font-weight:bold">@;)</td><td> </td></tr>
<tr><td style="font-size:12pt;font-weight:bold">@:-}</td><td> </td></tr>
<tr><td style="font-size:12pt;font-weight:bold">@XD</td><td> </td></tr>

<tr>
	<th colspan="3"> Convention Version 2 <br />Nesting is not allowed in version 2. Convention can be used in [[title]] area except another nested [[title]].<br /><br /></th>
</tr>
<tr>
	<th colspan="3"> Conventions Version 1<br />
	Both for HTML enabled and disabled. <br />
	<H3> Deprecated. DO NOT USE Version 1. </H3></th>
</tr>
<tr> <th> @+ </th><td> @  </td><td> Escape Character is @ </td> </tr>
<tr> <th> @s </th><td> &lt;s&gt; (actually &lt;del&gt;) </td><td> Starting <del>Strike Through</del> Tag</td> </tr>
<tr> <th> @-s </th><td> &lt;/s&gt; (actually &lt;/del&gt;) </td><td> Finishing <del>Strike Through</del> Tag</td> </tr>
<tr> <th> @img"URL" </th><td> &lt;img src="URL"&gt; </td><td> Show image of the URL </td></tr>
<tr> <th> @link"URL" </th><td> &lt;a href="URL"&gt; </td><td> Show link of the URL </td></tr>
<tr> <th> @#"NUM" </th><td> &lt;img src="...."&gt; </td><td> Show the attached image(number NUM) of the article<br />When there is no such NUM, show the closest. </td></tr>
<tr> <th> @hidden TitleName "Content" </th><td colspan="2"> Hidden Content "Content" is created with title "TitleName" ''(two consequent single quote) is converted into one double quote. Do not use "(double quote) in its title or content. no @ allowed. use &amp;#64; instead</td></tr>
<tr> <th> @\n </th><td> No &lt;br /&gt; </td><td> Do not add &lt;br /&gt; tag there. \n == ENTER </td></tr>
<tr> <th> @_ </th><td> &amp;nbsp; </td><td> Print a space. </td></tr>
</table>
<?
		break;
	default:
}
?>
</body>
</html>

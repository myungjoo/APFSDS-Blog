<?
// RSS 2.0 feed of a blog.
//   getRss.php?BLOGID=default (default) &COUNT=20 (1~50)
// The feed is always generated with guest permission, regardless of the
// session, because feed readers/aggregators cache and share it.
chdir(dirname(__FILE__)."/..");
include_once("Common/common.php");
require_once("Blog/engine.php");

DBConnect();

$blogID = request("BLOGID");
if ($blogID=='')
	$blogID = 'default';
$count = (int)request("COUNT");
if ($count<1)
	$count = 20;
if ($count>50)
	$count = 50;

function rssText($str)
{
	// Drop characters that are not allowed in XML 1.0.
	$str = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $str);
	return htmlspecialchars($str, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
}
function rssCDATA($str)
{
	$str = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $str);
	if (!mb_check_encoding($str, 'UTF-8'))
		$str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
	return '<![CDATA['.str_replace(']]>', ']]]]><![CDATA[>', $str).']]>';
}
function rssError($code, $msg)
{
	http_response_code($code);
	header("Content-Type: text/plain; charset=utf-8");
	print $msg."\n";
	exit;
}

$property = getBlogProperty($blogID);
if (!$property)
	rssError(404, "No such blog.");
// Only blogs open to guests are published.
if ($property['permissionIndex']>0 || $property['permissionRead']>0 ||
    $property['accessControlIndex']!=0 || $property['accessControlRead']!=0)
	rssError(403, "This blog is not public.");

// Same rule as showList(): skip articles having any tag that a guest cannot read.
// DB timestamps are in the MySQL server time zone; let MySQL convert them.
$rs = DBQ("SELECT art.*, UNIX_TIMESTAMP(art.createDate) AS pubTS, UNIX_TIMESTAMP(art.modifyDate) AS modTS FROM BlogArticle AS art WHERE art.blogID='$blogID' and art.deleted=0 ".
	"and (SELECT count(tagAS.tagID) FROM BlogTagArticleAssoc AS tagAS, BlogTag AS tag ".
		"WHERE tagAS.blogID=art.blogID and tagAS.articleID=art.articleID and tagAS.blogID=tag.blogID and tagAS.tagID=tag.tagID ".
		"and (tag.permissionRead>0 or tag.accessControlRead<>0))=0 ".
	"ORDER BY art.createDate DESC, art.articleID DESC LIMIT $count");

$items = '';
$lastBuild = 0;
while ($article = mysql_fetch_assoc($rs))
{
	$articleID = $article['articleID'];
	$link = $property['accessURL']."?Article=".urlencode($articleID);
	$pubDate = (int)$article['pubTS'];
	$modified = (int)$article['modTS'];
	if (max($pubDate, $modified)>$lastBuild)
		$lastBuild = max($pubDate, $modified);

	$author = ($article['author']=='' || $article['author']==null)?$article['guestName']:$article['author'];

	$categories = '';
	$rsTag = DBQ("select t.tagTitle from BlogTagArticleAssoc AS a, BlogTag AS t where a.blogID='$blogID' and a.articleID=$articleID and a.blogID=t.blogID and a.tagID=t.tagID");
	while ($rt = mysql_fetch_row($rsTag))
		if (strlen($rt[0])>0)
			$categories .= "\t\t<category>".rssText(strip_tags($rt[0]))."</category>\n";
	mysql_free_result($rsTag);

	// Render like showArticle(), without hiddenContent and without the inline javascript.
	$content = str_replace(array("\n"), array("<br />"),
		htmlConvention(
			(($article['html']==1)?$article['content']:html2text($article['content'])),
			getAttached($blogID, $articleID), $articleID, $blogID));
	$content = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $content);
	$content = preg_replace('#\s(id|onclick|onload)="[^"]*"#i', '', $content);
	$content = preg_replace('#<a href="javascript:[^"]*"[^>]*>(.*?)</a>#is', '$1', $content);

	$items .= "\t<item>\n".
		"\t\t<title>".rssText($article['title'])."</title>\n".
		"\t\t<link>".rssText($link)."</link>\n".
		"\t\t<guid isPermaLink=\"true\">".rssText($link)."</guid>\n".
		"\t\t<pubDate>".date(DATE_RSS, $pubDate)."</pubDate>\n".
		((strlen($author)>0)?"\t\t<dc:creator>".rssText($author)."</dc:creator>\n":"").
		$categories.
		"\t\t<description>".rssCDATA($content)."</description>\n".
		"\t</item>\n";
}
mysql_free_result($rs);
if ($lastBuild==0)
	$lastBuild = time();

$selfURL = $URL."rss/getRss.php".(($blogID=='default')?"":"?BLOGID=".urlencode($blogID));
$title = (strlen($property['titleTEXT'])>0)?$property['titleTEXT']:$blogID;

header("Content-Type: application/rss+xml; charset=utf-8");
header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastBuild)." GMT");
print '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
	<title><?=rssText($title)?></title>
	<link><?=rssText($property['accessURL'])?></link>
	<atom:link href="<?=rssText($selfURL)?>" rel="self" type="application/rss+xml" />
	<description><?=rssText((strlen($property['info'])>0)?strip_tags($property['info']):$title)?></description>
	<lastBuildDate><?=date(DATE_RSS, $lastBuild)?></lastBuildDate>
	<generator>APFSDS Blog Engine</generator>
<?=$items?>
</channel>
</rss>

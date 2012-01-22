<?
if ($_SESSION['APFSDS_Perm']<5)
 return;
include_once("../Common/common.php");
DBConnect();
$rs = DBQ("select * from IPSTAT order by lastaccess desc");
$count = 0;
?>
<table style="font:11pt;">
<tr>
<?
for ($i =0; $i<mysql_num_fields($rs); $i++)
	print "<th>".mysql_field_name($rs, $i)."</th>";
?>
</tr>
<?
while ($r = mysql_fetch_row($rs))
{
	$count ++;
	print "<tr>";
	for ($i=0 ; $i<sizeof($r); $i++)
		print "<td>".$r[$i]."</td>";
	print "</tr>";

	if ($count >= 100)
		break;
}
?>
</table>

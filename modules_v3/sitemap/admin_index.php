<?php
// Display a diff between two language files to help in translating.
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2002 to 2009  PGV Development Team.  All rights reserved.
//
// Modifications Copyright (c) 2010 Greg Roach
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// $Id$

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

$controller=new WT_Controller_Base();
$controller
	->requireAdminLogin()
	->setPageTitle(WT_I18N::translate('Generate Sitemap files'));

global $GEDCOM, $SHOW_MARRIED_NAMES;

$action = safe_REQUEST($_REQUEST, 'action', WT_REGEX_XREF);
$welcome = safe_REQUEST($_REQUEST, 'welcome', WT_REGEX_XREF);
$gedcom_name = safe_REQUEST($_REQUEST, 'gedcom_name');
$filename = safe_REQUEST($_REQUEST, 'filename');
$filenames = safe_REQUEST($_REQUEST, 'filenames');
$index = safe_REQUEST($_REQUEST, 'index');
$welcome_priority = safe_REQUEST($_REQUEST, 'welcome_priority', WT_REGEX_XREF);
$welcome_update = safe_REQUEST($_REQUEST, 'welcome_update', WT_REGEX_XREF);
$indi_rec = safe_REQUEST($_REQUEST, 'indi_rec', WT_REGEX_XREF);
$indirec_priority = safe_REQUEST($_REQUEST, 'indirec_priority', WT_REGEX_XREF);
$indirec_update = safe_REQUEST($_REQUEST, 'indirec_update', WT_REGEX_XREF);
$indi_lists = safe_REQUEST($_REQUEST, 'indi_lists', WT_REGEX_XREF);
$indilist_priority = safe_REQUEST($_REQUEST, 'indilist_priority', WT_REGEX_XREF);
$indilist_update = safe_REQUEST($_REQUEST, 'indilist_update', WT_REGEX_XREF);
$fam_rec = safe_REQUEST($_REQUEST, 'fam_rec', WT_REGEX_XREF);
$famrec_priority = safe_REQUEST($_REQUEST, 'famrec_priority', WT_REGEX_XREF);
$famrec_update = safe_REQUEST($_REQUEST, 'famrec_update', WT_REGEX_XREF);
$fam_lists = safe_REQUEST($_REQUEST, 'fam_lists', WT_REGEX_XREF);
$famlist_priority = safe_REQUEST($_REQUEST, 'famlist_priority', WT_REGEX_XREF);
$famlist_update = safe_REQUEST($_REQUEST, 'famlist_update', WT_REGEX_XREF);

if ($action=="sendFiles") {
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.$filename.'"');

	echo "<?xml version='1.0' encoding='UTF-8' ?>\n";
	echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n";
	echo " xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
	echo " xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n";
	echo " http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";

	if (isset($welcome)) {
		echo " <url>\n";
		echo " <loc>", WT_SERVER_NAME, WT_SCRIPT_PATH, "index.php?ctype=gedcom&amp;ged=", rawurlencode($gedcom_name), "</loc>\n";
		echo " <changefreq>", $welcome_update, "</changefreq>\n";
		echo " <priority>0.", $welcome_priority, "</priority>\n";
		echo " </url>\n";
	}
	$oldGEDCOM = $GEDCOM;
	$GEDCOM = $gedcom_name;

	if (isset($indi_rec)) {
		$statement=WT_DB::prepare("SELECT i_id, i_gedcom FROM `##individuals` WHERE i_file=?")->execute(array($index));
		while ($row=$statement->fetch(PDO::FETCH_NUM)) {
			$record=WT_Person::getInstance($row[0]);
			if ($record->canDisplayDetails(WT_PRIV_PUBLIC)) {
				echo " <url>\n";
				echo " <loc>", $record->getAbsoluteLinkUrl(), "</loc>\n";
				$chan=$record->getChangeEvent();
				if ($chan) {
					$date=$chan->getDate();
					if ($date->isOK()) {
						echo " <lastmod>", $date->MinDate()->Format('%Y-%m-%d'), "</lastmod>\n";
					}
				}
				echo " <changefreq>", $indirec_update, "</changefreq>\n";
				echo " <priority>0.", $indirec_priority, "</priority>\n";
				echo " </url>\n";
			}
		}
		$statement->closeCursor();
	}

	if (isset($fam_rec)) {
		$statement=WT_DB::prepare("SELECT f_id, f_gedcom FROM `##families` WHERE f_file=?")->execute(array($index));
		while ($row=$statement->fetch(PDO::FETCH_NUM)) {
			$record=WT_Family::getInstance($row[0]);
			if ($record->canDisplayDetails(WT_PRIV_PUBLIC)) {
				echo " <url>\n";
				echo " <loc>", WT_SERVER_NAME, WT_SCRIPT_PATH, "family.php?famid=", $row[0], "&amp;ged=", rawurlencode($gedcom_name), "</loc>\n";
				$chan=$record->getChangeEvent();
				if ($chan) {
					$date=$chan->getDate();
					if ($date->isOK()) {
						echo " <lastmod>", $date->MinDate()->Format('%Y-%m-%d'), "</lastmod>\n";
					}
				}
				echo " <changefreq>", $famrec_update, "</changefreq>\n";
				echo " <priority>0.", $famrec_priority, "</priority>\n";
				echo " </url>\n";
			}
		}
		$statement->closeCursor();
	}

	if (isset($fam_lists)) {
		foreach (WT_Query_Name::surnameAlpha($SHOW_MARRIED_NAMES, true, $index) as $letter=>$count) {
			if ($letter!='@') {
				echo " <url>\n";
				echo " <loc>", WT_SERVER_NAME, WT_SCRIPT_PATH, "famlist.php?alpha=", urlencode($letter), "&amp;ged=", rawurlencode($gedcom_name), "</loc>\n";
				echo " <changefreq>", $famlist_update, "</changefreq>\n";
				echo " <priority>0.", $famlist_priority, "</priority>\n";
				echo " </url>\n";
			}
		}
	}

	if (isset($indi_lists)) {
		foreach (WT_Query_Name::surnameAlpha($SHOW_MARRIED_NAMES, false, $index) as $letter=>$count) {
			if ($letter!='@') {
				echo " <url>\n";
				echo " <loc>", WT_SERVER_NAME, WT_SCRIPT_PATH, "indilist.php?alpha=", urlencode($letter), "&amp;ged=", rawurlencode($gedcom_name), "</loc>\n";
				echo " <changefreq>", $indilist_update, "</changefreq>\n";
				echo " <priority>0.", $indilist_priority, "</priority>\n";
				echo " </url>\n";
			}
		}
	}
	echo "</urlset>";
	$GEDCOM = $oldGEDCOM;
	exit;
}

if ($action=="sendIndex") {
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="SitemapIndex.xml"');

	echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
	echo "<sitemapindex xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
	echo "xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n";
	echo "url=\"http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd\"\n";
	echo "xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";


	if (isset($filenames)) {
		foreach ($filenames as $ged_index=>$ged_name) {
			$xml_name = str_ireplace(".ged",".xml", $ged_name);
			echo " <sitemap>\n";
			echo " <loc>", WT_SERVER_NAME, WT_SCRIPT_PATH, "SM_", $xml_name, "</loc>\n";
			echo " <lastmod>", date("%Y-%m-%d"), "</lastmod>\n ";
			echo " </sitemap>\n";
		}
	}
	echo "</sitemapindex>\n";
	exit;
}

$controller->pageHeader();

if ($action=="generate") {
	echo "<h3>";
	echo WT_I18N::translate('Generate Sitemap files');
	echo help_link('SITEMAP','sitemap');
	echo "</h3>\n";
	echo "<table class=\"facts_table\">\n";
	echo "<tr><td class=\"topbottombar\">", WT_I18N::translate('Selected items to store in Sitemap:'), "</td></tr>\n";
	if (isset($_POST["welcome_page"])) echo "<tr><td class=\"optionbox\">", WT_I18N::translate('Home page'), "</td></tr>\n";
	if (isset($_POST["indi_recs"])) echo "<tr><td class=\"optionbox\">", WT_I18N::translate('Individual information'), "</td></tr>\n";
	if (isset($_POST["indi_list"])) echo "<tr><td class=\"optionbox\">", WT_I18N::translate('Individual list'), "</td></tr>\n";
	if (isset($_POST["fam_recs"])) echo "<tr><td class=\"optionbox\">", WT_I18N::translate('Family information'), "</td></tr>\n";
	if (isset($_POST["fam_list"])) echo "<tr><td class=\"optionbox\">", WT_I18N::translate('Family list'), "</td></tr>\n";

	echo "<tr><td class=\"topbottombar\">", WT_I18N::translate('GEDCOMs to store in Sitemap:'), "</td></tr>\n";
	foreach (get_all_gedcoms() as $ged_id=>$gedcom) {
		if (isset($_POST["GEDCOM_{$ged_id}"])) echo "<tr><td class=\"optionbox\">", get_gedcom_setting($ged_id, 'title'), "</td></tr>\n";
	}

	echo "<tr><td class=\"topbottombar\">", WT_I18N::translate('The following Sitemap files have been generated and can be downloaded:'), "</td></tr>\n";
	$filecounter = 0;
	foreach (get_all_gedcoms() as $ged_id=>$gedcom) {
		if (isset($_POST["GEDCOM_{$ged_id}"])) {
			$filecounter += 1;
			$sitemapFilename = "SM_".str_ireplace(".ged",".xml",$gedcom);
			echo "<tr><td class=\"optionbox\"><a href=\"module.php?mod=sitemap&amp;mod_action=admin_index&amp;action=sendFiles&amp;index=", $ged_id, "&amp;gedcom_name=", rawurlencode($gedcom), "&filename=", $sitemapFilename;
			if (isset($_POST["welcome_page"])) echo "&welcome=true&welcome_priority=", $welcome_priority, "&welcome_update=", $welcome_update;
			if (isset($_POST["indi_recs"])) echo "&indi_rec=true&indirec_priority=", $indirec_priority, "&indirec_update=", $indirec_update;
			if (isset($_POST["indi_list"])) echo "&indi_lists=true&indilist_priority=", $indilist_priority, "&indilist_update=", $indilist_update;
			if (isset($_POST["fam_recs"])) echo "&fam_rec=true&famrec_priority=", $famrec_priority, "&famrec_update=", $famrec_update;
			if (isset($_POST["fam_list"])) echo "&fam_lists=true&famlist_priority=", $famlist_priority, "&famlist_update=", $famlist_update;
			echo "\"><b>", $sitemapFilename, "</b></a></td></tr>\n";
		}
	}
	if ($filecounter > 1) {
		echo "<tr><td class=\"optionbox\"><a href=\"module.php?mod=sitemap&amp;mod_action=admin_index&amp;action=sendIndex";
		foreach (get_all_gedcoms() as $ged_id=>$gedcom) {
			if (isset($_POST["GEDCOM_{$ged_id}"])) {
				echo "&filenames[", $ged_id, "]=", $gedcom;
			}
		}
		echo "\">SitemapIndex.xml</a></td></tr>\n";
	}
	echo "<tr><td class=\"topbottombar\">", WT_I18N::translate('Place all the files in the root of your webtrees installation.'), "</td></tr>\n";
	echo "</table>\n";
	echo "<br />\n";
}

if ($action=="") {
?>

<!-- "Help for this page" link -->
<div id="page_help"><?php echo  help_link('SITEMAP','sitemap'); ?></div>

<form method="post" enctype="multipart/form-data" id="sitemap" name="sitemap" action="module.php?mod=sitemap&amp;mod_action=admin_index">
	<input type="hidden" name="action" value="generate" />
	<table id="site_map">
		<tr>
			<th><?php echo WT_I18N::translate('GEDCOMs to store in Sitemap:'), help_link('SM_GEDCOM_SELECT','sitemap'); ?></th>
			<td colspan="3">
<?php
	foreach (get_all_gedcoms() as $ged_id=>$gedcom) {
		echo " <input type=\"checkbox\" name=\"GEDCOM_", $ged_id, "\" value=\"", $ged_id, "\" checked>", get_gedcom_setting($ged_id, 'title'), "<br />\n";
	}
?>
			</td>
		</tr>
		<tr>
			<th rowspan="6">
				<?php echo WT_I18N::translate('Selected items to store in Sitemap:'), help_link('SM_ITEM_SELECT','sitemap'); ?>
			</th>
			<th><?php echo WT_I18N::translate('Item'); ?></th>
			<th><?php echo WT_I18N::translate('Priority'); ?></th>
			<th><?php echo WT_I18N::translate('Updates'); ?></th>
		</tr>
		<tr>
			<td>
				<input type="checkbox" name="welcome_page" checked><?php echo WT_I18N::translate('Home page'); ?>
			</td>
			<td>
				<select name="welcome_priority">
					<option value="1">0.1</option>
					<option value="2">0.2</option>
					<option value="3">0.3</option>
					<option value="4">0.4</option>
					<option value="5">0.5</option>
					<option value="6">0.6</option>
					<option value="7" selected="selected">0.7</option>
					<option value="8">0.8</option>
					<option value="9">0.9</option>
				</select>
			</td>
			<td>
				<select name="welcome_update">
					<option value="always"><?php echo WT_I18N::translate('always'); ?></option>
					<option value="hourly"><?php echo WT_I18N::translate('hourly'); ?></option>
					<option value="daily"><?php echo WT_I18N::translate('daily'); ?></option>
					<option value="weekly"><?php echo WT_I18N::translate('weekly'); ?></option>
					<option value="monthly" selected="selected"><?php echo WT_I18N::translate('monthly'); ?></option>
					<option value="yearly"><?php echo WT_I18N::translate('yearly'); ?></option>
					<option value="never"><?php echo WT_I18N::translate('never'); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td><input type="checkbox" name="indi_recs" checked><?php echo WT_I18N::translate('Individual information'); ?></td>
			<td>
				<select name="indirec_priority">
					<option value="1">0.1</option>
					<option value="2">0.2</option>
					<option value="3">0.3</option>
					<option value="4">0.4</option>
					<option value="5" selected="selected">0.5</option>
					<option value="6">0.6</option>
					<option value="7">0.7</option>
					<option value="8">0.8</option>
					<option value="9">0.9</option>
				</select>
			</td>
			<td>
				<select name="indirec_update">
					<option value="always"><?php echo WT_I18N::translate('always'); ?></option>
					<option value="hourly"><?php echo WT_I18N::translate('hourly'); ?></option>
					<option value="daily"><?php echo WT_I18N::translate('daily'); ?></option>
					<option value="weekly"><?php echo WT_I18N::translate('weekly'); ?></option>
					<option value="monthly" selected="selected"><?php echo WT_I18N::translate('monthly'); ?></option>
					<option value="yearly"><?php echo WT_I18N::translate('yearly'); ?></option>
					<option value="never"><?php echo WT_I18N::translate('never'); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td><input type="checkbox" name="indi_list"><?php echo WT_I18N::translate('Individual list'); ?></td>
			<td>
				<select name="indilist_priority">
					<option value="1">0.1</option>
					<option value="2">0.2</option>
					<option value="3" selected="selected">0.3</option>
					<option value="4">0.4</option>
					<option value="5">0.5</option>
					<option value="6">0.6</option>
					<option value="7">0.7</option>
					<option value="8">0.8</option>
					<option value="9">0.9</option>
				</select>
			</td>
			<td>
				<select name="indilist_update">
					<option value="always"><?php echo WT_I18N::translate('always'); ?></option>
					<option value="hourly"><?php echo WT_I18N::translate('hourly'); ?></option>
					<option value="daily"><?php echo WT_I18N::translate('daily'); ?></option>
					<option value="weekly"><?php echo WT_I18N::translate('weekly'); ?></option>
					<option value="monthly" selected="selected"><?php echo WT_I18N::translate('monthly'); ?></option>
					<option value="yearly"><?php echo WT_I18N::translate('yearly'); ?></option>
					<option value="never"><?php echo WT_I18N::translate('never'); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td><input type="checkbox" name="fam_recs" checked><?php echo WT_I18N::translate('Family information'); ?></td>
			<td>
				<select name="famrec_priority">
					<option value="1">0.1</option>
					<option value="2">0.2</option>
					<option value="3">0.3</option>
					<option value="4">0.4</option>
					<option value="5" selected="selected">0.5</option>
					<option value="6">0.6</option>
					<option value="7">0.7</option>
					<option value="8">0.8</option>
					<option value="9">0.9</option>
				</select>
			</td>
			<td>
				<select name="famrec_update">
					<option value="always"><?php echo WT_I18N::translate('always'); ?></option>
					<option value="hourly"><?php echo WT_I18N::translate('hourly'); ?></option>
					<option value="daily"><?php echo WT_I18N::translate('daily'); ?></option>
					<option value="weekly"><?php echo WT_I18N::translate('weekly'); ?></option>
					<option value="monthly" selected="selected"><?php echo WT_I18N::translate('monthly'); ?></option>
					<option value="yearly"><?php echo WT_I18N::translate('yearly'); ?></option>
					<option value="never"><?php echo WT_I18N::translate('never'); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td><input type="checkbox" name="fam_list"><?php echo WT_I18N::translate('Family list'); ?></td>
			<td>
				<select name="famlist_priority">
					<option value="1">0.1</option>
					<option value="2">0.2</option>
					<option value="3" selected="selected">0.3</option>
					<option value="4">0.4</option>
					<option value="5">0.5</option>
					<option value="6">0.6</option>
					<option value="7">0.7</option>
					<option value="8">0.8</option>
					<option value="9">0.9</option>
				</select>
			</td>
			<td>
				<select name="famlist_update">
					<option value="always"><?php echo WT_I18N::translate('always'); ?></option>
					<option value="hourly"><?php echo WT_I18N::translate('hourly'); ?></option>
					<option value="daily"><?php echo WT_I18N::translate('daily'); ?></option>
					<option value="weekly"><?php echo WT_I18N::translate('weekly'); ?></option>
					<option value="monthly" selected="selected"><?php echo WT_I18N::translate('monthly'); ?></option>
					<option value="yearly"><?php echo WT_I18N::translate('yearly'); ?></option>
					<option value="never"><?php echo WT_I18N::translate('never'); ?></option>
				</select>
			</td>
		</tr>
	</table>
	<input id="savebutton" type="submit" value="<?php echo WT_I18N::translate('Generate'); ?>" />
</form>

<?php
}

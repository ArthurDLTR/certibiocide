<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       certibiocide/certibiocideindex.php
 *	\ingroup    certibiocide
 *	\brief      Home page of certibiocide top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("certibiocide@certibiocide"));

$action = GETPOST('action', 'aZ09');

$starting_date = "";
$ending_date = "";
$valuesForCSV = array();

$max = 5;
$now = dol_now();

// Security check - Protection if external user
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}



// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//if (!isModEnabled('certibiocide')) {
//	accessforbidden('Module not enabled');
//}
//if (! $user->hasRight('certibiocide', 'myobject', 'read')) {
//	accessforbidden();
//}
//restrictedArea($user, 'certibiocide', 0, 'certibiocide_myobject', 'myobject', '', 'rowid');
//if (empty($user->admin)) {
//	accessforbidden('Must be admin');
//}

/*
 * Functions
 */
function toCSV($array){
	header("Content-Description: File Transfer");
	header("Content-Type: text/csv; charset=utf-8");
    header('Content-Disposition: attachment; filename="certi.csv";');
	header("Pragma: no-cache");
	header('Expires: 0');
    // Function to translate an array into a .csv file
    $file = fopen("php://output", "w");
    foreach($array as $line){
        $val = explode(",", $line);
        fputcsv($file, $val);
    }
	readfile($file);
    fclose($file);
    exit;
}

/*
 * Actions
 */
if(GETPOST('starting_date', 'alpha')){
	$starting_date = GETPOST('starting_date', 'alpha');
}

if(GETPOST('ending_date', 'alpha')){
	$ending_date = GETPOST('ending_date', 'alpha');
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("CertibiocideArea"), '', '', 0, 0, '', '', '', 'mod-certibiocide page-index');

print load_fiche_titre($langs->trans("CertibiocideArea"), '', 'certibiocide.png@certibiocide');

print '<form method="POST" id="searchFormList" action="'. $_SERVER["PHP_SELF"] . ' ">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<label for="starting_date">' . $langs->trans('START_DATE') . '</label>';
print '<input type="date" id="starting_date" name="starting_date" value="' . $starting_date . '">';
print '<br>';
print '<label for="ending_date">' . $langs->trans('END_DATE') . '</label>';
print '<input type="date" id="ending_date" name="ending_date" value="' . $ending_date . '">';
print '<br >';
print '<input type="submit" value="'.$langs->trans("REFRESH").'">';
print '<input type="submit" value="'.$langs->trans("CSV").'" name="CSVButton">';
print '</form>';

// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (isModEnabled('certibiocide') && $user->hasRight('certibiocide', 'myobject', 'read')) {
	$langs->load("orders");

	// SQL Request with table joins and fields selection
	$sql = "SELECT s.nom, s_f.certibiocide_attr_thirdparty, p.label, p.ref, p_f.certibiocide_attr_product, SUM(c_d.qty) AS qty FROM dolibarr.llx_commande as c 
		JOIN dolibarr.llx_commandedet c_d ON c.rowid = c_d.fk_commande
		JOIN dolibarr.llx_product AS p on p.rowid = c_d.fk_product
		JOIN dolibarr.llx_product_extrafields AS p_f on p_f.fk_object = p.rowid
		JOIN dolibarr.llx_societe AS s on s.rowid = c.fk_soc
		JOIN dolibarr.llx_societe_extrafields AS s_f on s_f.fk_object = s.rowid";
	// Conditions to extract only the product concerned by Certibiode
	$conditions = " WHERE p_f.certibiocide_attr_product like 'TP%'";
	// Get the begin and the end of the period which the user want to see the sales of certibiocide products
	if($starting_date){
		$conditions.= " && c.date_commande >= '" . $starting_date . "'";
	}
	if ($ending_date){
		$conditions.= " && c.date_commande <= '". $ending_date . "'";
	}
	$sql.= $conditions;
	$sql.= " GROUP BY p.rowid, s.rowid";

	if ($socid)	$sql.= " AND c.fk_soc = ".((int) $socid);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("THIRDPARTY_NAME").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th>';
		print '<th>'.$langs->trans("CERTIBIOCIDE_CERTIFICATE").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th>';
		print '<th>'.$langs->trans("PRODUCT_REF").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th>';
		print '<th>'.$langs->trans("PRODUCT_LABEL").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th>';
		print '<th>'.$langs->trans("CERTIBIOCIDE").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th>';
		print '<th>'.$langs->trans("QTY").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th>';

		print '</tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);

				$valuesForCSV[$i] = $obj->nom . "," . $obj->certibiocide_attr_thirdparty . "," . $obj->ref . "," . $obj->label . "," . $obj->certibiocide_attr_product . "," . $obj->qty;
				
				print '<tr class="oddeven">';
				print '<td class="nowrap">' . $obj->nom . '</td>';
				print '<td class="nowrap">' . $obj->certibiocide_attr_thirdparty . '</td>';
				print '<td class="nowrap">' . $obj->ref . '</td>';
				print '<td class="nowrap">' . $obj->label . '</>';
				print '<td class="nowrap">'. $obj->certibiocide_attr_product .'</td>';
				print '<td class="nowrap">' . $obj->qty . '</td>';
				
				print '</tr>';
				$i++;
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

// CSV button check to make the CSV file when the button is clicked
if (GETPOSTISSET('CSVButton', 'bool')){
	toCSV($valuesForCSV);
}

//END MODULEBUILDER DRAFT MYOBJECT


print '</div><div class="fichetwothirdright">';


$NBMAX = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');

// BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
/*
if (isModEnabled('certibiocide') && $user->hasRight('certibiocide', 'read')) {

	$resql = $db->query("SELECT * FROM llx_product");
	print $resql;
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("BoxTitleLatestModifiedMyObjects", $max);
		print '</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$myobjectstatic->id=$objp->rowid;
				$myobjectstatic->ref=$objp->ref;
				$myobjectstatic->label=$objp->label;
				$myobjectstatic->status = $objp->status;

				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$myobjectstatic->getNomUrl(1).'</td>';
				print '<td class="right nowrap">';
				print "</td>";
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table><br>";
	}
}
*/


print '</div></div>';




// End of page
llxFooter();
$db->close();

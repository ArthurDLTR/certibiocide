<?php
/* Copyright (C) 2024 Lenoble Arthur <arthurl52100@gmail.com>
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
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/exports/class/export.class.php';

// Load translation files required by the page
$langs->loadLangs(array("certibiocide@certibiocide"));

$action = GETPOST('action', 'aZ09');

$starting_date = "";
$ending_date = "";
$valuesForCSV = array();
$datatoexport = "s.nom, s_f.certibiocide_attr_thirdparty, p.label, p.ref, p_f.certibiocide_attr_product, SUM(c_d.qty)";
// Creation of an export 
$export = new Export($db);


$max = 5;
$now = dol_now();

// Security check - Protection if external user
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// SQL Request with table joins and fields selection
$sql = "SELECT s.rowid as soc_id, s.nom as soc_nom, s.logo as soc_logo, s.status as soc_status, s_f.certibiocide_attr_thirdparty, p.rowid as prod_id, p.label as prod_label, p.ref as prod_ref, p.description as prod_descr, p.label as prod_label, p.tobuy as prod_tobuy, p.tosell as prod_tosell, p.entity as prod_entity, p_f.certibiocide_attr_product, SUM(c_d.qty) AS qty FROM ".MAIN_DB_PREFIX."commande as c 
LEFT JOIN ".MAIN_DB_PREFIX."commandedet c_d ON c.rowid = c_d.fk_commande
LEFT JOIN ".MAIN_DB_PREFIX."product AS p on p.rowid = c_d.fk_product
LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields AS p_f on p_f.fk_object = p.rowid
LEFT JOIN ".MAIN_DB_PREFIX."societe AS s on s.rowid = c.fk_soc
LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields AS s_f on s_f.fk_object = s.rowid";
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


// Getting the total number of rows in the request
$resql = $db->query($sql);
$totalnumofrows = $db->num_rows($resql);
$db->free($resql);

// Variables to define the limits of the request and the number of rows printed
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$page = GETPOSTINT('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if(empty($page) || $page < 0){
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
	
if ($offset > $totalnumofrows) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
	$page = 0;
	$offset = 0;
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
 * Actions
 */
if(GETPOST('starting_date', 'alpha')){
	$starting_date = GETPOST('starting_date', 'alpha');
}

if(GETPOST('ending_date', 'alpha')){
	$ending_date = GETPOST('ending_date', 'alpha');
}

// CSV button check to make the CSV file when the button is clicked
if (GETPOSTISSET('CSVButton', 'bool')){
	// SQL Request for the function call
	$sql = "SELECT s.nom, s_f.certibiocide_attr_thirdparty, p.label, p.ref, p_f.certibiocide_attr_product, SUM(c_d.qty) AS qty FROM ".MAIN_DB_PREFIX."commande as c 
		LEFT JOIN ".MAIN_DB_PREFIX."commandedet c_d ON c.rowid = c_d.fk_commande
		LEFT JOIN ".MAIN_DB_PREFIX."product AS p on p.rowid = c_d.fk_product
		LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields AS p_f on p_f.fk_object = p.rowid
		LEFT JOIN ".MAIN_DB_PREFIX."societe AS s on s.rowid = c.fk_soc
		LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields AS s_f on s_f.fk_object = s.rowid";
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

	$resql = $db->query($sql);
	if ($resql){
		$num = $db->num_rows($resql);
		if($num>0){
			$i = 0;
			while ($i < $num){
				$obj = $db->fetch_object($resql);
				$valuesForCSV[$i] = $obj->nom . "," . $obj->certibiocide_attr_thirdparty . "," . $obj->ref . "," . $obj->label . "," . $obj->certibiocide_attr_product . "," . $obj->qty;
				$i++;
			}
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
	}

	toCSV($valuesForCSV);
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$soc = new Societe($db);
$prod = new Product($db);



// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (isModEnabled('certibiocide') && $user->hasRight('certibiocide', 'myobject', 'read')) {
	$langs->load("orders");

	// SQL Request with table joins and fields selection
	$sql = "SELECT s.rowid as soc_id, s.nom as soc_nom, s.logo as soc_logo, s.status as soc_status, s_f.certibiocide_attr_thirdparty, p.rowid as prod_id, p.label as prod_label, p.ref as prod_ref, p.description as prod_descr, p.label as prod_label, p.tobuy as prod_tobuy, p.tosell as prod_tosell, p.entity as prod_entity, p_f.certibiocide_attr_product, SUM(c_d.qty) AS qty FROM ".MAIN_DB_PREFIX."commande as c 
		LEFT JOIN ".MAIN_DB_PREFIX."commandedet c_d ON c.rowid = c_d.fk_commande
		LEFT JOIN ".MAIN_DB_PREFIX."product AS p on p.rowid = c_d.fk_product
		LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields AS p_f on p_f.fk_object = p.rowid
		LEFT JOIN ".MAIN_DB_PREFIX."societe AS s on s.rowid = c.fk_soc
		LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields AS s_f on s_f.fk_object = s.rowid";

	// Conditions to extract only the product concerned by Certibiode
	$conditions = " WHERE p_f.certibiocide_attr_product LIKE 'TP%'";
	// Get the begin and the end of the period which the user want to see the sales of certibiocide products
	if($starting_date){
		$conditions.= " && c.date_commande >= '" . $starting_date . "'";
	}
	if ($ending_date){
		$conditions.= " && c.date_commande <= '". $ending_date . "'";
	}
	
	$sql.= $conditions;
	$sql.= " GROUP BY p.rowid, s.rowid";
	
	// Execute request with limits
	if($limit){
		$sql.=$db->plimit($limit+1, $offset);
	}
	
	if ($socid)	$sql.= " AND c.fk_soc = ".((int) $socid);


	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		
		$imax = ($limit ? min($num, $limit) : $num);

		//Printing the content of the table if resql worked
		llxHeader("", $langs->trans("CertibiocideArea"), '', '', 0, 0, '', '', '', 'mod-certibiocide page-index');

		print load_fiche_titre($langs->trans("CertibiocideArea"), '', 'certibiocide.png@certibiocide');
		//print '<p> Num, limit, offset, totalnbrows, page:'.$num.','.$limit.','.$offset.','.$totalnumofrows.','.$page.'</p>';
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
		// Affichage de la barre pour sélectionner la page à afficher
		print_barre_liste($langs->trans("BIOCIDE_PRODUCTS"), $page, $_SERVER["PHP_SELF"], '', '', '', '', $num, $totalnumofrows, 'product', 0, '', '', $limit, 0, 0, 1);
		
		print '</form>';


		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("THIRDPARTY_NAME").($imax?'<span class="badge marginleftonlyshort">'.$imax.'</span>':'').'</th>';
		print '<th>'.$langs->trans("CERTIBIOCIDE_CERTIFICATE").($imax?'<span class="badge marginleftonlyshort">'.$imax.'</span>':'').'</th>';
		print '<th>'.$langs->trans("PRODUCT_REF").($imax?'<span class="badge marginleftonlyshort">'.$imax.'</span>':'').'</th>';
		print '<th>'.$langs->trans("PRODUCT_LABEL").($imax?'<span class="badge marginleftonlyshort">'.$imax.'</span>':'').'</th>';
		print '<th>'.$langs->trans("CERTIBIOCIDE").($imax?'<span class="badge marginleftonlyshort">'.$imax.'</span>':'').'</th>';
		print '<th>'.$langs->trans("QTY").($imax?'<span class="badge marginleftonlyshort">'.$imax.'</span>':'').'</th>';

		print '</tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $imax)
			{

				$obj = $db->fetch_object($resql);
				
				$soc->id = $obj->soc_id;
				$soc->name = $obj->soc_nom;
				$soc->logo = $obj->soc_logo;
				$soc->status = $obj->soc_status;

				$prod->id = $obj->prod_id;
				$prod->ref = $obj->prod_ref;
				$prod->description = $obj->prod_descr;
				$prod->label = $obj->prod_label;
				$prod->status_buy = $obj->prod_tobuy;
				$prod->status = $obj->prod_tosell;
				$prod->entity = $obj->prod_entity;

				print '<tr class="oddeven">';
				print '<td class="tdoverflowmax200" data-ker="ref">' . $soc->getNomUrl(1, '', 100, 0, 1, 1) . '</td>';
				print '<td class="nowrap">' . $obj->certibiocide_attr_thirdparty . '</td>';
				print '<td class="nowrap">' . $prod->getNomUrl(1) . '</td>';
				print '<td class="tdoverflowmax200">' . $obj->prod_label . '</td>';
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


//END MODULEBUILDER DRAFT MYOBJECT


print '</div><div class="fichetwothirdright">';


$NBMAX = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');


print '</div></div>';


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
    fclose($file);
	
    exit;
	

}

// End of page
llxFooter();
$db->close();
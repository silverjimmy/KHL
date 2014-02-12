<?php
/* $Id$*/

include('includes/session.inc');

$title = _('Stock Category Maintenance');

include('includes/header.inc');

if (isset($_GET['SelectedCategory'])){
	$SelectedCategory = strtoupper($_GET['SelectedCategory']);
} else if (isset($_POST['SelectedCategory'])){
	$SelectedCategory = strtoupper($_POST['SelectedCategory']);
}

if (isset($SelectedCategory)) {
	echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" >' . _('Show All Stock Categories') . '</a></div>';
}

echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/supplier.png" title="' . _('Inventory Adjustment') . '" alt="" />' . ' ' . $title . '</p>';

if (isset($_GET['DeleteProperty'])){

	$ErrMsg = _('Could not delete the property') . ' ' . $_GET['DeleteProperty'] . ' ' . _('because');
	$sql = "DELETE FROM stockitemproperties WHERE stkcatpropid='" . $_GET['DeleteProperty'] . "'";
	$result = DB_query($sql,$db,$ErrMsg);
	$sql = "DELETE FROM stockcatproperties WHERE stkcatpropid='" . $_GET['DeleteProperty'] . "'";
	$result = DB_query($sql,$db,$ErrMsg);
	prnMsg(_('Deleted the property') . ' ' . $_GET['DeleteProperty'],'success');
}

if (isset($_POST['UpdateProperties'])) {
	if ($_POST['PropertyCounter']==0 and $_POST['PropLabel0']!='') {
		$_POST['PropertyCounter']=0;
	}

	for ($i=0;$i<=$_POST['PropertyCounter'];$i++){

		if (isset($_POST['PropReqSO' .$i]) and $_POST['PropReqSO' .$i] == true){
			$_POST['PropReqSO' .$i] =1;
		} else {
			$_POST['PropReqSO' .$i] =0;
		}
		if (isset($_POST['PropNumeric' .$i]) and $_POST['PropNumeric' .$i] == true){
			$_POST['PropNumeric' .$i] =1;
		} else {
			$_POST['PropNumeric' .$i] =0;
		}
		if ($_POST['PropID' .$i] =='NewProperty' AND mb_strlen($_POST['PropLabel'.$i])>0){
			$sql = "INSERT INTO stockcatproperties (categoryid,
													label,
													controltype,
													defaultvalue,
													minimumvalue,
													maximumvalue,
													numericvalue,
													reqatsalesorder)
											VALUES ('" . $SelectedCategory . "',
													'" . $_POST['PropLabel' . $i] . "',
													" . $_POST['PropControlType' . $i] . ",
													'" . $_POST['PropDefault' .$i] . "',
													'" . filter_number_input($_POST['PropMinimum' .$i]) . "',
													'" . filter_number_input($_POST['PropMaximum' .$i]) . "',
													'" . $_POST['PropNumeric' .$i] . "',
													" . $_POST['PropReqSO' .$i] . ')';
			$ErrMsg = _('Could not insert a new category property for') . $_POST['PropLabel' . $i];
			$result = DB_query($sql,$db,$ErrMsg);
		} elseif ($_POST['PropID' .$i] !='NewProperty') { //we could be amending existing properties
			$sql = "UPDATE stockcatproperties SET label ='" . $_POST['PropLabel' . $i] . "',
											  controltype = " . $_POST['PropControlType' . $i] . ",
											  defaultvalue = '"	. $_POST['PropDefault' .$i] . "',
											  minimumvalue = '" . filter_number_input($_POST['PropMinimum' .$i]) . "',
											  maximumvalue = '" . filter_number_input($_POST['PropMaximum' .$i]) . "',
											  numericvalue = '" . $_POST['PropNumeric' .$i] . "',
											  reqatsalesorder = " . $_POST['PropReqSO' .$i] . "
										WHERE stkcatpropid =" . $_POST['PropID' .$i];
			$ErrMsg = _('Updated the stock category property for') . ' ' . $_POST['PropLabel' . $i];
			$result = DB_query($sql,$db,$ErrMsg);
		}
	}
}

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	$_POST['CategoryID'] = strtoupper($_POST['CategoryID']);

	if (strlen($_POST['CategoryID']) > 6) {
		$InputError = 1;
		prnMsg(_('The Inventory Category code must be six characters or less long'),'error');
	} elseif (strlen($_POST['CategoryID'])==0) {
		$InputError = 1;
		prnMsg(_('The Inventory category code must be at least 1 character but less than six characters long'),'error');
	} elseif (strlen($_POST['CategoryDescription']) >20) {
		$InputError = 1;
		prnMsg(_('The Sales category description must be twenty characters or less long'),'error');
	} elseif ($_POST['StockType'] !='S'
			AND $_POST['StockType'] !='R'
			AND $_POST['StockType'] !='P'
			AND $_POST['StockType'] !='T'
			AND $_POST['StockType'] !='X') {
//		$InputError = 1;
//		prnMsg(_('The stock type selected must be one of') . ' "D" - ' . _('Dummy item') . ', "L" - ' . _('Labour stock item') . ', "F" - ' . _('Finished product') . ' ' . _('or') . ' "M" - ' . _('Raw Materials'),'error');
	}
	for ($i=0;$i<=$_POST['PropertyCounter'];$i++){
		if (isset($_POST['PropNumeric' .$i]) and $_POST['PropNumeric' .$i] == true){
			if (!is_numeric(filter_number_input($_POST['PropMinimum' .$i]))){
				$InputError = 1;
				prnMsg(_('The minimum value is expected to be a numeric value'),'error');
			}
			if (!is_numeric(filter_number_input($_POST['PropMaximum' .$i]))){
				$InputError = 1;
				prnMsg(_('The maximum value is expected to be a numeric value'),'error');
			}
		}
	} //check the properties are sensible

	if (isset($SelectedCategory) AND $InputError !=1) {

		/*SelectedCategory could also exist if submit had not been clicked this code
		would not run in this case cos submit is false of course  see the
		delete code below*/

		$sql = "UPDATE stockcategory SET stocktype = '" . $_POST['StockType'] . "',
									 categorydescription = '" . $_POST['CategoryDescription'] . "',
									 stockact = " . $_POST['StockAct'] . ",
									 adjglact = " . $_POST['AdjGLAct'] . ",
									 issueglact = " . $_POST['IssueGLAct'] . ",
									 purchpricevaract = " . $_POST['PurchPriceVarAct'] . ",
									 materialuseagevarac = " . $_POST['MaterialUseageVarAc'] . ",
									 wipact = " . $_POST['WIPAct'] . "
								WHERE categoryid = '" . $SelectedCategory . "'";
		$ErrMsg = _('Could not update the stock category') . $_POST['CategoryDescription'] . _('because');
		$result = DB_query($sql,$db,$ErrMsg);

		if ($_POST['PropertyCounter']==0 and $_POST['PropLabel0']!='') {
			$_POST['PropertyCounter']=0;
		}

		for ($i=0;$i<=$_POST['PropertyCounter'];$i++){

			if (isset($_POST['PropReqSO' .$i]) and $_POST['PropReqSO' .$i] == true){
					$_POST['PropReqSO' .$i] =1;
			} else {
					$_POST['PropReqSO' .$i] =0;
			}
			if (isset($_POST['PropNumeric' .$i]) and $_POST['PropNumeric' .$i] == true){
					$_POST['PropNumeric' .$i] =1;
			} else {
					$_POST['PropNumeric' .$i] =0;
			}
			if ($_POST['PropID' .$i] =='NewProperty' AND mb_strlen($_POST['PropLabel'.$i])>0){
				$sql = "INSERT INTO stockcatproperties (categoryid,
														label,
														controltype,
														defaultvalue,
														minimumvalue,
														maximumvalue,
														numericvalue,
														reqatsalesorder)
											VALUES ('" . $SelectedCategory . "',
													'" . $_POST['PropLabel' . $i] . "',
													" . $_POST['PropControlType' . $i] . ",
													'" . $_POST['PropDefault' .$i] . "',
													'" . filter_number_input($_POST['PropMinimum' .$i]) . "',
													'" . filter_number_input($_POST['PropMaximum' .$i]) . "',
													'" . $_POST['PropNumeric' .$i] . "',
													" . $_POST['PropReqSO' .$i] . ')';
				$ErrMsg = _('Could not insert a new category property for') . $_POST['PropLabel' . $i];
				$result = DB_query($sql,$db,$ErrMsg);
			} elseif ($_POST['PropID' .$i] !='NewProperty') { //we could be amending existing properties
				$sql = "UPDATE stockcatproperties SET label ='" . $_POST['PropLabel' . $i] . "',
													  controltype = " . $_POST['PropControlType' . $i] . ",
													  defaultvalue = '"	. $_POST['PropDefault' .$i] . "',
													  minimumvalue = '" . filter_number_input($_POST['PropMinimum' .$i]) . "',
													  maximumvalue = '" . filter_number_input($_POST['PropMaximum' .$i]) . "',
													  numericvalue = '" . $_POST['PropNumeric' .$i] . "',
													  reqatsalesorder = " . $_POST['PropReqSO' .$i] . "
												WHERE stkcatpropid =" . $_POST['PropID' .$i];
				$ErrMsg = _('Updated the stock category property for') . ' ' . $_POST['PropLabel' . $i];
				$result = DB_query($sql,$db,$ErrMsg);
			}

		} //end of loop round properties

		prnMsg(_('Updated the stock category record for') . ' ' . stripslashes($_POST['CategoryDescription']),'success');
		unset($SelectedCategory);
		unset($_POST['CategoryID']);
		unset($_POST['StockType']);
		unset($_POST['CategoryDescription']);
		unset($_POST['StockAct']);
		unset($_POST['AdjGLAct']);
		unset($_POST['IssueGLAct']);
		unset($_POST['PurchPriceVarAct']);
		unset($_POST['MaterialUseageVarAc']);
		unset($_POST['WIPAct']);
	} elseif ($InputError !=1) {

	/*Selected category is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new stock category form */

		$sql = "INSERT INTO stockcategory (categoryid,
											stocktype,
											categorydescription,
											stockact,
											adjglact,
											issueglact,
											purchpricevaract,
											materialuseagevarac,
											wipact)
										VALUES (
											'" . $_POST['CategoryID'] . "',
											'" . $_POST['StockType'] . "',
											'" . $_POST['CategoryDescription'] . "',
											'" . $_POST['StockAct'] . "',
											'" . $_POST['AdjGLAct'] . "',
											'" . $_POST['IssueGLAct'] . "',
											'" . $_POST['PurchPriceVarAct'] . "',
											'" . $_POST['MaterialUseageVarAc'] . "',
											'" . $_POST['WIPAct'] . "')";
		$ErrMsg = _('Could not insert the new stock category') . $_POST['CategoryDescription'] . _('because');
		$result = DB_query($sql,$db,$ErrMsg);
		prnMsg(_('A new stock category record has been added for') . ' ' . $_POST['CategoryDescription'],'success');

	}
	//run the SQL from either of the above possibilites

	unset($_POST['StockType']);
	unset($_POST['CategoryDescription']);
	unset($_POST['StockAct']);
	unset($_POST['AdjGLAct']);
	unset($_POST['IssueGLAct']);
	unset($_POST['PurchPriceVarAct']);
	unset($_POST['MaterialUseageVarAc']);
	unset($_POST['WIPAct']);


} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN 'StockMaster'

	$sql= "SELECT stockid FROM stockmaster WHERE stockmaster.categoryid='" . $SelectedCategory . "'";
	$result = DB_query($sql,$db);

	if (DB_num_rows($result)>0) {
		prnMsg(_('Cannot delete this stock category because stock items have been created using this stock category') .
			'<br /> ' . _('There are') . ' ' . $myrow[0] . ' ' . _('items referring to this stock category code'),'warn');

	} else {
		$sql = "SELECT stkcat FROM salesglpostings WHERE stkcat='" . $SelectedCategory . "'";
		$result = DB_query($sql,$db);

		if (DB_num_rows($result)>0) {
			prnMsg(_('Cannot delete this stock category because it is used by the sales') . ' - ' . _('GL posting interface') . '. ' . _('Delete any records in the Sales GL Interface set up using this stock category first'),'warn');
		} else {
			$sql = "SELECT stkcat FROM cogsglpostings WHERE stkcat='" . $SelectedCategory . "'";
			$result = DB_query($sql,$db);

			if (DB_num_rows($result)>0) {
				prnMsg(_('Cannot delete this stock category because it is used by the cost of sales') . ' - ' . _('GL posting interface') . '. ' . _('Delete any records in the Cost of Sales GL Interface set up using this stock category first'),'warn');
			} else {
				$sql="DELETE FROM stockcategory WHERE categoryid='" . $SelectedCategory . "'";
				$result = DB_query($sql,$db);
				prnMsg(_('The stock category') . ' ' . $SelectedCategory . ' ' . _('has been deleted') . ' !','success');
				unset ($SelectedCategory);
			}
		}
	} //end if stock category used in debtor transactions
}

if (!isset($SelectedCategory)) {

/* It could still be the second time the page has been run and a record has been selected for modification - SelectedCategory will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
then none of the above are true and the list of stock categorys will be displayed with
links to delete or edit each. These will call the same page again and allow update/input
or deletion of the records*/

	$sql = "SELECT categoryid,
					categorydescription,
					stocktype,
					stockact,
					adjglact,
					issueglact,
					purchpricevaract,
					materialuseagevarac,
					wipact
				FROM stockcategory";
	$result = DB_query($sql,$db);

	echo '<br />
		<table class="selection">
			<tr>
				<th>' . _('Cat Code') . '</th>
				<th>' . _('Description') . '</th>
				<th>' . _('Type') . '</th>
				<th>' . _('Stock GL') . '</th>
				<th>' . _('Adjts GL') . '</th>
				<th>' . _('Issues GL') . '</th>
				<th>' . _('Price Var GL') . '</th>
				<th>' . _('Usage Var GL') . '</th>
				<th>' . _('WIP GL') . '</th>
			</tr>';

	$k=0; //row colour counter

	while ($myrow = DB_fetch_array($result)) {
		if ($k==1){
			echo '<tr class="EvenTableRows">';
			$k=0;
		} else {
			echo '<tr class="OddTableRows">';
			$k=1;
		}
		printf('<td>%s</td>
				<td>%s</td>
				<td>%s</td>
				<td class="number">%s</td>
				<td class="number">%s</td>
				<td class="number">%s</td>
				<td class="number">%s</td>
				<td class="number">%s</td>
				<td class="number">%s</td>
				<td><a href="%sSelectedCategory=%s">' . _('Edit') . '</td>
				<td><a href="%sSelectedCategory=%s&delete=yes" onclick="return confirm("' . _('Are you sure you wish to delete this stock category? Additional checks will be performed before actual deletion to ensure data integrity is not compromised.') . '");">' . _('Delete') . '</td>
			</tr>',
				$myrow['categoryid'],
				$myrow['categorydescription'],
				$myrow['stocktype'],
				$myrow['stockact'],
				$myrow['adjglact'],
				$myrow['issueglact'],
				$myrow['purchpricevaract'],
				$myrow['materialuseagevarac'],
				$myrow['wipact'],
				htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?',
				$myrow['categoryid'],
				htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?',
				$myrow['categoryid']);
	}
	//END WHILE LIST LOOP
	echo '</table>';
}

//end of ifs and buts!

echo '<form name="CategoryForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (isset($SelectedCategory)) {
	//editing an existing stock category
	if (!isset($_POST['UpdateTypes'])) {
		$sql = "SELECT categoryid,
						stocktype,
						categorydescription,
						stockact,
						adjglact,
						issueglact,
						purchpricevaract,
						materialuseagevarac,
						wipact
					FROM stockcategory
					WHERE categoryid='" . $SelectedCategory . "'";

		$result = DB_query($sql, $db);
		$myrow = DB_fetch_array($result);

		$_POST['CategoryID'] = $myrow['categoryid'];
		$_POST['StockType']  = $myrow['stocktype'];
		$_POST['CategoryDescription']  = $myrow['categorydescription'];
		$_POST['StockAct']  = $myrow['stockact'];
		$_POST['AdjGLAct']  = $myrow['adjglact'];
		$_POST['IssueGLAct']  = $myrow['issueglact'];
		$_POST['PurchPriceVarAct']  = $myrow['purchpricevaract'];
		$_POST['MaterialUseageVarAc']  = $myrow['materialuseagevarac'];
		$_POST['WIPAct']  = $myrow['wipact'];
	}
	echo '<input type="hidden" name="SelectedCategory" value="' . $SelectedCategory . '" />';
	echo '<input type="hidden" name="CategoryID" value="' . $_POST['CategoryID'] . '" />';
	echo '<table class="selection">
			<tr>
				<th class="header" colspan="2">' . _('Edit Stock Category Details') . '</th>
			</tr>
			<tr>
				<td>' . _('Category Code') . ':</td>
				<td>' . $_POST['CategoryID'] . '</td>
			</tr>';

} else { //end of if $SelectedCategory only do the else when a new record is being entered
	if (!isset($_POST['CategoryID'])) {
		$_POST['CategoryID'] = '';
	}
	echo '<table class="selection">
			<tr>
				<th class="header" colspan="2">' . _('New Stock Category Details') . '</th>
			</tr>
			<tr>
				<td>' . _('Category Code') . ':</td>
				<td><input type="text" name="CategoryID" size="7" maxlength="6" value="' . $_POST['CategoryID'] . '" /></td>
			</tr>';
}

//SQL to poulate account selection boxes
$sql = "SELECT accountcode,
				accountname
			FROM chartmaster,
				accountgroups
			WHERE chartmaster.group_=accountgroups.groupname
				AND accountgroups.pandl=0
			ORDER BY accountcode";

$BSAccountsResult = DB_query($sql,$db);

$sql = "SELECT accountcode,
				accountname
			FROM chartmaster,
				accountgroups
			WHERE chartmaster.group_=accountgroups.groupname
				AND accountgroups.pandl!=0
			ORDER BY accountcode";

$PnLAccountsResult = DB_query($sql,$db);

if (!isset($_POST['CategoryDescription'])) {
	$_POST['CategoryDescription'] = '';
}

echo '<tr><td>' . _('Category Description') . ':</td>
			<td><input type="text" name="CategoryDescription" size="22" maxlength="20" value="' . $_POST['CategoryDescription'] . '" /></td></tr>';


echo '<tr><td>' . _('Stock Type') . ':</td>';

$StockTypes=StockTypes();
echo '<td><select name="StockType" >';
echo '<option value=""></option>';
foreach ($StockTypes as $Type=>$Name) {
	if (isset($_POST['StockType']) and $_POST['StockType']==$Type) {
		echo '<option selected value="'.$Type.'">' . $Name . '</option>';
	} else {
		echo '<option value="'.$Type.'">' . $Name . '</option>';
	}
}
echo '</select></td>';

echo '</tr>';

echo '<input type="submit" name="UpdateItems" style="visibility:hidden;width:1px" value="Not Seen" />';
if (isset($_POST['StockType']) and $_POST['StockType']=='L') {
	$Result = $PnLAccountsResult;
	echo '<tr><td>' . _('Recovery GL Code');
} else {
	$Result = $BSAccountsResult;
	echo '<tr><td>' . _('Stock GL Code');
}
echo ':</td><td><select name="StockAct">';

while ($myrow = DB_fetch_array($Result)){

	if (isset($_POST['StockAct']) and $myrow['accountcode']==$_POST['StockAct']) {
		echo '<option selected="selected" value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . ' ('.$myrow['accountcode'].')' . '</option>';
	} else {
		echo '<option value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . ' ('.$myrow['accountcode'].')' . '</option>';
	}
} //end while loop
DB_data_seek($PnLAccountsResult,0);
DB_data_seek($BSAccountsResult,0);
echo '</select></td></tr>';

echo '<tr><td>' . _('WIP GL Code') . ':</td><td><select name="WIPAct">';

while ($myrow = DB_fetch_array($BSAccountsResult)) {

	if (isset($_POST['WIPAct']) and $myrow['accountcode']==$_POST['WIPAct']) {
		echo '<option selected="selected" value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . ' ('.$myrow['accountcode'].')' . '</option>';
	} else {
		echo '<option value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . ' ('.$myrow['accountcode'].')' . '</option>';
	}

} //end while loop
echo '</select></td></tr>';
DB_data_seek($BSAccountsResult,0);

echo '<tr><td>' . _('Stock Adjustments GL Code') . ':</td>
			<td><select name="AdjGLAct">';

while ($myrow = DB_fetch_array($PnLAccountsResult)) {
	if (isset($_POST['AdjGLAct']) and $myrow['accountcode']==$_POST['AdjGLAct']) {
		echo '<option selected="selected" value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . ' ('.$myrow['accountcode'].')' . '</option>';
	} else {
		echo '<option value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . ' ('.$myrow['accountcode'].')' . '</option>';
	}

} //end while loop
DB_data_seek($PnLAccountsResult,0);
echo '</select></td></tr>';

echo '<tr>
		<td>' . _('Internal Stock Issues GL Code') . ':</td>
		<td><select name="IssueGLAct">';

while ($myrow = DB_fetch_array($PnLAccountsResult)) {
	if (isset($_POST['IssueGLAct']) and $myrow['accountcode']==$_POST['IssueGLAct']) {
		echo '<option selected="selected" value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . ' ('.$myrow['accountcode'].')' . '</option>';
	} else {
		echo '<option value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . ' ('.$myrow['accountcode'].')' . '</option>';
	}

} //end while loop
DB_data_seek($PnLAccountsResult,0);
echo '</select></td></tr>';

echo '<tr><td>' . _('Price Variance GL Code') . ':</td>
			<td><select name="PurchPriceVarAct">';

while ($myrow = DB_fetch_array($PnLAccountsResult)) {
	if (isset($_POST['PurchPriceVarAct']) and $myrow['accountcode']==$_POST['PurchPriceVarAct']) {
		echo '<option selected="selected" value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . ' ('.$myrow['accountcode'].')' . '</option>';
	} else {
		echo '<option value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . ' ('.$myrow['accountcode'].')' . '</option>';
	}

} //end while loop
DB_data_seek($PnLAccountsResult,0);

echo '</select></td>
		</tr>
		<tr>
			<td>';
if (isset($_POST['StockType']) and $_POST['StockType']=='L') {
	echo  _('Labour Efficiency Variance GL Code');
} else {
	echo  _('Usage Variance GL Code');
}
echo ':</td>
		<td><select name="MaterialUseageVarAc">';

while ($myrow = DB_fetch_array($PnLAccountsResult)) {
	if (isset($_POST['MaterialUseageVarAc']) and $myrow['accountcode']==$_POST['MaterialUseageVarAc']) {
		echo '<option selected="selected" value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . ' ('.$myrow['accountcode'].')' . '</option>';
	} else {
		echo '<option value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . ' ('.$myrow['accountcode'].')' . '</option>';
	}

} //end while loop
DB_free_result($PnLAccountsResult);

echo '</select></td>
		</tr>
		</table>';

if (isset($SelectedCategory)) {
	//editing an existing stock category

	$sql = "SELECT stkcatpropid,
					label,
					controltype,
					defaultvalue,
					numericvalue,
					reqatsalesorder,
					minimumvalue,
					maximumvalue
			   FROM stockcatproperties
			   WHERE categoryid='" . $SelectedCategory . "'
			   ORDER BY stkcatpropid";

	$result = DB_query($sql, $db);

/*		echo '<br />Number of rows returned by the sql = ' . DB_num_rows($result) .
			'<br />The SQL was:<br />' . $sql;
*/
	echo '<br />
			<table class="selection">
				<tr>
					<th>' . _('Property Label') . '</th>
					<th>' . _('Control Type') . '</th>
					<th>' . _('Default Value') . '</th>
					<th>' . _('Numeric Value') . '</th>
					<th>' . _('Minimum Value') . '</th>
					<th>' . _('Maximum Value') . '</th>
					<th>' . _('Require in SO') . '</th>
				</tr>';
	$PropertyCounter =0;
	while ($myrow = DB_fetch_array($result)) {
		echo '<input type="hidden" name="PropID' . $PropertyCounter .'" value="' . $myrow['stkcatpropid'] . '" />';
		echo '<tr>
				<td><input type="text" name="PropLabel' . $PropertyCounter . '" size="50" maxlength="100" value="' . $myrow['label'] . '" /></td>
				<td><select name="PropControlType' . $PropertyCounter . '">';
		if ($myrow['controltype']==0){
			echo '<option selected="selected" value="0">' . _('Text Box') . '</option>';
		} else {
			echo '<option value="0">' . _('Text Box') . '</option>';
		}
		if ($myrow['controltype']==1){
			echo '<option selected="selected" value="1">' . _('Select Box') . '</option>';
		} else {
			echo '<option value="1">' . _('Select Box') . '</option>';
		}
		if ($myrow['controltype']==2){
			echo '<option selected="selected" value="2">' . _('Check Box') . '</option>';
		} else {
			echo '<option value="2">' . _('Check Box') . '</option>';
		}
		if ($myrow['controltype']==3){
			echo '<option selected="selected" value="3">' . _('Date Box') . '</option>';
		} else {
			echo '<option value="3">' . _('Date Box') . '</option>';
		}
		echo '</select></td>
					<td><input type="text" name="PropDefault' . $PropertyCounter . '" value="' . $myrow['defaultvalue'] . '" /></td>';

		if ($myrow['numericvalue']==1){
			echo '<td><input type="checkbox" name="PropNumeric' . $PropertyCounter . '" checked="checked" /></td>';
		} else {
			echo '<td><input type="checkbox" name="PropNumeric' . $PropertyCounter . '" /></td>';
		}

		echo '<td><input type="text" name="PropMinimum' . $PropertyCounter . '" value="' . $myrow['minimumvalue'] . '" /></td>
				<td><input type="text" name="PropMaximum' . $PropertyCounter . '" value="' . $myrow['maximumvalue'] . '" /></td>';

		if ($myrow['reqatsalesorder']==1){
			echo '<td align="center"><input type="checkbox" name="PropReqSO' . $PropertyCounter .'" checked="True" /></td>';
		} else {
			echo '<td align="center"><input type="checkbox" name="PropReqSO' . $PropertyCounter .'" /></td>';
		}

		echo '<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?DeleteProperty=' . $myrow['stkcatpropid'] .'&SelectedCategory=' . $SelectedCategory . '" onclick=\'return confirm("' . _('Are you sure you wish to delete this property? All properties of this type set up for stock items will also be deleted.') . '");\'>' . _('Delete') . '</td>
			</tr>';

		$PropertyCounter++;
	} //end loop around defined properties for this category
	echo '<input type="hidden" name="PropID' . $PropertyCounter .'" value="NewProperty" />';
	echo '<tr>
			<td><input type="text" name="PropLabel' . $PropertyCounter . '" size="50" maxlength="100" /></td>
			<td><select name="PropControlType' . $PropertyCounter . '">
				<option selected="selected" value="0">' . _('Text Box') . '</option>
				<option value="1">' . _('Select Box') . '</option>
				<option value="2">' . _('Check Box') . '</option>
				<option value="3">' . _('Date Box') . '</option>
				</select></td>
			<td><input type="text" name="PropDefault' . $PropertyCounter . '" /></td>
			<td><input type="checkbox" name="PropNumeric' . $PropertyCounter . '" /></td>
			<td><input type="text" class="number" name="PropMinimum' . $PropertyCounter . '" /></td>
			<td><input type="text" class="number" name="PropMaximum' . $PropertyCounter . '" /></td>
			<td align="center"><input type="checkbox" name="PropReqSO' . $PropertyCounter .'" /></td>
			<td><button type="submit" name="UpdateProperties">' . _('Add new Property') . '</button></td>
			</tr>';
	echo '</table>';
	echo '<input type="hidden" name="PropertyCounter" value="' . $PropertyCounter . '" />';

} /* end if there is a category selected */

echo '<br />
		<div class="centre">
			<button type="submit" name="submit">' . _('Enter Information') . '</button>
		</div>
	</form>';

if (isset($SelectedCategory)) {
	echo '<div style="text-align: right"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . _('Show All Stock Categories') . '</a></div>';
}

include('includes/footer.inc');
?>
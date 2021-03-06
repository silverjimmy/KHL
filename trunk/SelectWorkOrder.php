<?php

/* $Id$*/

include('includes/session.inc');
$title = _('Search Work Orders');
include('includes/header.inc');

echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . $title . '</p>';
echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


if (isset($_POST['ResetPart'])){
	unset($_REQUEST['SelectedStockItem']);
}

if (isset($_REQUEST['WO']) AND $_REQUEST['WO']!='') {
	$_REQUEST['WO'] = trim($_REQUEST['WO']);
	if (!is_numeric($_REQUEST['WO'])){
		prnMsg(_('The work order number entered MUST be numeric'),'warn');
		unset ($_REQUEST['WO']);
		include('includes/footer.inc');
		exit;
	} else {
		echo _('Work Order Number') . ' - ' . $_REQUEST['WO'];
	}
}

if (isset($_POST['SearchParts'])){

	if ($_POST['Keywords'] AND $_POST['StockCode']) {
		echo _('Stock description keywords have been used in preference to the Stock code extract entered');
	}
	if ($_POST['Keywords']) {
		//insert wildcard characters in spaces
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						SUM(locstock.quantity) AS qoh,
						stockmaster.units
					FROM stockmaster,
						locstock
					WHERE stockmaster.stockid=locstock.stockid
						AND stockmaster.description " . LIKE . " '" . $SearchString . "'
						AND stockmaster.categoryid='" . $_POST['StockCat']. "'
						AND stockmaster.mbflag='M'
					GROUP BY stockmaster.stockid,
							stockmaster.description,
							stockmaster.units
					ORDER BY stockmaster.stockid";

	 } elseif (isset($_POST['StockCode'])){
		$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						sum(locstock.quantity) as qoh,
						stockmaster.units
					FROM stockmaster,
						locstock
					WHERE stockmaster.stockid=locstock.stockid
						AND stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%'
						AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
						AND stockmaster.mbflag='M'
					GROUP BY stockmaster.stockid,
							stockmaster.description,
							stockmaster.units
					ORDER BY stockmaster.stockid";

	 } elseif (!isset($_POST['StockCode']) AND !isset($_POST['Keywords'])) {
		$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						sum(locstock.quantity) as qoh,
						stockmaster.units
					FROM stockmaster,
						locstock
					WHERE stockmaster.stockid=locstock.stockid
						AND stockmaster.categoryid='" . $_POST['StockCat'] ."'
						AND stockmaster.mbflag='M'
					GROUP BY stockmaster.stockid,
							stockmaster.description,
							stockmaster.units
					ORDER BY stockmaster.stockid";
	 }

	$ErrMsg =  _('No items were returned by the SQL because');
	$DbgMsg = _('The SQL used to retrieve the searched parts was');
	$StockItemsResult = DB_query($SQL,$db,$ErrMsg,$DbgMsg);
}

if (isset($_POST['StockID'])){
	$StockID = trim(mb_strtoupper($_POST['StockID']));
} elseif (isset($_GET['StockID'])){
	$StockID = trim(mb_strtoupper($_GET['StockID']));
}

if (!isset($StockID)) {

	 /* Not appropriate really to restrict search by date since may miss older
	 ouststanding orders
	$OrdersAfterDate = Date('d/m/Y',Mktime(0,0,0,Date('m')-2,Date('d'),Date('Y')));
	 */

	if (!isset($_REQUEST['WO']) or ($_REQUEST['WO']=='')){
		echo '<table class="selection"><tr><td>';
		if (isset($_REQUEST['SelectedStockItem'])) {
			echo _('For the item') . ': ' . $_REQUEST['SelectedStockItem'] . ' ' . _('and') .
			' <input type="hidden" name="SelectedStockItem" value="' . $_REQUEST['SelectedStockItem'] . '" />';
		}
		echo _('Work Order number') . ': <input type="text" name="WO" maxlength="8" size="9" />&nbsp ' . _('Processing at') . ':<select name="StockLocation"> ';

		$sql = "SELECT loccode, locationname FROM locations";

		$resultStkLocs = DB_query($sql,$db);

		while ($myrow=DB_fetch_array($resultStkLocs)){
			if (isset($_POST['StockLocation'])){
				if ($myrow['loccode'] == $_POST['StockLocation']){
					 echo '<option selected="True" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
				} else {
					 echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
				}
			} elseif ($myrow['loccode']==$_SESSION['UserStockLocation']){
				 echo '<option selected="True" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
			} else {
				 echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
			}
		}

		echo '</select> &nbsp&nbsp';
		echo '<select name="ClosedOrOpen">';

		if ($_GET['ClosedOrOpen']=='Closed_Only'){
			$_POST['ClosedOrOpen']='Closed_Only';
		}

		if ($_POST['ClosedOrOpen']=='Closed_Only'){
			echo '<option selected="True" value="Closed_Only">' . _('Closed Work Orders Only') . '</option>';
			echo '<option value="Open_Only">' . _('Open Work Orders Only') . '</option>';
		} else {
			echo '<option value="Closed_Only">' . _('Closed Work Orders Only') . '</option>';
			echo '<option selected="True" value="Open_Only">' . _('Open Work Orders Only') . '</option>';
		}

		echo '</select> &nbsp&nbsp';
		echo '<input type="submit" name="SearchOrders" value="' . _('Search') . '" />';
		echo '&nbsp;&nbsp;<a href="' . $rootpath . '/WorkOrderEntry.php">' . _('New Work Order') . '</a></td></tr></table><br />';
	}

	$SQL="SELECT categoryid,
				categorydescription
			FROM stockcategory
			ORDER BY categorydescription";

	$result1 = DB_query($SQL,$db);

	echo '<table class="selection">
			<tr>
				<th colspan="6"><font size="3" color="#616161">' . _('To search for work orders for a specific item use the item selection facilities below') . '</font></th>
			</tr>
	  	<tr>
	  		<td><font size="1">' . _('Select a stock category') . ':</font>
	  			<select name="StockCat">';

	while ($myrow1 = DB_fetch_array($result1)) {
		echo '<option value="'. $myrow1['categoryid'] . '">' . $myrow1['categorydescription'] . '</option>';
	}

	  echo '</select>
				<td><font size="1">' . _('Enter text extract(s) in the description') . ':</font></td>
				<td><input type="text" name="Keywords" size="20" maxlength="25" /></td>
			</tr>
			<tr>
				<td></td>
				<td><font size="3"><b>' . _('OR') . ' </b></font><font size="1">' . _('Enter extract of the Stock Code') . '</b>:</font></td>
				<td><input type="text" name="StockCode" size="15" maxlength="18" /></td>
			</tr>
		</table><br />';
	echo '<div class="centre"><input type="submit" name="SearchParts" value="' . _('Search Items Now') . '" />
		<input type="submit" name="ResetPart" value="' . _('Show All') . '" /></div>';
}

if (isset($StockItemsResult)) {

	echo '<br /><table cellpadding="2" class="selection">';
	$TableHeader = '<tr>
				<th>' . _('Code') . '</th>
				<th>' . _('Description') . '</th>
				<th>' . _('On Hand') . '</th>
				<th>' . _('Units') . '</th>
			</tr>';
	echo $TableHeader;

	$j=1;
	$k=0; //row colour counter

	while ($myrow=DB_fetch_array($StockItemsResult)) {

		if ($k==1){
			echo '<tr class="EvenTableRows">';
			$k=0;
		} else {
			echo '<tr class="OddTableRows">';
			$k++;
		}

		printf('<td><input type="submit" name="SelectedStockItem" value="%s" /></td>
			<td>%s</td>
			<td class="number">%s</td>
			<td>%s</td>
			</tr>',
			$myrow['stockid'],
			$myrow['description'],
			$myrow['qoh'],
			$myrow['units']);

		$j++;
		if ($j == 12){
			$j=1;
			echo $TableHeader;
		}
//end of page full new headings if
	}
//end of while loop

	echo '</table>';

}
//end if stock search results to show
  else {

  	if (!isset($_POST['StockLocation'])) {
  		$_POST['StockLocation'] = '';
  	}

	//figure out the SQL required from the inputs available
	if (isset($_POST['ClosedOrOpen']) and $_POST['ClosedOrOpen']=='Open_Only'){
		$ClosedOrOpen = 0;
	} else {
		$ClosedOrOpen = 1;
	}
	if (isset($_REQUEST['WO']) and $_REQUEST['WO'] !='') {
			$SQL = "SELECT workorders.wo,
					woitems.stockid,
					stockmaster.description,
					woitems.qtyreqd,
					woitems.qtyrecd,
					workorders.requiredby
					FROM workorders
					INNER JOIN woitems ON workorders.wo=woitems.wo
					INNER JOIN stockmaster ON woitems.stockid=stockmaster.stockid
					WHERE workorders.closed='" . $ClosedOrOpen . "'
					AND workorders.wo='". $_REQUEST['WO'] ."'
					ORDER BY workorders.wo,
							 woitems.stockid";
	} else {
		  /* $DateAfterCriteria = FormatDateforSQL($OrdersAfterDate); */

			if (isset($_REQUEST['SelectedStockItem'])) {
				$SQL = "SELECT workorders.wo,
					woitems.stockid,
					stockmaster.description,
					woitems.qtyreqd,
					woitems.qtyrecd,
					workorders.requiredby
					FROM workorders
					INNER JOIN woitems ON workorders.wo=woitems.wo
					INNER JOIN stockmaster ON woitems.stockid=stockmaster.stockid
					WHERE workorders.closed='" . $ClosedOrOpen . "'
					AND woitems.stockid='". $_REQUEST['SelectedStockItem'] ."'
					AND workorders.loccode='" . $_POST['StockLocation'] . "'
					ORDER BY workorders.wo,
							 woitems.stockid";
			} else {
				$SQL = "SELECT workorders.wo,
					woitems.stockid,
					stockmaster.description,
					woitems.qtyreqd,
					woitems.qtyrecd,
					workorders.requiredby
					FROM workorders
					INNER JOIN woitems ON workorders.wo=woitems.wo
					INNER JOIN stockmaster ON woitems.stockid=stockmaster.stockid
					WHERE workorders.closed='" . $ClosedOrOpen . "'
					AND workorders.loccode='" . $_POST['StockLocation'] . "'
					ORDER BY workorders.wo,
							 woitems.stockid";
			}
	} //end not order number selected

	$ErrMsg = _('No works orders were returned by the SQL because');
	$WorkOrdersResult = DB_query($SQL,$db,$ErrMsg);

	/*show a table of the orders returned by the SQL */
	if (DB_num_rows($WorkOrdersResult)>0) {
		echo '<br /><table cellpadding="2" width="95%" class="selection">';


		$tableheader = '<tr>
							<th>' . _('Modify') . '</th>
							<th>' . _('Status') . '</th>
							<th>' . _('Receive') . '</th>
							<th>' . _('Issue To') . '</th>
							<th>' . _('Costing') . '</th>
							<th>' . _('Item') . '</th>
							<th>' . _('Quantity Required') . '</th>
							<th>' . _('Quantity Received') . '</th>
							<th>' . _('Quantity Outstanding') . '</th>
							<th>' . _('Required Date') . '</th>
						</tr>';

		echo $tableheader;
	}
	$j = 1;
	$k=0; //row colour counter
	while ($myrow=DB_fetch_array($WorkOrdersResult)) {

		if ($k==1){
			echo '<tr class="EvenTableRows">';
			$k=0;
		} else {
			echo '<tr class="OddTableRows">';
			$k++;
		}

		$ModifyPage = $rootpath . '/WorkOrderEntry.php?WO=' . $myrow['wo'];
		$Status_WO = $rootpath . '/WorkOrderStatus.php?WO=' .$myrow['wo'] . '&StockID=' . $myrow['stockid'];
		$Receive_WO = $rootpath . '/WorkOrderReceive.php?WO=' .$myrow['wo'] . '&StockID=' . $myrow['stockid'];
		$Issue_WO = $rootpath . '/WorkOrderIssue.php?WO=' .$myrow['wo'] . '&StockID=' . $myrow['stockid'];
		$Costing_WO =$rootpath . '/WorkOrderCosting.php?WO=' .$myrow['wo'];

		$FormatedRequiredByDate = ConvertSQLDate($myrow['requiredby']);


		printf('<td><a href="%s">%s</a></td>
				<td><a href="%s">' . _('Status') . '</a></td>
				<td><a href="%s">' . _('Receive') . '</a></td>
				<td><a href="%s">' . _('Issue To') . '</a></td>
				<td><a href="%s">' . _('Costing') . '</a></td>
				<td>%s - %s</td>
				<td class="number">%s</td>
				<td class="number">%s</td>
				<td class="number">%s</td>
				<td>%s</td>
				</tr>',
				$ModifyPage,
				$myrow['wo'],
				$Status_WO,
				$Receive_WO,
				$Issue_WO,
				$Costing_WO,
				$myrow['stockid'],
				$myrow['description'],
				$myrow['qtyreqd'],
				$myrow['qtyrecd'],
				$myrow['qtyreqd']-$myrow['qtyrecd'],
				$FormatedRequiredByDate);

		$j++;
		if ($j == 12){
			$j=1;
			echo $tableheader;
		}
	//end of page full new headings if
	}
	//end of while loop

	echo '</table>';
}

echo '<script>defaultControl(document.forms[0].WO);</script>';

echo '</form>';

include('includes/footer.inc');
?>
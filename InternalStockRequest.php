<?php

/* $Id: StockTransfers.php 4576 2011-05-27 10:59:20Z daintree $*/

include('includes/DefineStockRequestClass.php');

include('includes/session.inc');
$title = _('Create an Internal Materials Request');

include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

if (isset($_GET['New'])) {
	unset($_SESSION['Transfer']);
	$_SESSION['Request'] = new StockRequest();
}

if (isset($_POST['Update'])) {
	$InputError=0;
	if ($_POST['Department']=='') {
		prnMsg( _('You must select a Department for the request'), 'error');
		$InputError=1;
	}
	if ($_POST['Location']=='') {
		prnMsg( _('You must select a Location to request the items from'), 'error');
		$InputError=1;
	}
	if ($InputError==0) {
		$_SESSION['Request']->Department=$_POST['Department'];
		$_SESSION['Request']->Location=$_POST['Location'];
		$_SESSION['Request']->DispatchDate=$_POST['DispatchDate'];
		$_SESSION['Request']->Narrative=$_POST['Narrative'];
	}
}

if (isset($_POST['Edit'])) {
	$_SESSION['Request']->LineItems[$_POST['LineNumber']]->Quantity=$_POST['Quantity'];
}

if (isset($_GET['Delete'])) {
	unset($_SESSION['Request']->LineItems[$_GET['Delete']]);
	echo '<br />';
	prnMsg( _('The line was successfully deleted'), 'success');
	echo '<br />';
}

foreach ($_POST as $key => $value) {
	if (mb_strstr($key,'StockID')) {
		$Index=mb_substr($key, 7);
		if (filter_number_input($_POST['Quantity'.$Index])>0) {
			$StockID=$value;
			$ItemDescription=$_POST['ItemDescription'.$Index];
			$DecimalPlaces=$_POST['DecimalPlaces'.$Index];
			$NewItem_array[$StockID] = filter_number_input($_POST['Quantity'.$Index]);
			$_POST['Units'.$StockID]=$_POST['Units'.$Index];
			$_SESSION['Request']->AddLine($StockID, $ItemDescription, $NewItem_array[$StockID], $_POST['Units'.$StockID], $DecimalPlaces);
		}
	}
}

if (isset($_POST['Submit'])) {
	DB_Txn_Begin($db);
	$InputError=0;
	if ($_SESSION['Request']->Department=='') {
		prnMsg( _('You must select a Department for the request'), 'error');
		$InputError=1;
	}
	if ($_SESSION['Request']->Location=='') {
		prnMsg( _('You must select a Location to request the items from'), 'error');
		$InputError=1;
	}
	if ($InputError==0) {
		$RequestNo = GetNextTransNo(38, $db);
		$HeaderSQL="INSERT INTO stockrequest (dispatchid,
											loccode,
											departmentid,
											despatchdate,
											narrative)
										VALUES(
											'" . $RequestNo . "',
											'" . $_SESSION['Request']->Location . "',
											'" . $_SESSION['Request']->Department . "',
											'" . FormatDateForSQL($_SESSION['Request']->DispatchDate) . "',
											'" . $_SESSION['Request']->Narrative . "'
											)";
		$ErrMsg =_('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The request header record could not be inserted because');
		$DbgMsg = _('The following SQL to insert the request header record was used');
		$Result = DB_query($HeaderSQL,$db,$ErrMsg,$DbgMsg,true);

		foreach ($_SESSION['Request']->LineItems as $LineItems) {
			$LineSQL="INSERT INTO stockrequestitems (dispatchitemsid,
													dispatchid,
													stockid,
													quantity,
													decimalplaces,
													uom)
												VALUES(
													'".$LineItems->LineNumber."',
													'".$RequestNo."',
													'".$LineItems->StockID."',
													'".$LineItems->Quantity."',
													'".$LineItems->DecimalPlaces."',
													'".$LineItems->UOM."'
												)";
			$ErrMsg =_('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The request line record could not be inserted because');
			$DbgMsg = _('The following SQL to insert the request header record was used');
			$Result = DB_query($LineSQL,$db,$ErrMsg,$DbgMsg,true);

		}

	}
	DB_Txn_Commit($db);
	prnMsg( _('The internal stock request has been entered and now needs to be authorised'), 'success');
	echo '<br /><div class="centre"><a href="'. htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?New=Yes">' . _('Create another request') . '</a></div>';
	include('includes/footer.inc');
	unset($_SESSION['Request']);
	exit;
}

echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/supplier.png" title="' . _('Dispatch') .
		'" alt="" />' . ' ' . $title . '</p>';

if (isset($_GET['Edit'])) {
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table class="selection">';
	echo '<tr>
			<th colspan="2" class="header">' . _('Edit the Request Line') . '</th>
		</tr>';
	echo '<tr>
			<td>' . _('Line number') . '</td>
			<td>' . $_SESSION['Request']->LineItems[$_GET['Edit']]->LineNumber . '</td>
		</tr>';
	echo '<tr>
			<td>' . _('Stock Code') . '</td>
			<td>' . $_SESSION['Request']->LineItems[$_GET['Edit']]->StockID . '</td>
		</tr>';
	echo '<tr>
			<td>' . _('Item Description') . '</td>
			<td>' . $_SESSION['Request']->LineItems[$_GET['Edit']]->ItemDescription . '</td>
		</tr>';
	echo '<tr>
			<td>' . _('Unit of Measure') . '</td>
			<td>' . $_SESSION['Request']->LineItems[$_GET['Edit']]->UOM . '</td>
		</tr>';
	echo '<tr>
			<td>' . _('Quantity Requested') . '</td>
			<td><input type="text" class="number" name="Quantity" value="' . locale_number_format($_SESSION['Request']->LineItems[$_GET['Edit']]->Quantity, $_SESSION['Request']->LineItems[$_GET['Edit']]->DecimalPlaces) . '" /></td>
		</tr>';
	echo '<input type="hidden" name="LineNumber" value="' . $_SESSION['Request']->LineItems[$_GET['Edit']]->LineNumber . '" />';
	echo '</table><br />';
	echo '<div class="centre"><button type="submit" name="Edit">' . _('Update Line') . '</button></div></form>';
	include('includes/footer.inc');
	exit;
}

echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method=post>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<table class="selection">';
echo '<tr>
		<th colspan="2" class="header">' . _('Internal Stock Request Details') . '</th>
	</tr>';
echo '<tr>
		<td>' . _('Department') . ':</td>';

$sql="SELECT departmentid,
			description
		FROM departments
		ORDER BY description";

$result=DB_query($sql, $db);
echo '<td><select name="Department">';
echo '<option value="">' . _('Select your department') . '</option>';
while ($myrow=DB_fetch_array($result)){
	if (isset($_SESSION['Request']->Department) and $_SESSION['Request']->Department==$myrow['departmentid']){
		echo '<option selected="True" value="' . $myrow['departmentid'] . '">' . $myrow['departmentid'].' - ' .htmlentities($myrow['description'], ENT_QUOTES,'UTF-8').'</option>';
	} else {
		echo '<option value="' . $myrow['departmentid'] . '">' . $myrow['departmentid'].' - ' .htmlentities($myrow['description'], ENT_QUOTES,'UTF-8').'</option>';
	}
}
echo '</select></td></tr>';

echo '<tr>
		<td>' . _('Location from which to request stock') . ':</td>';
$sql="SELECT loccode,
			locationname
		FROM locations
		ORDER BY locationname";

$result=DB_query($sql, $db);
echo '<td><select name="Location">';
echo '<option value="">' . _('Select a Location') . '</option>';
while ($myrow=DB_fetch_array($result)){
	if (isset($_SESSION['Request']->Location) and $_SESSION['Request']->Location==$myrow['loccode']){
		echo '<option selected="True" value="' . $myrow['loccode'] . '">' . $myrow['loccode'].' - ' .htmlentities($myrow['locationname'], ENT_QUOTES,'UTF-8').'</option>';
	} else {
		echo '<option value="' . $myrow['loccode'] . '">' . $myrow['loccode'].' - ' .htmlentities($myrow['locationname'], ENT_QUOTES,'UTF-8').'</option>';
	}
}
echo '</select></td></tr>';

echo '<tr>
		<td>' . _('Date when required') . ':</td>';
echo '<td><input type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="DispatchDate" maxlength="10" size="11" value="' . $_SESSION['Request']->DispatchDate . '" /></td>';

echo '<tr><td>' . _('Narrative') . ':</td>';
echo '<td><textarea name="Narrative" cols="30">'.$_SESSION['Request']->Narrative.'</textarea></td>';

echo '</table><br />';

echo '<div class="centre"><button type="submit" name="Update">' . _('Update') . '</button></div>';

echo '</div></form>';

if (!isset($_SESSION['Request']->Location)) {
	include('includes/footer.inc');
	exit;
}

//****************MUESTRO LA TABLA CON LOS REGISTROS DE LA TRANSFERENCIA*************************************
$i = 0; //Line Item Array pointer
echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<br /><table class="selection">';
echo '<tr>
		<th colspan="7" class="header">' . _('Details of Items Requested') . '</th>
	</tr>';
echo '<tr>
		<th>'. _('Line Number') . '</th>
		<th>'. _('Item Code') . '</th>
		<th>'. _('Item Description'). '</th>
		<th>'. _('Quantity Required'). '</th>
		<th>'. _('UOM'). '</th>
	</tr>';

$k=0;

foreach ($_SESSION['Request']->LineItems as $LineItems) {

	if ($k==1){
		echo '<tr class="EvenTableRows">';
		$k=0;
	} else {
		echo '<tr class="OddTableRows">';
		$k++;
	}
	echo '<td>' . $LineItems->LineNumber . '</td>
			<td>' . $LineItems->StockID . '</td>
			<td>' . $LineItems->ItemDescription . '</td>
			<td class="number">' . locale_number_format($LineItems->Quantity, $LineItems->DecimalPlaces) . '</td>
			<td>' . $LineItems->UOM . '</td>
			<td><a href="'. htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Edit='.$LineItems->LineNumber.'">' . _('Edit') . '</a></td>
			<td><a href="'. htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Delete='.$LineItems->LineNumber.'">' . _('Delete') . '</a></td>
		</tr>';

}

echo '</table><br />';
echo '<div class="centre"><button type="submit" name="Submit">' . _('Submit') . '</button></div><br />';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<p class="page_title_text"><img src="' . $rootpath . '/css/' . $theme . '/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . _('Search for Inventory Items'). '</p>';
$SQL = "SELECT categoryid,
				categorydescription
			FROM stockcategory
			ORDER BY categorydescription";
$result1 = DB_query($SQL, $db);
if (DB_num_rows($result1) == 0) {
	echo '<p><font size="4" color="red">' . _('Problem Report') . ':</font><br />' . _('There are no stock categories currently defined please use the link below to set them up').'</p>';
	echo '<br /><a href="' . $rootpath . '/StockCategories.php">' . _('Define Stock Categories') . '</a>';
	exit;
}
echo '<table class="selection"><tr>';
echo '<td>' . _('In Stock Category') . ':';
echo '<select name="StockCat">';
if (!isset($_POST['StockCat'])) {
	$_POST['StockCat'] = "";
}
if ($_POST['StockCat'] == 'All') {
	echo '<option selected="True" value="All">' . _('All').'</option>';
} else {
	echo '<option value="All">' . _('All').'</option>';
}
while ($myrow1 = DB_fetch_array($result1)) {
	if ($myrow1['categoryid'] == $_POST['StockCat']) {
		echo '<option selected="True" value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'].'</option>';
	} else {
		echo '<option value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'].'</option>';
	}
}
echo '</select></td>';
echo '<td>' . _('Enter partial') . '<b> ' . _('Description') . '</b>:</td>';
if (isset($_POST['Keywords'])) {
	echo '<td><input type="text" name="Keywords" value="' . $_POST['Keywords'] . '" size="20" maxlength="25" /></td>';
} else {
	echo '<td><input type="text" name="Keywords" size="20" maxlength="25" /></td>';
}
echo '</tr>
		<tr>
			<td></td>';
echo '<td><font size="3"><b>' . _('OR') . ' ' . '</b></font>' . _('Enter partial') . ' <b>' . _('Stock Code') . '</b>:</td>';

if (isset($_POST['StockCode'])) {
	echo '<td><input type="text" name="StockCode" value="' . $_POST['StockCode'] . '" size="15" maxlength="18" /></td>';
} else {
	echo '<td><input type="text" name="StockCode" size="15" maxlength="18" /></td>';
}
echo '</tr></table><br />';
echo '<div class="centre"><button type="submit" name="Search">' . _('Search Now') . '</button></div><br />';
echo '<script  type="text/javascript">defaultControl(document.forms[0].StockCode);</script>';
echo '</form>';

if (isset($_POST['Search']) or isset($_POST['Next']) or isset($_POST['Prev'])){

	if ($_POST['Keywords']!='' AND $_POST['StockCode']=='') {
		prnMsg ( _('Order Item description has been used in search'), 'warn' );
	} elseif ($_POST['StockCode']!='' AND $_POST['Keywords']=='') {
		prnMsg ( _('Stock Code has been used in search'), 'warn' );
	} elseif ($_POST['Keywords']=='' AND $_POST['StockCode']=='') {
		prnMsg ( _('Stock Category has been used in search'), 'warn' );
	}
	if (isset($_POST['Keywords']) AND mb_strlen($_POST['Keywords'])>0) {
		//insert wildcard characters in spaces
		$_POST['Keywords'] = mb_strtoupper($_POST['Keywords']);
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		if ($_POST['StockCat']=='All'){
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster,
						stockcategory
					WHERE stockmaster.categoryid=stockcategory.categoryid
						AND stockmaster.mbflag <>'G'
						AND stockmaster.description " . LIKE . " '$SearchString'
						AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster,
						stockcategory
					WHERE  stockmaster.categoryid=stockcategory.categoryid
						AND stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
						AND stockmaster.description " . LIKE . " '" . $SearchString . "'
						AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}

	} elseif (mb_strlen($_POST['StockCode'])>0){

		$_POST['StockCode'] = mb_strtoupper($_POST['StockCode']);
		$SearchString = '%' . $_POST['StockCode'] . '%';

		if ($_POST['StockCat']=='All'){
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster,
						stockcategory
					WHERE stockmaster.categoryid=stockcategory.categoryid
						AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
						AND stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster,
						stockcategory
					WHERE stockmaster.categoryid=stockcategory.categoryid
						AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
						AND stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
						AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}

	} else {
		if ($_POST['StockCat']=='All'){
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster,
						stockcategory
					WHERE  stockmaster.categoryid=stockcategory.categoryid
						AND stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster,
						stockcategory
					WHERE stockmaster.categoryid=stockcategory.categoryid
						AND stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
						AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}
	}

	if (isset($_POST['Next'])) {
		$Offset = $_POST['nextlist'];
	}
	if (isset($_POST['Prev'])) {
		$Offset = $_POST['previous'];
	}
	if (!isset($Offset) or $Offset<0) {
		$Offset=0;
	}
	$SQL = $SQL . ' LIMIT ' . $_SESSION['DefaultDisplayRecordsMax'].' OFFSET '.($_SESSION['DefaultDisplayRecordsMax']*$Offset);

	$ErrMsg = _('There is a problem selecting the part records to display because');
	$DbgMsg = _('The SQL used to get the part selection was');
	$SearchResult = DB_query($SQL,$db,$ErrMsg, $DbgMsg);

	if (DB_num_rows($SearchResult)==0 ){
		prnMsg (_('There are no products available meeting the criteria specified'),'info');
	}
	if (DB_num_rows($SearchResult)<$_SESSION['DisplayRecordsMax']){
		$Offset=0;
	}

} //end of if search
/* display list if there is more than one record */
if (isset($searchresult) AND !isset($_POST['Select'])) {
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	$ListCount = DB_num_rows($searchresult);
	if ($ListCount > 0) {
		// If the user hit the search button and there is more than one item to show
		$ListPageMax = ceil($ListCount / $_SESSION['DisplayRecordsMax']);
		if (isset($_POST['Next'])) {
			if ($_POST['PageOffset'] < $ListPageMax) {
				$_POST['PageOffset'] = $_POST['PageOffset'] + 1;
			}
		}
		if (isset($_POST['Previous'])) {
			if ($_POST['PageOffset'] > 1) {
				$_POST['PageOffset'] = $_POST['PageOffset'] - 1;
			}
		}
		if ($_POST['PageOffset'] > $ListPageMax) {
			$_POST['PageOffset'] = $ListPageMax;
		}
		if ($ListPageMax > 1) {
			echo '<div class="centre"><br />&nbsp;&nbsp;' . $_POST['PageOffset'] . ' ' . _('of') . ' ' . $ListPageMax . ' ' . _('pages') . '. ' . _('Go to Page') . ': ';
			echo '<select name="PageOffset">';
			$ListPage = 1;
			while ($ListPage <= $ListPageMax) {
				if ($ListPage == $_POST['PageOffset']) {
					echo '<option value=' . $ListPage . ' selected>' . $ListPage . '</option>';
				} else {
					echo '<option value=' . $ListPage . '>' . $ListPage . '</option>';
				}
				$ListPage++;
			}
			echo '</select>
				<button type="submit" name="Go">' . _('Go') . '</button>
				<button type="submit" name="Previous">' . _('Previous') . '</button>
				<button type="submit" name="Next">' . _('Next') . '</button>';
			echo '<input type="hidden" name=Keywords value="'.$_POST['Keywords'].'" />';
			echo '<input type="hidden" name=StockCat value="'.$_POST['StockCat'].'" />';
			echo '<input type="hidden" name=StockCode value="'.$_POST['StockCode'].'" />';
//			echo '<input type="hidden" name=Search value="Search" />';
			echo '<br /></div>';
		}
		echo '<table cellpadding="2" class="selection">';
		echo '<tr>
				<th>' . _('Code') . '</th>
				<th>' . _('Description') . '</th>
				<th>' . _('Total Qty On Hand') . '</th>
				<th>' . _('Units') . '</th>
				<th>' . _('Stock Status') . '</th>
			</tr>';
		$j = 1;
		$k = 0; //row counter to determine background colour
		$RowIndex = 0;
		if (DB_num_rows($searchresult) <> 0) {
			DB_data_seek($searchresult, ($_POST['PageOffset'] - 1) * $_SESSION['DisplayRecordsMax']);
		}
		while (($myrow = DB_fetch_array($searchresult)) AND ($RowIndex <> $_SESSION['DisplayRecordsMax'])) {
			if ($k == 1) {
				echo '<tr class="EvenTableRows">';
				$k = 0;
			} else {
				echo '<tr class="OddTableRows">';
				$k++;
			}
			if ($myrow['mbflag'] == 'D') {
				$qoh = _('N/A');
			} else {
				$qoh = locale_number_format($myrow['qoh'], $myrow['decimalplaces']);
			}
			if ($myrow['discontinued']==1){
				$ItemStatus = '<font class="bad">' . _('Obsolete') . '</font>';
			} else {
				$ItemStatus ='';
			}

			echo '<td><button type="submit" name="Select" value="' . $myrow['stockid'] . '" />' . $myrow['stockid'] . '</button></td>
					<td>'.$myrow['description'].'</td>
					<td class="number">' . $qoh . '</td>
					<td>' . $myrow['units'] . '</td>
					<td><a target="_blank" href="' . $rootpath . '/StockStatus.php?StockID=' . $myrow['stockid'].'">' . _('View') . '</a></td>
					<td>' . $ItemStatus . '</td>
				</tr>';
			//end of page full new headings if
		}
		//end of while loop
		echo '</table></form><br />';
	}
}
/* end display list if there is more than one record */

if (isset($SearchResult)) {
	echo '<br />';
	echo '<div class="page_help_text">' . _('Select an item by entering the quantity required.  Click Order when ready.') . '</div>';
	echo '<br />';
	$j = 1;
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" name="orderform">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<table class="selection">';
	echo '<tr><td>
			<input type="hidden" name="previous" value="'.($Offset-1).'" />
			<button tabindex="'.($j+8).'" type="submit" name="Prev">'._('Prev').'</button></td>';
	echo '<td style="text-align:center" colspan="6">
			<input type="hidden" name="order_items" value="1" />
			<button tabindex="'.($j+9).'" type="submit">'._('Add to Requisition').'</button></td>';
	echo '<td>
			<input type="hidden" name="nextlist" value="'.($Offset+1).'" />
			<button tabindex="'.($j+10).'" type="submit" name="Next">'._('Next').'</button></td></tr>';
	echo '<tr>
			<th>' . _('Code') . '</th>
			<th>' . _('Description') . '</th>
			<th>' . _('Units') . '</th>
			<th>' . _('On Hand') . '</th>
			<th>' . _('On Demand') . '</th>
			<th>' . _('On Order') . '</th>
			<th>' . _('Available') . '</th>
			<th>' . _('Quantity') . '</th>
		</tr>';
	$ImageSource = _('No Image');

	$k=0; //row colour counter
	$i=0;
	while ($myrow=DB_fetch_array($SearchResult)) {
		// Find the quantity in stock at location
		if ($myrow['decimalplaces']=='') {
			$DecimalPlacesSQL="SELECT decimalplaces
								FROM stockmaster
								WHERE stockid='" .$myrow['stockid'] . "'";
			$DecimalPlacesResult = DB_query($DecimalPlacesSQL, $db);
			$DecimalPlacesRow = DB_fetch_array($DecimalPlacesResult);
			$DecimalPlaces = $DecimalPlacesRow['decimalplaces'];
		} else {
			$DecimalPlaces=$myrow['decimalplaces'];
		}

		$QOHSQL = "SELECT sum(locstock.quantity) AS qoh
							   FROM locstock
							   WHERE locstock.stockid='" .$myrow['stockid'] . "' AND
							   loccode = '" . $_SESSION['Request']->Location . "'";
		$QOHResult =  DB_query($QOHSQL,$db);
		$QOHRow = DB_fetch_array($QOHResult);
		$QOH = $QOHRow['qoh'];

		// Find the quantity on outstanding sales orders
		$sql = "SELECT SUM(salesorderdetails.quantity-salesorderdetails.qtyinvoiced) AS dem
						 FROM salesorderdetails,
					  			salesorders
						 WHERE salesorders.orderno = salesorderdetails.orderno AND
							salesorders.fromstkloc='" . $_SESSION['Request']->Location . "' AND
 							salesorderdetails.completed=0 AND
		 					salesorders.quotation=0 AND
				 			salesorderdetails.stkcode='" . $myrow['stockid'] . "'";
		$ErrMsg = _('The demand for this product from') . ' ' . $_SESSION['Request']->Location . ' ' . _('cannot be retrieved because');
		$DemandResult = DB_query($sql,$db,$ErrMsg);

		$DemandRow = DB_fetch_row($DemandResult);
		if ($DemandRow[0] != null){
			$DemandQty =  $DemandRow[0]/$PriceRow['conversionfactor'];
		} else {
		  $DemandQty = 0;
		}

		// Find the quantity on purchase orders
		$sql = "SELECT SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd)*purchorderdetails.conversionfactor AS dem
					 FROM purchorderdetails LEFT JOIN purchorders
						ON purchorderdetails.orderno=purchorders.orderno
					 WHERE purchorderdetails.completed=0
					 AND purchorders.status<>'Cancelled'
					 AND purchorders.status<>'Rejected'
					AND purchorderdetails.itemcode='" . $myrow['stockid'] . "'";

		$ErrMsg = _('The order details for this product cannot be retrieved because');
		$PurchResult = DB_query($sql,$db,$ErrMsg);

		$PurchRow = DB_fetch_row($PurchResult);
		if ($PurchRow[0]!=null){
			$PurchQty =  $PurchRow[0];
		} else {
			$PurchQty = 0;
		}

		// Find the quantity on works orders
		$sql = "SELECT SUM(woitems.qtyreqd - woitems.qtyrecd) AS dedm
			   FROM woitems
			   WHERE stockid='" . $myrow['stockid'] ."'";
		$ErrMsg = _('The order details for this product cannot be retrieved because');
		$WoResult = DB_query($sql,$db,$ErrMsg);

		$WoRow = DB_fetch_row($WoResult);
		if ($WoRow[0]!=null){
			$WoQty =  $WoRow[0];
		} else {
			$WoQty = 0;
		}

		if ($k==1){
			echo '<tr class="EvenTableRows">';
			$k=0;
		} else {
			echo '<tr class="OddTableRows">';
			$k=1;
		}
		$OnOrder = $PurchQty + $WoQty;
		$Available = $QOH - $DemandQty + $OnOrder;
		echo '<td>'.$myrow['stockid'].'</font></td>
				<td>'.$myrow['description'].'</td>
				<td>'.$myrow['stockunits'].'</td>
				<td class="number">'.locale_number_format($QOH,$DecimalPlaces).'</td>
				<td class="number">'.locale_number_format($DemandQty,$DecimalPlaces).'</td>
				<td class="number">'.locale_number_format($OnOrder, $DecimalPlaces).'</td>
				<td class="number">'.locale_number_format($Available,$DecimalPlaces).'</td>
				<td><font size="1"><input class="number"  tabindex="'.($j+7).'" type="text" size="6" name="Quantity'.$i.'" value="0" />
				<input type="hidden" name="StockID'.$i.'" value="'.$myrow['stockid'].'" />
				</td>
			</tr>';
		echo '<input type="hidden" name="DecimalPlaces'.$i.'" value="' . $myrow['decimalplaces'] . '" />';
		echo '<input type="hidden" name="ItemDescription'.$i.'" value="' . $myrow['description'] . '" />';
		echo '<input type="hidden" name="Units'.$i.'" value="' . $myrow['stockunits'] . '" />';
		if ($j==1) {
			$jsCall = '<script  type="text/javascript">if (document.SelectParts) {defaultControl(document.SelectParts.itm'.$myrow['stockid'].');}</script>';
		}
		$i++;
#end of page full new headings if
	}
#end of while loop
	echo '<tr><td><input type="hidden" name="previous" value="'.($Offset-1).'" />
			<button tabindex="'.($j+7).'" type="submit" name="Prev">'._('Prev').'</button></td>';
	echo '<td style="text-align:center" colspan="6"><input type="hidden" name="order_items" value="1" />
		<button tabindex="'.($j+8).'" type="submit">'._('Add to Requisition').'</button></td>';
	echo '<td><input type="hidden" name="nextlist" value="'.($Offset+1).'" />
		<button tabindex="'.($j+9).'" type="submit" name="Next">'._('Next').'</button></td><tr/>';
	echo '</table></form>';
	echo $jsCall;

}#end if SearchResults to show

//*********************************************************************************************************
include('includes/footer.inc');
?>
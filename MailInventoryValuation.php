<?php

/* $Id$ */

$AllowAnyone = true;

$FromCriteria ='1'; /*Category From */
$ToCriteria ='zzzzzzzz'; /*Category To */
$Location =  'All';  /* Location to report on */
$DetailedReport = 'Yes';  /* Total by category or complete listing */
$Recipients = array('"Postmaster" < postmaster@localhost>','"someone" <someone@localhost>');


$_POST['DetailedReport'] = $DetailedReport; /* so PDFInventoryValnPageHeader.inc works too */
$_POST['FromCriteria']=$FromCriteria; /* so PDFInventoryValnPageHeader.inc works too */
$_POST['ToCriteria']=$ToCriteria; /* so PDFInventoryValnPageHeader.inc works too */
$_POST['Location'] = $Location; /* so PDFInventoryValnPageHeader.inc works too */

include('includes/session.inc');
include ('includes/class.pdf.php');

/* A4_Portrait */

$Page_Width=595;
$Page_Height=842;
$Top_Margin=30;
$Bottom_Margin=30;
$Left_Margin=40;
$Right_Margin=30;

// Javier: now I use the native constructor
// Javier: better to not use references
// $PageSize = array(0,0,$Page_Width,$Page_Height);
// $pdf = & new Cpdf($PageSize);
$pdf = new Cpdf('P', 'pt', 'A4');

// $PageNumber = 0;

/* Standard PDF file creation header stuff */

$pdf->addInfo('Creator','WebERP http://www.web-erp.org');
$pdf->addInfo('Author','WebERP ' . $Version);


// $FontSize=10;
$pdf->addInfo('Title', _('Inventory Valuation Report'));
$pdf->addInfo('Subject', _('Inventory Valuation'));

/* Javier: I have brought this piece from the pdf class constructor to get it closer to the admin/user,
	I corrected it to match TCPDF, but it still needs check, after which,
	I think it should be moved to each report to provide flexible Document Header and Margins in a per-report basis. */
	$pdf->setAutoPageBreak(0);	// Javier: needs check.
	$pdf->setPrintHeader(false);	// Javier: I added this must be called before Add Page
	$pdf->AddPage();
//	$this->SetLineWidth(1); 	   Javier: It was ok for FPDF but now is too gross with TCPDF. TCPDF defaults to 0'57 pt (0'2 mm) which is ok.
	$pdf->cMargin = 0;		// Javier: needs check.
/* END Brought from class.pdf.php constructor */

$PageNumber = 1;
$line_height = 12;

/*Now figure out the inventory data to report for the category range under review */
if ($Location=='All'){

	$SQL = "SELECT stockmaster.categoryid,
			stockcategory.categorydescription,
			stockmaster.stockid,
			stockmaster.description,
			stockmaster.decimalplaces,
			SUM(locstock.quantity) as qtyonhand,
			stockmaster.materialcost + stockmaster.labourcost + stockmaster.overheadcost AS unitcost,
			SUM(locstock.quantity) *(stockmaster.materialcost + stockmaster.labourcost + stockmaster.overheadcost) AS itemtotal
		FROM stockmaster,
			stockcategory,
			locstock
		WHERE stockmaster.stockid=locstock.stockid
		AND stockmaster.categoryid=stockcategory.categoryid
		GROUP BY stockmaster.categoryid,
			stockcategory.categorydescription,
			unitcost,
			stockmaster.stockid,
			stockmaster.description
		HAVING SUM(locstock.quantity)!=0
		AND stockmaster.categoryid >= '" . $FromCriteria . "'
		AND stockmaster.categoryid <= '" . $ToCriteria . "'
		ORDER BY stockmaster.categoryid,
			stockmaster.stockid";

} else {

	$SQL = "SELECT stockmaster.categoryid,
			stockcategory.categorydescription,
			stockmaster.stockid,
			stockmaster.description,
			stockmaster.decimalplaces,
			locstock.quantity as qtyonhand,
			stockmaster.materialcost + stockmaster.labourcost + stockmaster.overheadcost AS unitcost,
			locstock.quantity *(stockmaster.materialcost + stockmaster.labourcost + stockmaster.overheadcost) AS itemtotal
		FROM stockmaster,
			stockcategory,
			locstock
		WHERE stockmaster.stockid=locstock.stockid
		AND stockmaster.categoryid=stockcategory.categoryid
		AND locstock.quantity!=0
		AND stockmaster.categoryid >= '" . $FromCriteria . "'
		AND stockmaster.categoryid <= '" . $ToCriteria . "'
		AND locstock.loccode = '" . $Location . "'
		ORDER BY stockmaster.categoryid,
			stockmaster.stockid";

}
$InventoryResult = DB_query($SQL,$db,'','',false,true);
$ListCount = DB_num_rows($InventoryResult);

if (DB_error_no($db) !=0) {
	$title = _('Inventory Valuation') . ' - ' . _('Problem Report');
	include('includes/header.inc');
	echo _('The inventory valuation could not be retrieved by the SQL because') . ' - ' . DB_error_msg($db);
	echo '<br /><a href="' .$rootpath .'/index.php">' . _('Back to the menu') . '</a>';
	if ($debug==1){
		echo '<br />' . $SQL;
	}

include('includes/footer.inc');
exit;
}

include ('includes/PDFInventoryValnPageHeader.inc');

$Tot_Val=0;
$Category = '';
$CatTot_Val=0;
while ($InventoryValn = DB_fetch_array($InventoryResult,$db)){

	if ($Category!=$InventoryValn['categoryid']){
		$FontSize=10;
		if ($Category!=''){ /*Then it's NOT the first time round */

		/* need to print the total of previous category */
			if ($_POST['DetailedReport']=='Yes'){
				$YPos -= (2*$line_height);
				$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,260-$Left_Margin,$FontSize,_('Total for') . ' ' . $Category . ' - ' . $CategoryName);
			}

			$DisplayCatTotVal = locale_money_format($CatTot_Val,$_SESSION['CompanyRecord']['currencydefault']);
			$LeftOvers = $pdf->addTextWrap(500,$YPos,60,$FontSize,$DisplayCatTotVal, 'right');
			$YPos -=$line_height;

			if ($_POST['DetailedReport']=='Yes'){
			/*draw a line under the CATEGORY TOTAL*/
				$pdf->line($Left_Margin, $YPos+$line_height-2,$Page_Width-$Right_Margin, $YPos+$line_height-2);
				$YPos -=(2*$line_height);
			}
			$CatTot_Val=0;
		}
		$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,260-$Left_Margin,$FontSize,$InventoryValn['categoryid'] . " - " . $InventoryValn['categorydescription']);
		$Category = $InventoryValn['categoryid'];
		$CategoryName = $InventoryValn['categorydescription'];
	}

	if ($_POST['DetailedReport']=='Yes'){
		$YPos -=$line_height;
		$FontSize=8;

		$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,60,$FontSize,$InventoryValn['stockid']);				$LeftOvers = $pdf->addTextWrap(120,$YPos,260,$FontSize,$InventoryValn['description']);
		$DisplayUnitCost = locale_money_format($InventoryValn['unitcost'],$_SESSION['CompanyRecord']['currencydefault']);
		$DisplayQtyOnHand = locale_number_format($InventoryValn['qtyonhand'],$InventoryValn['decimalplaces']);
		$DisplayItemTotal = locale_money_format($InventoryValn['itemtotal'],$_SESSION['CompanyRecord']['currencydefault']);

		$LeftOvers = $pdf->addTextWrap(380,$YPos,60,$FontSize,$DisplayQtyOnHand,'right');
		$LeftOvers = $pdf->addTextWrap(440,$YPos,60,$FontSize,$DisplayUnitCost, 'right');
		$LeftOvers = $pdf->addTextWrap(500,$YPos,60,$FontSize,$DisplayItemTotal, 'right');

	}
	$Tot_Val += $InventoryValn['itemtotal'];
	$CatTot_Val += $InventoryValn['itemtotal'];

	if ($YPos < $Bottom_Margin + $line_height){
		include('includes/PDFInventoryValnPageHeader.inc');
	}

} /*end inventory valn while loop */

$FontSize =10;
/*Print out the category totals */
if ($_POST['DetailedReport']=='Yes'){
	$YPos -=$line_height;
	$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,260-$Left_Margin,$FontSize, _('Total for') . ' ' . $Category . ' - ' . $CategoryName, 'left');
}

$DisplayCatTotVal = locale_money_format($CatTot_Val,$_SESSION['CompanyRecord']['currencydefault']);
$LeftOvers = $pdf->addTextWrap(500,$YPos,60,$FontSize,$DisplayCatTotVal, 'right');

if ($_POST['DetailedReport']=='Yes'){
	/*draw a line under the CATEGORY TOTAL*/
	$pdf->line($Left_Margin, $YPos+$line_height-2,$Page_Width-$Right_Margin, $YPos+$line_height-2);
	$YPos -=(2*$line_height);
}

$YPos -= (2*$line_height);

/*Print out the grand totals */
$LeftOvers = $pdf->addTextWrap(80, $YPos,260-$Left_Margin,$FontSize, _('Grand Total Value'), 'right');
$DisplayTotalVal = locale_money_format($Tot_Val,$_SESSION['CompanyRecord']['currencydefault']);
$LeftOvers = $pdf->addTextWrap(500,$YPos,60,$FontSize,$DisplayTotalVal, 'right');
if ($_POST['DetailedReport']=='Yes'){
	$pdf->line($Left_Margin, $YPos+$line_height-2,$Page_Width-$Right_Margin, $YPos+$line_height-2);
	$YPos -=(2*$line_height);
}

if ($ListCount == 0) {
	$title = _('Print Inventory Valuation Error');
	include('includes/header.inc');
	echo '<br />' . _('There were no items with any value to print out for the location specified');
	echo '<br /><a href="' . $rootpath . '/index.php">' . _('Back to the menu') . '</a>';
	include('includes/footer.inc');
	exit; // Javier: needs check
} else {
	include('includes/htmlMimeMail.php');

	$pdf->Output($_SESSION['reports_dir'] . '/InventoryReport.pdf', 'F');
	$pdf-> __destruct();

	$mail = new htmlMimeMail();
	$attachment = $mail->getFile( $_SESSION['reports_dir'] . '/InventoryReport.pdf');
	$mail->setText(_('Please find herewith the stock valuation report'));
	$mail->setSubject(_('Inventory Valuation Report'));
	$mail->addAttachment($attachment, 'InventoryReport.pdf', 'application/pdf');
	$mail->setFrom($_SESSION['CompanyRecord']['coyname'] . "<" . $_SESSION['CompanyRecord']['email'] . ">");
	$result = $mail->send($Recipients);

}
?>
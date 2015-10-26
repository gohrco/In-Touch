<?php
/******************************************************************
 * quotepdf.tpl
 * Compatible with WHMCS v6.0 and above
 * 
 * @projectName@ - Custom Template File
 *
 * @package    @projectName@
 * @copyright  @copyWrite@
 * @license    @buildLicense@
 * @version    @fileVers@ ( $Id$ )
 * @author     @buildAuthor@
 * @since      2.2.0
 ******************************************************************/

// We must have the Dunamis Framework so lets build the path
$path			=	( isset( $this->template_dir ) ? rtrim( dirname( $this->template_dir ), DIRECTORY_SEPARATOR ) : dirname( dirname( __DIR__ ) ) )
				.	DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'dunamis.php';
$logo			= false;
$legalfooter	= null;

// If the Dunamis Framework file is in place we should be okay to load
if ( file_exists( $path ) ) {
		
	// Include the file
	include_once( $path );
		
	// Initialize the invoices controller of the In Touch module
	$module	= dunmodule( 'intouch.quotes' );
		
	// If we got back false then there is a problem
	if ( $module ) {
		
		// See if we should even be customizing
		if ( $module->shouldCustomize() ) {
			$logo			= $module->getLogoPath();
			$addr			= $module->getCustomAddress();
			$legalfooter	= $module->getLegalFooter();
			
			if ( $addr ) {
				$companyaddress = $addr;
			}
		}
	}
}

# In Touch Logo Customization
if ( $logo ) $pdf->Image( \$logo, 20, 25, 75 );
else if ( file_exists( ROOTDIR . '/assets/img/logo.png' ) )	$pdf->Image( ROOTDIR . '/assets/img/logo.png',20,25,75 );
else if ( file_exists( ROOTDIR . '/assets/img/logo.jpg' ) )	$pdf->Image( ROOTDIR . '/assets/img/logo.jpg',20,25,75 );
else $pdf->Image( ROOTDIR . '/assets/img/placeholder.png',20,25,75 );

# Company Details
$pdf->SetFont('freesans','',13);
$pdf->Cell(0,6,trim($companyaddress[0]),0,1,'R');
$pdf->SetFont('freesans','',9);
for ( $i = 1; $i <= ((count($companyaddress)>6) ? count($companyaddress) : 6); $i += 1) {
    $pdf->Cell(0,4,trim($companyaddress[$i]),0,1,'R');
}
$pdf->Ln(5);

$pdf->SetFont('freesans','B',10);
$pdf->SetX($pdf->GetX()+10);
$pdf->Cell(20,6,$_LANG['quotenumber'],1,0,'C');
$pdf->Cell(70,6,$_LANG['quotesubject'],1,0,'C');
$pdf->Cell(35,6,$_LANG['quotedatecreated'],1,0,'C');
$pdf->Cell(35,6,$_LANG['quotevaliduntil'],1,1,'C');

$pdf->SetFont('freesans','',9);
$pdf->SetX($pdf->GetX()+10);
$rowcount = $pdf->getNumLines($subject, 60);
$height = $rowcount * 5;
$pdf->MultiCell(20,$height,$quotenumber,1,'C',0,0);
$pdf->MultiCell(70,$height,$subject,1,'C',0,0);
$pdf->MultiCell(35,$height,$datecreated,1,'C',0,0);
$pdf->MultiCell(35,$height,$validuntil,1,'C',0,1);

$pdf->Ln(10);

$pdf->SetFont('freesans','B',10);
$pdf->Cell(0,4,$_LANG['quoterecipient'],0,1);
$pdf->SetFont('freesans','',9);
if ($clientsdetails["companyname"]) {
    $pdf->Cell(0,4,$clientsdetails["companyname"],0,1,'L');
    $pdf->Cell(0,4,$_LANG["invoicesattn"].": ".$clientsdetails["firstname"]." ".$clientsdetails["lastname"],0,1,'L');
} else {
    $pdf->Cell(0,4,$clientsdetails["firstname"]." ".$clientsdetails["lastname"],0,1,'L');
}
$pdf->Cell(0,4,$clientsdetails["address1"],0,1,'L');
if ($clientsdetails["address2"]) {
    $pdf->Cell(0,4,$clientsdetails["address2"],0,1,'L');
}
$pdf->Cell(0,4,$clientsdetails["city"].', '.$clientsdetails["state"].', '.$clientsdetails["postcode"],0,1,'L');
$pdf->Cell(0,4,$clientsdetails["country"],0,1,'L');

$pdf->Ln(10);

if ($proposal) {
    $pdf->SetFont('freesans','',9);
    $pdf->MultiCell(170,5,$proposal);
    $pdf->Ln(10);
}

$pdf->SetDrawColor(200);
$pdf->SetFillColor(239);

$pdf->SetFont('freesans','',8);

$tblhtml = '<table width="100%" bgcolor="#ccc" cellspacing="1" cellpadding="2" border="0">
    <tr height="30" bgcolor="#efefef" style="font-weight:bold;text-align:center;">
        <td width="5%">'.$_LANG['quoteqty'].'</td>
        <td width="45%">'.$_LANG['quotedesc'].'</td>
        <td width="15%">'.$_LANG['quoteunitprice'].'</td>
        <td width="15%">'.$_LANG['quotediscount'].'</td>
        <td width="20%">'.$_LANG['quotelinetotal'].'</td>
    </tr>';
foreach ($lineitems AS $item) {
    $tblhtml .= '
    <tr bgcolor="#fff">
        <td align="center">'.$item['qty'].'</td>
        <td align="left">'.nl2br($item['description']).'<br /></td>
        <td align="center">'.$item['unitprice'].'</td>
        <td align="center">'.$item['discount'].'</td>
        <td align="center">'.$item['total'].'</td>
    </tr>';
}
$tblhtml .= '
    <tr height="30" bgcolor="#efefef" style="font-weight:bold;">
        <td align="right" colspan="4">'.$_LANG['invoicessubtotal'].'</td>
        <td align="center">'.$subtotal.'</td>
    </tr>';
if ($taxlevel1['rate']>0) $tblhtml .= '
    <tr height="30" bgcolor="#efefef" style="font-weight:bold;">
        <td align="right" colspan="4">'.$taxlevel1['name'].' @ '.$taxlevel1['rate'].'%</td>
        <td align="center">'.$tax1.'</td>
    </tr>';
if ($taxlevel2['rate']>0) $tblhtml .= '
    <tr height="30" bgcolor="#efefef" style="font-weight:bold;">
        <td align="right" colspan="4">'.$taxlevel2['name'].' @ '.$taxlevel2['rate'].'%</td>
        <td align="center">'.$tax2.'</td>
    </tr>';
$tblhtml .= '
    <tr height="30" bgcolor="#efefef" style="font-weight:bold;">
        <td align="right" colspan="4">'.$_LANG['invoicestotal'].'</td>
        <td align="center">'.$total.'</td>
    </tr>
</table>';

$pdf->writeHTML($tblhtml, true, false, false, false, '');

if ($notes) {
    $pdf->Ln(6);
    $pdf->SetFont('freesans','',8);
    $pdf->MultiCell(170,5,$_LANG['invoicesnotes'].": $notes");
}


# Legal Footer
$pdf->writeHTML( html_entity_decode( $legalfooter ), true, false, false, false, '' );
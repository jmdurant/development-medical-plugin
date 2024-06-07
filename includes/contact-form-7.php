<?php

add_action( 'wpcf7_before_send_mail', 'gcm_pre_send_cf7_email_insert_attachments' );


/**
 * This function is from the old theme and is responsible for adding the
 * XML, XDP, and TXT attachments to Contact Form 7 emails for the i693 form.
 *
 * @param WPCF7_ContactForm $cf7
 *
 * @return void
 */
function gcm_pre_send_cf7_email_insert_attachments( $cf7 ) {
	// get info about the form and current submission instance
	
	// $wpcf7 = WPCF7_ContactForm::get_current();
	// $form_id = $wpcf7->id;
	
	$form_id = $cf7->id;
	
	if ($form_id === 3170) {
		
		$to = $cf7->mail['recipient']; // same recipient as mail(1)
		// $to = "admin@greatcitymedical.com,i693@greatcitymedical.com";
		// $to = "malikkamranrashid@gmail.com";
		
		$headers = array( "From: " . $cf7->mail['sender'] );
		// $headers = "From: Great City Medical <noreply@greatcitymedical.com>" . "\r\n";
		
		$originalDate = $_POST['dob'];
		$newDate = date("m/d/Y", strtotime($originalDate));
		$FilenameDate = date("mdY", strtotime($originalDate));
		
		$today = date("m/d/Y");
		
		$output = "";
		$output .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<form1>
<!--User generated-->
<Pt1Line1b_GivenName>" . $_POST['FirstName'] . "</Pt1Line1b_GivenName>
<!--Last name of patient-->
<Pt1Line1a_FamilyName>" . $_POST['LastName'] . "</Pt1Line1a_FamilyName>
<!--First name of patient-->
<Pt1Line1c_MiddleName>" . $_POST['MiddleName'] . "</Pt1Line1c_MiddleName>
<!--Middle name of patient-->
<Pt1Line2_StreetNumberName>" . $_POST['Street'] . "</Pt1Line2_StreetNumberName>
<!--Patients address-->
<Pt1Line2_Unit> " . $_POST['AppartmentType'] . " </Pt1Line2_Unit>
<!--Appartment type-->
<Pt1Line2_AptSteFlrNumber>" . $_POST['Appartment'] . "</Pt1Line2_AptSteFlrNumber>
<!--Appartment unit-->
<P1Line2_CityOrTown>" . $_POST['CityTown'] . "</P1Line2_CityOrTown>
<!--Patient's city-->
<P1Line2_State>" . $_POST['State'] . "</P1Line2_State>
<!--Patient's State-->
<P1Line2_ZipCode>" . $_POST['ZipCode'] . "</P1Line2_ZipCode>
<!--Patient's zip-->
<Pt1Line3_Gender>" . $_POST['Gender'] . "</Pt1Line3_Gender>
<!--Gender of patient-->
<Pt1Line3_DateOfBirth>" . $newDate . "</Pt1Line3_DateOfBirth>
<!--DOB of patient-->
<Pt1Line3_CityTownVillageofBirth>" . $_POST['CityBirth'] . "</Pt1Line3_CityTownVillageofBirth>
<!--City of birth-->
<Pt1Line3_CountryofBirth>" . $_POST['CountryBirth'] . "</Pt1Line3_CountryofBirth>
<!--Country of birth-->
<Pt1Line3e_AlienNumber>" . $_POST['ANumber'] . "</Pt1Line3e_AlienNumber>
<!--A#-->
<Pt1Line3f_USCISOnlineAcctNumber>" . $_POST['USCIS'] . "</Pt1Line3f_USCISOnlineAcctNumber>
<Pt2Line3_DaytimePhone>" . $_POST['DaytimeTelephone'] . "</Pt2Line3_DaytimePhone>
<!--Patient's day time phone number-->
<Pt2Line4_Mobilephone>" . $_POST['MobileTelephone'] . "</Pt2Line4_Mobilephone>
<!--Patient's cell-->
<Pt2Line5_EmailAddress>" . $_POST['Emailaddres'] . "</Pt2Line5_EmailAddress>
<!--Patient's email -->";
		$output .= "</form1>";
		
		
		
		$output2 = "";
		$output2 .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
		<?xfa generator=\"XFA2_4\" APIVersion=\"3.6.14289.0\"?>
<xdp:xdp xmlns:xdp=\"http://ns.adobe.com/xdp/\" timeStamp=\"2020-12-20T15:19:40Z\" uuid=\"6de0130a-2e5c-4c62-b10d-1ac75a98779a\">
<xfa:datasets xmlns:xfa=\"http://www.xfa.org/schema/xfa-data/1.0/\">
<xfa:data>
<form1>
<!--User generated-->
<Pt1Line1b_GivenName>" . $_POST['FirstName'] . "</Pt1Line1b_GivenName>
<!--Last name of patient-->
<Pt1Line1a_FamilyName>" . $_POST['LastName'] . "</Pt1Line1a_FamilyName>
<!--First name of patient-->
<Pt1Line1c_MiddleName>" . $_POST['MiddleName'] . "</Pt1Line1c_MiddleName>
<!--Middle name of patient-->
<Pt1Line2_StreetNumberName>" . $_POST['Street'] . "</Pt1Line2_StreetNumberName>
<!--Patients address-->
<Pt1Line2_Unit> " . $_POST['AppartmentType'] . " </Pt1Line2_Unit>
<!--Appartment type-->
<Pt1Line2_AptSteFlrNumber>" . $_POST['Appartment'] . "</Pt1Line2_AptSteFlrNumber>
<!--Appartment unit-->
<P1Line2_CityOrTown>" . $_POST['CityTown'] . "</P1Line2_CityOrTown>
<!--Patient's city-->
<P1Line2_State>" . $_POST['State'] . "</P1Line2_State>
<!--Patient's State-->
<P1Line2_ZipCode>" . $_POST['ZipCode'] . "</P1Line2_ZipCode>
<!--Patient's zip-->
<Pt1Line3_Gender>" . $_POST['Gender'] . "</Pt1Line3_Gender>
<!--Gender of patient-->
<Pt1Line3_DateOfBirth>" . $newDate . "</Pt1Line3_DateOfBirth>
<!--DOB of patient-->
<Pt1Line3_CityTownVillageofBirth>" . $_POST['CityBirth'] . "</Pt1Line3_CityTownVillageofBirth>
<!--City of birth-->
<Pt1Line3_CountryofBirth>" . $_POST['CountryBirth'] . "</Pt1Line3_CountryofBirth>
<!--Country of birth-->
<Pt1Line3e_AlienNumber>" . $_POST['ANumber'] . "</Pt1Line3e_AlienNumber>
<!--A#-->
<Pt1Line3f_USCISOnlineAcctNumber>" . $_POST['USCIS'] . "</Pt1Line3f_USCISOnlineAcctNumber>
<Pt2Line3_DaytimePhone>" . $_POST['DaytimeTelephone'] . "</Pt2Line3_DaytimePhone>
<!--Patient's day time phone number-->
<Pt2Line4_Mobilephone>" . $_POST['MobileTelephone'] . "</Pt2Line4_Mobilephone>
<!--Patient's cell-->
<Pt2Line5_EmailAddress>" . $_POST['Emailaddres'] . "</Pt2Line5_EmailAddress>
<!--Patient's email -->";
		$output2 .= "<!--The text below is for Form prefill -->
<Pt7Line2_MedPracticeName>Great City Medical</Pt7Line2_MedPracticeName>
<Pt7Line3_StreetNumberName>51 Saint Nicholas Ave</Pt7Line3_StreetNumberName>
<Pt7Line3_AptSteFlrNumber></Pt7Line3_AptSteFlrNumber>
<Pt7Line3_CityOrTown>New York</Pt7Line3_CityOrTown>
<Pt7Line3_State>NY</Pt7Line3_State>
<Pt7Line3_ZipCode>10026</Pt7Line3_ZipCode>
<Pt7Line4_StreetNumberName>51 Saint Nicholas Ave</Pt7Line4_StreetNumberName>
<Pt7Line4_AptSteFlrNumber></Pt7Line4_AptSteFlrNumber>
<Pt7Line4_CityOrTown>New York</Pt7Line4_CityOrTown>
<Pt7Line4_State>NY</Pt7Line4_State>
<Pt7Line4_ZipCode>10026</Pt7Line4_ZipCode>
<Pt7Line5_DaytimePhone>2122818600</Pt7Line5_DaytimePhone>
<Pt7Line7_EmailAddress>admin@greatcitymedical.com</Pt7Line7_EmailAddress>
<Pt8Line1B1a_name>RPR</Pt8Line1B1a_name>
<Pt8Line1C1a_name>NG Urine PCR</Pt8Line1C1a_name>
<Pt10Line1_NotAge1>1</Pt10Line1_NotAge1>
<Pt10Line3_NotAge3>0</Pt10Line3_NotAge3>
<Pt10Line5_NotAge5>1</Pt10Line5_NotAge5>
<Pt10Line8_NotAge8>1</Pt10Line8_NotAge8>
<Pt10Line10_NotAge10>1</Pt10Line10_NotAge10>
<Pt10Line11_NotAge11>1</Pt10Line11_NotAge11>
<Pt10Line12_NotAge12>1</Pt10Line12_NotAge12>
<Pt10Line9_InfluVaccineInsufficient9>1</Pt10Line9_InfluVaccineInsufficient9>
<P10_Remarks>Currently not flu season.</P10_Remarks>
<Pt7Line3_Unit></Pt7Line3_Unit>
<Pt7Line4_Unit></Pt7Line4_Unit>
<Pt8Line2A_Disorders>1</Pt8Line2A_Disorders>
<Pt8Line1D1_Findings>a</Pt8Line1D1_Findings>
<Pt8Line3A_Findings>1</Pt8Line3A_Findings>
<Pt10_PVVaccineCheckBox>0</Pt10_PVVaccineCheckBox>
<P10_TDVaccineCheckBox>Td</P10_TDVaccineCheckBox>
<!--End of form prefill -->
</form1>
</xfa:data>
</xfa:datasets>
<pdf href=\"Z:/i693 Forms/1i-693-Office - 1unlocked.pdf\" xmlns=\"http://ns.adobe.com/xdp/pdf/\"/>
</xdp:xdp>";
		
		$output3 = "";
		$output3 .= $_POST['FirstName'] . "," . $_POST['LastName'] . "," . $_POST['dob'] . "," . $_POST['DaytimeTelephone'] . "," . $_POST['MobileTelephone'] . "," . $_POST['Emailaddres'] . "," . $_POST['find'] . "," . $_POST['examination'] . "," . $_POST['lawyer'] . "," . $_POST['lawyer-name'] . "," . $_POST['lawyer-company'] . "," . $_POST['lawyer-phone'] . "," . $today;
		
		
		$filename = $_POST['FirstName'] . "_" . $_POST['LastName'] . "_" . $FilenameDate . "_pt.xml";
		
		$Newsubject = "i693 Form Data " . $_POST['FirstName'] . "_" . $_POST['LastName'] . "_" . $FilenameDate;
		
		//file_put_contents("$filename", $output);
		file_put_contents("i693FormData.xml", $output);
		file_put_contents("i693FormData.xdp", $output2);
		file_put_contents("i693Survey.txt", $output3);
		
		// then send email
		
		$subject = "$Newsubject";
		//$body = "Please find the attachment for XML file";
		$loc = $_POST['location'];
		if ($loc == "1513V") {
			$FulLoc = "Location: 1513 Voorhies Ave 3rd Floor, Brooklyn, NY 11235";
		} else if ($loc == "51SN") {
			$FulLoc = "Location: 51 Saint Nicholas Ave, Ground Floor, New York, NY 10026";
		} else if ($loc == "68E") {
			// Old Address
			// $FulLoc = "Location: 68e 131st Street Suite 100, New York, NY 10037";
			$FulLoc = "Location: 51 Saint Nicholas Ave, Ground Floor, New York, NY 10026";
		} else {
			$FulLoc = "No location selected";
		}
		
		$body = "Please find the attachment for XML file." . "\r\n";
		$body .= $_POST['appointmentfield'] . "\n";
		$body .= $FulLoc;;
		
		$attachments2 = array("i693FormData.xml", "i693FormData.xdp", "i693Survey.txt");
		$attachments = array("$filename");
		
		// wp_mail($to, $subject, $body, $headers, $attachments);
		wp_mail($to, $subject, $body, $headers, $attachments2);
	}
}

/**
 * If the i693 included an insurance name, wrap the value such as: k^Hello^k
 * (The k^^k formatting was requested by Kirill so his script can alert staff members to get the proper insurance code for the form)
 *
 * @param array $posted_data
 *
 * @return array
 */
function gcm_add_prefix_to_insurance_id( $posted_data ) {
	
	if ( !empty( $posted_data['insurance_name'] ) ) {
		$posted_data['insurance_name'] = 'k^' . $posted_data['insurance_name'] . '^k';
	}
	
	/*
	if ( !empty( $posted_data['insurance_id'] ) ) {
		$posted_data['insurance_id'] = 'k^' . $posted_data['insurance_id'] . '^k';
	}
	*/
	
	return $posted_data;
}
add_filter( 'wpcf7_posted_data', 'gcm_add_prefix_to_insurance_id', 10, 1 );

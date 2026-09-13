<?php
/**
 * Optional i693 demographic exports. Select the real native CF7 form using
 * Additional Settings: gcm_export: i693. There is no installation-specific ID.
 * These files contain applicant-supplied intake, not certified medical findings.
 */
function gcm_i693_attachments($form, $data, $dob, $today) {
    if ($form->additional_setting('gcm_export', 0) !== array('i693')) {
        throw new UnexpectedValueException('Select exactly one i693 export adapter');
    }
    foreach (array('FirstName', 'LastName', 'dob') as $required) {
        if (!isset($data[$required]) || trim($data[$required]) === '') {
            throw new UnexpectedValueException('Missing applicant identity fields');
        }
    }
    // Field names map to the existing demographic import contract. No exam,
    // vaccination, laboratory, clinician attestation or practice-address defaults.
    $mapping = array(
        'Pt1Line1b_GivenName' => 'FirstName', 'Pt1Line1a_FamilyName' => 'LastName',
        'Pt1Line1c_MiddleName' => 'MiddleName', 'Pt1Line2_StreetNumberName' => 'Street',
        'Pt1Line2_Unit' => 'ApartmentType', 'Pt1Line2_AptSteFlrNumber' => 'Apartment',
        'P1Line2_CityOrTown' => 'CityTown', 'P1Line2_State' => 'State',
        'P1Line2_ZipCode' => 'ZipCode', 'Pt1Line3_Gender' => 'Gender',
        'Pt1Line3_CityTownVillageofBirth' => 'CityBirth', 'Pt1Line3_CountryofBirth' => 'CountryBirth',
        'Pt1Line3e_AlienNumber' => 'ANumber', 'Pt1Line3f_USCISOnlineAcctNumber' => 'USCIS',
        'Pt2Line3_DaytimePhone' => 'DaytimeTelephone', 'Pt2Line4_Mobilephone' => 'MobileTelephone',
        'Pt2Line5_EmailAddress' => 'Emailaddres',
    );
    $fields = "<form1>\n";
    foreach ($mapping as $xml_name => $input_name) {
        $fields .= '  <' . $xml_name . '>' . esc_xml($data[$input_name] ?? '') . '</' . $xml_name . ">\n";
    }
    $fields .= '  <Pt1Line3_DateOfBirth>' . esc_xml($dob) . "</Pt1Line3_DateOfBirth>\n</form1>\n";
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $fields;
    $xdp = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xdp .= '<xdp:xdp xmlns:xdp="http://ns.adobe.com/xdp/" timeStamp="' . esc_attr(gmdate('Y-m-d\TH:i:s\Z')) .
        '" uuid="' . esc_attr(wp_generate_uuid4()) . '">' . "\n";
    $xdp .= '<xfa:datasets xmlns:xfa="http://www.xfa.org/schema/xfa-data/1.0/"><xfa:data>' . "\n" . $fields .
        "</xfa:data></xfa:datasets>\n";
    $templates = $form->additional_setting('gcm_i693_pdf', 0);
    if (count($templates) > 1 || (count($templates) === 1 &&
        !preg_match('/^[A-Za-z0-9][A-Za-z0-9 _.-]{0,119}\.pdf$/D', $templates[0]))) {
        throw new UnexpectedValueException('PDF template must be a single local basename');
    }
    if ($templates) {
        // A clinician/operator-selected local template only: no network URL or old drive path.
        $xdp .= '<pdf xmlns="http://ns.adobe.com/xdp/pdf/" href="' . esc_attr($templates[0]) . "\"/>\n";
    }
    $xdp .= "</xdp:xdp>\n";
    $columns = array('FirstName', 'LastName', 'dob', 'DaytimeTelephone', 'MobileTelephone',
        'Emailaddres', 'find', 'examination', 'lawyer', 'lawyer-name', 'lawyer-company', 'lawyer-phone');
    $row = array_map(static function ($key) use ($data) {
        $value = $data[$key] ?? '';
        // CSV is data, not a spreadsheet instruction (including leading whitespace).
        return preg_match('/^[\s]*[=+@-]/u', $value) ? "'" . $value : $value;
    }, $columns);
    $row[] = $today;
    $stream = fopen('php://memory', 'w+');
    if (!$stream) {
        throw new RuntimeException('Could not create in-memory survey');
    }
    try {
        if (fputcsv($stream, $row, ',', '"', '', "\r\n") === false || !rewind($stream)) {
            throw new RuntimeException('Could not encode survey');
        }
        $survey = stream_get_contents($stream);
        if ($survey === false) {
            throw new RuntimeException('Could not read survey');
        }
    } finally {
        fclose($stream);
    }
    $attachments = array('i693FormData.xml' => $xml, 'i693FormData.xdp' => $xdp, 'i693Survey.txt' => $survey);
    if (!empty($data['appointmentfield']) || !empty($data['location'])) {
        $attachments['i693Appointment.txt'] = "Applicant-supplied appointment details; not verified against scheduling.\n" .
            ($data['appointmentfield'] ?? '') . "\nLocation selection: " . ($data['location'] ?? '') . "\n";
    }
    return $attachments;
}

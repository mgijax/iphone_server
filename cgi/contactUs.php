<?php

// Configuration Settings
$sendFrom = "Mobile App Feedback <donotreply@feedback.com>";
$sendTo = "mgi-help@jax.org"; // was jmrecla@gmail.com
$msgBody = "";
$contact_name = "";
$contact_email = "";
$contact_subject = "";
$contact_message = "";
$contact_rating = "";
$subject =     "Feedback on GenomeCompass";
$thanksURL =   "thanks.html";  //confirmation page
$rtnjsonobj->success = "false";


// Read in the form values from the app's contact form
// save the contact_name
if (isset($_GET['contact_name'])) {
	global $rtnjsonobj, $contact_name;
	$contact_name = $_GET['contact_name'];
	$rtnjsonobj->contact_name = $contact_name;
} // end of if

// save the contact_email
if (isset($_GET['contact_email'])) {
	global $rtnjsonobj, $contact_email;
	$contact_email = $_GET['contact_email'];
	$rtnjsonobj->contact_email = $contact_email;
} // end of if

// save the contact_subject
if (isset($_GET['contact_subject'])) {
	global $rtnjsonobj, $contact_subject;
	$contact_subject = $_GET['contact_subject'];
	$rtnjsonobj->contact_subject = $contact_subject;
} // end of if

// save the contact_message
if (isset($_GET['contact_message'])) {
	global $rtnjsonobj, $contact_message;
	$contact_message = $_GET['contact_message'];
//	$contact_message = wordwrap($message, 70); // message lines should not exceed 70 characters (PHP rule), so wrap it
	$rtnjsonobj->contact_message = $contact_message;
} // end of if

// save the contact_rating
if (isset($_GET['contact_rating'])) {
	global $rtnjsonobj, $contact_rating;
	$contact_rating = $_GET['contact_rating'];
	$rtnjsonobj->contact_rating = $contact_rating;
} // end of if


// Build Message Body from Web Form Input
$msgBody .= "Submitted by: " . $contact_name . "\n";
$msgBody .= "Reply-to: " . $contact_email . "\n";
$msgBody .= "Subject: " . $contact_subject . "\n";
$msgBody .= "Message: " . "\n" . $contact_message . "\n\n";
$msgBody .= "Rating: " . $contact_rating . "\n";


// Send E-Mail and Direct Browser to Confirmation Page
mail($sendTo, $contact_subject, $msgBody, "From: $sendFrom");
//header("Location: $ThanksURL");


// Wrap and write a JSON-formatted object with a function call, using the supplied value of parm 'callback' in the URL:
$rtnjsonobj->success = "true";
echo $_GET['callback']. '('. json_encode($rtnjsonobj) . ')';

exit;

?>
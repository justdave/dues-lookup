<?php

require "vendor/autoload.php";
use Dompdf\Dompdf;

add_shortcode( 'oa-card-printer', 'oadueslookup_card_printer' );

function oadueslookup_card_printer($attr)
{
    global $wpdb;
    $dbprefix = $wpdb->prefix . "oalm_";

    $action = 'showform';
    $missing = [];
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
    }
    if ($action == 'lookup') {
        $required_keys = ['last_name','bsaid'];
        $missing = array_keys(array_diff_key(array_flip($required_keys), $_POST));
        if (count($missing) > 0) {
            $action = "showform";
        } else {
            # look up member by last name + bsaid to confirm they match and get dues record
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ${dbprefix}member_cards WHERE last_name = %s AND bsaid = %s", $_POST['last_name'], $_POST['bsaid']));
            if (count($results) > 0) {
                $dues_years = [];
                $member = $results[0];
                unset($member['dues_year']);
                foreach ($results as $row) {
                    $dues_years[] = $row->dues_year;
                }
                $names = [];

                # Build name option 1 : $first $last $suffix
                $name = $member['first_name'] . " " . $member['last name'];
                if ($member['suffix'] != '') {
                    $name .= " " . $member['suffix'];
                }
                $names[] = $name;

                if ($member['middle_name'] != "") {
                    # Build name option 2 : $first $middle $last $suffix
                    $name = $member['first_name'];
                    $name .= " " . $member['middle_name'];
                    $name .= " " . $member['last_name'];
                    if ($member['suffix'] != '') {
                        $name .= " " . $member['suffix'];
                    }
                    $names[] = $name;

                    if (strlen($member['middle_name']) > 1) {
                        # Build name option 3 : $first $mid_init $last $suffix
                        $name = $member['first_name'];
                        $name .= " " . substr($member['middle_name'], 0, 1);
                        $name .= " " . $member['last_name'];
                        if ($member['suffix'] != '') {
                            $name .= " " . $member['suffix'];
                        }
                        $names[] = $name;
                    }
                }

                if ($member['nickname'] != "") {
                    #Build name option 4 : $nick $last $suffix
                    $name = $member['nickname'];
                    $name .= " " . $member['last_name'];
                    if ($member['suffix'] != '') {
                        $name .= " " . $member['suffix'];
                    }
                    $names[] = $name;

                    if ($member['suffix'] != '') {
                        # Build name option 5 : $nick $last (w/o $suffix)
                        # only add this if they have a suffix because option 4 will already be this if they didn't
                        $name = $member['nickname'] . " " . $member['last_name'];
                        $names[] = $name;
                    }
                }

                ob_start();
?><div class="oa_card_form">
<form method="POST">
Choose how to display your name:<br>
<select name="full_name"><?php
                foreach ($names as $name) {
                    ?><option value="<?php esc_html_e($name); ?>"><?php esc_html_e($name); ?></option><?php
                }
                ?></select><br>
Choose which dues year to print:<br>
<select name="dues_year"><?php
                foreach ($dues_years as $year) {
                    ?><option value="<?php esc_html_e($year); ?>"><?php esc_html_e($year); ?></option><?php
                }
                ?></select>
<input type="hidden" name="action" value="print">
<input type="Submit" name="Submit" value="Submit"></form></div><?php
                return ob_get_clean();
            }
            else {
                # TODO: failed to find any dues years.
            }

    }
    elseif ($action == 'print') {
        # full_name picked by radio buttons from available formats on pre-print screen (w/ or wo middle name, nick name, etc)
        $required_keys = ['last_name','full_name','bsaid','dues_year'];
        $missing = array_keys(array_diff_key(array_flip($required_keys), $_POST));
        if (count($missing) > 0) {
            $action = "showform";
        } else {
            # look up member by last name + bsaid to confirm they match and get dues record
            $results = $wpdb->get_row($wpdb->prepare("SELECT * FROM ${dbprefix}member_cards WHERE last_name = %s AND bsaid = %s AND dues_year = %s", $_POST['last_name'], $_POST['bsaid'], $_POST['dues_year']), ARRAY_A);

            # TODO: calculate level by checking against their dates to see what level they were at the end of the passed dues_year
            oadueslookup_print_card($name, $level, $unit, $bsaid, $duesyear);
            # this prints a PDF so it'll exit without returning to WordPress
        }
    }
    # the following is not an 'elseif' because other actions might change it back to this before we get here
    if ($action == 'showform') {
        $output = "";
        ob_start();
        if (count($missing) > 0) {
            ?><div class="error">Incorrect fields were submitted, try again.</div><?php
        }
        ?><div class="oa_cardprint"><form method="POST">
        <input type="text" name="last_name"><br>
        <input type="text" name="bsaid"><br>
        <input type="hidden" name="action" value="lookup">
        <input type="submit" name="Submit" value="Submit">
        </form></div><?php
        return ob_get_clean();
    }
}

function oadueslookup_print_card(
    $name = "Johnny Arrowman",
    $level = "Brotherhood",
    $unit = "Troop 1234",
    $bsaid = "123456789",
    $duesyear = "2019")
{

    $secretary = "Kyle Peters",
    $expires = "12/31/${duesyear}",
    $cardrev = "2013");
    if (2018 < $duesyear) {
        $cardrev = "2018";
    }

    $html = "";

    if (true) { // (isset($_POST['names'])) {
      ob_start();
      ?><!DOCTYPE html>
      <html>
      <head>
      <title>Online Membership Card</title>
      <style type="text/css"><!--
    @page {
        size: letter;
        margin: 0.5in;
    }
    html {
        height: 98%;
        width: 98%
    }
    body {
        font-family: 'DejaVu Sans';
        font-size: 9pt;
        height: 100%;
        width: 100%;
    }
    p {
        margin: 0;
        font-size: 7pt;
    }
    .banner {
        text-align: center;
    }
    .title h1 {
        margin-top: 0px;
        text-align: center;
    }
    .fold {
        width: 7.02in;
        height: 1em;
        color: black;
        text-align: center;
    }
    .foldtext {
        font-size: 8pt;
    }
    .foldmark {
        font-size: 12pt;
    }
    .topfold {
        position: absolute;
        top: 1.27in;
        left: 0.25in;
    }
    .bottomfold {
        position: absolute;
        top: 3.41in;
        left: 0.25in;
    }
    .topfoldtext {
        position: absolute;
        top: 1.2in;
        left: 0.25in;
    }
    .bottomfoldtext {
        position: absolute;
        top: 3.61in;
        left: 0.25in;
    }
    .instructions {
        position: absolute;
        top: 4in;
        left: 0.25in;
    }
    <?php

      if ($cardrev == "2013") {
    ?>
    .front {
        border: 1px solid gray;
        background-image: url('pdfassets/cardfront2013.png');
        background-color: white;
        background-repeat: no-repeat;
        background-position: top left;
        background-size: 3.5in 2in;
        position: absolute;
        top: 1.5in;
        left: 0.25in;
        width: 3.5in;
        height: 2in;
    }
    .back {
        border: 1px solid gray;
        background-image: url('pdfassets/cardback2013.png');
        background-color: white;
        background-repeat: no-repeat;
        background-position: top left;
        background-size: 3.5in 2in;
        position: absolute;
        top: 1.5in;
        left: 3.76in;
        width: 3.5in;
        height: 2in;
    }
    .name {
        width: 3.5in;
        height: 1.15em;
        position: relative;
        top: 0.5in;
        left: 0in;
        text-align: center;
        color: black;
        font-size: 12pt;
        font-weight: bold;
    }
    .level {
        width: 3.5in;
        height: 1em;
        position: relative;
        top: 0.5in;
        left: 0in;
        text-align: center;
        color: black;
        font-size: 10pt;
    }
    .lodge {
        width: 3.5in;
        height: 1.15em;
        position: relative;
        top: 0.5in;
        left: 0in;
        text-align: center;
        color: black;
        font-size: 12pt;
        font-weight: bold;
    }
    .unit {
        width: 3.5in;
        height: 1em;
        position: relative;
        top: 0.6in;
        left: 0in;
        text-align: center;
        color: black;
        font-size: 8pt;
    }
    <?php
      }
      if ($cardrev == "2018") {
    ?>
    .front {
        border: 1px solid gray;
        background-image: url('pdfassets/cardfront2018.png');
        background-color: white;
        background-repeat: no-repeat;
        background-position: top left;
        background-size: 3.5in 2in;
        position: absolute;
        top: 1.5in;
        left: 0.25in;
        width: 3.5in;
        height: 2in;
    }
    .back {
        border: 1px solid gray;
        background-image: url('pdfassets/cardback2018.png');
        background-color: white;
        background-repeat: no-repeat;
        background-position: top left;
        background-size: 3.5in 2in;
        position: absolute;
        top: 1.5in;
        left: 3.76in;
        width: 3.5in;
        height: 2in;
    }
    .name {
        width: 3.5in;
        height: 1.15em;
        position: relative;
        top: 0.5in;
        left: 0in;
        text-align: center;
        color: black;
        font-size: 12pt;
        font-weight: bold;
    }
    .level {
        width: 3.5in;
        height: 1em;
        position: relative;
        top: 0.5in;
        left: 0in;
        text-align: center;
        color: black;
        font-size: 10pt;
    }
    .lodge {
        width: 3.5in;
        height: 1.15em;
        position: relative;
        top: 0.5in;
        left: 0in;
        text-align: center;
        color: black;
        font-size: 12pt;
        font-weight: bold;
    }
    .unit {
        width: 3.5in;
        height: 1em;
        position: relative;
        top: 0.6in;
        left: 0in;
        text-align: center;
        color: black;
        font-size: 8pt;
    }
    <?php
      }
    ?>
      --></style>
      </head>
      <body>
      <div class="banner"><img width="1474" height="250" src="pdfassets/NSLodgeHeader.png" type="image/png"></div>
      <div class="title"><h1>Your Membership Card</h1></div>
      <div class="fold topfoldtext foldtext">FOLD</div>
      <div class="fold topfold foldmark">▼</div>
      <div class="front">
        <div class="name"><?php echo htmlspecialchars($name) ?></div>
        <div class="level">is a <?php echo htmlspecialchars($level) ?> member in</div>
        <div class="lodge">Nataepu Shohpe Lodge</div>
        <div class="unit"><?php echo htmlspecialchars($unit) ?></div>
        <div class="signature"><?php echo htmlspecialchars($secretary) ?><hr>Netami Lekhiket</div>
        <div class="expiration"><?php echo htmlspecialchars($expires) ?><hr>Expiration Date</div>
      </div><div class="back"></div>
      <div class="bottomfold fold foldmark">▲</div>
      <div class="bottomfoldtext fold foldtext">FOLD</div>
      <div class="instructions">
      <h2>Instructions:</h2>
      <ol>
      <li>Print this PDF, preferably on good quality paper.</li>
      <li>Sign your name in the space marked on the back side.</li>
      <li>Cut out the card above on the gray lines.</li>
      <li>Fold in half where marked.</li>
      <li>Place the card in your wallet or other place you can have it handy when you need it.</li>
      </ol>
      </div>
      </body>
    </html><?php
      $html = ob_get_clean();
    }

    if ($html == "") {
        echo "No names were entered. Please use your browser's back button and try again.";
    } else {
        $DEBUG = 0;
        if ($DEBUG) {
            echo $html;
        } else {
            $dompdf = new Dompdf();
            $dompdf->set_option('isHtml5ParserEnabled', true);
            $dompdf->set_option('dpi', 300);
            $dompdf->setPaper('letter','portrait');
            $dompdf->loadHtml($html);
            $dompdf->render();
            $dompdf->stream("OAMembershipCard.pdf");
            //$dompdf->stream("ballot.pdf", array("Attachment"=>0));
            die; // don't return to WordPress because we already sent data to the user
        }
    }
}

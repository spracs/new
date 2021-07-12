<?php
/**
 * --------------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of useditemsexport.
 *
 * useditemsexport is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * useditemsexport is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * --------------------------------------------------------------------------
 * @author    François Legastelois
 * @copyright Copyright © 2015 - 2018 Teclib'
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/pluginsGLPI/useditemsexport
 * @link      https://pluginsglpi.github.io/useditemsexport/
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
   require_once $autoload;
} else {
   echo __('Run "composer install --no-dev" in the plugin tree', 'useditemsexport');
   die();
}

class PluginUseditemsexportExport extends CommonDBTM {

   public static $rightname = 'plugin_useditemsexport_export';

   static function getTypeName($nb = 0) {

      return __('Used items export', 'useditemsexport');
   }

   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType()=='User') {
         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry(self::getTypeName(), self::countForItem($item));
         }
         return self::getTypeName();
      }
      return '';
   }

   /**
    * @see CommonGLPI::displayTabContentForItem()
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      global $CFG_GLPI;

      if ($item->getType()=='User') {
         if (Session::haveRightsOr('plugin_useditemsexport_export', [READ, CREATE, PURGE])) {

            $PluginUseditemsexportExport = new self();
            $PluginUseditemsexportExport->showForUser($item);

         } else {
            echo "<div align='center'><br><br><img src=\"" . $CFG_GLPI["root_doc"] .
                     "/pics/warning.png\" alt=\"warning\"><br><br>";
            echo "<b>" . __("Access denied") . "</b></div>";
         }

      }
   }

   /**
    * @param $item    CommonDBTM object
   **/
   public static function countForItem(CommonDBTM $item) {
      return countElementsInTable(getTableForItemType(__CLASS__), ['users_id' => $item->getID()]);
   }

   /**
    * Get all generated export for user.
    *
    * @param $users_id user ID
    *
    * @return array of exports
   **/
   static function getAllForUser($users_id) {
      global $DB;

      $exports = [];

      // Get default one
      foreach ($DB->request(getTableForItemType(__CLASS__), "`users_id` = '$users_id'") as $data) {
         $exports[$data['id']] = $data;
      }

      return $exports;
   }

   /**
    * @param CommonDBTM $item
    * @param array $options
    * @return nothing
    */
   function showForUser($item, $options = []) {
      global $DB, $CFG_GLPI;

      $users_id = $item->getField('id');

      $exports = self::getAllForUser($users_id);

      $canpurge = self::canPurge();
      $cancreate = self::canCreate();

      if ($cancreate) {
         $rand = mt_rand();

         echo "<div class='center'>";
         echo "<form method='post' name='useditemsexport_form$rand' id='useditemsexport_form$rand'
                  action=\"" . Plugin::getWebDir('useditemsexport') . "/front/export.form.php\">";

         echo "<table class='tab_cadre_fixehov'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>".__('Generate new export', 'useditemsexport');
               echo "&nbsp;&nbsp<input type='submit' name='generate' value=\"".__('Create')."\" class='submit'>";
               echo "<input type='hidden' name='users_id' value='$users_id'>";
            echo "</th></tr>";
         echo "</table>";

         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='center'>";

      if ($canpurge && count($exports) > 0) {
         $rand = mt_rand();
         Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
         $massiveactionparams = ['item' => $item, 'container' => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='" . ($canpurge ? 5 : 4) . "'>"
                     . __('Used items export generated', 'useditemsexport') . "</th></tr><tr>";

      if (count($exports) == 0) {

         echo "<tr class='tab_bg_1'>";
            echo "<td class='center' colspan='" . ($canpurge ? 5 : 4) . "'>"
                     .__('No item to display')."</td>";
         echo "</tr>";

      } else {

         if ($canpurge) {
            echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
         }
         echo "<th>" . __('Reference number of export', 'useditemsexport') . "</th>";
         echo "<th>" . __('Date of export', 'useditemsexport') . "</th>";
         echo "<th>" . __('Author of export', 'useditemsexport') . "</th>";
         echo "<th>" . __('Export document', 'useditemsexport') . "</th>";
         echo "</tr>";

         foreach ($exports as $data) {
            echo "<tr class='tab_bg_1'>";

            if ($canpurge) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
               echo "</td>";
            }

               echo "<td class='center'>";
               echo $data["refnumber"];
               echo "</td>";

               echo "<td class='center'>";
               echo Html::convDateTime($data["date_mod"]);
               echo "</td>";

               $User = new User();
               $User->getFromDB($data['authors_id']);
               echo "<td class='center'>";
               echo $User->getLink();
               echo "</td>";

               $Doc = new Document();
               $Doc->getFromDB($data['documents_id']);
               echo "<td class='center'>";
               echo $Doc->getDownloadLink();
               echo "</td>";
            echo "</tr>";
         }

      }

      echo "</table>";
      if ($canpurge && count($exports) > 0) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();

      }
      echo "</div>";

   }


   /**
    * Generate PDF for user and add entry into DB
    *
    * @param $users_id user ID
    *
    * @return array of exports
   **/
   static function generatePDF($users_id) {

      $num       = self::getNextNum();
      $refnumber = self::getNextRefnumber();

      if (isset($_SESSION['plugins']['useditemsexport']['config'])) {
         $useditemsexport_config = $_SESSION['plugins']['useditemsexport']['config'];
      }

      // Compile address from current_entity
      $entity = new Entity();
      $entity->getFromDB($_SESSION['glpiactive_entity']);
      $entity_address = '<h3>' . $entity->fields["name"] . '</h3><br />';
      $entity_address.= $entity->fields["address"] . '<br />';
      $entity_address.= $entity->fields["postcode"] . ' - ' . $entity->fields['town'] . '<br />';
      $entity_address.= $entity->fields["country"].'<br />';

      if (isset($entity->fields["email"])) {
         $entity_address.= __('Email') . ' : ' . $entity->fields["email"] . '<br />';
      }

      if (isset($entity->fields["phonenumber"])) {
         $entity_address.= __('Phone') . ' : ' . $entity->fields["phonenumber"] . '<br />';
      }

      // Get User information
      $User = new User();
      $User->getFromDB($users_id);

      // Get Author information
      $Author = new User();
      $Author->getFromDB(Session::getLoginUserID());

      // Logo
      $logo = GLPI_PLUGIN_DOC_DIR.'/useditemsexport/logo_fmedia.png';

      ob_start();
      ?>
      <style type="text/css">
table { 
border-spacing: 0;
border-collapse: collapse;
width: 100%;
font-family: freesans;
}

th,td {
padding: 5px;
}

/*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */

/* Document
========================================================================== */

/**
* 1. Correct the line height in all browsers.
* 2. Prevent adjustments of font size after orientation changes in iOS.
*/

html {
line-height: 1.15; /* 1 */
-webkit-text-size-adjust: 100%; /* 2 */
}

/* Sections
========================================================================== */

/**
* Remove the margin in all browsers.
*/

body {
margin: 20px;
}

/**
* Render the `main` element consistently in IE.
*/

main {
display: block;
}

/**
* Correct the font size and margin on `h1` elements within `section` and
* `article` contexts in Chrome, Firefox, and Safari.
*/

h1 {
font-size: 2em;
margin: 0.67em 0;
}

/* Grouping content
========================================================================== */

/**
* 1. Add the correct box sizing in Firefox.
* 2. Show the overflow in Edge and IE.
*/

hr {
box-sizing: content-box; /* 1 */
height: 0; /* 1 */
overflow: visible; /* 2 */
}

/**
* 1. Correct the inheritance and scaling of font size in all browsers.
* 2. Correct the odd `em` font sizing in all browsers.
*/

pre {
font-family: monospace, monospace; /* 1 */
font-size: 1em; /* 2 */
}

/* Text-level semantics
========================================================================== */

/**
* Remove the gray background on active links in IE 10.
*/

a {
background-color: transparent;
}

/**
* 1. Remove the bottom border in Chrome 57-
* 2. Add the correct text decoration in Chrome, Edge, IE, Opera, and Safari.
*/

abbr[title] {
border-bottom: none; /* 1 */
text-decoration: underline; /* 2 */
text-decoration: underline dotted; /* 2 */
}

/**
* Add the correct font weight in Chrome, Edge, and Safari.
*/

b,
strong {
font-weight: bolder;
}

/**
* 1. Correct the inheritance and scaling of font size in all browsers.
* 2. Correct the odd `em` font sizing in all browsers.
*/

code,
kbd,
samp {
font-family: monospace, monospace; /* 1 */
font-size: 1em; /* 2 */
}

/**
* Add the correct font size in all browsers.
*/

small {
font-size: 80%;
}

/**
* Prevent `sub` and `sup` elements from affecting the line height in
* all browsers.
*/

sub,
sup {
font-size: 75%;
line-height: 0;
position: relative;
vertical-align: baseline;
}

sub {
bottom: -0.25em;
}

sup {
top: -0.5em;
}

/* Embedded content
========================================================================== */

/**
* Remove the border on images inside links in IE 10.
*/

img {
border-style: none;
}

/* Forms
========================================================================== */

/**
* 1. Change the font styles in all browsers.
* 2. Remove the margin in Firefox and Safari.
*/

button,
input,
optgroup,
select,
textarea {
font-family: inherit; /* 1 */
font-size: 100%; /* 1 */
line-height: 1.15; /* 1 */
margin: 0; /* 2 */
}

/**
* Show the overflow in IE.
* 1. Show the overflow in Edge.
*/

button,
input { /* 1 */
overflow: visible;
}

/**
* Remove the inheritance of text transform in Edge, Firefox, and IE.
* 1. Remove the inheritance of text transform in Firefox.
*/

button,
select { /* 1 */
text-transform: none;
}

/**
* Correct the inability to style clickable types in iOS and Safari.
*/

button,
[type="button"],
[type="reset"],
[type="submit"] {
-webkit-appearance: button;
}

/**
* Remove the inner border and padding in Firefox.
*/

button::-moz-focus-inner,
[type="button"]::-moz-focus-inner,
[type="reset"]::-moz-focus-inner,
[type="submit"]::-moz-focus-inner {
border-style: none;
padding: 0;
}

/**
* Restore the focus styles unset by the previous rule.
*/

button:-moz-focusring,
[type="button"]:-moz-focusring,
[type="reset"]:-moz-focusring,
[type="submit"]:-moz-focusring {
outline: 1px dotted ButtonText;
}

/**
* Correct the padding in Firefox.
*/

fieldset {
padding: 0.35em 0.75em 0.625em;
}

/**
* 1. Correct the text wrapping in Edge and IE.
* 2. Correct the color inheritance from `fieldset` elements in IE.
* 3. Remove the padding so developers are not caught out when they zero out
*    `fieldset` elements in all browsers.
*/

legend {
box-sizing: border-box; /* 1 */
color: inherit; /* 2 */
display: table; /* 1 */
max-width: 100%; /* 1 */
padding: 0; /* 3 */
white-space: normal; /* 1 */
}

/**
* Add the correct vertical alignment in Chrome, Firefox, and Opera.
*/

progress {
vertical-align: baseline;
}

/**
* Remove the default vertical scrollbar in IE 10+.
*/

textarea {
overflow: auto;
}

/**
* 1. Add the correct box sizing in IE 10.
* 2. Remove the padding in IE 10.
*/

[type="checkbox"],
[type="radio"] {
box-sizing: border-box; /* 1 */
padding: 0; /* 2 */
}

/**
* Correct the cursor style of increment and decrement buttons in Chrome.
*/

[type="number"]::-webkit-inner-spin-button,
[type="number"]::-webkit-outer-spin-button {
height: auto;
}

/**
* 1. Correct the odd appearance in Chrome and Safari.
* 2. Correct the outline style in Safari.
*/

[type="search"] {
-webkit-appearance: textfield; /* 1 */
outline-offset: -2px; /* 2 */
}

/**
* Remove the inner padding in Chrome and Safari on macOS.
*/

[type="search"]::-webkit-search-decoration {
-webkit-appearance: none;
}

/**
* 1. Correct the inability to style clickable types in iOS and Safari.
* 2. Change font properties to `inherit` in Safari.
*/

::-webkit-file-upload-button {
-webkit-appearance: button; /* 1 */
font: inherit; /* 2 */
}

/* Interactive
========================================================================== */

/*
* Add the correct display in Edge, IE 10+, and Firefox.
*/

details {
display: block;
}

/*
* Add the correct display in all browsers.
*/

summary {
display: list-item;
}

/* Misc
========================================================================== */

/**
* Add the correct display in IE 10+.
*/

template {
display: none;
}

/**
* Add the correct display in IE 10.
*/

[hidden] {
display: none;
}



/*! HTML5 Boilerplate v8.0.0 | MIT License | https://html5boilerplate.com/ */

/* main.css 2.1.0 | MIT License | https://github.com/h5bp/main.css#readme */
/*
* What follows is the result of much research on cross-browser styling.
* Credit left inline and big thanks to Nicolas Gallagher, Jonathan Neal,
* Kroc Camen, and the H5BP dev community and team.
*/

/* ==========================================================================
Base styles: opinionated defaults
========================================================================== */

html {
color: #222;
font-size: 1em;
line-height: 1.4;
}

/*
* Remove text-shadow in selection highlight:
* https://twitter.com/miketaylr/status/12228805301
*
* Vendor-prefixed and regular ::selection selectors cannot be combined:
* https://stackoverflow.com/a/16982510/7133471
*
* Customize the background color to match your design.
*/

::-moz-selection {
background: #b3d4fc;
text-shadow: none;
}

::selection {
background: #b3d4fc;
text-shadow: none;
}

/*
* A better looking default horizontal rule
*/

hr {
display: block;
height: 1px;
border: 0;
border-top: 1px solid #ccc;
margin: 1em 0;
padding: 0;
}

/*
* Remove the gap between audio, canvas, iframes,
* images, videos and the bottom of their containers:
* https://github.com/h5bp/html5-boilerplate/issues/440
*/

audio,
canvas,
iframe,
img,
svg,
video {
vertical-align: middle;
}

/*
* Remove default fieldset styles.
*/

fieldset {
border: 0;
margin: 0;
padding: 0;
}

/*
* Allow only vertical resizing of textareas.
*/

textarea {
resize: vertical;
}

/* ==========================================================================
Author's custom styles
========================================================================== */

/* ==========================================================================
Helper classes
========================================================================== */

/*
* Hide visually and from screen readers
*/

.hidden,
[hidden] {
display: none !important;
}

/*
* Hide only visually, but have it available for screen readers:
* https://snook.ca/archives/html_and_css/hiding-content-for-accessibility
*
* 1. For long content, line feeds are not interpreted as spaces and small width
*    causes content to wrap 1 word per line:
*    https://medium.com/@jessebeach/beware-smushed-off-screen-accessible-text-5952a4c2cbfe
*/

.sr-only {
border: 0;
clip: rect(0, 0, 0, 0);
height: 1px;
margin: -1px;
overflow: hidden;
padding: 0;
position: absolute;
white-space: nowrap;
width: 1px;
/* 1 */
}

/*
* Extends the .sr-only class to allow the element
* to be focusable when navigated to via the keyboard:
* https://www.drupal.org/node/897638
*/

.sr-only.focusable:active,
.sr-only.focusable:focus {
clip: auto;
height: auto;
margin: 0;
overflow: visible;
position: static;
white-space: inherit;
width: auto;
}

/*
* Hide visually and from screen readers, but maintain layout
*/

.invisible {
visibility: hidden;
}

/*
* Clearfix: contain floats
*
* For modern browsers
* 1. The space content is one way to avoid an Opera bug when the
*    `contenteditable` attribute is included anywhere else in the document.
*    Otherwise it causes space to appear at the top and bottom of elements
*    that receive the `clearfix` class.
* 2. The use of `table` rather than `block` is only necessary if using
*    `:before` to contain the top-margins of child elements.
*/

.clearfix::before,
.clearfix::after {
content: " ";
display: table;
}

.clearfix::after {
clear: both;
}

/* ==========================================================================
EXAMPLE Media Queries for Responsive Design.
These examples override the primary ('mobile first') styles.
Modify as content requires.
========================================================================== */

@media only screen and (min-width: 35em) {
/* Style adjustments for viewports that meet the condition */
}

@media print,
(-webkit-min-device-pixel-ratio: 1.25),
(min-resolution: 1.25dppx),
(min-resolution: 120dpi) {
/* Style adjustments for high resolution devices */
}

/* ==========================================================================
Print styles.
Inlined to avoid the additional HTTP request:
https://www.phpied.com/delay-loading-your-print-css/
========================================================================== */

@media print {
*,
*::before,
*::after {
background: #fff !important;
color: #000 !important;
/* Black prints faster */
box-shadow: none !important;
text-shadow: none !important;
}

a,
a:visited {
text-decoration: underline;
}

a[href]::after {
content: " (" attr(href) ")";
}

abbr[title]::after {
content: " (" attr(title) ")";
}

/*
* Don't show links that are fragment identifiers,
* or use the `javascript:` pseudo protocol
*/
a[href^="#"]::after,
a[href^="javascript:"]::after {
content: "";
}

pre {
white-space: pre-wrap !important;
}

pre,
blockquote {
border: 1px solid #999;
page-break-inside: avoid;
}

/*
* Printing Tables:
* https://web.archive.org/web/20180815150934/http://css-discuss.incutio.com/wiki/Printing_Tables
*/
thead {
display: table-header-group;
}

tr,
img {
page-break-inside: avoid;
}

p,
h2,
h3 {
orphans: 3;
widows: 3;
}

h2,
h3 {
page-break-after: avoid;
}
}


page_footer {
position: absolute;
bottom: 0;
margin: 20px 50%;
}
      </style>
      <page backtop="70mm" backleft="10mm" backright="10mm" backbottom="30mm">
         <page_header>
            <table>
               <tr><td style="text-align: center; width: 100%"><img src="<?php echo $logo; ?>" /></td></tr>
               <tr><td style="text-align: center; width: 100%; height: 30mm; font-size: 20pt">
                     <?php echo __('Acceptance certificate', 'useditemsexport'); ?>
	          </td>
	       </tr>
            </table>
         </page_header>

         <table>
            <tr>
               <td style="border: 1px solid #000000; text-align: center; width: 100%; font-size: 15pt; height: 8mm;">
                  <?php echo __('Asset export ref : ', 'useditemsexport') . $refnumber; ?>
               </td>
            </tr>
         </table>

         <br><br><br><br><br>
         <table>
            <tr>
               <th style="border: 1px solid #000000; width: 25%;">
                  <?php echo __('Name'); ?>
               </th>
               <th style="border: 1px solid #000000; width: 25%;">
                  <?php echo __('Type'); ?>
               </th>
	       <th style="border: 1px solid #000000; width: 37%;">
                  <?php echo __('Serial number'); ?>
               </th>
               <th style="border: 1px solid #000000; width: 13%;">
                  <?php echo __('Inv. number', 'useditemsexport'); ?>
               </th>
            </tr>
            <?php

            $allUsedItemsForUser = self::getAllUsedItemsForUser($users_id);

            foreach ($allUsedItemsForUser as $itemtype => $used_items) {

               $item = getItemForItemtype($itemtype);

               foreach ($used_items as $item_datas) {

                  ?>
            <tr>
               <td style="border: 1px solid #000000; width: 25%;">
                  <?php echo $item_datas['name']; ?>
               </td>
               <td style="border: 1px solid #000000; width: 25%;">
                  <?php echo $item->getTypeName(1); ?>
               </td>
               <td style="border: 1px solid #000000; width: 37%;">
                  <?php
                  if (isset($item_datas['serial'])) {
                     echo $item_datas['serial'];
                  } ?>
               </td>
               <td style="border: 1px solid #000000; width: 13%;">
                  <?php
                  if (isset($item_datas['otherserial'])) {
                     echo $item_datas['otherserial'];
                  } ?>
               </td>
            </tr>
                  <?php

               }
            }

            ?>
         </table>
         <br><br><br><br><br>
         <table style="border-collapse: collapse;">
            <tr>
               <td style="width: 50%; border-bottom: 1px solid #000000;">
                  <strong><?php echo __('Issued', 'useditemsexport'); ?>:<br>
		  <?php echo $Author->getFriendlyName(); ?> </strong>
               </td>
               <td style="width: 50%; border-bottom: 1px solid #000000">
                  <strong><?php echo __('Received', 'useditemsexport'); ?>:<br>
		  <?php echo $User->getFriendlyName(); ?> </strong>
               </td>
            </tr>
            <tr>
               <td style="border: 1px solid #000000; width: 50%; vertical-align: top">
                  <?php echo __('Signature', 'useditemsexport'); ?> : <br><br><br><br><br>
               </td>
               <td style="border: 1px solid #000000; width: 50%; vertical-align: top;">
                  <?php echo __('Signature', 'useditemsexport'); ?> : <br><br><br><br><br>
               </td>
            </tr>
         </table>
         <page_footer>
            <div style="width: 100%; text-align: center; font-size: 8pt">
               - <?php echo $useditemsexport_config['footer_text']; ?> -
	       <br><?php echo date("Y-m-d H:i:s"); ?>
            </div>
         </page_footer>
      </page>
      <?php
      $content = ob_get_clean();
file_put_contents('/tmp/test.html', $content);
      // Generate PDF with HTML2PDF lib
      $pdf = new HTML2PDF($useditemsexport_config['orientation'],
                          $useditemsexport_config['format'],
                          $useditemsexport_config['language'],
                          true,
                          'UTF-8'
      );

      $pdf->pdf->SetDisplayMode('fullpage');
      $pdf->writeHTML($content);

      $contentPDF = $pdf->Output('', 'S');

      // Store PDF in GLPi upload dir and create document
      file_put_contents(GLPI_UPLOAD_DIR . '/' . $refnumber.'.pdf', $contentPDF);
      $documents_id = self::createDocument($refnumber);

      // Add log for last generated PDF
      $export = new self();

      $input = [];
      $input['users_id']     = $users_id;
      $input['date_mod']     = date("Y-m-d H:i:s");
      $input['num']          = $num;
      $input['refnumber']    = $refnumber;
      $input['authors_id']   = Session::getLoginUserID();
      $input['documents_id'] = $documents_id;

      if ($export->add($input)) {
         return true;
      }

      return false;
   }

   /**
    * Store Document into GLPi DB
    * @param refnumber
    * @return integer id of Document
    */
   static function createDocument($refnumber) {

      $doc = new Document();

      $input                          = [];
      $input["entities_id"]           = $_SESSION['glpiactive_entity'];
      $input["name"]                  = __('Used-Items-Export', 'useditemsexport').'-'.$refnumber;
      $input["upload_file"]           = $refnumber.'.pdf';
      $input["documentcategories_id"] = 0;
      $input["mime"]                  = "application/pdf";
      $input["date_mod"]              = date("Y-m-d H:i:s");
      $input["users_id"]              = Session::getLoginUserID();

      $doc->check(-1, CREATE, $input);
      $newdocid=$doc->add($input);

      return $newdocid;
   }

   /**
    * Get next num
    * @param nothing
    * @return integer
    */
   static function getNextNum() {
      global $DB;

      $query = "SELECT MAX(num) as num
                  FROM " . self::getTable();

      $result = $DB->query($query);
      $nextNum = $DB->result($result, 0, 'num');
      if (!$nextNum) {
         return 1;
      } else {
         $nextNum++;
         return $nextNum;
      }

      return false;
   }

   /**
    * Compute next refnumber
    * @param nothing
    * @return string
    */
   static function getNextRefnumber() {
      global $DB;

      if ($nextNum = self::getNextNum()) {
         $nextRefnumber = str_pad($nextNum, 4, "0", STR_PAD_LEFT);
         $date = new DateTime();
         return $nextRefnumber . '-' . $date->format('Y');
      } else {
         return false;
      }
   }

   /**
    * Get all used items for user
    * @param ID of user
    * @return array
    */
   static function getAllUsedItemsForUser($ID) {
      global $DB, $CFG_GLPI;

      $items = [];

      foreach ($CFG_GLPI['linkuser_types'] as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($item->canView()) {
            $itemtable = getTableForItemType($itemtype);
            $query = "SELECT *
                      FROM `$itemtable`
                      WHERE `users_id` = '$ID'";

            if ($item->maybeTemplate()) {
               $query .= " AND `is_template` = '0' ";
            }
            if ($item->maybeDeleted()) {
               $query .= " AND `is_deleted` = '0' ";
            }
            $result    = $DB->query($query);

            $type_name = $item->getTypeName();

            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetchAssoc($result)) {
                  $items[$itemtype][] = $data;
               }
            }
         }
      }

      // Consumables
      $consumables = $DB->request(
         [
            'SELECT' => ['name', 'otherserial'],
            'FROM'   => ConsumableItem::getTable(),
            'WHERE'  => [
               'id' => new QuerySubQuery(
                  [
                     'SELECT' => 'consumableitems_id',
                     'FROM'   => Consumable::getTable(),
                     'WHERE'  => [
                        'itemtype' => User::class,
                        'items_id' => $ID
                     ],
                  ]
               )
            ],
         ]
      );

      foreach ($consumables as $data) {
         $items['ConsumableItem'][] = $data;
      }

      return $items;
   }

   /**
    * Clean GLPi DB on export purge
    *
    * @return nothing
    */
   function cleanDBonPurge() {

      // Clean Document GLPi
      $doc = new Document();
      $doc->getFromDB($this->fields['documents_id']);
      $doc->delete(['id' => $this->fields['documents_id']], true);
   }

   /**
    * Install all necessary tables for the plugin
    *
    * @return boolean True if success
    */
   static function install(Migration $migration) {
      global $DB;

      $table = getTableForItemType(__CLASS__);

      if (!$DB->tableExists($table)) {
         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `users_id` INT(11) NOT NULL DEFAULT '0',
                  `date_mod` TIMESTAMP NULL DEFAULT NULL,
                  `num` SMALLINT(2) NOT NULL DEFAULT 0,
                  `refnumber` VARCHAR(9) NOT NULL DEFAULT '0000-0000',
                  `authors_id` INT(11) NOT NULL DEFAULT '0',
                  `documents_id` INT(11) NOT NULL DEFAULT '0',
               PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
            $DB->query($query) or die ($DB->error());
      }
   }

   /**
    * Uninstall previously installed tables of the plugin
    *
    * @return boolean True if success
    */
   static function uninstall() {
      global $DB;

      $table = getTableForItemType(__CLASS__);

      $query = "DROP TABLE IF EXISTS  `".$table."`";
      $DB->query($query) or die ($DB->error());
   }

}

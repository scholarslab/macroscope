<?php

date_default_timezone_set("UTC");

$qtsdata = file('QTS_texts_clean.txt', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);

$citationsCSV = fopen("citations.CSV", "w");
fputcsv($citationsCSV, array("id","doi","title","author","journaltitle","volume","issue","pubdate","pagerange","publisher","type","reviewed-work"));
$metaCSV = fopen("meta.csv", "w");

$malletFile = fopen("mallet_input.txt", "w");
//$RmalletFile = fopen("rmallet_input.csv", "w");
//fwrite($RmalletFile, "id,text\n");

$docNumber = 0;
$blankLines = 0;
$duplicateNumbers = 0;
$concatenatedPoems = 0;
$blankPoems = 0;

$metadataHack = false;

$writtenDocs = 0;

$docTitle = "";
$docAuthor = "";

$docIDs = array();

$UTCtz = new DateTimeZone('GMT');
$docDate = DateTime::createFromFormat('Y-m-d H:i:s', '0618-01-01 00:00:00', $UTCtz);

$poemLines = 0;
$metadataErrors = 0;

foreach ($qtsdata as $qtsline) {

  if (preg_match('/^卷\s([0-9\s_]*?)《(.*?)$/', $qtsline, $matches)) {
    $docID = str_replace(' ', '', $matches[1]);
    $restOfMetadata = $matches[2]; 

    if (preg_match('/^.*?》.*?$/', $restOfMetadata)) {

      // Unfortunately this line can have nested 》s

      $restArray = explode('》', $restOfMetadata);

      $docTitle = str_replace(' ', '', implode('》', array_slice($restArray, 0, -1)));
      $docAuthor = str_replace(' ', '', end(array_values($restArray)));

      $metadataHack = false;

      //$docTitle = str_replace(' ', '', $matches[1]);
      //$docAuthor = str_replace(' ', '', $matches[2]);
    
    } else {

      echo "Possible malformed metadata string at doc ID " . $docID . "\n";

      $restArray = explode('。', $restOfMetadata);
      $qtsText = trim(implode('。', array_slice($restArray, 1)));

      $docTitle = str_replace(' ', '', $restArray[0]);
      $docAuthor = "?";
      
      $metadataErrors++;

      $metadataHack = true;

    }

//    $docNumber++;
    
    $docName = $docID;
    $docID = str_replace('_', '/', $docID);

    if (isset($docIDs[$docID])) {
      $prevSuffix = $docIDs[$docID];
      $newSuffix = $prevSuffix++;
      echo "pre-existing doc ID: " . $docID . ", adding suffix " . $newSuffix . "\n";
      $docName .= $newSuffix;
      $docID .= $newSuffix;
      $duplicateNumbers++;
      $docIDs[$docID] = $newSuffix;
    } else {
      $docIDs[$docID] = 'a';
    }
    
    $docNameArray = explode("/", $docID);
    $docVolume = $docNameArray[0];
    $docIssue = $docNameArray[1];

    if ($docTitle == "") {
      $docTitle = "untitled";
    }
    if ($docAuthor == "") {
      $docAuthor = "?";
    }

//    $docDate = sprintf("%04d-01-01T00:00:00Z", $docNumber);

    $docDate->add(new DateInterval('P3DT16H40M'));

    $dateStr = date_format($docDate, 'Y-m-d') . 'T' . date_format($docDate, 'H:i:s') . 'Z';

    fwrite($citationsCSV, $docID . "," . $docID . " ," . $docTitle . "\t," . $docAuthor . ",全唐詩\t," . $docVolume . " ," . $docIssue . "\t," . $dateStr . " ,p. " . $docNumber . "\t,Tang Dynasty Press\t,fla\t, ,\n");
    fwrite($metaCSV, '"'. $docID . '","' . $docTitle . "" . '","' . $docAuthor . "" . '","全唐詩' . "" . '",' . $docVolume.',"' . $docIssue . "" . '","' . $dateStr . '","p. ' . $docNumber . "" . '","fla' . "" . '"' . "\n");

    if ($poemLines > 0) {

      $writtenDocs++;

      $rawFile = fopen('qts_raw/' . $prevDocName . '.txt', "w");
      fwrite($rawFile, trim($rawText));
      fclose($rawFile);

      fwrite($malletFile, $prevDocName . ", " . $docNumber . ", " . trim($rawText) . "\n");
//      fwrite($RmalletFile, $prevDocName . ',"' . trim($rawText) . '"' . "\n");

      $poemHTML .= '</p></body></html>';

      if (preg_match('/^[[:blank:]]*?$/', $poemString)) {
        echo "EMPTY POEM? at docID " . $docID . "\n";
      }

      $poemFile = fopen('poems/' . $prevDocName . '.html', "w");
      fwrite($poemFile, $poemHTML);
      fclose($poemFile);

      $charCounts = array();
      $poemArray = explode(' ', $poemString);

      foreach($poemArray as $rawchar) {
        $character = trim($rawchar);
        if (($character == " ") || ($character == "") || ($character == "　"))
          continue;
//        if (preg_match('/[0-9a-zA-Z]/', $character))
        if (!preg_match('/[\p{L}\p{M}]/', $character))
          continue;
  
        if (!isset($charCounts[$character])) {
          $charCounts[$character] = 1;
        } else {
          $charCounts[$character]++;
        }
      }
     
      $weightFile = fopen("wordcounts/wordcounts_" . $prevDocName . ".CSV", "w");
      fputcsv($weightFile, array("WORDCOUNTS","WEIGHT"));

      foreach($charCounts as $thisChar => $weight) {
        fputcsv($weightFile, array($thisChar, $weight));
      }
      fclose($weightFile);

    } else {
      if (!$metadataHack) {
        echo "Possible blank poem at " . $docID . "\n";
        $blankPoems++;
      }
    }
    
    $poemLines = 0;

    $poemHTML = '<!DOCTYPE HTML>
<html lang="en-US">
  <head>
    <meta charset="UTF-8">
    <title>' . $docName . ': 《' . $docTitle . '》' . $docAuthor . '</title>
    <style>
      html *
      {
           font-size: 1em !important;
           color: #000 !important;
           font-family: Arial !important;
      }
    </style>
  </head>
  <body><p>' . $docName . ': 《' . $docTitle . '》' . $docAuthor . '</p>
        <p>';

    $rawText = "";
    $poemString = "";
    $prevDocName = $docName;

    $docNumber++;

    if ($metadataHack == false) {
      continue;
    }

  }
 // else 

//    echo "for " . $docID . ", text is " . $qtsText . "\n";
    
    if ($metadataHack == false)
      $qtsText = trim($qtsline);

    // Otherwise it's already been set above
    
    if (str_replace(array("\t", ' ', '　'), '', $qtsText) == "") {
      echo "BLANK LINE detected at docID " . $docID . "\n";
      $blankLines++;
      continue;
    }
    
    $poemLines++;
 
    if (preg_match('/卷\s([0-9\s_]*?)《(.*?)$/', $qtsText)) {
      echo "CONCATENATED POEMS at docID " . $docID . "\n";
      $concatenatedPoems++;
    }

    $rawText .= str_replace(array("\n", '　'), '', $qtsText);

    if ($poemLines > 1) {
      $poemHTML .= '<br>';
      echo "MULTI LINE POEM at docID " . $docID . "\n";
    }

    $poemLine = str_replace(' ', '', $qtsText);
    $poemLine = str_replace('。　　', '。<br>', $poemLine);
    $poemLine = str_replace('，　　', '，<br>', $poemLine); 
    $poemLine = str_replace('　　', '', $poemLine);

    $poemHTML .= $poemLine;
      
    $qtsText = str_replace(array('，', '。', ':', '：'), '', $qtsText);
//    $qtsText = str_replace("\t", '', $qtsText);

    // Get the counts for each character

    $poemString .= $qtsText;

  // end curly brace
}

      $writtenDocs++;

      $rawFile = fopen('qts_raw/' . $prevDocName . '.txt', "w");
      fwrite($rawFile, $rawText);
      fclose($rawFile);

      fwrite($malletFile, $prevDocName . ", " . $docNumber . ", " . trim($rawText) . "\n");

      $poemHTML .= '</p></body></html>';

      if (preg_match('/^[[:blank:]]*?$/', $poemString)) {
        echo "EMPTY POEM? at docID " . $docID . "\n";
      }

      $poemFile = fopen('poems/' . $prevDocName . '.html', "w");
      fwrite($poemFile, $poemHTML);
      fclose($poemFile);

      $charCounts = array();
      $poemArray = explode(' ', $poemString);

      foreach($poemArray as $rawchar) {
        $character = trim($rawchar);
        if (($character == " ") || ($character == "") || ($character == "　"))
          continue;
//        if (preg_match('/[0-9a-zA-Z]/', $character))
        if (!preg_match('/[\p{L}\p{M}]/', $character))
          continue;
  
        if (!isset($charCounts[$character])) {
          $charCounts[$character] = 1;
        } else {
          $charCounts[$character]++;
        }
      }

      $weightFile = fopen("wordcounts/wordcounts_" . $prevDocName . ".CSV", "w");
      fputcsv($weightFile, array("WORDCOUNTS","WEIGHT"));

      foreach($charCounts as $thisChar => $weight) {
        fputcsv($weightFile, array($thisChar, $weight));
      }
      fclose($weightFile);

echo $blankLines . " blank lines found\n";
echo $duplicateNumbers . " duplicate poem IDs disambiguated\n";
echo $metadataErrors . " metadata errors\n";
echo $concatenatedPoems . " concatenated poems\n";
echo $writtenDocs . " poem texts actually written to files\n";
echo $blankPoems . " potential blank poems\n";
echo "Max doc number is " . $docNumber . "\n";

fclose($citationsCSV);
fclose($malletFile);
//fclose($metaCSV);

?>

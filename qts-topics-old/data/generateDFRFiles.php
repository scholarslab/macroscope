<?php

ini_set("auto_detect_line_endings", true);

function raw_json_encode($input) {

  return preg_replace_callback('/\\\\u([0-9a-zA-Z]{4})/',
    function ($matches) {
      return mb_convert_encoding(pack('H*',$matches[1]),'UTF-8','UTF-16');
    },
    json_encode($input)
  );

}

$vocabWords = array(); // One entry for each word in the corpus
$topicWordCounts = array();

// meta.csv is generated by createBibAndWordcounts.php at the same
// time as citations.CSV (which contains the same data)

// Create info.json and keys.csv (contain the same data)
$infoJSON = array("title" => "Complete Tang Poems", 
  "meta_info" => "<h2></h2>",
  "VIS" => array("overview_words" => 20));

file_put_contents("info.json", json_encode($infoJSON));

// Create labels.json (for user topic labels)
$labelsJSON = array();
$labelsData = file("topic_labels.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($labelsData as $labelInfo) {
  $labelArray = explode("\t", $labelInfo);
  $labelsJSON[$labelArray[0]] = $labelArray[1];
}
file_put_contents("labels.json", json_encode($labelsJSON));

$paramsFile = fopen("params.csv", "w");
fwrite($paramsFile, '"","beta","n_tokens","LL"' . "\n");
/* This is based on output from running mallet */
fwrite($paramsFile, '"1",0.01052,2155323,-8.46279');
fclose($paramsFile);

// Create keys.csv - seems to have same data as the tpr.xml file

// XXX NOTE THAT tw.json IS CREATED BY make prepare

//$twJSON = array();
//$twJSON["alpha"] = array();
//$twJSON["tw"] = array();

$topicKeys = array(); // Maps topic IDs to their 19 most likely characters

$tprDoc = new DOMDocument();
$tprDoc->load('mallet_out/qts.150.tpr.xml');

$keysFile = fopen("keys.csv", "w");
fwrite($keysFile, "topic,alpha,word,weight\n");

$x = $tprDoc->documentElement;

foreach ($x->childNodes as $item) {

  if ($item->nodeName == "topic") {
    $topicID = $item->getAttribute("id");
    $topicNumber = $topicID + 1;
    $topicKeys[$topicNumber] = array();
    $topicAlpha = $item->getAttribute("alpha");

//    $twJSON["alpha"][] = $topicAlpha+0;
    
    $topicWords = array(); 
    $topicWeights = array(); 
    
    foreach ($item->childNodes as $child) {
      if ($child->nodeName == "word") {

        $word = $child->nodeValue;
        $weight = $child->getAttribute("count");
        
        fwrite($keysFile, $topicNumber . "," . $topicAlpha . "," . $word . "," . $weight . "\n");

        $topicWords[] = $word;
        $topicWeights[] = $weight+0;
        
        $topicKeys[$topicNumber][$word] = $weight+0;

      }
    }

//    $twJSON["tw"][] = array("words" => $topicWords, "weights" => $topicWeights);

  }
}
fclose($keysFile);

//file_put_contents("tw.json", raw_json_encode($twJSON));
// Generate vocab.txt from the character:doc counts file
// Also set the word order for the topic_words file

$countsData = file("mallet_out/qts.150.counts.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$vocabFile = fopen("vocab.txt", "w");

foreach($countsData as $countsLine) {

  $lineArray = explode(' ', $countsLine);
  $charID = $lineArray[0];
  $character = $lineArray[1];

  for ($i = 2; $i<count($lineArray); $i++) {
    $countArray = explode(':', $lineArray[$i]);
    $topicID = $countArray[0];
    $topicNumber = $topicID + 1;
    if (!isset($topicWordCounts[$topicNumber])) {
      $topicWordCounts[$topicNumber] = array();
    }
    if (isset($topicKeys[$topicNumber][$character])) {
      $charCount = $countArray[1];
      $topicWordCounts[$topicNumber][$character] = $charCount;

      if (!in_array($character, $vocabWords)) { 
        $vocabWords[] = $character;
        fwrite($vocabFile, $character . "\n");
      }
    }
  }    

}
fclose($vocabFile);

// Create topic_words.csv

$topicWordsFile = fopen("topic_words.csv", "w");

for ($t = 1; $t<=150; $t++) {
  $lineArray = array();

  foreach($vocabWords as $vocabWord) {

    if (isset($topicWordCounts[$t][$vocabWord])) {
      $lineArray[] = $topicWordCounts[$t][$vocabWord]; 
    } else {
      $lineArray[] = 0;
    }

  }
  $lineString = implode(',', $lineArray);
  fwrite($topicWordsFile, $lineString . "\n");
}

// Generate doc_topics.csv and id_map.txt from the document topic report CSV
$dtrData = file("mallet_out/qts.150.dtr.csv", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$idMapFile = fopen("id_map.txt", "w");

$docTopics = fopen("doc_topics.csv", "w");

for ($h=1;$h<=150;$h++) {
  fwrite($docTopics, "topic" . $h . ",");
}
fwrite($docTopics, "id\n");

for ($j=1;$j<count($dtrData);$j++) {

  $dtrLine = $dtrData[$j];

  $dtrArray = explode(',', $dtrLine);
  if (count($dtrArray) < 2) {
    echo "PROBLEM WITH DTR LINE # " . $j . ": " . $dtrLine . "\n";
  }
  $dtrMetadata = explode("\t", $dtrArray[0]);
  if (count($dtrMetadata) < 2) {
    echo "PROBLEM WITH DTR METADATA AT LINE # " . $j . ": " . $dtrArray[0] . "\n";
  }
  $docID = $dtrMetadata[0];
  $docName = $dtrMetadata[1];

  echo "Processing document " . $docName . "\n";

  fwrite($idMapFile, $docName . "\n");

  $docFreqCounts = array();

  $docFreqData = file("wordcounts/wordcounts_" .$docName. ".CSV", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach($docFreqData as $freqLine) {
    $freqData = explode(',', $freqLine);
    if ($freqData[0] == "WORDCOUNTS")
      continue;
    $thisChar = $freqData[0];
    $thisFreq = $freqData[1];

    $docFreqCounts[$thisChar] = $thisFreq;
  }

  $topicHits = array();
  for ($z=1;$z<=150;$z++) {
    $topicHits[$z] = 0;

    foreach ($docFreqCounts as $docWord => $docFreq) {
      if (($docFreq > 0) && (isset($topicWordCounts[$z][$docWord]))) {
          $topicHits[$z] += $docFreq;
      }
    }
//    fwrite($docTopics, $topicHits[$z] . ",");
  }

  $dtrInfo = explode("\t",$dtrArray[1]);

  $totalRatios = 0;
  $topicRatios = array();
  $minRatio = 100;

  for ($k=1;$k<count($dtrInfo)-1;$k+=2) {
    $topicID = $dtrInfo[$k];
    $topicNum = $topicID+1;
    $topicWeight = $dtrInfo[$k+1];
//    echo "looking at topic ID " . $topicID . ", weight " . $topicWeight . "\n";
    $totalRatios += $topicWeight;
    $topicRatios[$topicNum] = $topicWeight;
    $minRatio = min($minRatio, $topicWeight);
  }

  if ($minRatio == 100) {
    echo "MIN RATIO IS TOO BIG FOR DOC " . $docName . "\n";
  }

//  echo "For document " . $docName . ", total ratios is " . $totalRatios . ", minRatio is " . $minRatio . "\n";

  ksort($topicRatios);

  foreach ($topicRatios as $topicNum => $topicWeight) {
    if ($topicWeight == $minRatio) {
      $topicVal = 0;
    } else {
//      $topicVal = $topicWeight / $minRatio;
      $topicVal = $topicHits[$topicNum];
    }
    fwrite($docTopics, $topicVal . ",");
  }

  fwrite($docTopics, $docName . "\n");

}

fclose($idMapFile);
fclose($docTopics);

// Generate dt.json XXX NEVER MIND THIS IS GENERATED BY make prepare
// This has 3 indices: i, p, and x.
// p defines bins that contain all 150 topics, so it has 151 elements, from
// 0 to the number of elements in i and x, increasing by a variable amount.
// i is also increasing at a set amount
// i and x have the same number of entries
// x seems to be the weight of a topic in a doc (its max is the max in
// doc_topics.csv)

/* DATA FROM PREVIOUS RUN:
 * counts: p: 151, i: 229226, x: 229226
 * p increases from 0 to 229226 with 151 entries total
 * i is a repeating list of documents
 * x goes from 1 to 1152
 * doc count: 34820
 */
/*
$dtJSON = json_decode(file_get_contents("backup/dt.json"), true);

$icount = count($dtJSON["i"]);
$pcount = count($dtJSON["p"]);
$xmax = max($dtJSON["x"]);
$xmin = min($dtJSON["x"]);
$xtotal = array_sum($dtJSON["x"]);
$xcount = count($dtJSON["x"]);

echo "counts: p: " . $pcount . ", i: " . $icount . ", x: " . $xcount . "\n";
echo "xmin is " . $xmin . ", xmax is " . $xmax . ", xtotal is " . $xtotal . "\n";

$doclenJSON = json_decode(file_get_contents("backup/doc_len.json"), true);

echo "doc count: " . count($doclenJSON["doc_len"]) . "\n";
 */

?>

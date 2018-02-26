<?php

$tprDoc = new DOMDocument();
$tprDoc->load("qts.150.tpr.xml");

$ITEMS_PER_TOPIC = 5;

$wordCorpusFreqs = array();
$totalCorpusWords = 0;

$topicID = -1;
$currentTopicWords = array();
$currentTopicTitles = array();
$currentTopicPhrases = array();

$topicWords = array();
$topicPhrases = array();
$allTopicTitles = array();

$x = $tprDoc->documentElement;

foreach ($x->childNodes as $item) {

  if ($item->nodeName == "topic") {
    $topicID = $item->getAttribute("id");
    $titles = $item->getAttribute("titles");
    $allTopicTitles[$topicID] = explode(", ", $titles);
    $totalCorpusWords += $item->getAttribute("totalTokens");
    
    echo "currentTopic is " . $topicID . "\n";
    $topicWords[$topicID] = array();
    $topicPhrases[$topicID] = array();
    
    foreach ($item->childNodes as $child) {
      if ($child->nodeName == "word") {
//        echo "topic " . $topicID . ", looking at word " . $child->nodeValue . "\n";
        $topicWords[$topicID][$child->nodeValue] = $child->getAttribute("count");
        if (isset($wordCorpusFreqs[$child->nodeValue])) {
          $wordCorpusFreqs[$child->nodeValue] += $child->getAttribute("count");
        } else {
          $wordCorpusFreqs[$child->nodeValue] = $child->getAttribute("count");
        }
      }
      if ($child->nodeName == "phrase") {
        $topicPhrases[$topicID][$child->nodeValue] = $child->getAttribute("count");
      }
    }
  }
}

echo "Total words in the corpus: " . $totalCorpusWords . "\n";

$allfreqs = array();
$allweights = array();

foreach ($allTopicTitles as $topicID => $topicTitles) {

  $titlesFile = fopen("topic_titles" . $topicID . ".csv", "w");
  fwrite($titlesFile, "text,size,freq,topic\n");
  $itemsSoFar = 0;
  
  $currentTopicWords = $topicWords[$topicID];
  $currentTopicPhrases = $topicPhrases[$topicID];

  $phrasesSoFar = array();

  foreach ($topicTitles as $title) {

    if ($itemsSoFar >= $ITEMS_PER_TOPIC)
      break;

    if (isset($currentTopicWords[$title])) {
      $freq = $wordCorpusFreqs[$title] / $totalCorpusWords;
      $weight = $currentTopicWords[$title];
      $allfreqs[] = $freq;
      $allweights[] = $weight;
      fwrite($titlesFile, $title . "," . $weight . "," . $freq . "," . $topicID . "\n");
      $itemsSoFar++;
    }
    if (isset($currentTopicPhrases[$title])) {
      $phraseArray = explode(' ', $title);
      $shortTitle = str_replace(' ', '', $title);
      $phraseFirstWord = $phraseArray[0];
      $phraseSecondWord = $phraseArray[1];
      if ((!in_array($phraseSecondWord . $phraseFirstWord, $phrasesSoFar)) &&
          (isset($currentTopicWords[$phraseFirstWord])) &&
          (isset($currentTopicWords[$phraseSecondWord])) && ($phraseFirstWord != $phraseSecondWord)) {
        $weight = min($currentTopicWords[$phraseFirstWord], $currentTopicWords[$phraseSecondWord]);
        $freq = min($wordCorpusFreqs[$phraseFirstWord], $wordCorpusFreqs[$phraseSecondWord]) / $totalCorpusWords; 
        fwrite($titlesFile, $shortTitle . "," . $weight . "," . $freq . "," . $topicID . "\n");
        $itemsSoFar++;
        $phrasesSoFar[] = $shortTitle;
      }
    }
  }
  fclose($titlesFile);
}

foreach ($topicWords as $topicID => $topicList) {

  arsort($topicList);

  $topicFile = fopen("topic_words" . $topicID . ".csv", "w");
  fwrite($topicFile, "text,size,freq,topic\n");
  
  $itemsSoFar = 0;

  foreach ($topicList as $word => $count) {

    if ($itemsSoFar >= $ITEMS_PER_TOPIC)
      break;

    $freq = $wordCorpusFreqs[$word] / $totalCorpusWords;
    fwrite($topicFile, $word . "," . $count . "," . $freq . "," . $topicID . "\n");
    $itemsSoFar++;

  }
  fclose($topicFile);
}

echo "max freq is " . max($allfreqs) . "\n";
echo "min freq is " . min($allfreqs) . "\n";
echo "max weight is " . max($allweights) . "\n";
echo "min weight is " . min($allweights) . "\n";

?>

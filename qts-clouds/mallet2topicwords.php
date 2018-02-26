<?php

$weightsData = file('qts.150.weights', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$WORDS_PER_TOPIC = 5;

$wordCorpusFreqs = array();

$topicWordWeights = array();

$totalCorpusWords = 0;

$currentTopic = -1;
$currentTopicWeights = array();

foreach ($weightsData as $weightLine) {

  $weightArray = explode("\t", $weightLine);
//  echo "Looking at weightLine " . $weightLine . "\n";

  if (count($weightArray) != 3) {
    echo "ERROR: couldn't parse line " . $weightLine . "\n";
  }

  $topicNumber = $weightArray[0];
  $topicWord = $weightArray[1];
  $topicWeight = round($weightArray[2]);
  
  if (!isset($wordCorpusFreqs[$topicWord])) {
    $wordCorpusFreqs[$topicWord] = $topicWeight;
  } else {
    $wordCorpusFreqs[$topicWord] += $topicWeight;
  }

  $totalCorpusWords++;

  if ($topicWeight == 0)
    continue;

  if ($topicNumber != $currentTopic) {

    if (count($currentTopicWeights)) {
      arsort($currentTopicWeights);
      $topicWordWeights[$currentTopic] = $currentTopicWeights;
      $currentTopicWeights = array();
    } 

    $currentTopic = $topicNumber;
    echo "currentTopic is " . $currentTopic . "\n";

  }

  $currentTopicWeights[$topicWord] = $topicWeight;

}
if (count($currentTopicWeights)) {
  arsort($currentTopicWeights);
  $topicWordWeights[$currentTopic] = $currentTopicWeights;
}

echo "Total words in the corpus: " . $totalCorpusWords . "\n";

$allfreqs = array();
$allweights = array();

foreach ($topicWordWeights as $currentTopic => $topicWeights) {

  $topicFile = fopen("topic_words" . $currentTopic . ".csv", "w");
  fwrite($topicFile, "text,size,freq,topic\n");
  $wordsSoFar = 0;
  foreach($topicWeights as $word => $weight) {
    if ($wordsSoFar >= $WORDS_PER_TOPIC)
      break;
    $freq = $wordCorpusFreqs[$word] / $totalCorpusWords; 
    $allfreqs[] = $freq;
    $allweights[] = $weight;
    fwrite($topicFile, $word . "," . $weight . "," . $freq . "," . $currentTopic . "\n");
    $wordsSoFar++;
  }
  fclose($topicFile);
}

echo "max freq is " . max($allfreqs) . "\n";
echo "min freq is " . min($allfreqs) . "\n";
echo "max weight is " . max($allweights) . "\n";
echo "min weight is " . min($allweights) . "\n";

?>

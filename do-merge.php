<?php
require("http.php");

echo date("Y-m-d H:i:s") . PHP_EOL . PHP_EOL;

$positiveVoteTypes = [
	"+1",
	"laugh",
	"hooray",
	"heart",
];

// Key is the PR number, value is an array of usernames who voted.
$votes = [];

// $committersLast* arrays contain a list of usernames who have already had a PR merged.
$committersLastMonth = [];
$committersLastWeek = [];

// Step 1: Build votes array on all open PRs.
foreach(http("/repos/GithubGame/1/pulls") as $pull) {
	$numPR = $pull->number;
	echo "Building votes for $numPR" . PHP_EOL;

	$votes[$numPR] = [];
	$comment = http("/repos/GithubGame/1/issues/$numPR");

	$reactions = http("/repos/GithubGame/1/issues/$numPR/reactions");
	foreach($reactions as $reaction) {
		if(!in_array($reaction->content, $positiveVoteTypes)) {
			continue;
		}

		$votes[$numPR] []= $reaction->user->login;
		echo $reaction->user->login . " ";
	}

	echo PHP_EOL;
	$votes[$numPR] = array_unique($votes[$numPR]);
}

echo "Votes:" . PHP_EOL;
var_dump($votes);

// Step 2: Store lists of users who have already had their
// PRs merged (for vote weighting).
$formatString = "Y-m-d\TH:i:s\Z";
$lastMonth = new DateTime("-30 days");
$lastMonthFormat = $lastMonth->format($formatString);
$lastWeek = new DateTime("-7 days");
$lastWeekFormat = $lastWeek->format($formatString);

foreach(http("/repos/GithubGame/1/commits?since=$lastMonthFormat") as $commit) {
	$username = $commit->author->login;
	$dateTime = new DateTime($commit->commit->author->date);

	if($dateTime < $lastMonth) {
		continue;
	}
	else if($dateTime < $lastWeek) {
		$committersLastMonth [] = $username;
	}
	else {
		$committersLastWeek []= $username;
	}
}

// Step 3: Count committers.
$committersLastWeek = array_unique($committersLastWeek);
$committersLastMonth = array_unique($committersLastMonth);

$totalCommitters = count($committersLastMonth) + count($committersLastWeek);
$thresholdWeek = 0;
$thresholdMonth = 0;

echo "Committers last month:" . PHP_EOL;
var_dump($committersLastMonth);

echo "Committers last week:" . PHP_EOL;
var_dump($committersLastWeek);


// Step 4: Calculate thresholds.
if($totalCommitters >= 10) {
	$thresholdWeek = $totalCommitters * 0.1;
	$thresholdMonth = $totalCommitters * 0.3;
}

echo "Total committers: $totalCommitters" . PHP_EOL;
echo "Threshold (week): $thresholdWeek" . PHP_EOL;
echo "Threshold (month): $thresholdMonth" . PHP_EOL;

// Step 5: Merge PRs with enough votes.
$title = urlencode("Test title");
$message = urlencode("Test message.");

echo "Performing merges!" . PHP_EOL;

foreach($votes as $numPR => $voters) {
	echo "Checking $numPR..." . PHP_EOL;

	$totalVotesFromCommittersLastWeek = 0;
	$totalVotesFromCommittersLastMonth = 0;
	foreach($committersLastWeek as $username) {
		if(in_array($username, $committersLastWeek)) {
			$totalVotesFromCommittersLastWeek ++;
		}
		if(in_array($username, $committersLastMonth)) {
			$totalVotesFromCommittersLastMonth ++;
		}
	}

	echo "Total votes from committers last week: "
		. $totalVotesFromCommittersLastWeek . PHP_EOL;
	echo "Total votes from committers last month: "
		. $totalVotesFromCommittersLastMonth . PHP_EOL;

	if($totalVotesFromCommittersLastWeek > $thresholdWeek
	|| $totalVotesFromCommittersLastMonth > $thresholdMonth) {
		echo "Merging $numPR..." . PHP_EOL;

		echo http("/repos/GithubGame/1/pulls/$numPR/merge"
			. "?commit_title=$title"
			. "&commit_message=$message"
			, "PUT"
		) . PHP_EOL;
	}
}
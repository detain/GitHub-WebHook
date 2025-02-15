<?php
declare(strict_types=1);

/**
* @link https://docs.github.com/en/developers/webhooks-and-events/webhooks/webhook-events-and-payloads
* @link https://github.com/github/docs/blob/main/content/developers/webhooks-and-events/webhooks/webhook-events-and-payloads.md
* @link https://github.com/organizations/interserver/settings/hooks/359889086?tab=deliveries
*
*/

header('Content-Type: text/plain; charset=utf-8');
// set default response code
http_response_code(500);

require __DIR__ . '/../src/config.php';
require __DIR__ . '/../src/GithubWebhook.php';
require __DIR__ . '/../src/IgnoredEventException.php';
require __DIR__ . '/../src/NotImplementedException.php';

$Hook = new GitHubWebHook();
try {
	if (!$Hook->ValidateHubSignature(GITHUB_WEBHOOKS_SECRET))
		throw new Exception('Secret validation failed.');
	$Hook->ProcessRequest();
	$RepositoryName = $Hook->GetFullRepositoryName();
	$EventType = $Hook->GetEventType();
	// format message
	//$Converter = new Converter($Hook->GetEventType(), $Hook->GetPayload());
	//$Message = $Converter->GetEmbed();
	$Message = $Hook->GetPayload();
	$log = ['repo' => $RepositoryName, 'event' => $EventType, 'data' => $Message];
	$User = $Message['sender']['login'];
	$UserImg = $Message['sender']['avatar_url'];
	file_put_contents(__DIR__.'/../log/'.date('Ymd_His').'_'.$EventType.(isset($Message['action']) ? '_'.$Message['action'] : '').'_'.$User.'_'.str_replace(['/', '-', ' '], ['_', '_', '_'], $RepositoryName).'.json', json_encode($log, JSON_PRETTY_PRINT));
	if (empty($Message))
		throw new Exception('Empty message, not sending.');
	switch ($EventType) {
		case 'push':
			$Branch = str_replace('refs/heads/', '', $Message['ref']);
			$CommitMsg = $Message['head_commit']['message'];
			$CommitCount = count($Message['commits']);
			$ChatMsg = "{$User} pushed ".($CommitCount == 1 ? 'a commit' : $CommitCount.' commits')." to https://github.com/{$RepositoryName} [*{$Branch}*]\n:notepad_spiral: {$CommitMsg}";
			$Msg = [
				"alias" => $Message['head_commit']['author']['name'],
				"avatar" => $UserImg,
				"text" => $ChatMsg,
			];
			SendToRocketChat($rocketChatChannels['notifications'], $Msg);
			break;
        case 'check_suite':
        case 'check_run':
            $UserImg = $Message[$EventType]['app']['owner']['avatar_url'];
            $User = $Message[$EventType]['app']['name'];
            break;
		default:
			$ChatMsg = "{$User} triggered a {$EventType} event ".(isset($Message['action']) ? $Message['action'].' action ' : '')." notification on https://github.com/{$RepositoryName} ".(isset($Message['ref']) ? str_replace('refs/heads/', '', $Message['ref']) : '').".";
			$Msg = [
				"alias" => $Message['head_commit']['author']['name'],
				"avatar" => $UserImg,
				"text" => $ChatMsg,
			];
			SendToRocketChat($rocketChatChannels['notifications'], $Msg);
			break;
	}
	/*
	foreach($githubWebhooks as $Event => $Repos) {
		if (!WildMatch($EventType, $Event))
		foreach ($Repos as $Repo => $SendTargets) {
			if (!WildMatch($RepositoryName, $Repo))
				continue;
			foreach($SendTargets as $ChannelName) {
				$ChannelUrl = $rocketChatChannels[$ChannelName];
				SendToRocketChat($Target, $Message);
			}
		}
	}
	*/
	error_log('GitHub Hook '.$EventType.' on '.$RepositoryName.' called');
	http_response_code(202);
} catch(IgnoredEventException $e) {
	http_response_code(200);
	error_log('This GitHub event is ignored.');
} catch(NotImplementedException $e) {
	http_response_code(501);
	error_log('Unsupported GitHub event: ' . $e->EventName);
} catch(Exception $e) {
	error_log('Exception: ' . $e->getMessage() . PHP_EOL);
}

function WildMatch(string $string, string $expression) : bool {
	if (strpos($expression, '*') === false)
		return strcmp($expression, $string) === 0;
	$expression = preg_quote($expression, '/');
	$expression = str_replace('\*', '.*', $expression);
	return preg_match('/^' . $expression . '$/', $string) === 1;
}

function SendToRocketChat(string $Url, array $Payload) : bool {
	$c = curl_init();
	curl_setopt_array($c, [
		CURLOPT_USERAGENT      => 'https://github.com/xPaw/GitHub-WebHook',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => 0,
		CURLOPT_TIMEOUT        => 30,
		CURLOPT_CONNECTTIMEOUT => 30,
		CURLOPT_URL            => $Url,
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => json_encode($Payload),
		CURLOPT_HTTPHEADER     => [
			'Content-Type: application/json',
		],
	]);
	curl_exec($c);
	$Code = curl_getinfo($c, CURLINFO_HTTP_CODE);
	curl_close($c);
	error_log('Rocket Chat HTTP ' . $Code . PHP_EOL);
	return $Code >= 200 && $Code < 300;
}

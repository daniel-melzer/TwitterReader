<?php


namespace DanielMelzer\Twitter;


class Reader {


	/**
	 * @var string
	 */
	const API_URL = 'http://api.twitter.com/1/';

	/**
	 * @var array
	 */
	private $rawTimeline = array();

	/**
	 * @var array
	 */
	private $timeline = array();

	/**
	 * @var array
	 */
	private $user = array(
			'favourites_count' => '',
			'description' => '',
			'listed_count' => '',
			'url' => '',
			'time_zone' => '',
			'lang' => '',
			'created_at' => '',
			'location' => '',
			'followers_count' => '',
			'name' => '',
			'friends_count' => '',
			'screen_name' => '',
			'id' => 0,
			'statuses_count' => '',
			'utc_offset' => '',
	);

	/**
	 * @var array
	 */
	private $options = array(
			'count'	=> 20,
			'include_rts' => 1,
	);


	/**
	 * Constructor accepts an array with options for the Twitter API. These options are used for
	 * the timeline only.
	 * 
	 * @param array $options
	 */
	public function  __construct($options = array()) {
		if(0 < count($options)) {
			$this->options = $options;
		}
	}

	/**
	 * Fetches the public timeline and process the tweets. Also, this method filters replies but
	 * keeps retweets.
	 * 
	 * @return TwitterReader 
	 */
	public function retrieveTweets() {
		if(empty($this->user['id'])) {
			throw new Exception('no user id available', 5);
		}

		$apiUrl = sprintf(
				'%sstatuses/user_timeline.json?user_id=%s', self::API_URL, $this->user['id']);
		foreach($this->options as $key => $value) {
			$apiUrl .= sprintf('&%s=%s', $key, $value);
		}

		$curlOptions = array(
				CURLOPT_URL => $apiUrl,
				CURLOPT_RETURNTRANSFER => true
		);

		$curl = curl_init();
		curl_setopt_array($curl, $curlOptions);

		$timelineJson = curl_exec($curl);

		if(empty($timelineJson)) {
			throw new Exception('no response', 2);
		}

		$timeline = json_decode($timelineJson);

		if(is_object($timeline) && isset($timeline->error)) {
			throw new Exception('Twitter API: ' . $timeline->error, 3);
		}

		$tweetCounter = $this->user['statuses_count'];
		foreach($timeline as $tweet) {
			$this->rawTimeline[] = $tweet;

			if(null === $tweet->in_reply_to_status_id) {
				$this->timeline[] = array(
						'favorited' => $tweet->favorited,
						'created_at' => strtotime($tweet->created_at),
						'text' => $this->processText($tweet->text),
						'hashtags' => $this->extractTags($tweet->text),
						'counter' => $tweetCounter,
						'id' => sprintf('%.0F', $tweet->id),
						'source' => $tweet->source,
						'in_reply_to_status_id'	=> $tweet->in_reply_to_status_id,
				);
			}

			$tweetCounter--;
		}

		return $this;
	}

	/**
	 * Fetches the user information for a given screen name.
	 * 
	 * @param string $screenName
	 * @return TwitterReader 
	 */
	public function retrieveUserByScreenName($screenName) {
		$screenName = filter_var($screenName, FILTER_SANITIZE_STRING);

		$apiUrl = sprintf(
				'%susers/show.json?screen_name=%s&include_entities=true',
				self::API_URL,
				urlencode(strtolower($screenName))
		);
		$curlOptions = array(
				CURLOPT_URL => $apiUrl,
				CURLOPT_RETURNTRANSFER => true
		);

		$curl = curl_init();
		curl_setopt_array($curl, $curlOptions);

		$userJson = curl_exec($curl);
										
		if(empty($userJson)) {
			throw new Exception('no response', 2);
		}
																				
		$user = json_decode($userJson);

		if(isset($user->error)) {
			throw new Exception('Twitter API: ' . $user->error, 3);
		}

		$this->user = array(
				'favourites_count' => $user->favourites_count,
				'description' => $user->description,
				'listed_count' => $user->listed_count,
				'url' => $user->url,
				'time_zone' => $user->time_zone,
				'lang' => $user->lang,
				'created_at' => strtotime($user->created_at),
				'location' => $user->location,
				'followers_count' => $user->followers_count,
				'name' => $user->name,
				'friends_count' => $user->friends_count,
				'screen_name' => $screenName,
				'id' => sprintf('%.0F', $user->id),
				'statuses_count' => $user->statuses_count,
				'utc_offset' => $user->utc_offset,
		);

		return $this;
	}

	/**
	 * Fetches the user information for an given id.
	 *
	 * Ids are treated as string, because Twitter profile ids exceeds integer value range.
	 *
	 * @param string $id
	 * @return TwitterReader
	 */
	public function retrieveUserById($id) {
		$id = filter_var($id, FILTER_SANITIZE_STRING);
		$apiUrl = sprintf(
				'%susers/show.json?user_id=%s&include_entities=true',
				self::API_URL,
				$id
		);
		$curlOptions = array(
				CURLOPT_URL => $apiUrl,
				CURLOPT_RETURNTRANSFER => true
		);

		$curl = curl_init();
		curl_setopt_array($curl, $curlOptions);

		$userJson = curl_exec($curl);

		if(empty($userJson)) {
			throw new Exception('no response', 2);
		}

		$user = json_decode($userJson);

		if(isset($user->error)) {
			throw new Exception('Twitter API: ' . $user->error, 3);
		}

		$this->user = array(
				'favourites_count' => $user->favourites_count,
				'description' => $user->description,
				'listed_count' => $user->listed_count,
				'url' => $user->url,
				'time_zone' => $user->time_zone,
				'lang' => $user->lang,
				'created_at' => strtotime($user->created_at),
				'location' => $user->location,
				'followers_count' => $user->followers_count,
				'name' => $user->name,
				'friends_count' => $user->friends_count,
				'screen_name' => $user->screen_name,
				'id' => sprintf('%.0F', $id),
				'statuses_count' => $user->statuses_count,
				'utc_offset' => $user->utc_offset,
		);

		return $this;
	}

	/**
	 * Returns the array with the user information.
	 * 
	 * @return array
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * Returns the processed timeline array.
	 *
	 * @return array
	 */
	public function getTimeline() {
		return $this->timeline;
	}

	/**
	 * Returns the raw timeline array.
	 *
	 * @return array
	 */
	public function getRawTimeline() {
		return $this->rawTimeline;
	}

	/**
	 * Replaces @-tags and links to clickabel links.
	 * 
	 * @param string $text
	 * @return string
	 */
	private function processText($text) {
		$text = htmlentities($text, ENT_COMPAT, 'UTF-8');

		$text = preg_replace('((.*)(http(s)?://[^\s]+)(.*))', '$1<a href="$2">$2</a>$3', $text);
		$text = preg_replace('((.*@)([\w]+)(.*))', '$1<a href="http://twitter.com/$2">$2</a>$3', $text);

		return $text;
	}

	/**
	 * Extracts the hashtags from the text.
	 *
	 * @param string $text
	 * @return array
	 */
	private function extractTags($text) {
		$return = array();
		preg_match_all('(#[\w]+)', $text, $return);

		if(isset($return[0]) && is_array($return[0])) {
			$return = $return[0];
		}

		return $return;
	}

}

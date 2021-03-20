<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
	<channel>
		<title><?php $this->printAsText('title') ?></title>
		<link><?php $this->printUrl() ?></link>
		<description><?php $this->printAsText('subtitle') ?></description>
		<language><?php $this->printAsText('lang') ?></language>
		<lastBuildDate><?php print date('r') ?></lastBuildDate>
		<generator>TYPO3 - Open Source Content Management</generator>
 
 		<?php foreach($this->entries as $entry): ?>
		<item>
			<title><?php $this->printAsText('title', $entry) ?></title>
			<link><?php print htmlspecialchars($this->asRaw('link', $entry)) ?></link>
			<description><?php $this->printSummary($entry) ?></description>
			<pubDate><?php print date('r', $this->asText('published', $entry)) ?></pubDate>
			<guid><?php print htmlspecialchars($this->asRaw('link', $entry)) ?></guid>
		</item>
		<?php endforeach ?>
	</channel>
</rss>

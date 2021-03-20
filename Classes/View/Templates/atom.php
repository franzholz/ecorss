<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="<?php $this->printAsText('lang') ?>">
	<title><?php $this->printAsText('title') ?></title>
	<subtitle><?php $this->printAsText('subtitle') ?></subtitle>
	<link rel="alternate" type="text/html" href="<?php $this->printAsText('url') ?>"/>
	<id><?php $this->printUrl() ?></id>
	<updated><?php print date('c') ?></updated>

	<generator uri="http://typo3.org" version="<?php echo tx_div2007_core::getTypoVersion(); ?>">TYPO3 - Open Source Content Management</generator>
	<link rel="self" type="application/atom+xml" href="<?php $this->printUrl() ?>" />
	<?php foreach($this->entries as $entry): ?>
	<entry>
		<id><?php print htmlspecialchars($this->asRaw('link', $entry)) ?></id>
		<title><?php $this->printAsText('title', $entry) ?></title>
		<?php if($this->printAsRaw('link', $entry) != ''): /* check if there is a link to display */ ?>
		<link rel="alternate" type="text/html" href="<?php print htmlspecialchars($this->asRaw('link', $entry)) ?>"/>
		<?php endif ?>
		<published><?php print date('c', $this->asText('published', $entry)) ?></published>
		<updated><?php print date('c', $this->asText('updated', $entry)) ?></updated>
		<author>
			<name><?php print $this->asText('author', $entry) ?></name>
		</author>
		<summary type="html"><?php $this->printSummary($entry) ?></summary>
	</entry>
	<?php endforeach; ?>
</feed>

<?php inc('organism','header'); ?>
<div role="main">
	<article class="article">
		<header class="article-header">
			<div class="eyebrow">PopWatch</div> <div class="eyebrow">Oscars</div>
			<h1>Jack Black gets roasted: Here are the top 15 zingers of the night</h1>
			<?php inc('molecule','byline-author-time') ?>
		</header>
		<div class="text article-entry">
			<div class="wp-image">
				<?php inc('atom','landscape-image') ?>
				<p class="wp-caption-text">Image Credit: A Great Photographer</p>
			</div>
			<?php inc('organism','article-body') ?>
		</div>
		<footer class="article-footer">
			<?php inc('molecule','social-article') ?>
		</footer>
	</article>
	<section id="comments" class="comments box">
		<h3>Post a comment</h3>
		<?php inc('molecule','comment-form') ?>
		<a href="#" class="btn btn-block">32 Comments</a>
	</section>
	
	<section class="box">
		<h3>Related</h3>
		<ul class="bullet-list">
			<li><a href="#">Jack Black is getting a Friars Club roast–let's get the ball rolling!</a></li>
			<li><a href="#">From 'Jurassic Park' to 'Back to the Future': Movies meant to be seen on the big screen</a></li>
		</ul>
	</section>

	<section class="box">
		<h3>More from PopWatch</h3>
		<ul class="bullet-list">
			<li><a href="#">Jack Black is getting a Friars Club roast–let's get the ball rolling!</a></li>
			<li><a href="#">From 'Jurassic Park' to 'Back to the Future': Movies meant to be seen on the big screen</a></li>
			<li><a href="#">'Saturday Night Live' recap: Melissa McCarthy brought the heat and hammed it up</a></li>
			<li><a href="#">No HBO, no problem: Get your blood and battles fix with 'Vikings' instead</a></li>
		</ul>
		<a href="#" class="text-btn">Go to Pattern Watch</a>
	</section>

	<section class="section">
		<ul class="headline-list">
			<li><?php inc('molecule','block-thumb-headline') ?></li>
			<li><?php inc('molecule','block-thumb-headline') ?></li>
		</ul>
		<a href="#" class="btn btn-more">More Featured News</a>
	</section>

	<?php inc('organsim','section-partners') ?>
	<?php inc('organsim','section-sponsored-links') ?>
</div><!--End role=main-->
<?php inc('organism','footer'); ?>
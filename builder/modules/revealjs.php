<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

		<title><?php echo title() . ' [AW DECK]';?></title>
		<link href="<?php echo fileUrl(variable(VARSafeName)); ?>-icon.png" rel="icon">

		<?php cssTag(assetUrl('3p/reset.css', assetManager::core));?>
		<?php cssTag(assetUrl('3p/reveal.css', assetManager::core));?>

		<?php cssTag(assetUrl('presentation.css', assetManager::core)); ?>
		<?php cssTag(assetUrl('typography.css', assetManager::core)); ?>

<?php main::analytics(); ?>
	</head>
	<body id="<?php echo variable('all_page_params'); ?>">
		<!-- header thanks to: https://www.raymondcamden.com/2014/04/01/Adding-an-Absolutely-Positioned-Header-to-Revealjs -->
		<header id="topbar-wrapper">
			<div id="topbar" class="box-shadow"><?php if (!isset($_GET['iframe'])) {
				echo concatSlugs(['<a id="home-link" href="', currentUrl() , '">',
					NEWLINE, '				<img src="', fileUrl(variable(VARSafeName) . '-icon.png'),
					'" alt="', variable('name'), '" height="30px"></a>', NEWLINE ], ''); }?>
			<span id="hash-id" style="color: #fff; font-size: 16px;">[slide-id]</span></div>
		</header>

		<div class="reveal">
			<div class="slides">
<section>
				<?php echo variable('deck'); ?>
</section>

			</div>
		</div>

		<?php scriptTag(assetUrl('3p/reveal.js', assetManager::core)); ?>
		<?php if (hasVariable('slide-style')) cssTag(variable('slide-style')); ?>
		<?php echo hasPageparameter('print') ? NEWLINE . variableOr('print-css', '') . NEWLINE : '' ?>
		<script>
			// More info about initialization & config:
			// - https://revealjs.com/initialization/
			// - https://revealjs.com/config/
			const hashOnPageLoad = location.hash;
			Reveal.addEventListener('slidechanged', setHashId);

			Reveal.initialize({
				hash: true,
				<?php echo hasPageparameter('print') ? variableOr('print-config', '') . NEWLINE : ''; ?>
				// Learn about plugins: https://revealjs.com/plugins/
				// NOTE: making it superlight
				// plugins: [ RevealMarkdown, RevealHighlight, RevealNotes ]
			}).then(setMissingHashIds);

			function setMissingHashIds(event) {
				let slideIndex = 0;
				const slides = Reveal.getSlides();

				slides.forEach(function (slide) {
					slideIndex++;
					if (slide.id) return;

					var hidden = slide.querySelectorAll('input[type=hidden]');

					let id = '0' + slideIndex;
					if (hidden.length) id += '-' + hidden[0].value.replaceAll(' ', '-');

					slide.id = id;
				});

				if (hashOnPageLoad)
					location.hash = hashOnPageLoad;

				setHashId(event);
			}

			function setHashId(event) {
				document.getElementById('hash-id').innerText = event.currentSlide.id.replaceAll('-', ' ');
			}
		</script>
	</body>
</html>

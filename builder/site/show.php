<?php
getSiteUrlKey();
runFrameworkFile('site/network');

setTheme();
setSubTheme(VARSubthemeGo);

variables([
	VARMediakit => '?palette=1',
	VARNode => SITEHOME,
	'name' => 'TODO (show.php)',
	VARFooterMessage => 'Proud Member of "' . NETWORKABBR . VARQUOTE,

	VARChatraID => VARUseAmadeusWeb,
	VARGoogleAnalytics => VARUseAmadeusWeb,
	VAREmail => VARSystemEmail,
	VARPhone => $ph1 = VARSystemMobile,
	VARWhatsapp => $ph1,
	VARAddress => VARSystemAddress,
	VARNetwork => 'Webring',
	VARDAWNMenu => true,
]);

add_body_class('showing-sites');
addStyle('v9-spring', assetManager::core);
addStyle('v9-features', assetManager::core);
addStyle('typography', assetManager::core);

//TODO: HI: this file is never used, refactor into feed.
DEFINE('SITEPATH', SHOWSITESAT);
runThemePart('header');
runFrameworkFile('site/listing');
runThemePart('footer');

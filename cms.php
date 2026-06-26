<?php
variables([
	VARSectionsHaveFiles => true,
	socialBuilder::variableName => main::defaultSocial(socialBuilder::default()),
]);

function site_before_render() {
	autosetPageMenu([VARLinkToNodeHome => true, DontOverwriteLogo => false]);
}

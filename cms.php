<?php
variables([
	VARSectionsHaveFiles => true,
	socialBuilder::variableName => main::defaultSocial(socialBuilder::default()),
]);

function site_before_render() {
	if (!sectionIs('network'))
		autosetPageMenu([VARLinkToNodeHome => true, DontOverwriteLogo => false]);
}

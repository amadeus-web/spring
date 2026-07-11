/***
 * This code is part of the php framework "AmadeusWeb Spring" and is Proprietary, Source-available software!
 * Author: Imran Ali Namazi <imran@amadeusweb.world>
 * You MUST agree to and adhere to all "courtesies" required by:
 *     https://github.com/joyfulearth/spring#License-1-ov-file
***/

if (typeof ($) === 'undefined') $ = jQuery.noConflict();

$(document).ready(function () {
	const sansTH = $('.table-sans-th');
	if (sansTH.length) {
		sansTH.each(function (ix, el) {
			const th = $('tr:first', $(this));
			const tr = []; //You cannot dynamically change headers on an active DataTables instance.
			$('td', th).each(function() {
				var title = $(this).text();
				const append = '<input class="filter filter-' + title.toLowerCase().replaceAll(' ', '-')
					+ '" type="text" placeholder="' + title + '" />';
				$(this).replaceWith('<th>' + $(this).html() + '</th>');
				tr.push('<td>' + append + '</td>');
			});
			$(this).find('thead').append(th).append('<tr class="filters">' + tr.join('') + '</tr>');
			$(this).addClass('amadeus-data-table');
		});
	}

	if ($('.amadeus-data-table').length == 0) return;

	function thisTable(el) {
		return el.closest('.amadeus-data-table');
	}

	$('.amadeus-data-table').each(function (ix, tbl) {
		const sth = $(tbl).hasClass('table-sans-th');
		$(tbl).DataTable(getDTParams(sth, $(tbl)));
	});

	function getDTParams(slim) {
		return {
			//https://datatables.net/reference/option/layout
			layout: {
				top: slim ? null : 'info',
				topStart: null,
				topEnd: {
					search: {
						placeholder: 'Search'
					}
				},
				bottom: slim ? null : { buttons: ['copy', 'pdf', 'print'] },
				bottomStart: null,
				bottomEnd: null,
			},

			orderCellsTop: true,
			responsive: true,
			paging: false,
			'order': [], //off by default

			initComplete: initAWBTComplete,
		};
	}

	function initAWBTComplete(settings, json) {
		const tableJQ = $(this);
		const table = this.api();
		table.columns().every(function () {
			var that = this;
			$('.filters input:nth-of-type(' + this.index() + ') ', tableJQ).on('keyup change clear', function () {
				that.search(this.value).draw();
			});
		});
	}
});

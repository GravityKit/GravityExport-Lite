/**
 * Renders the per-form export history.
 *
 * Styled to match Gravity Forms' own results charts (includes/addon/class-gf-results.php):
 * silver bars, 14px type, no legend, and an axis pinned to zero. GF uses Google
 * Charts and this uses Chart.js, so the match is visual rather than structural —
 * the point is that a user moving between the two screens should not feel they
 * changed products.
 */
( function () {
	'use strict';

	// Google Charts' "silver", which is what GF asks for in its own chart options.
	var BAR = '#c0c0c0';
	var BAR_HOVER = '#a8a8a8';
	var GRID = '#e5e5e5';
	var TEXT = '#4f4f4f';

	function render( canvas ) {
		var data;

		try {
			data = JSON.parse( canvas.dataset.series || '{}' );
		} catch ( e ) {
			return;
		}

		var labels = Object.keys( data );

		if ( ! labels.length || typeof window.Chart === 'undefined' ) {
			return;
		}

		new window.Chart( canvas, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [ {
					data: labels.map( function ( day ) {
						return data[ day ];
					} ),
					backgroundColor: BAR,
					hoverBackgroundColor: BAR_HOVER,
					borderWidth: 0,
					// Keeps a single-export day visible rather than a hairline.
					minBarLength: 2,
				} ],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				animation: false,
				plugins: {
					// GF sets visibleInLegend false; one series needs no key.
					legend: { display: false },
					tooltip: {
						displayColors: false,
						callbacks: {
							label: function ( ctx ) {
								var n = ctx.parsed.y;

								return n === 1 ? '1 export' : n + ' exports';
							},
						},
					},
				},
				scales: {
					x: {
						grid: { display: false },
						ticks: {
							color: TEXT,
							font: { size: 14 },
							maxRotation: 0,
							autoSkipPadding: 24,
							callback: function ( value, index ) {
								var label = this.getLabelForValue( value );
								var parts = label.split( '-' );

								// Date only where it fits; the tooltip carries the rest.
								return index % 1 === 0 ? parts[ 2 ] + '/' + parts[ 1 ] : '';
							},
						},
					},
					y: {
						// GF pins its axis at zero explicitly; a chart that floats
						// its baseline exaggerates every change on it.
						beginAtZero: true,
						grid: { color: GRID, drawBorder: false },
						ticks: {
							color: TEXT,
							font: { size: 14 },
							precision: 0,
						},
					},
				},
			},
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call(
			document.querySelectorAll( '[data-gfexcel-download-chart]' ),
			render
		);
	} );
}() );

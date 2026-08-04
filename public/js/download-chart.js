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

	// GF's own charts use Google Charts' "silver" (#c0c0c0). That measures 1.82:1
	// against white and fails WCAG 1.4.11, which requires 3:1 for a graphical
	// object carrying meaning — so the bars here are WordPress admin grey, which
	// keeps the neutral look and measures 3.24:1. Matching a design should not
	// mean inheriting its accessibility failures.
	var BAR = '#8c8f94';
	var BAR_HOVER = '#50575e';
	var GRID = '#c3c4c7';
	var TEXT = '#3c434a';

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
				// Off regardless of preference: this is a static admin figure, and
				// an animated bar chart is motion nobody asked for.
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

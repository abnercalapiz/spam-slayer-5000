(function( $ ) {
	'use strict';

	/**
	 * Spam Slayer 5000 Admin JavaScript
	 */
	
	$( document ).ready( function() {
		
		// Test provider connection
		$( '.ss5k-test-provider' ).on( 'click', function( e ) {
			e.preventDefault();
			
			var $button = $( this );
			var $status = $button.siblings( '.ss5k-test-status' );
			var provider = $button.data( 'provider' );
			
			// Get form data for this provider
			var apiKey = $( '#' + provider + '_api_key' ).val();
			var model = $( '#' + provider + '_model' ).val();
			var enabled = $( '#' + provider + '_enabled' ).is( ':checked' );
			var projectId = $( '#' + provider + '_project_id' ).val();
			var region = $( '#' + provider + '_region' ).val();
			
			if ( ! apiKey ) {
				$status.html( '<span class="sfs-test-error">Please enter an API key</span>' );
				return;
			}
			
			// For Gemini/Vertex AI, check project ID
			if ( provider === 'gemini' && ! projectId ) {
				$status.html( '<span class="sfs-test-error">Please enter a Project ID</span>' );
				return;
			}
			
			// Disable button and show loading
			$button.prop( 'disabled', true );
			$status.html( '<span class="sfs-test-loading">Testing...</span>' );
			
			// Make AJAX request
			$.ajax( {
				url: spam_slayer_5000_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'ss5k_test_provider',
					provider: provider,
					api_key: apiKey,
					model: model,
					project_id: projectId,
					region: region,
					nonce: spam_slayer_5000_admin.nonce
				},
				success: function( response ) {
					if ( response.success ) {
						$status.html( '<span class="sfs-test-success">' + response.data.message + '</span>' );
					} else {
						$status.html( '<span class="sfs-test-error">' + response.data.message + '</span>' );
					}
				},
				error: function() {
					$status.html( '<span class="sfs-test-error">Connection failed</span>' );
				},
				complete: function() {
					$button.prop( 'disabled', false );
				}
			} );
		} );
		
		// Handle whitelist actions
		$( '.ss5k-remove-whitelist' ).on( 'click', function( e ) {
			e.preventDefault();
			
			if ( ! confirm( spam_slayer_5000_admin.confirm_remove ) ) {
				return;
			}
			
			var $link = $( this );
			var id = $link.data( 'id' );
			
			$.ajax( {
				url: spam_slayer_5000_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'ss5k_remove_from_whitelist',
					id: id,
					nonce: spam_slayer_5000_admin.nonce
				},
				success: function( response ) {
					if ( response.success ) {
						$link.closest( 'tr' ).fadeOut( function() {
							$( this ).remove();
						} );
					}
				}
			} );
		} );
		
		// Handle submission actions
		$( document ).on( 'click', '.ss5k-action-btn', function( e ) {
			e.preventDefault();
			
			var $button = $( this );
			var id = $button.data( 'id' );
			var action = $button.data( 'action' );
			
			if ( action === 'view' ) {
				// Handle view action
				$( '#ss5k-view-modal' ).show();
				$( '#ss5k-modal-body' ).html( '<p>Loading...</p>' );
				
				$.ajax( {
					url: spam_slayer_5000_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'ss5k_get_submission_details',
						id: id,
						nonce: spam_slayer_5000_admin.nonce
					},
					success: function( response ) {
						if ( response.success ) {
							$( '#ss5k-modal-body' ).html( response.data );
						} else {
							$( '#ss5k-modal-body' ).html( '<p>Error loading submission details.</p>' );
						}
					}
				} );
			} else {
				// Handle approve/spam actions
				$.ajax( {
					url: spam_slayer_5000_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'ss5k_update_submission_status',
						id: id,
						status: action,
						nonce: spam_slayer_5000_admin.nonce
					},
					success: function( response ) {
						if ( response.success ) {
							// Reload the page to show updated status
							window.location.reload();
						}
					}
				} );
			}
		} );
		
		// Handle add to whitelist
		$( document ).on( 'click', '.ss5k-add-whitelist', function( e ) {
			e.preventDefault();
			
			var $button = $( this );
			var email = $button.data( 'email' );
			
			if ( confirm( 'Add ' + email + ' to whitelist?' ) ) {
				$.ajax( {
					url: spam_slayer_5000_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'ss5k_add_to_whitelist',
						email: email,
						nonce: spam_slayer_5000_admin.nonce
					},
					success: function( response ) {
						if ( response.success ) {
							$button.remove();
							alert( 'Email added to whitelist successfully.' );
						}
					}
				} );
			}
		} );
		
		// Modal close
		$( '.ss5k-modal-close' ).on( 'click', function() {
			$( '#ss5k-view-modal' ).hide();
		} );
		
		// Close modal on outside click
		$( window ).on( 'click', function( e ) {
			if ( $( e.target ).is( '#ss5k-view-modal' ) ) {
				$( '#ss5k-view-modal' ).hide();
			}
		} );
		
		// Export functionality
		$( '#sfs-export-csv' ).on( 'click', function( e ) {
			e.preventDefault();
			
			var filters = $( '#sfs-filters-form' ).serialize();
			window.location.href = spam_slayer_5000_admin.export_url + '&' + filters;
		} );
		
		// Chart.js initialization for analytics
		if ( $( '#sfs-submissions-chart' ).length ) {
			var ctx = document.getElementById( 'sfs-submissions-chart' ).getContext( '2d' );
			var chartData = spam_slayer_5000_admin.chart_data;
			
			new Chart( ctx, {
				type: 'line',
				data: {
					labels: chartData.labels,
					datasets: [
						{
							label: 'Total Submissions',
							data: chartData.total,
							borderColor: 'rgb(75, 192, 192)',
							backgroundColor: 'rgba(75, 192, 192, 0.2)',
							tension: 0.1
						},
						{
							label: 'Spam Blocked',
							data: chartData.spam,
							borderColor: 'rgb(255, 99, 132)',
							backgroundColor: 'rgba(255, 99, 132, 0.2)',
							tension: 0.1
						}
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: {
						y: {
							beginAtZero: true
						}
					}
				}
			} );
		}
		
		// Provider cost chart
		if ( $( '#sfs-provider-chart' ).length ) {
			var ctx = document.getElementById( 'sfs-provider-chart' ).getContext( '2d' );
			var providerData = spam_slayer_5000_admin.provider_data;
			
			new Chart( ctx, {
				type: 'doughnut',
				data: {
					labels: providerData.labels,
					datasets: [{
						data: providerData.costs,
						backgroundColor: [
							'rgba(54, 162, 235, 0.8)',
							'rgba(255, 206, 86, 0.8)',
							'rgba(75, 192, 192, 0.8)'
						],
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom'
						}
					}
				}
			} );
		}
		
		// Toggle API key visibility
		$( '.ss5k-toggle-api-key' ).on( 'click', function( e ) {
			e.preventDefault();
			
			var $input = $( this ).siblings( 'input[type="password"], input[type="text"]' );
			
			if ( $input.attr( 'type' ) === 'password' ) {
				$input.attr( 'type', 'text' );
				$( this ).text( 'Hide' );
			} else {
				$input.attr( 'type', 'password' );
				$( this ).text( 'Show' );
			}
		} );
		
	} );

})( jQuery );
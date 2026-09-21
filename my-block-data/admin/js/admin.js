document.addEventListener( 'DOMContentLoaded', function () {

	// ---- Dynamic "add / remove link" rows on the block form ----------
	var wrap = document.getElementById( 'mbd-links-wrap' );
	var addBtn = document.getElementById( 'mbd-add-link' );
	var template = document.getElementById( 'mbd-link-row-template' );

	if ( wrap && addBtn && template ) {
		addBtn.addEventListener( 'click', function () {
			var clone = template.content.cloneNode( true );
			wrap.appendChild( clone );
		} );

		wrap.addEventListener( 'click', function ( e ) {
			if ( e.target && e.target.classList.contains( 'mbd-remove-link' ) ) {
				var row = e.target.closest( '.mbd-link-row' );
				// Keep at least one row.
				if ( wrap.querySelectorAll( '.mbd-link-row' ).length > 1 ) {
					row.remove();
				} else {
					var input = row.querySelector( 'input' );
					if ( input ) {
						input.value = '';
					}
				}
			}
		} );
	}

	// ---- Import page: toggle "new dataset name" row -------------------
	var datasetSelect = document.getElementById( 'mbd-import-dataset' );
	var newRow = document.getElementById( 'mbd-new-dataset-row' );

	function toggleNewDatasetRow() {
		if ( ! datasetSelect || ! newRow ) {
			return;
		}
		newRow.style.display = datasetSelect.value === '' ? '' : 'none';
	}

	if ( datasetSelect ) {
		datasetSelect.addEventListener( 'change', toggleNewDatasetRow );
		toggleNewDatasetRow();
	}
} );

/**
 * Add/remove directories from to the editor table.
 *
 * @author     Paul Faulkner <paul@headwall.co.uk>
 * @license	   http://www.opensource.org/licenses/mit-license.html  MIT License
 */


var isAutoMenuEnabled = false;


$(function() {
    console.log( "ready!" );

    if( isAutoMenuEnabled ) {
    	$( "#toggle-menu-button" ).click();
    }


	$( "#addDirectoryButton" ).on( "click", function() {
	  addDirectory( )
	});

});


function addDirectory( path, urlSuffix, isScanned, areChildrenScanned )
{
	path = path || "";
	urlSuffix = urlSuffix || "";

	var firstRow	= $( "#directory-template-row" );
	var newRow		= firstRow.clone();
	var newRowIndex = 1;

	$( "#directories-table tbody" ).append( newRow );

	
	newRow.css( "display" , "block" );
	newRow.find( "input:text" ).val( "XXX" );

	newRow.find( "input[name ='paths[]']").val( path );
	newRow.find( "input[name ='urlSuffuxes[]']").val( urlSuffix );

	newRow.find( "input[name ='isScanned[]']").val( isScanned ? "yes" : "no" );
	newRow.find( "input[name ='isScannedCb']").prop( "checked" , isScanned );
	newRow.find( "input[name ='isScannedCb']").on( "click", function() {
		$( this ).parent().find( "input[name ='isScanned[]']").val( $( this ).prop( "checked" ) ? "yes" : "no" );
	});

	newRow.find( "input[name ='areChildrenScanned[]']").val( areChildrenScanned ? "yes" : "no" );
	newRow.find( "input[name ='areChildrenScannedCb']").prop( "checked" , areChildrenScanned );
	newRow.find( "input[name ='areChildrenScannedCb']").on( "click", function() {
		$( this ).parent().find( "input[name ='areChildrenScanned[]']").val( $( this ).prop( "checked" ) ? "yes" : "no" );
	});
	
	newRow.find( "button[name = 'removeDirectoryButton']" ).on( "click", function() {
		$( this ).parents().closest("tr").remove();
	});

}

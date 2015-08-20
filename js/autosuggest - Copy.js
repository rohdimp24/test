$(function() {

	var allbusstops = new Array();

		$.getJSON('stops.json', function(data) {
				for (var i in data.BusStops.BusStop) {
					allbusstops[i] = (data.BusStops.BusStop[i].StopName);
				}
		});


    //get the tags making ajax call
    $.get("autocomplete.php", { name: "John", time: "2pm" },
        function(data){
            availableTags=data.split(";");
        })



    function split( val ) {
        return val.split( /,\s*/ );
    }
    function extractLast( term ) {
        return split( term ).pop();
    }






		 $( "#src, #dst").each(function(){
	        /*$(this).bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
                $( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })    */
        })
        .autocomplete({
           /* minLength: 1,
            source: function( request, response ) {
                // delegate back to autocomplete, but extract the last term
                var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( request.term ), "i" );
            response( $.grep( allbusstops, function( item ){
                return matcher.test( item );
            }) );
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                var terms = split( this.value );
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push( ui.item.value );
                // add placeholder to get the comma-and-space at the end
                this.value = terms;
                return false;
            }
            */
			 target: $('#suggestionsSrc'),
			 icon: 'none',
		      source: allbusstops,
				//link: 'target.html?term=',
				callback: function(e) {
						var matchSrc = $(e.currentTarget); // access the selected item
						$('#src').val(matchSrc.text()); // place the value of the selection into the search box
						$('#src').autocomplete('clear'); // clear the listview
					},
				minLength: 1,
				matchFromStart: true,
				numListElements:3

        });


        $("#dst").autocomplete({
            target: $('#suggestionsDst'),
            icon: 'none',
            source: function( request, response ) {
                // delegate back to autocomplete, but extract the last term
                response( $.ui.autocomplete.filter(
                    availableTags, extractLast( request.term ) ) );
            },
            //link: 'target.html?term=',
            callback: function(e) {
                var matchDst = $(e.currentTarget); // access the selected item
                $('#dst').val(matchDst.text()); // place the value of the selection into the search box
                $('#dst').autocomplete('clear'); // clear the listview
            },
            minLength: 1,
			matchFromStart: true,
            numListElements:3
        });
        
        $('#src').focus(function() {
			$('#suggestionsSrc').show();
			$('#suggestionsDst').hide();
			});

		$('#dst').focus(function() {
				$('#suggestionsDst').show();
			  $('#suggestionsSrc').hide();
			});

    
	
	
	
	
	
	
	
	
	$( "#tags" )
        // don't navigate away from the field on tab when selecting an item
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
                $( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            minLength: 1,
			matchFromStart: true,
            numListElements:3,
            source: allbusstops,/*function( request, response ) {
                // delegate back to autocomplete, but extract the last term
                response( $.ui.autocomplete.filter(
                    availableTags, extractLast( request.term ) ) );
            },*/
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                var terms = split( this.value );
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push( ui.item.value );
                // add placeholder to get the comma-and-space at the end
                terms.push( "" );
				this.value=terms;
                //this.value = terms.join( ", " );
                return false;
            }
        });
});
$(function() {

	var allbusstops = new Array();

	$.getJSON('stops.json', function(data) {
			for (var i in data.BusStops.BusStop) {
				allbusstops[i] = (data.BusStops.BusStop[i].StopName);
			}
	});


    
    function split( val ) {
        return val.split( /,\s*/ );
    }
    function extractLast( term ) {
        return split( term ).pop();
    }


		 $( "#src, #dst").each(function(){
        }).autocomplete({
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
				matchFromStart: false,
				max:3

        });


        $("#dst").autocomplete({
            target: $('#suggestionsDst'),
            icon: 'none',
            source: allbusstops,
            //link: 'target.html?term=',
            callback: function(e) {
                var matchDst = $(e.currentTarget); // access the selected item
                $('#dst').val(matchDst.text()); // place the value of the selection into the search box
                $('#dst').autocomplete('clear'); // clear the listview
            },
            minLength: 1,
			matchFromStart: false,
            max:3
        });
        
        $('#src').focus(function() {
			$('#suggestionsSrc').show();
			$('#suggestionsDst').hide();
			});

		$('#dst').focus(function() {
				$('#suggestionsDst').show();
			  $('#suggestionsSrc').hide();
			});

    
	
	
});
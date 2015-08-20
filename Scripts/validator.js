
// function to check the email field

function checkEmail(email){
var str=email;
var filter=/^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i
if (filter.test(str))
	testresults=true
else{
//alert("Please input a valid email address!")
testresults=false
}
return (testresults)
}

// function to check for the Date of Birth

function isValidDate(dateStr) {
	// Checks for the following valid date formats:
	// MM/DD/YY   MM/DD/YYYY   MM-DD-YY   MM-DD-YYYY
	// Also separates date into month, day, and year variables

	var datePat = /^(\d{1,2})(\/|-)(\d{1,2})\2(\d{2}|\d{4})$/;

	// To require a 4 digit year entry, use this line instead:
	// var datePat = /^(\d{1,2})(\/|-)(\d{1,2})\2(\d{4})$/;

	var matchArray = dateStr.match(datePat); // is the format ok?
	if (matchArray == null) {
	alert("Date is not in a valid format.")
	return false;
	}
	month = matchArray[1]; // parse date into variables
	day = matchArray[3];
	year = matchArray[4];
	if (month < 1 || month > 12) { // check month range
	alert("Month must be between 1 and 12.");
	return false;
	}
	if (day < 1 || day > 31) {
	alert("Day must be between 1 and 31.");
	return false;
	}
	if ((month==4 || month==6 || month==9 || month==11) && day==31) {
	alert("Month "+month+" doesn't have 31 days!")
	return false
	}
	if (month == 2) { // check for february 29th
	var isleap = (year % 4 == 0 && (year % 100 != 0 || year % 400 == 0));
	if (day>29 || (day==29 && !isleap)) {
	alert("February " + year + " doesn't have " + day + " days!");
	return false;
	   }
	}
	return true;  // date is valid
}


// function to validate the radio button has been selected
function valRadioButton(btn) {
	
    var cnt = -1;
	for (var i=btn.length-1; i > -1; i--) {
       if (btn[i].checked) {cnt = i; i = -1;}
    }
  if (cnt > -1) return btn[cnt].value;
    else return null;
}
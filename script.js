function CodeLookup() {

    console.log("CodeLookup Function Called\n");

    var LookupCode = document.getElementById("CableCode").value;

    if (!LookupCode) { return };
    console.log("Code: "+LookupCode)
    var Lookup = $.ajax({
        type : "POST",
        url  : "lookup.php",
        data : {
            CableCode : LookupCode,
        }
    });

    Lookup.done(function(msg) {
        if (msg) {
            alert("Please enter a cable code.");
        } else {
            alert("Data returned: "+msg);
        }
    });

    Lookup.fail(function(_jqXHR, textStatus) {
        alert("Request failed: "+textStatus);
    });
};